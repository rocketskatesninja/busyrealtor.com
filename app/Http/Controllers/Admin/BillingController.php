<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BillingController extends Controller
{
    public function show()
    {
        $tenant   = app('tenant');
        $sys      = SystemSetting::get();
        $invoices = [];

        if ($tenant->stripe_id && $sys->hasStripe()) {
            try {
                $invoices = $tenant->invoices();
            } catch (\Exception $e) {
                Log::warning('Could not fetch invoices from Stripe', ['tenant_id' => $tenant->id, 'error' => $e->getMessage()]);
            }
        }

        return view('tenant.admin.billing', compact('tenant', 'invoices'));
    }

    public function subscribe($account, Request $request)
    {
        $request->validate(['plan' => 'required|in:starter,pro']);
        $tenant = app('tenant');
        $user   = auth()->user();

        if (!$user->is_super_admin && $user->tenant_id !== $tenant->id) {
            abort(403);
        }

        $sys = SystemSetting::get();

        if (!$sys->hasStripe()) {
            return back()->with('error', 'Billing is not available right now. Please contact support.');
        }

        $priceId = $request->plan === 'pro'
            ? $sys->stripe_pro_price_id
            : $sys->stripe_starter_price_id;

        if (!$priceId) {
            return back()->with('error', 'The selected plan is not available right now. Please contact support.');
        }

        try {
            $checkout = $tenant->newSubscription('default', $priceId)
                ->checkout([
                    'success_url' => route('tenant.admin.billing', ['account' => $tenant->slug]) . '?subscribed=1',
                    'cancel_url'  => route('tenant.admin.billing', ['account' => $tenant->slug]),
                ]);
            return redirect($checkout->url);
        } catch (\Exception $e) {
            Log::error('Stripe checkout error', ['tenant_id' => $tenant->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Could not start checkout. Please try again or contact support.');
        }
    }

    public function portal($account)
    {
        $tenant = app('tenant');

        if (!$tenant->stripe_id) {
            return back()->with('error', 'No active subscription found.');
        }

        try {
            $url = $tenant->billingPortalUrl(
                route('tenant.admin.billing', ['account' => $tenant->slug])
            );
            return redirect($url);
        } catch (\Exception $e) {
            Log::error('Stripe portal error', ['tenant_id' => $tenant->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Could not open billing portal. Please try again or contact support.');
        }
    }

    public function downloadInvoice($account, string $invoiceId)
    {
        $tenant = app('tenant');

        if (!$tenant->stripe_id) {
            abort(404);
        }

        try {
            // Verify this invoice actually belongs to this tenant before serving it
            $invoice = $tenant->findInvoice($invoiceId);

            if (!$invoice || $invoice->customer !== $tenant->stripe_id) {
                abort(404);
            }

            return $tenant->downloadInvoice($invoiceId, [
                'vendor'  => 'BusyRealtor',
                'product' => ucfirst($tenant->plan) . ' Plan Subscription',
            ]);
        } catch (\Exception $e) {
            Log::error('Invoice download error', ['tenant_id' => $tenant->id, 'invoice' => $invoiceId, 'error' => $e->getMessage()]);
            return back()->with('error', 'Could not download invoice. Please try again.');
        }
    }
}
