<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RestoreController extends Controller
{
    public function restore($account, Request $request)
    {
        $request->validate(['backup' => 'required|file|mimes:zip']);

        $zip  = new \ZipArchive();
        $path = $request->file('backup')->getPathname();
        if ($zip->open($path) !== true) {
            return response()->json(['success' => false, 'message' => 'Invalid or corrupted backup file.'], 422);
        }

        // Read manifest to detect original tenant_id (for file path remapping)
        $manifest         = json_decode($zip->getFromName('manifest.json') ?: '{}', true);
        $originalTenantId = $manifest['tenant_id'] ?? null;
        $tenant           = app('tenant');
        $r                = [
            'properties'  => 0,
            'images'      => 0,
            'messages'    => 0,
            'staff'       => 0,
            'appointments'=> 0,
            'legal_pages' => 0,
            'settings'    => false,
            'files'       => 0,
        ];

        // ── Staff ──────────────────────────────────────────────────────
        if ($json = $zip->getFromName('data/staff.json')) {
            foreach (json_decode($json, true) as $s) {
                unset($s['created_at'], $s['updated_at']);
                $s['tenant_id'] = $tenant->id;
                \App\Models\StaffMember::updateOrCreate(['id' => $s['id'], 'tenant_id' => $tenant->id], $s);
                $r['staff']++;
            }
        }

        // ── Properties + images ────────────────────────────────────────
        if ($json = $zip->getFromName('data/properties.json')) {
            foreach (json_decode($json, true) as $p) {
                $images = $p['images'] ?? [];
                unset($p['images'], $p['created_at'], $p['updated_at']);
                $p['tenant_id'] = $tenant->id;
                $property = \App\Models\Property::updateOrCreate(['id' => $p['id'], 'tenant_id' => $tenant->id], $p);
                foreach ($images as $img) {
                    unset($img['created_at'], $img['updated_at']);
                    $img['property_id'] = $property->id;
                    $img['tenant_id']   = $tenant->id;
                    if ($originalTenantId && $originalTenantId !== $tenant->id) {
                        $img['image_url'] = str_replace(
                            "tenants/{$originalTenantId}/",
                            "tenants/{$tenant->id}/",
                            $img['image_url'] ?? ''
                        );
                    }
                    \App\Models\PropertyImage::updateOrCreate(['id' => $img['id']], $img);
                    $r['images']++;
                }
                $r['properties']++;
            }
        }

        // ── Appointments ───────────────────────────────────────────────
        if ($json = $zip->getFromName('data/appointments.json')) {
            foreach (json_decode($json, true) as $a) {
                unset($a['updated_at']);
                $a['tenant_id'] = $tenant->id;
                \App\Models\Appointment::updateOrCreate(['id' => $a['id'], 'tenant_id' => $tenant->id], $a);
                $r['appointments']++;
            }
        }

        // ── Messages ───────────────────────────────────────────────────
        if ($json = $zip->getFromName('data/messages.json')) {
            foreach (json_decode($json, true) as $m) {
                unset($m['created_at'], $m['updated_at']);
                $m['tenant_id'] = $tenant->id;
                \App\Models\Message::updateOrCreate(['id' => $m['id'], 'tenant_id' => $tenant->id], $m);
                $r['messages']++;
            }
        }

        // ── Legal pages ────────────────────────────────────────────────
        if ($json = $zip->getFromName('data/legal_pages.json')) {
            foreach (json_decode($json, true) as $lp) {
                unset($lp['created_at'], $lp['updated_at']);
                $lp['tenant_id'] = $tenant->id;
                \App\Models\LegalPage::updateOrCreate(['id' => $lp['id'], 'tenant_id' => $tenant->id], $lp);
                $r['legal_pages']++;
            }
        }

        // ── Site settings ──────────────────────────────────────────────
        if ($json = $zip->getFromName('data/settings.json')) {
            $s = json_decode($json, true);
            unset($s['id'], $s['created_at'], $s['updated_at']);
            $s['tenant_id'] = $tenant->id;
            foreach (['logo_image', 'hero_image', 'default_share_image'] as $field) {
                if (!empty($s[$field]) && $originalTenantId && $originalTenantId !== $tenant->id) {
                    $s[$field] = str_replace(
                        "tenants/{$originalTenantId}/",
                        "tenants/{$tenant->id}/",
                        $s[$field]
                    );
                }
            }
            \App\Models\SiteSettings::updateOrCreate(['tenant_id' => $tenant->id], $s);
            $r['settings'] = true;
        }

        // ── Binary files ───────────────────────────────────────────────
        $storageBase = storage_path('app/public');
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (!str_starts_with($name, 'files/') || str_ends_with($name, '/')) continue;

            $destRelative = substr($name, 6); // strip 'files/'
            if ($originalTenantId && $originalTenantId !== $tenant->id) {
                $destRelative = str_replace(
                    "tenants/{$originalTenantId}/",
                    "tenants/{$tenant->id}/",
                    $destRelative
                );
            }

            $destPath = "{$storageBase}/{$destRelative}";

            // Guard against path traversal in crafted ZIPs
            $realBase = realpath($storageBase);
            $realDest = realpath(dirname($destPath)) ?: dirname($destPath);
            if (!str_starts_with($realDest . '/', $realBase . '/')) continue;

            @mkdir(dirname($destPath), 0775, true);
            file_put_contents($destPath, $zip->getFromIndex($i));
            $r['files']++;
        }

        $zip->close();
        return response()->json(['success' => true, ...$r]);
    }
}
