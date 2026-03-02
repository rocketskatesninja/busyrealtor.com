<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class BackupController extends Controller
{
    public function create($account)
    {
        $tenant   = app('tenant');
        $filename = "backup-{$tenant->slug}-" . date('Y-m-d-His') . '.zip';
        $tmpPath  = tempnam(sys_get_temp_dir(), 'busyrealtor_backup_') . '.zip';

        $zip = new \ZipArchive();
        if ($zip->open($tmpPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
            return response()->json(['error' => 'Failed to create archive'], 500);
        }

        // Manifest
        $zip->addFromString('manifest.json', json_encode([
            'version'    => 2,
            'tenant_id'  => $tenant->id,
            'slug'       => $tenant->slug,
            'created_at' => now()->toISOString(),
        ]));

        // JSON data
        $zip->addFromString('data/properties.json',   \App\Models\Property::with('images')->get()->toJson());
        $zip->addFromString('data/messages.json',     \App\Models\Message::get()->toJson());
        $zip->addFromString('data/staff.json',        \App\Models\StaffMember::get()->toJson());
        $zip->addFromString('data/appointments.json', \App\Models\Appointment::get()->toJson());
        $zip->addFromString('data/legal_pages.json',  \App\Models\LegalPage::get()->toJson());

        $settings = \App\Models\SiteSettings::first();
        if ($settings) {
            $zip->addFromString('data/settings.json', $settings->toJson());
        }

        // Binary files — mirror tenant storage directory into ZIP
        $storageBase = storage_path('app/public');
        $tenantDir   = "{$storageBase}/tenants/{$tenant->id}";
        if (is_dir($tenantDir)) {
            $iter = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($tenantDir, \RecursiveDirectoryIterator::SKIP_DOTS)
            );
            foreach ($iter as $file) {
                if ($file->isFile()) {
                    $relPath = 'files/' . ltrim(str_replace($storageBase, '', $file->getPathname()), '/');
                    $zip->addFile($file->getPathname(), $relPath);
                }
            }
        }

        $zip->close();
        return response()->download($tmpPath, $filename)->deleteFileAfterSend(true);
    }
}
