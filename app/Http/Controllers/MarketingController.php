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
        $settings = \App\Models\SystemSetting::get();
        return view('marketing.privacy', compact('settings'));
    }

    public function terms()
    {
        $settings = \App\Models\SystemSetting::get();
        return view('marketing.terms', compact('settings'));
    }

    public function sitemap()
    {
        $tenants = \App\Models\Tenant::where('is_active', true)->get(['slug']);
        return response()
            ->view('marketing.sitemap', compact('tenants'))
            ->header('Content-Type', 'text/xml');
    }

    public function sitemapPages()
    {
        return response()
            ->view('marketing.sitemap-pages')
            ->header('Content-Type', 'text/xml');
    }
}
