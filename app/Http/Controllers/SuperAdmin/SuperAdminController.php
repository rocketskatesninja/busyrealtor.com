<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
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
        $sys           = \App\Models\SystemSetting::get();
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
        $request->validate([
            'name'          => 'required|string|max:255',
            'slug'          => 'required|string|max:60|unique:tenants,slug,' . $tenant->id,
            'plan'          => 'required|in:trial,starter,pro',
            'trial_ends_at' => 'nullable|date',
            'notes'         => 'nullable|string|max:5000',
        ]);

        $tenant->fill($request->only('name', 'slug', 'trial_ends_at', 'notes'));
        $tenant->plan = $request->plan;
        $tenant->save();
        logActivity('updated', "Updated tenant: {$tenant->name}", $tenant);

        return back()->with('success', 'Tenant updated successfully.');
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