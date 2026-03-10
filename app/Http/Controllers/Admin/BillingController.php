<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

class BillingController extends Controller
{
    private function stripeClient(): StripeClient
    {
        return new StripeClient(SystemSetting::get()->stripe_secret);
    }

    private function authorizeForTenant(): void
    {
        $user   = auth()->user();
        $tenant = app('tenant');
        if (!$user->is_super_admin && $user->tenant_id !== $tenant->id) {
            abort(403);
        }
    }

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

        return view('tenant.admin.billing', compact('tenant', 'invoices', 'sys'));
    }

    public function subscribe($account, Request $request)
    {
        $request->validate(['plan' => 'required|in:starter,pro']);
        $this->authorizeForTenant();

        $tenant = app('tenant');
        $sys    = SystemSetting::get();

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
            // Already subscribed — swap plans in-place with proration
            if ($tenant->stripe_subscription_status === 'active' && $tenant->stripe_subscription_id) {
                $stripe    = $this->stripeClient();
                $stripeSub = $stripe->subscriptions->retrieve($tenant->stripe_subscription_id);
                $stripe->subscriptions->update($tenant->stripe_subscription_id, [
                    'items' => [[
                        'id'    => $stripeSub->items->data[0]->id,
                        'price' => $priceId,
                    ]],
                    'proration_behavior'   => 'create_prorations',
                    'cancel_at_period_end' => false, // clear any pending cancellation on plan change
                ]);
                $tenant->plan             = $request->plan;
                $tenant->stripe_cancel_at = null;
                $tenant->save();
                return redirect()
                    ->route('tenant.admin.billing', ['account' => $tenant->slug])
                    ->with('success', 'Your plan has been updated to ' . ucfirst($request->plan) . '.');
            }

            // New subscription — Stripe Checkout
            $checkout = $tenant->newSubscription('default', $priceId)
                ->checkout([
                    'success_url' => route('tenant.admin.billing.subscribed', ['account' => $tenant->slug])
                        . '?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url'  => route('tenant.admin.billing', ['account' => $tenant->slug]),
                ]);
            return redirect($checkout->url);
        } catch (\Exception $e) {
            Log::error('Stripe billing error', ['tenant_id' => $tenant->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Could not update plan. Please try again or contact support.');
        }
    }

    public function subscribed($account, Request $request)
    {
        $sessionId = $request->query('session_id');

        if ($sessionId && SystemSetting::get()->hasStripe()) {
            try {
                $session = $this->stripeClient()->checkout->sessions->retrieve($sessionId);
                if ($session->payment_status !== 'paid' && $session->status !== 'complete') {
                    return redirect()
                        ->route('tenant.admin.billing', ['account' => $account])
                        ->with('error', 'Payment was not completed. Please try again.');
                }
            } catch (\Exception $e) {
                Log::warning('Could not verify checkout session', ['session_id' => $sessionId]);
            }
        }

        return redirect()
            ->route('tenant.admin.billing', ['account' => $account])
            ->with('success', 'Subscription activated! Welcome to BusyRealtor ' . ucfirst(app('tenant')->plan) . '.');
    }

    public function cancel($account)
    {
        $this->authorizeForTenant();

        $tenant = app('tenant');

        if (!$tenant->stripe_subscription_id || $tenant->stripe_subscription_status !== 'active') {
            return back()->with('error', 'No active subscription to cancel.');
        }

        try {
            $stripe = $this->stripeClient();
            $sub    = $stripe->subscriptions->update($tenant->stripe_subscription_id, [
                'cancel_at_period_end' => true,
            ]);
            $tenant->stripe_cancel_at = Carbon::createFromTimestamp($sub->cancel_at);
            $tenant->save();

            return redirect()
                ->route('tenant.admin.billing', ['account' => $tenant->slug])
                ->with('success', 'Your subscription has been cancelled. You will have access until ' . $tenant->stripe_cancel_at->format('F j, Y') . '.');
        } catch (\Exception $e) {
            Log::error('Stripe cancel error', ['tenant_id' => $tenant->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Could not cancel subscription. Please try again or contact support.');
        }
    }

    public function resume($account)
    {
        $this->authorizeForTenant();

        $tenant = app('tenant');

        if (!$tenant->stripe_subscription_id || !$tenant->stripe_cancel_at) {
            return back()->with('error', 'No pending cancellation to resume.');
        }

        try {
            $this->stripeClient()->subscriptions->update($tenant->stripe_subscription_id, [
                'cancel_at_period_end' => false,
            ]);
            $tenant->stripe_cancel_at = null;
            $tenant->save();

            return redirect()
                ->route('tenant.admin.billing', ['account' => $tenant->slug])
                ->with('success', 'Your subscription has been resumed. Nothing will change.');
        } catch (\Exception $e) {
            Log::error('Stripe resume error', ['tenant_id' => $tenant->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Could not resume subscription. Please try again or contact support.');
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
