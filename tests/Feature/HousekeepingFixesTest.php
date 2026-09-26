<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Tenant;
use App\Services\GoogleCalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Support\MakesTenants;
use Tests\TestCase;

/*
 | Four unrelated defects that shared a shape: each was invisible until the data was large
 | enough, far enough from Eastern, or deleted.
 */
class HousekeepingFixesTest extends TestCase
{
    use MakesTenants;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Http::preventStrayRequests();
        Http::fake(['*' => Http::response([], 200)]);
    }

    /** Calendar invites were written in Eastern whatever the agency's own timezone said. */
    public function test_a_calendar_event_uses_the_tenants_timezone(): void
    {
        $pacific = $this->makeTenant(['slug' => 'pacific'], ['timezone' => 'America/Los_Angeles']);
        $appointment = Appointment::withoutGlobalScopes()->create([
            'tenant_id' => $pacific->id, 'visitor_name' => 'Vic', 'visitor_email' => 'vic@example.test',
            'appointment_date' => now()->addDay()->toDateString(), 'appointment_time' => '10:00:00',
            'appointment_type' => 'showing', 'status' => 'pending',
        ]);

        $this->assertSame(
            'America/Los_Angeles',
            GoogleCalendarService::timezoneFor($appointment->load('tenant.siteSettings'))
        );
    }

    /**
     * The fallback is reached when a tenant has no settings row at all — `site_settings.timezone`
     * is NOT NULL and defaults to America/New_York, so it is never itself null. Which also
     * bounds the original bug: a tenant who never opened the setting was in Eastern either way;
     * the ones being written at the wrong hour were those who had actually set it.
     */
    public function test_a_tenant_with_no_settings_row_falls_back_to_the_app_default(): void
    {
        config(['app.timezone' => 'UTC']);

        $tenant = Tenant::create([
            'slug' => 'nosettings', 'name' => 'No Settings', 'email' => 'no@example.test',
            'plan' => 'pro', 'is_active' => true,
        ]);
        $appointment = Appointment::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id, 'visitor_name' => 'Vic', 'visitor_email' => 'vic@example.test',
            'appointment_date' => now()->addDay()->toDateString(), 'appointment_time' => '10:00:00',
            'appointment_type' => 'showing', 'status' => 'pending',
        ]);

        $this->assertSame('UTC', GoogleCalendarService::timezoneFor($appointment->load('tenant.siteSettings')));
    }

    /**
     * The export paged with chunk(), which is offset-based, over a query ordered by
     * created_at — not unique. Rows could shift between pages, so the CSV could skip or
     * repeat them; only reachable past the 500-row page size.
     *
     * Honest about what this test is: it pins the invariant (every row exactly once, newest
     * first) but it does **not** discriminate the fix — it passes against the old chunk() too,
     * because reproducing the shift needs writes landing between pages, which a single-process
     * test cannot stage. It is a guard against future regressions, not proof of this one.
     */
    public function test_the_activity_export_returns_every_row_exactly_once(): void
    {
        $tenant = $this->makeTenant();
        $super = $this->makeSuperAdmin();

        // All sharing one timestamp: the pathological case for offset paging.
        $stamp = now()->subHour();
        $rows = [];
        for ($i = 1; $i <= 1200; $i++) {
            $rows[] = [
                'tenant_id' => $tenant->id, 'action' => 'updated',
                'description' => "entry-{$i}", 'created_at' => $stamp,
            ];
        }
        foreach (array_chunk($rows, 300) as $batch) {
            ActivityLog::insert($batch);
        }

        $csv = $this->actingAs($super)->get('/super-admin/activity/export')->streamedContent();

        preg_match_all('/entry-(\d+)/', $csv, $matches);
        $found = $matches[1];

        $this->assertCount(1200, $found, 'every row appears');
        $this->assertCount(1200, array_unique($found), 'and none appears twice');
        $this->assertSame('1200', $found[0], 'newest first, as the screen and the old export both were');
    }

    /** Deleting a tenant left its uploads on disk for ever. */
    public function test_deleting_a_tenant_removes_its_uploaded_files(): void
    {
        $tenant = $this->makeTenant(['slug' => 'goner']);
        $keeper = $this->makeTenant(['slug' => 'keeper']);

        Storage::disk('public')->putFileAs("tenants/{$tenant->id}/properties", UploadedFile::fake()->image('a.jpg'), 'a.jpg');
        Storage::disk('public')->putFileAs("tenants/{$keeper->id}/properties", UploadedFile::fake()->image('b.jpg'), 'b.jpg');

        Storage::disk('public')->assertExists("tenants/{$tenant->id}/properties/a.jpg");

        $tenant->delete();

        Storage::disk('public')->assertMissing("tenants/{$tenant->id}/properties/a.jpg");
        Storage::disk('public')->assertExists("tenants/{$keeper->id}/properties/b.jpg");
        $this->assertNull(Tenant::find($tenant->id));
    }

    /** A restore kept created_at in the payload, but the model dropped it. */
    public function test_an_appointment_can_be_restored_with_its_original_created_at(): void
    {
        $tenant = $this->makeTenant();
        $original = now()->subMonths(4)->startOfMinute();

        $appointment = Appointment::withoutGlobalScopes()->updateOrCreate(
            ['id' => 4321, 'tenant_id' => $tenant->id],
            [
                'tenant_id' => $tenant->id, 'visitor_name' => 'Vic', 'visitor_email' => 'vic@example.test',
                'appointment_date' => now()->addDay()->toDateString(), 'appointment_time' => '10:00:00',
                'appointment_type' => 'showing', 'status' => 'pending',
                'created_at' => $original,
            ]
        );

        $this->assertTrue(
            $original->equalTo($appointment->fresh()->created_at),
            'the restored row keeps its original date instead of being stamped now'
        );
    }
}
