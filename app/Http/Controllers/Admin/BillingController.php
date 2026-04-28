<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Subscription;
use Stripe\StripeClient;

/*
 * BillingController — wraps Laravel Cashier 16 for tenant billing.
 *
 * Cashier owns the source of truth for subscription state — it lives in the
 * `subscriptions` and `subscription_items` tables, populated by Stripe
 * webhooks. We do NOT store subscription_id / status on the tenant itself
 * (that path is unused; the legacy columns on the tenants table were
 * never populated).
 *
 * The "default" subscription type below is Cashier's convention — every
 * tenant has at most one default subscription at a time.
 *
 * For the in-place plan switch (Starter <-> Pro) we use $sub->swap($priceId)
 * so Cashier handles proration AND the local DB sync. Plain Stripe-API
 * updates would skip the local sync.
 */
class BillingController extends Controller
{
    /*
     * Stripe client built from the encrypted system setting (NOT from .env).
     * Used only for things Cashier doesn't expose, like checkout.session
     * verification on the post-checkout redirect.
     */
    private function stripeClient(): StripeClient
    {
        return new StripeClient(SystemSetting::get()->stripe_secret);
    }

    /*
     * Tenants are scoped per-account in the URL. This guards the action
     * routes against a logged-in user from tenant A POSTing to tenant B.
     * Super admins (impersonating or otherwise) are allowed through.
     */
    private function authorizeForTenant(): void
    {
        $user   = auth()->user();
        $tenant = app('tenant');
        if (!$user->is_super_admin && $user->tenant_id !== $tenant->id) {
            abort(403);
        }
    }

    /*
     * Cashier helper — returns the active "default" subscription, or null.
     * subscribed() returns true even on a cancelled-but-still-in-grace
     * subscription, which is what we want for plan swaps and resumes.
     */
    private function activeSub(Tenant $tenant): ?Subscription
    {
        return $tenant->subscribed('default') ? $tenant->subscription('default') : null;
    }

    /*
     * Render the billing page. We pull invoices from Stripe live (cached
     * by Stripe themselves) so the page always reflects truth.
     */
    public function show($account)
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

    /*
     * Subscribe or swap plans.
     *
     * If the tenant has an active subscription (including one in cancel
     * grace period), swap the plan in place — proration handled by
     * Cashier. Otherwise, send them to Stripe Checkout for a new sub.
     *
     * The activeSub() check prevents the duplicate-subscription bug
     * the old code had, where it relied on tenant columns that were
     * never populated and so always created a new sub on every click.
     */
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
            $sub = $this->activeSub($tenant);

            // Already subscribed — swap plan in place. swap() also clears
            // any pending cancellation (cancel_at_period_end) on the sub.
            if ($sub) {
                $sub->swap($priceId);
                $tenant->plan             = $request->plan;
                $tenant->stripe_cancel_at = null;
                $tenant->save();
                logActivity('updated', "Switched billing plan to {$request->plan}", $tenant);
                return redirect()
                    ->route('tenant.admin.billing', ['account' => $tenant->slug])
                    ->with('success', 'Your plan has been updated to ' . ucfirst($request->plan) . '.');
            }

            // New subscription — Stripe-hosted Checkout.
            $checkout = $tenant->newSubscription('default', $priceId)
                ->checkout([
                    'success_url' => route('tenant.admin.billing.subscribed', ['account' => $tenant->slug])
                        . '?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url'  => route('tenant.admin.billing', ['account' => $tenant->slug]),
                ]);
            logActivity('created', "Initiated {$request->plan} plan subscription checkout", $tenant);
            return redirect($checkout->url);
        } catch (\Exception $e) {
            Log::error('Stripe billing error', ['tenant_id' => $tenant->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Could not update plan. Please try again or contact support.');
        }
    }

    /*
     * Stripe Checkout success redirect.
     *
     * Verifies the session actually completed (defends against a user
     * crafting their own success URL) then bounces to billing with a
     * success flash.
     *
     * The actual sub creation in our DB happens via webhook
     * (customer.subscription.created), which arrives separately and
     * may race with this redirect — that's why we re-fetch the tenant
     * for the flash message rather than trusting the in-memory copy.
     */
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

        $tenant = app('tenant')->fresh();
        return redirect()
            ->route('tenant.admin.billing', ['account' => $account])
            ->with('success', 'Subscription activated! Welcome to BusyRealtor ' . ucfirst($tenant->plan) . '.');
    }

    /*
     * Cancel at period end — keeps access until the paid-through date.
     * Cashier's $sub->cancel() sets cancel_at_period_end on Stripe AND
     * mirrors ends_at into the local subscriptions row in one call.
     */
    public function cancel($account)
    {
        $this->authorizeForTenant();

        $tenant = app('tenant');
        $sub    = $this->activeSub($tenant);

        if (!$sub) {
            return back()->with('error', 'No active subscription to cancel.');
        }

        try {
            $sub->cancel();
            // Mirror to the legacy column for views that still read it.
            $tenant->stripe_cancel_at = $sub->ends_at;
            $tenant->save();
            logActivity('updated', "Cancelled subscription (effective {$sub->ends_at->format('Y-m-d')})", $tenant);

            return redirect()
                ->route('tenant.admin.billing', ['account' => $tenant->slug])
                ->with('success', 'Your subscription has been cancelled. You will have access until ' . $sub->ends_at->format('F j, Y') . '.');
        } catch (\Exception $e) {
            Log::error('Stripe cancel error', ['tenant_id' => $tenant->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Could not cancel subscription. Please try again or contact support.');
        }
    }

    /*
     * Resume a cancelled-but-still-in-grace subscription. Only valid
     * during the grace window between cancel() and ends_at.
     */
    public function resume($account)
    {
        $this->authorizeForTenant();

        $tenant = app('tenant');
        $sub    = $tenant->subscription('default');

        if (!$sub || !$sub->onGracePeriod()) {
            return back()->with('error', 'No pending cancellation to resume.');
        }

        try {
            $sub->resume();
            $tenant->stripe_cancel_at = null;
            $tenant->save();
            logActivity('updated', "Resumed subscription", $tenant);

            return redirect()
                ->route('tenant.admin.billing', ['account' => $tenant->slug])
                ->with('success', 'Your subscription has been resumed. Nothing will change.');
        } catch (\Exception $e) {
            Log::error('Stripe resume error', ['tenant_id' => $tenant->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Could not resume subscription. Please try again or contact support.');
        }
    }

    /*
     * Open Stripe's hosted billing portal — handles payment method
     * updates, invoice history, plan switching, etc. Configured once
     * per Stripe account in dashboard > Settings > Billing > Portal.
     */
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

    /*
     * Download a single invoice as a styled PDF. Defends against
     * cross-tenant access by checking the invoice's customer matches
     * this tenant's stripe_id before serving.
     */
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
