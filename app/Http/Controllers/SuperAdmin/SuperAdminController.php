<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Laravel\Cashier\Subscription;
use App\Models\SystemSetting;
use App\Mail\CampaignMail;
use App\Models\MailCampaign;
use Illuminate\Support\Facades\Mail;

class SuperAdminController extends Controller
{
    public function dashboard()
    {
        $totalTenants  = Tenant::count();
        $trials        = Tenant::where('plan', 'trial')->count();
        $starterCount  = Tenant::where('plan', 'starter')->count();
        $proCount      = Tenant::where('plan', 'pro')->count();
        $cancelled     = Tenant::where('stripe_subscription_status', 'canceled')->count();
        $newThisMonth  = Tenant::whereMonth('created_at', now()->month)
                                ->whereYear('created_at', now()->year)->count();
        $activeSubs    = Tenant::where('stripe_subscription_status', 'active')->count();
        $sys           = \App\Models\SystemSetting::current();
        $mrr           = ($starterCount * $sys->starter_price) + ($proCount * $sys->pro_price);

        $stats = [
            'total_tenants'        => $totalTenants,
            'new_this_month'       => $newThisMonth,
            'active_subscriptions' => $activeSubs,
            'trials'               => $trials,
            'mrr'                  => $mrr,
            'pro_count'            => $proCount,
            'starter_count'        => $starterCount,
            'cancelled_count'      => $cancelled,
        ];

        $recentTenants = Tenant::with('users')->latest()->limit(10)->get();

        // Health alerts
        $expiringTrials = Tenant::where('plan', 'trial')
            ->where('is_active', true)
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', now())
            ->where('trial_ends_at', '<=', now()->addDays(7))
            ->orderBy('trial_ends_at')
            ->get();

        $failedPayments = Tenant::whereNotNull('payment_failed_at')
            ->where('is_active', true)
            ->orderBy('payment_failed_at', 'desc')
            ->get();

        $inactiveTenants = Tenant::where('is_active', true)
            ->whereDoesntHave('activityLogs', fn ($q) => $q->where('created_at', '>', now()->subDays(30)))
            ->whereHas('users')
            ->get();

        $alerts = compact('expiringTrials', 'failedPayments', 'inactiveTenants');

        return view('super-admin.dashboard', compact('stats', 'recentTenants', 'alerts'));
    }

    public function tenants(Request $request)
    {
        $query = Tenant::with('users')
            ->withCount('properties')
            ->latest();

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->plan) {
            $query->where('plan', $request->plan);
        }

        if ($request->status === 'active') {
            $query->where('stripe_subscription_status', 'active');
        } elseif ($request->status === 'trial') {
            $query->where('plan', 'trial')->where('trial_ends_at', '>', now());
        } elseif ($request->status === 'cancelled') {
            $query->where('stripe_subscription_status', 'canceled');
        }

        $tenants = $query->paginate(25);

