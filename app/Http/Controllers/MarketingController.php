<?php

namespace App\Http\Controllers;

class MarketingController extends Controller
{
    public function index()
    {
        if (auth()->check()) {
            $user = auth()->user();
            if ($user->is_super_admin) {
                return redirect('/super-admin');
            }
            if ($user->tenant_id) {
                $tenant = \App\Models\Tenant::find($user->tenant_id);
                if ($tenant) return redirect('/' . $tenant->slug . '/admin');
            }
        }

        $settings = \App\Models\SystemSetting::get();
        return view('marketing.home', compact('settings'));
    }

    public function privacy()
    {
        return view('marketing.privacy');
    }

    public function terms()
    {
        return view('marketing.terms');
    }
}
