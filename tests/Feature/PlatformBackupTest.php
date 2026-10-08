<?php

namespace Tests\Feature;

use App\Services\PlatformBackup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesTenants;
use Tests\TestCase;

/**
 * The backup console holds the database and every tenant's uploads, including the
 * encrypted Stripe and SMTP credentials in system_settings. Who can reach it, and what a
 * filename is allowed to be, are the two things worth pinning.
 *
 * Creating a real backup is not tested here: it shells out to mysqldump and tar against
 * the live database, which belongs in a manual check, not in a suite that runs on every
 * change. That path is verified by restoring an archive into a throwaway database.
 */
class PlatformBackupTest extends TestCase
{
    use MakesTenants;
    use RefreshDatabase;

    public function test_only_a_super_admin_can_reach_the_backup_console(): void
    {
        $this->get('/super-admin/backups')->assertRedirect('/login');

        // EnsureSuperAdmin bounces to login with a flash rather than returning 403; what
        // matters is that the console never renders for them.
        $tenant = $this->makeTenant();
        $this->actingAs($this->makeAdmin($tenant))->get('/super-admin/backups')->assertRedirect('/login');

        $this->actingAs($this->makeSuperAdmin())->get('/super-admin/backups')->assertOk();
    }

    public function test_a_tenant_admin_cannot_create_or_delete_backups(): void
    {
        $admin = $this->makeAdmin($this->makeTenant());

        $this->actingAs($admin)->post('/super-admin/backups')->assertRedirect('/login');
        $this->actingAs($admin)->delete('/super-admin/backups/busyrealtor-20260101-000000.tar.gz')->assertRedirect('/login');
    }

    /** @dataProvider badNameProvider */
    public function test_only_names_this_service_writes_resolve_to_a_path(string $name): void
    {
        // The name arrives from the URL and the directory holds the database, so nothing
        // here may be built by concatenating it.
        $this->assertNull(PlatformBackup::path($name), "{$name} should not resolve");
    }

    public static function badNameProvider(): array
    {
        return [
            'traversal' => ['../../../.env'],
            'encoded traversal' => ['..%2F..%2F.env'],
            'absolute' => ['/etc/passwd'],
            'wrong extension' => ['busyrealtor-20260101-000000.sql'],
            'wrong prefix' => ['evil-20260101-000000.tar.gz'],
            'no timestamp' => ['busyrealtor-.tar.gz'],
            'empty' => [''],
        ];
    }

    public function test_an_unknown_backup_is_a_404_not_a_server_error(): void
    {
        $this->actingAs($this->makeSuperAdmin())
            ->get('/super-admin/backups/busyrealtor-20260101-000000.tar.gz/download')
            ->assertNotFound();
    }

    public function test_deleting_requires_the_filename_typed_back(): void
    {
        $dir = PlatformBackup::directory();
        $name = 'busyrealtor-20260101-000000.tar.gz';
        file_put_contents($dir.'/'.$name, 'not a real archive');

        try {
            $this->actingAs($this->makeSuperAdmin())
                ->delete('/super-admin/backups/'.$name, ['confirm' => 'something else'])
                ->assertSessionHas('error');
            $this->assertFileExists($dir.'/'.$name, 'a mistyped confirmation must not delete');

            $this->actingAs($this->makeSuperAdmin())
                ->delete('/super-admin/backups/'.$name, ['confirm' => $name])
                ->assertSessionHas('success');
            $this->assertFileDoesNotExist($dir.'/'.$name);
        } finally {
            @unlink($dir.'/'.$name);
        }
    }

    public function test_the_nightly_backup_is_scheduled(): void
    {
        // The whole point is that it runs without anyone remembering to run it.
        $events = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
            ->filter(fn ($e) => str_contains($e->command ?? '', 'app:backup'));

        $this->assertCount(1, $events, 'app:backup is not on the schedule');
        $this->assertSame('30 2 * * *', $events->first()->expression);
    }
}
