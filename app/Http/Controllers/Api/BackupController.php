<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
    public function create($account)
    {
        $tenant   = app('tenant');
        $filename = "backup-{$tenant->slug}-" . date('Y-m-d-His') . '.zip';
        $tmpPath  = storage_path("app/backups/{$filename}");

        @mkdir(storage_path('app/backups'), 0755, true);

        // Simple backup: just export properties as JSON
        $properties = \App\Models\Property::with('images')->get()->toJson();
        $messages   = \App\Models\Message::get()->toJson();

        $zip = new \ZipArchive();
        if ($zip->open($tmpPath, \ZipArchive::CREATE) === TRUE) {
            $zip->addFromString('properties.json', $properties);
            $zip->addFromString('messages.json', $messages);
            $zip->close();
        }

        return response()->download($tmpPath, $filename)->deleteFileAfterSend(true);
    }
}
