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

    /**
     * Two users write here: the web process (www-data) when someone uses the console, and
     * whoever the scheduler runs as. Whichever creates the directory first owns it, so it
     * is made group-writable and handed to the web group explicitly -- mkdir's mode alone
     * is filtered by umask, which is how this first went wrong.
     */
    public static function directory(): string
    {
        $dir = storage_path('app/backups');

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
            @chmod($dir, 0775);
            @chgrp($dir, 'www-data');
        }

        if (! is_writable($dir)) {
            throw new \RuntimeException(
                "The backup directory is not writable by ".(function_exists('posix_getpwuid')
                    ? posix_getpwuid(posix_geteuid())['name'] : 'this user')
                .": {$dir}. Fix with: sudo chgrp -R www-data {$dir} && sudo chmod -R g+w {$dir}"
            );
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

    /**
     * Replace the database and every tenant's uploads with the contents of a backup.
     *
     * Takes a fresh backup first, always. The thing being overwritten is the only record
     * of what was there, and the most likely reason to restore the wrong archive is being
     * in a hurry. The safety copy is returned so the caller can name it.
     *
     * @return array{safety:string,tables:int,files:int}
     *
     * @throws \RuntimeException leaving the database untouched if anything before the
     *                           import fails; once the import starts it runs to the end.
     */
    public static function restore(string $name): array
    {
        $archive = self::path($name);

        if ($archive === null) {
            throw new \RuntimeException("No such backup: {$name}");
        }

        $safety = self::make();

        $work = self::directory().'/.restore-'.Carbon::now()->format('Ymd-His');
        mkdir($work, 0775, true);

        try {
            $extract = Process::timeout(600)->run(['tar', '-xzf', $archive, '-C', $work]);
            if (! $extract->successful()) {
                throw new \RuntimeException('could not extract: '.trim($extract->errorOutput()));
            }

            $sql = $work.'/database.sql';
            if (! is_file($sql) || filesize($sql) === 0) {
                throw new \RuntimeException('archive holds no usable database.sql');
            }

            self::importDatabase($sql);
            $files = self::restoreUploads($work.'/uploads');

            $tables = (int) \Illuminate\Support\Facades\DB::scalar(
                'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()'
            );

            return ['safety' => $safety, 'tables' => $tables, 'files' => $files];
        } finally {
            Process::run(['rm', '-rf', $work]);
        }
    }

    private static function importDatabase(string $sql): void
    {
        $db = config('database.connections.'.config('database.default'));

        $import = Process::timeout(900)->env(['MYSQL_PWD' => (string) ($db['password'] ?? '')])
            ->input(file_get_contents($sql) ?: '')
            ->run([
                'mysql',
                '--host='.($db['host'] ?? '127.0.0.1'),
                '--port='.($db['port'] ?? 3306),
                '--user='.($db['username'] ?? ''),
                (string) ($db['database'] ?? ''),
            ]);

        if (! $import->successful()) {
            throw new \RuntimeException('mysql import failed: '.trim($import->errorOutput()));
        }
    }

    /** Mirror the archive's uploads over the live ones; returns how many files resulted. */
    private static function restoreUploads(string $from): int
    {
        if (! is_dir($from)) {
            return 0;
        }

        $to = storage_path('app/public/tenants');
        if (! is_dir($to)) {
            mkdir($to, 0775, true);
        }

        // Trailing slashes and --delete: the live directory should end up matching the
        // archive, including files the archive does not have.
        $sync = Process::timeout(600)->run(['rsync', '-a', '--delete', $from.'/', $to.'/']);

        if (! $sync->successful()) {
            throw new \RuntimeException('restoring uploads failed: '.trim($sync->errorOutput()));
        }

        $count = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($to, \RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
            if ($file->isFile()) {
                $count++;
            }
        }

        return $count;
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
