<?php

namespace App\Jobs;

use App\Models\Property;
use App\Services\GooglePlacesService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Fetch the nearby schools, hospitals and shops for a property.
 *
 * This used to happen inside the public property page render, on any cache miss: three
 * sequential Google Places requests at a 10 second timeout each, so a visitor could wait
 * up to half a minute for a page whose content was already in hand — and every visitor
 * during a Google outage waited the full thirty seconds before seeing the listing.
 *
 * The page now renders with whatever is cached, including nothing, and this fills it in for
 * the next visitor.
 */
class FetchNearbyPlaces implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 60;

    /**
     * One job per property per hour. The cache timestamp is only written on success, so
     * without this a property Google keeps failing for would queue a fresh job on every
     * single pageview.
     */
    public int $uniqueFor = 3600;

    public function __construct(public int $propertyId)
    {
    }

    public function uniqueId(): string
    {
        return (string) $this->propertyId;
    }

    public function handle(): void
    {
        // withoutGlobalScopes: there is no resolved tenant inside a worker, and this job is
        // dispatched with an id the controller already confirmed belongs to its tenant.
        $property = Property::withoutGlobalScopes()->find($this->propertyId);

        if (! $property || ! $property->latitude || ! $property->longitude) {
            return;
        }

        if ($property->hasNearbyPlacesCache()) {
            return;
        }

        $data = (new GooglePlacesService())->fetchNearbyPlaces($property);

        // An all-empty result is not an answer, it is a failure wearing the shape of one.
        // The service catches its own HTTP errors per category and returns an empty list for
        // each, so a Google outage produces ['schools' => [], 'hospitals' => [], ...] — which
        // is truthy. Writing that would replace a panel that was working with an empty one
        // and stamp it fresh for the next sixty days. Keep whatever is already there.
        if (! $data || ! collect($data)->contains(fn ($places) => ! empty($places))) {
            Log::info('FetchNearbyPlaces: no places returned, keeping any existing cache', [
                'property_id' => $property->id,
            ]);

            return;
        }

        $property->update([
            'nearby_places_cache' => $data,
            'nearby_places_fetched_at' => now(),
        ]);
    }
}
