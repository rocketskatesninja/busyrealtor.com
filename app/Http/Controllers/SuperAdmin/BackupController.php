<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Services\PlatformBackup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Create, list, download and delete platform backups.
 *
 * There is no restore here on purpose. A platform restore replaces every tenant, every
 * user and the platform's own credentials while the app is serving; that belongs on the
 * box, done deliberately, with a fresh backup taken first. The procedure is in the
 * docblock of App\Console\Commands\RunBackup.
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
