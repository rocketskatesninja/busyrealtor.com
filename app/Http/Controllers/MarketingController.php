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

        return view('marketing.home');
    }
}