        return view('super-admin.tenants.index', compact('tenants'));
    }

    public function showTenant(Tenant $tenant)
    {
        $tenant->load('users');

        $stats = [
            'properties' => $tenant->properties()->count(),
            'messages'   => $tenant->messages()->count(),
            'staff'      => $tenant->staffMembers()->count(),
        ];

        return view('super-admin.tenants.show', compact('tenant', 'stats'));
    }

    public function searchTenants(Request $request)
    {
        $q = $request->input('q', '');
        if (strlen($q) < 2) return response()->json([]);

        $tenants = Tenant::where('name', 'like', "%{$q}%")
            ->orWhere('slug', 'like', "%{$q}%")
            ->orWhere('email', 'like', "%{$q}%")
            ->limit(8)
            ->get(['name', 'slug', 'plan']);

        return response()->json($tenants);
    }

    public function updateTenant(Request $request, Tenant $tenant)
    {
        // Snapshot the old plan BEFORE validation/fill so we can detect
        // forbidden transitions (trial -> paid, paid -> trial).
        $oldPlan = $tenant->plan;
        $newPlan = $request->input('plan');

        $request->validate([
            'name'          => 'required|string|max:255',
            'slug'          => 'required|string|max:60|unique:tenants,slug,' . $tenant->id,
            'plan'          => [
                'required',
                Rule::in(['trial', 'starter', 'pro']),
                // Forbid trial -> starter/pro from the super-admin path.
                // Tenants must subscribe themselves via Stripe Checkout —
                // there is no Stripe customer/subscription for a trial,
                // so swap() cannot work, and a DB-only override would
                // get reverted by the next webhook.
                function ($attr, $value, $fail) use ($oldPlan) {
                    if ($oldPlan === 'trial' && in_array($value, ['starter', 'pro'], true)) {
                        $fail('Trial-to-paid upgrades must be initiated by the tenant via the billing page (Stripe Checkout). Super-admin cannot create a Stripe subscription on their behalf.');
                    }
                },
                // Forbid starter/pro -> trial. Downgrading a paying tenant
                // to trial would leave them with an active Stripe sub but a
                // trial UI — confusing and billing-broken. Use the cancel
                // flow instead (sub->cancel() in the existing billing path).
                function ($attr, $value, $fail) use ($oldPlan) {
                    if (in_array($oldPlan, ['starter', 'pro'], true) && $value === 'trial') {
                        $fail('Downgrading a paid tenant to trial requires cancelling their Stripe subscription. Use the cancel/refund flow instead.');
                    }
                },
            ],
            'trial_ends_at' => 'nullable|date|before:2100-01-01',
            'notes'         => 'nullable|string|max:5000',
        ]);

        // Always allow these field edits independent of plan transitions.
        $tenant->fill($request->only('name', 'slug', 'trial_ends_at', 'notes'));

        // Plan transition logic. By the time we reach here, the validator
        // has already rejected trial<->paid moves, so the only changes
        // possible are: trial->trial, starter->pro, pro->starter, or same.
        if ($newPlan !== $oldPlan && in_array($newPlan, ['starter', 'pro'], true)) {
            // starter <-> pro: do the real Stripe swap so the customer is
            // billed at the new prorated rate AND the local plan column
            // stays in sync (Cashier's swap() updates both).
            $sys = SystemSetting::current();
            if (!$sys->hasStripe()) {
                return back()->withErrors(['plan' => 'Stripe is not configured — cannot swap subscription plans.'])->withInput();
            }

            $priceId = $newPlan === 'pro' ? $sys->stripe_pro_price_id : $sys->stripe_starter_price_id;
            if (!$priceId) {
                return back()->withErrors(['plan' => "The {$newPlan} price ID is not configured in System Settings."])->withInput();
            }

            $sub = $this->activeSub($tenant);
            if (!$sub) {
                return back()->withErrors(['plan' => 'This tenant has no active Stripe subscription to swap. They may need to re-subscribe via the billing page.'])->withInput();
            }

            try {
                // swap() calls Stripe AND updates Cashier's local subscription
                // tables atomically. It also clears any pending
                // cancel_at_period_end on the subscription.
                $sub->swap($priceId);
            } catch (\Stripe\Exception\ApiErrorException $e) {
                // Loud failure — do NOT save the local plan change. We
                // only update tenant.plan if Stripe accepted the swap.
                \Illuminate\Support\Facades\Log::error('Super-admin plan swap failed', [
                    'tenant_id' => $tenant->id,
                    'from'      => $oldPlan,
                    'to'        => $newPlan,
                    'error'     => $e->getMessage(),
                ]);
                return back()->withErrors(['plan' => 'Stripe error: ' . $e->getMessage()])->withInput();
            }

            $tenant->plan             = $newPlan;
            $tenant->stripe_cancel_at = null;
            $tenant->save();
            logActivity('updated', "Super-admin swapped plan from {$oldPlan} to {$newPlan} (Stripe synced)", $tenant);

            return back()->with('success', "Tenant plan changed from {$oldPlan} to {$newPlan}. Stripe has been updated and the customer will be billed prorated.");
        }

        // No plan change (or trial -> trial). Just save other field edits
        // (name, slug, notes, trial_ends_at). Useful for trial extensions:
        // super-admin sets trial_ends_at to a future date and saves —
        // no Stripe call needed because trial tenants have no Stripe sub.
        $tenant->plan = $newPlan;
        $tenant->save();

        // Distinct activity message for trial extensions vs. plain edits
        // so the audit log is useful when looking back.
        if ($newPlan === 'trial' && $tenant->wasChanged('trial_ends_at')) {
            logActivity('updated', "Super-admin extended trial to {$tenant->trial_ends_at?->format('Y-m-d')}", $tenant);
        } else {
            logActivity('updated', "Updated tenant: {$tenant->name}", $tenant);
        }

        return back()->with('success', 'Tenant updated successfully.');
    }

    /*
     * Returns the tenant's active Cashier subscription, or null. Mirrors
     * the helper in Admin\BillingController — both controllers wrap the
     * same Cashier conventions (default subscription per tenant).
     */
    private function activeSub(Tenant $tenant): ?Subscription
    {
        return $tenant->subscribed('default') ? $tenant->subscription('default') : null;
    }

    public function destroyTenant(Tenant $tenant)
    {
        logActivity('deleted', "Deleted tenant: {$tenant->name} ({$tenant->slug})", $tenant);
        $tenant->delete();
        return redirect()->route('super.tenants')->with('success', 'Tenant deleted.');
    }

    public function updateTenantOwner(Request $request, Tenant $tenant)
    {
        $owner = $tenant->users()->first();
        abort_unless($owner, 404, 'No owner found for this tenant.');

        $request->validate([
            'email' => 'required|email|max:255|unique:users,email,' . $owner->id,
        ]);

        if ($owner->email === $request->email) {
            return back()->with('success', 'No changes made.');
        }

        $owner->email = $request->email;
        $owner->email_verified_at = null;
        $owner->save();
        logActivity('updated', "Changed tenant owner email to {$request->email} for {$tenant->name}", $tenant);

        try {
            $owner->sendEmailVerificationNotification();
            return back()->with('success', 'Owner email updated. Verification email sent to ' . $owner->email);
        } catch (\Exception $e) {
            \Log::warning('Failed to send verification email for user ' . $owner->id . ': ' . $e->getMessage());
            return back()->with('error', 'Owner email updated but verification email could not be sent.');
        }
    }

    public function verifyTenantOwner(Tenant $tenant)
    {
        $owner = $tenant->users()->first();
        abort_unless($owner, 404, 'No owner found for this tenant.');

        $owner->email_verified_at = now();
        $owner->save();
        logActivity('updated', "Manually verified owner email for {$tenant->name}", $tenant);

        return back()->with('success', 'Owner email marked as verified.');
    }

    public function resendTenantVerification(Tenant $tenant)
    {
        $owner = $tenant->users()->first();
        abort_unless($owner, 404, 'No owner found for this tenant.');

        try {
            $owner->sendEmailVerificationNotification();
            logActivity('updated', "Resent verification email to {$owner->email} for {$tenant->name}", $tenant);
            return back()->with('success', 'Verification email sent to ' . $owner->email);
        } catch (\Exception $e) {
            \Log::warning('Failed to resend verification for user ' . $owner->id . ': ' . $e->getMessage());
            return back()->with('error', 'Failed to send verification email. Check mail configuration.');
        }
    }

    public function mailer()
    {
        $users = User::where('is_super_admin', false)
            ->whereNotNull('email_verified_at')
            ->whereNull('unsubscribed_at')
            ->with('tenant')
            ->orderBy('first_name')
            ->get();

        $campaigns = MailCampaign::latest()->take(20)->get();

        return view('super-admin.mailer', compact('users', 'campaigns'));
    }

    public function sendMail(Request $request)
    {
        $request->validate([
            'subject'  => 'required|string|max:255',
            'body'     => 'required|string',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        $users = User::whereIn('id', $request->user_ids)
            ->where('is_super_admin', false)
            ->whereNull('unsubscribed_at')
            ->get();

        $subject = $request->subject;
        $bodyTemplate = $request->body;

        foreach ($users->chunk(50) as $chunk) {
            foreach ($chunk as $user) {
                $personalizedBody = str_replace(
                    ['{{first_name}}', '{{last_name}}', '{{email}}'],
                    [$user->first_name, $user->last_name, $user->email],
                    $bodyTemplate
                );
                Mail::to($user->email)->send(new CampaignMail($subject, $personalizedBody, $user));
            }
        }

        MailCampaign::create([
            'subject'         => $subject,
            'body'            => $bodyTemplate,
            'recipient_count' => $users->count(),
            'sent_at'         => now(),
        ]);
        logActivity('created', "Sent campaign email \"{$subject}\" to {$users->count()} recipients");

        return back()->with('success', "Email sent to {$users->count()} recipients.");
    }
}