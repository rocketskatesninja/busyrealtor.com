<?php

namespace Tests\Feature;

use App\Jobs\FetchNearbyPlaces;
use App\Models\Property;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\Support\MakesTenants;
use Tests\TestCase;

/*
 | The nearby-places panel used to be fetched inside the public property page render, on any
 | cache miss: three sequential Google Places requests at a 10 second timeout each. A visitor
 | could wait half a minute for a page whose listing content was already in hand, and during
 | a Google outage every visitor waited the full thirty seconds before seeing anything.
 |
 | The page now renders from cache, including from no cache at all, and queues the fetch.
 */
class NearbyPlacesTest extends TestCase
{
    use MakesTenants;
    use RefreshDatabase;

    private function withMapsKey(): void
    {
        SystemSetting::current()->update(['google_maps_key' => 'test-maps-key']);
    }

    public function test_the_property_page_does_not_call_google_while_rendering(): void
    {
        $this->withMapsKey();
        Http::preventStrayRequests();
        Queue::fake();

        $tenant = $this->makeTenant();
        $property = $this->makeProperty($tenant);

        // preventStrayRequests turns any outbound call into a failure, so this passing is
        // the assertion: nothing was fetched during the render.
        $this->get("/{$tenant->slug}/property/{$property->id}")->assertOk();

        Queue::assertPushed(FetchNearbyPlaces::class);
    }

    public function test_the_page_renders_when_there_is_nothing_cached_yet(): void
    {
        $this->withMapsKey();
        Http::preventStrayRequests();
        Queue::fake();

        $tenant = $this->makeTenant();
        $property = $this->makeProperty($tenant);

        $this->get("/{$tenant->slug}/property/{$property->id}")
            ->assertOk()
            ->assertSee($property->title);
    }

    public function test_a_fresh_cache_queues_nothing(): void
    {
        $this->withMapsKey();
        Queue::fake();

        $tenant = $this->makeTenant();
        $property = $this->makeProperty($tenant, [
            'nearby_places_cache' => ['schools' => [['name' => 'Glynn Academy', 'address' => '1001 Mansfield St', 'rating' => 4.2, 'distance_miles' => 1.4]]],
            'nearby_places_fetched_at' => now()->subDays(2),
        ]);

        $this->get("/{$tenant->slug}/property/{$property->id}")->assertOk();

        Queue::assertNotPushed(FetchNearbyPlaces::class);
    }

    public function test_a_stale_cache_is_still_shown_while_the_refresh_is_queued(): void
    {
        $this->withMapsKey();
        Queue::fake();

        $tenant = $this->makeTenant();
        $property = $this->makeProperty($tenant, [
            'nearby_places_cache' => ['schools' => [['name' => 'Glynn Academy', 'address' => '1001 Mansfield St', 'rating' => 4.2, 'distance_miles' => 1.4]]],
            'nearby_places_fetched_at' => now()->subDays(90),
        ]);

        $this->get("/{$tenant->slug}/property/{$property->id}")->assertOk();

        Queue::assertPushed(FetchNearbyPlaces::class);
        $this->assertNotNull($property->fresh()->nearby_places_cache, 'the stale panel was thrown away');
    }

    public function test_nothing_is_queued_without_a_maps_key(): void
    {
        Queue::fake();

        $tenant = $this->makeTenant();
        $property = $this->makeProperty($tenant);

        $this->get("/{$tenant->slug}/property/{$property->id}")->assertOk();

        Queue::assertNotPushed(FetchNearbyPlaces::class);
    }

    public function test_the_job_writes_what_google_returns(): void
    {
        $this->withMapsKey();

        $tenant = $this->makeTenant();
        $property = $this->makeProperty($tenant);

        Http::fake([
            'places.googleapis.com/*' => Http::response([
                'places' => [
                    ['displayName' => ['text' => 'Glynn Academy'], 'formattedAddress' => '1001 Mansfield St', 'rating' => 4.2],
                ],
            ], 200),
        ]);

        (new FetchNearbyPlaces($property->id))->handle();

        $fresh = $property->fresh();
        $this->assertNotNull($fresh->nearby_places_cache);
        $this->assertNotNull($fresh->nearby_places_fetched_at);
        $this->assertArrayHasKey('schools', $fresh->nearby_places_cache);
    }

    public function test_the_job_leaves_the_cache_alone_when_google_fails(): void
    {
        $this->withMapsKey();

        $tenant = $this->makeTenant();
        $property = $this->makeProperty($tenant, [
            'nearby_places_cache' => ['schools' => [['name' => 'Previously fetched', 'address' => '1 Old Rd', 'rating' => null, 'distance_miles' => 2.5]]],
            'nearby_places_fetched_at' => now()->subDays(90),
        ]);

        Http::fake(['places.googleapis.com/*' => Http::response('upstream is down', 503)]);

        (new FetchNearbyPlaces($property->id))->handle();

        $fresh = $property->fresh();
        $this->assertSame(
            [['name' => 'Previously fetched', 'address' => '1 Old Rd', 'rating' => null, 'distance_miles' => 2.5]],
            $fresh->nearby_places_cache['schools'],
            'a failed refresh destroyed the panel that was already there'
        );
    }

    public function test_the_job_is_harmless_when_the_property_has_gone(): void
    {
        $this->withMapsKey();
        Http::preventStrayRequests();

        (new FetchNearbyPlaces(999999))->handle();

        $this->assertSame(0, Property::withoutGlobalScopes()->count());
    }

    /** One job per property per hour, so a property Google keeps failing for cannot pile up. */
    public function test_the_job_is_unique_per_property(): void
    {
        $job = new FetchNearbyPlaces(42);

        $this->assertSame('42', $job->uniqueId());
        $this->assertSame(3600, $job->uniqueFor);
    }
}
