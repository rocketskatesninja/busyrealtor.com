<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function show()
    {
        $tenant = app('tenant');
        return view('tenant.admin.billing', compact('tenant'));
    }

    public function subscribe($account, Request $request)
    {
        $request->validate(['plan' => 'required|in:starter,pro']);
        $tenant = app('tenant');

        // Verify user belongs to this tenant
        if (auth()->user()->tenant_id !== $tenant->id) abort(403);

        $plan   = $request->plan;
        $prices = [
            'starter' => config('services.stripe.starter_price'),
            'pro'     => config('services.stripe.pro_price'),
        ];

        if (!$prices[$plan]) {
            return back()->with('error', 'Stripe is not configured yet.');
        }

        try {
            $checkout = $tenant->newSubscription('default', $prices[$plan])
                ->checkout([
                    'success_url' => route('tenant.admin.billing', ['account' => $tenant->slug]) . '?subscribed=1',
                    'cancel_url'  => route('tenant.admin.billing', ['account' => $tenant->slug]),
                ]);
            return redirect($checkout->url);
        } catch (\Exception $e) {
            return back()->with('error', 'Stripe error: ' . $e->getMessage());
        }
    }

    public function portal($account)
    {
        $tenant = app('tenant');
        try {
            $url = $tenant->billingPortalUrl(
                route('tenant.admin.billing', ['account' => $tenant->slug])
            );
            return redirect($url);
        } catch (\Exception $e) {
            return back()->with('error', 'Could not open billing portal: ' . $e->getMessage());
        }
    }
}
