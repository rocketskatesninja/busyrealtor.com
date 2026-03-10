<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;

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

        return view('super-admin.dashboard', compact('stats', 'recentTenants'));
    }

    public function tenants(Request $request)
    {
        $query = Tenant::with('users')->latest();

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

    public function updateTenant(Request $request, Tenant $tenant)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'slug'          => 'required|string|max:60|unique:tenants,slug,' . $tenant->id,
            'plan'          => 'required|in:trial,starter,pro',
            'trial_ends_at' => 'nullable|date',
        ]);

        $tenant->fill($request->only('name', 'slug', 'trial_ends_at'));
        $tenant->plan = $request->plan;
        $tenant->save();

        return back()->with('success', 'Tenant updated successfully.');
    }

    public function destroyTenant(Tenant $tenant)
    {
        $tenant->delete();
        return redirect()->route('super.tenants')->with('success', 'Tenant deleted.');
    }
}
