<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteSettings;
use Illuminate\Http\Request;

class DashboardOrderController extends Controller
{
    public function save($account, Request $request)
    {
        $request->validate([
            'section' => 'required|in:stat_cards,charts,tables',
            'order'   => 'required|array',
        ]);

        $tenant   = app('tenant');
        $settings = SiteSettings::firstOrCreate(['tenant_id' => $tenant->id]);
        $config   = $settings->dashboard_config ?? [];

        $keyMap = ['stat_cards' => '_stat_order', 'charts' => '_chart_order', 'tables' => '_table_order'];
        $config[$keyMap[$request->section]] = $request->order;

        $settings->update(['dashboard_config' => $config]);

        return response()->json(['success' => true]);
    }
}
