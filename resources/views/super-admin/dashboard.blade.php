@extends('layouts.super-admin')
@section('title', 'Super Admin Dashboard')
@section('page-title', 'Dashboard')
@section('page-description', 'Platform overview and statistics')
@section('content')
<div class="space-y-6">
    {{-- Stats Row --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-gray-500">Total Tenants</span>
                <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900">{{ $stats['total_tenants'] }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $stats['new_this_month'] }} new this month</p>
        </div>
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-gray-500">Active Subscriptions</span>
                <div class="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900">{{ $stats['active_subscriptions'] }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $stats['trials'] }} on trial</p>
        </div>
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-gray-500">Monthly Revenue</span>
                <div class="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900">${{ number_format($stats['mrr'], 0) }}</p>
            <p class="text-xs text-gray-400 mt-1">estimated MRR</p>
        </div>
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-gray-500">Pro Subscribers</span>
                <div class="w-10 h-10 rounded-xl bg-yellow-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900">{{ $stats['pro_count'] }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $stats['starter_count'] }} on starter</p>
        </div>
    </div>

    {{-- Health Alerts --}}
    @if($alerts['expiringTrials']->count() || $alerts['failedPayments']->count() || $alerts['inactiveTenants']->count())
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                Attention Needed
            </h3>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($alerts['expiringTrials'] as $t)
            <div class="px-6 py-3 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-yellow-400 flex-shrink-0"></span>
                    <div>
                        <p class="text-sm font-medium text-gray-800">{{ $t->name }}</p>
                        <p class="text-xs text-gray-400">Trial expires {{ $t->trial_ends_at->diffForHumans() }}</p>
                    </div>
                </div>
                <a href="{{ route('super.tenants.show', $t->slug) }}" class="text-xs text-blue-600 font-medium hover:underline">View</a>
            </div>
            @endforeach
            @foreach($alerts['failedPayments'] as $t)
            <div class="px-6 py-3 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-red-500 flex-shrink-0"></span>
                    <div>
                        <p class="text-sm font-medium text-gray-800">{{ $t->name }}</p>
                        <p class="text-xs text-gray-400">Payment failed {{ $t->payment_failed_at->diffForHumans() }}</p>
                    </div>
                </div>
                <a href="{{ route('super.tenants.show', $t->slug) }}" class="text-xs text-blue-600 font-medium hover:underline">View</a>
            </div>
            @endforeach
            @foreach($alerts['inactiveTenants'] as $t)
            <div class="px-6 py-3 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-gray-400 flex-shrink-0"></span>
                    <div>
                        <p class="text-sm font-medium text-gray-800">{{ $t->name }}</p>
                        <p class="text-xs text-gray-400">No activity in 30+ days</p>
                    </div>
                </div>
                <a href="{{ route('super.tenants.show', $t->slug) }}" class="text-xs text-blue-600 font-medium hover:underline">View</a>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Recent Signups --}}
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b flex items-center justify-between">
                <h3 class="font-semibold text-gray-800">Recent Signups</h3>
                <a href="{{ route('super.tenants') }}" class="text-sm text-blue-600 hover:underline">View all</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Business</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($recentTenants as $tenant)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div>
                                    <p class="font-medium text-gray-800">{{ $tenant->name }}</p>
                                    <p class="text-xs text-gray-400">{{ $tenant->slug }}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $tenant->plan === 'pro' ? 'bg-purple-100 text-purple-700' : ($tenant->plan === 'starter' ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700') }}">
                                    {{ ucfirst($tenant->plan) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($tenant->stripe_subscription_status === 'active')
                                    <span class="text-xs text-green-600 font-medium">Active</span>
                                @elseif($tenant->plan === 'trial')
                                    <span class="text-xs text-yellow-600 font-medium">Trial</span>
                                @else
                                    <span class="text-xs text-gray-400 font-medium capitalize">{{ $tenant->stripe_subscription_status ?? 'None' }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-500 text-xs">{{ $tenant->created_at->format('M j, Y') }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('super.tenants.show', $tenant->slug) }}" class="text-blue-600 hover:text-blue-800 text-xs font-medium">View</a>
                                    <form method="POST" action="{{ route('super.impersonate', $tenant->slug) }}">
                                        @csrf
                                        <button type="submit" class="text-gray-500 hover:text-gray-800 text-xs">Impersonate</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-400">No tenants yet</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Plan Breakdown --}}
        <div class="space-y-4">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Plan Breakdown</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
                            <span class="text-sm text-gray-600">Trial</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold text-gray-800">{{ $stats['trials'] }}</span>
                            <div class="w-20 h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-yellow-400 rounded-full" style="width: {{ $stats['total_tenants'] > 0 ? ($stats['trials'] / $stats['total_tenants'] * 100) : 0 }}%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                            <span class="text-sm text-gray-600">Starter</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold text-gray-800">{{ $stats['starter_count'] }}</span>
                            <div class="w-20 h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-blue-500 rounded-full" style="width: {{ $stats['total_tenants'] > 0 ? ($stats['starter_count'] / $stats['total_tenants'] * 100) : 0 }}%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-purple-500"></div>
                            <span class="text-sm text-gray-600">Pro</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold text-gray-800">{{ $stats['pro_count'] }}</span>
                            <div class="w-20 h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-purple-500 rounded-full" style="width: {{ $stats['total_tenants'] > 0 ? ($stats['pro_count'] / $stats['total_tenants'] * 100) : 0 }}%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-gray-300"></div>
                            <span class="text-sm text-gray-600">Cancelled</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold text-gray-800">{{ $stats['cancelled_count'] }}</span>
                            <div class="w-20 h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-gray-300 rounded-full" style="width: {{ $stats['total_tenants'] > 0 ? ($stats['cancelled_count'] / $stats['total_tenants'] * 100) : 0 }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Quick Actions</h3>
                <div class="space-y-2">
                    <a href="{{ route('super.tenants') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 transition-colors text-sm text-gray-700">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        Manage Tenants
                    </a>
                    <a href="{{ route('register') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 transition-colors text-sm text-gray-700">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Create New Tenant
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
