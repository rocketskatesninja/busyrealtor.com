<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Process;

/**
 * A backup of the whole platform: the database, and every tenant's uploads.
 *
 * Deliberately not the same thing as the per-tenant ZIP a realtor downloads from their
 * own settings page. That one is per-model JSON for one tenant -- no users, no tenants
 * row, no system_settings (which is where the encrypted Stripe and SMTP credentials
 * live), no schema. It can repopulate a database that already exists; it cannot rebuild
 * one. This can.
 *
 * One file per backup, so the console can list, download and delete without having to
 * keep a set of files in step. Inside: database.sql and uploads/, which is what the
 * restore procedure expects.
 *
 * Restores are not performed from the web. Replacing every tenant, every user and the
 * platform's own credentials while the app is serving is a thing to do deliberately over
 * SSH; see the class docblock on the command for the procedure.
 */
class PlatformBackup
{
    public const PREFIX = 'busyrealtor-';

    /** Matches what make() writes, and nothing else -- used to police the download path. */
    public const PATTERN = '/^busyrealtor-\d{8}-\d{6}\.tar\.gz$/';

    public static function directory(): string
    {
        $dir = storage_path('app/backups');

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        return $dir;
    }

    /**
     * Write a new backup and return its filename.
     *
     * @throws \RuntimeException when the dump, the archive or the verification fails --
     *                           a backup that is not known-good is worse than none, since
     *                           it is the thing you stop worrying about.
     */
    public static function make(): string
    {
        $stamp = Carbon::now()->format('Ymd-His');
        $name = self::PREFIX.$stamp.'.tar.gz';
        $dir = self::directory();
        $work = $dir.'/.staging-'.$stamp;

        mkdir($work, 0775, true);

        try {
            self::dumpDatabase($work.'/database.sql');
            self::copyUploads($work.'/uploads');

            $tar = Process::timeout(600)->run(['tar', '-czf', $dir.'/'.$name, '-C', $work, '.']);
            if (! $tar->successful()) {
                throw new \RuntimeException('tar failed: '.trim($tar->errorOutput()));
            }
        } finally {
            Process::run(['rm', '-rf', $work]);
        }

        self::verify($dir.'/'.$name);

        return $name;
    }

    /** Read the archive back. A file that cannot be listed is not a backup. */
    public static function verify(string $path): void
    {
        $check = Process::timeout(300)->run(['tar', '-tzf', $path]);

        if (! $check->successful()) {
            @unlink($path);
            throw new \RuntimeException('archive did not verify and was removed: '.trim($check->errorOutput()));
        }

        if (! str_contains($check->output(), 'database.sql')) {
            @unlink($path);
            throw new \RuntimeException('archive holds no database.sql and was removed');
        }
    }

    /** @return list<array{name:string,size:int,created_at:Carbon}> newest first */
    public static function all(): array
    {
        $files = glob(self::directory().'/'.self::PREFIX.'*.tar.gz') ?: [];

        $backups = array_map(fn ($path) => [
            'name' => basename($path),
            'size' => filesize($path) ?: 0,
            'created_at' => Carbon::createFromTimestamp(filemtime($path)),
        ], $files);

        usort($backups, fn ($a, $b) => $b['created_at'] <=> $a['created_at']);

        return $backups;
    }

    /** The full path of a backup, or null if the name is not one of ours or is missing. */
    public static function path(string $name): ?string
    {
        if (! preg_match(self::PATTERN, $name)) {
            return null;
        }

        $path = self::directory().'/'.$name;

        return is_file($path) ? $path : null;
    }

    /** Keep the newest $keep, remove the rest. Returns the names removed. */
    public static function prune(int $keep): array
    {
        $removed = [];

        foreach (array_slice(self::all(), max(1, $keep)) as $old) {
            if (@unlink(self::directory().'/'.$old['name'])) {
                $removed[] = $old['name'];
            }
        }

        return $removed;
    }

    private static function dumpDatabase(string $to): void
    {
        $db = config('database.connections.'.config('database.default'));

        // The password goes in the environment, not the argument list, where it would be
        // visible to anyone running ps while the dump is in progress.
        $dump = Process::timeout(600)->env(['MYSQL_PWD' => (string) ($db['password'] ?? '')])->run([
            'mysqldump',
            '--host='.($db['host'] ?? '127.0.0.1'),
            '--port='.($db['port'] ?? 3306),
            '--user='.($db['username'] ?? ''),
            '--single-transaction',   // consistent without locking the site out
            '--quick',
            '--routines',
            '--events',
            '--result-file='.$to,
            (string) ($db['database'] ?? ''),
        ]);

        if (! $dump->successful()) {
            throw new \RuntimeException('mysqldump failed: '.trim($dump->errorOutput()));
        }
    }

    private static function copyUploads(string $to): void
    {
        $from = storage_path('app/public/tenants');

        if (! is_dir($from)) {
            mkdir($to, 0775, true);   // no uploads yet; keep the archive's shape stable

            return;
        }

        $copy = Process::timeout(600)->run(['cp', '-a', $from, $to]);

        if (! $copy->successful()) {
            throw new \RuntimeException('copying uploads failed: '.trim($copy->errorOutput()));
        }
    }
}
