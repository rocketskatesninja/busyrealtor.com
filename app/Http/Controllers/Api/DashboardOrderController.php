<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteSettings;
use Illuminate\Http\Request;

class DashboardOrderController extends Controller
{
    public function save($account, Request $request)
    {
        $request->validate(['order' => 'nullable|array']);
        $tenant   = app('tenant');
        $settings = SiteSettings::where('tenant_id', $tenant->id)->first();
        if ($settings) {
            $config = $settings->dashboard_config ?? [];
            $config['widget_order'] = $request->order ?? [];
            $settings->update(['dashboard_config' => $config]);
        }
        return response()->json(['success' => true]);
    }
}
