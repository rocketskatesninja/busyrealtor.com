<?php

namespace App\Console\Commands;

use App\Services\PlatformBackup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Nightly platform backup.
 *
 * TO RESTORE (on the box, deliberately -- there is no button for this):
 *
 *   cd /var/www/busyrealtor.com
 *   php artisan down
 *   mkdir /tmp/restore && tar -xzf storage/app/backups/busyrealtor-<stamp>.tar.gz -C /tmp/restore
 *   mysql -u<user> -p <database> < /tmp/restore/database.sql
 *   rsync -a --delete /tmp/restore/uploads/ storage/app/public/tenants/
 *   chown -R www-data:www-data storage/app/public/tenants
 *   php artisan up
 *
 * Take a fresh backup before restoring an old one: the restore replaces the database
 * outright, and the thing you overwrite is the only record of what was there.
 */
class RunBackup extends Command
{
    protected $signature = 'app:backup {--keep=14 : How many backups to retain}';

    protected $description = 'Back up the database and every tenant upload, then prune old backups';

    public function handle(): int
    {
        try {
            $name = PlatformBackup::make();
        } catch (\Throwable $e) {
            // Loud on purpose. A backup that silently stopped running is the failure mode
            // that matters, because nobody notices until they need the thing.
            Log::error('Platform backup failed', ['error' => $e->getMessage()]);
            $this->error('Backup failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $size = number_format((filesize(PlatformBackup::directory().'/'.$name) ?: 0) / 1048576, 1);
        $this->info("Wrote {$name} ({$size} MB)");

        foreach (PlatformBackup::prune((int) $this->option('keep')) as $old) {
            $this->line("  pruned {$old}");
        }

        return self::SUCCESS;
    }
}
