<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class SystemSettingsController extends Controller
{
    public function index()
    {
        $settings = SystemSetting::get();
        return view('super-admin.settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'lock_message' => 'nullable|string|max:500',
        ]);

        SystemSetting::get()->update([
            'registrations_enabled' => $request->boolean('registrations_enabled'),
            'site_locked'           => $request->boolean('site_locked'),
            'lock_message'          => $request->lock_message,
        ]);

        return redirect()->route('super.settings')->with('success', 'Settings saved.');
    }
}
