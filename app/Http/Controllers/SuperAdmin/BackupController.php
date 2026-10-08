<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Services\PlatformBackup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Create, list, download, delete and restore platform backups.
 *
 * Restore is the dangerous one: it replaces every tenant, every user and the platform's
 * own credentials. Three things make it survivable rather than merely possible -- a fresh
 * backup is taken first and named in the result, the site is in maintenance mode while it
 * runs, and the operator has to type the filename back. The same procedure can still be
 * run by hand; see the docblock on App\Console\Commands\RunBackup.
 */
class BackupController extends Controller
{
    public function index(): View
    {
        $backups = PlatformBackup::all();

        return view('super-admin.backups', [
            'backups' => $backups,
            'total' => array_sum(array_column($backups, 'size')),
            'free' => @disk_free_space(PlatformBackup::directory()) ?: 0,
            'lastRun' => $backups[0]['created_at'] ?? null,
        ]);
    }

    public function store(): RedirectResponse
    {
        try {
            $name = PlatformBackup::make();
        } catch (\Throwable $e) {
            return back()->with('error', 'Backup failed: '.$e->getMessage());
        }

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'created',
            'description' => "Created platform backup {$name}",
        ]);

        return back()->with('success', "Backup created: {$name}");
    }

    public function restore(Request $request, string $name): RedirectResponse
    {
        $path = PlatformBackup::path($name);

        abort_if($path === null, 404);

        if ($request->input('confirm') !== $name) {
            return back()->with('error', 'Type the filename exactly to confirm the restore.');
        }

        // A dump import is not interruptible halfway. Hold the door shut so nothing writes
        // to a database that is being replaced underneath it.
        Artisan::call('down', ['--retry' => 60]);

        try {
            $result = PlatformBackup::restore($name);
        } catch (\Throwable $e) {
            Log::error('Platform restore failed', ['backup' => $name, 'error' => $e->getMessage()]);

            return back()->with('error', 'Restore failed: '.$e->getMessage());
        } finally {
            Artisan::call('up');
        }

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'updated',
            'description' => "Restored platform from {$name}",
        ]);

        Log::info('Platform restored', $result + ['from' => $name]);

        // Not a flash. Sessions live in the database, so the operator's session -- and any
        // message put in it -- was replaced along with everything else a moment ago. The
        // query string is the only channel that survives being signed out mid-request.
        return redirect()->route('login', [
            'restored' => $name,
            'safety' => $result['safety'],
        ]);
    }

    public function download(string $name): BinaryFileResponse
    {
        // Resolved through the service, which only returns a path when the name matches
        // the pattern it writes. A name is user input and this directory holds the
        // database, so nothing here is built by concatenation.
        $path = PlatformBackup::path($name);

        abort_if($path === null, 404);

        return response()->download($path);
    }

    public function destroy(Request $request, string $name): RedirectResponse
    {
        $path = PlatformBackup::path($name);

        abort_if($path === null, 404);

        // Typing the filename is the confirmation: this is the only copy on this box.
        if ($request->input('confirm') !== $name) {
            return back()->with('error', 'Type the filename exactly to confirm deletion.');
        }

        @unlink($path);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'deleted',
            'description' => "Deleted platform backup {$name}",
        ]);

        return back()->with('success', "Deleted {$name}");
    }
}
