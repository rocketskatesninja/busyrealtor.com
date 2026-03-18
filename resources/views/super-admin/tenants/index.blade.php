@extends('layouts.super-admin')
@section('title', 'Tenants')
@section('page-title', 'Tenants')
@section('page-description', 'Manage all realtor accounts on the platform')
@section('content')
<div class="space-y-6">
    {{-- Header + Search --}}
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
        <form method="GET" class="flex flex-col sm:flex-row sm:flex-wrap gap-3 sm:items-end">
            <div class="flex-1 sm:min-w-48">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, slug, or email..." class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex gap-3 sm:contents">
                <select name="plan" class="flex-1 sm:flex-none border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none">
                    <option value="">All Plans</option>
                    <option value="trial" {{ request('plan') === 'trial' ? 'selected' : '' }}>Trial</option>
                    <option value="starter" {{ request('plan') === 'starter' ? 'selected' : '' }}>Starter</option>
                    <option value="pro" {{ request('plan') === 'pro' ? 'selected' : '' }}>Pro</option>
                </select>
                <select name="status" class="flex-1 sm:flex-none border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="trial" {{ request('status') === 'trial' ? 'selected' : '' }}>Trial</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="flex items-center gap-3">
                <button type="submit" class="flex-1 sm:flex-none bg-blue-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-blue-700 transition">Filter</button>
                @if(request()->hasAny(['search','plan','status']))
                <a href="{{ route('super.tenants') }}" class="text-sm text-gray-500 py-2.5 whitespace-nowrap">Clear</a>
                @endif
            </div>
        </form>
    </div>

    {{-- Tenants Table --}}
    {{-- Mobile: card list --}}
    <div class="md:hidden space-y-3">
        @forelse($tenants as $tenant)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-start justify-between gap-3 mb-3">
                <div class="min-w-0">
                    <p class="font-semibold text-gray-800 truncate">{{ $tenant->name }}</p>
                    <p class="text-xs text-gray-400 font-mono">{{ $tenant->slug }}</p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $tenant->plan === 'pro' ? 'bg-purple-100 text-purple-700' : ($tenant->plan === 'starter' ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700') }}">{{ ucfirst($tenant->plan) }}</span>
                    @if($tenant->stripe_subscription_status === 'active')
                        <span class="inline-flex items-center gap-1 text-xs text-green-600 font-medium"><span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Active</span>
                    @elseif($tenant->plan === 'trial' && $tenant->trial_ends_at?->isFuture())
                        <span class="inline-flex items-center gap-1 text-xs text-yellow-600 font-medium"><span class="w-1.5 h-1.5 rounded-full bg-yellow-500"></span> Trial</span>
                    @elseif($tenant->stripe_subscription_status === 'canceled' || ($tenant->plan === 'trial' && !$tenant->trial_ends_at?->isFuture()))
                        <span class="inline-flex items-center gap-1 text-xs text-red-500 font-medium"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Expired</span>
                    @else
                        <span class="text-xs text-gray-400">—</span>
                    @endif
                </div>
            </div>
            @if($tenant->users->first())
            <div class="text-sm text-gray-600 mb-3">
                <span class="font-medium">{{ $tenant->users->first()->name }}</span>
                <span class="text-gray-400 text-xs ml-1">{{ $tenant->users->first()->email }}</span>
            </div>
            @endif
            <div class="flex items-center justify-between border-t border-gray-100 pt-3">
                <span class="text-xs text-gray-400">Joined {{ $tenant->created_at->format('M j, Y') }}</span>
                <div class="flex items-center gap-4">
                    <a href="{{ route('super.tenants.show', $tenant->slug) }}" class="text-blue-600 text-xs font-medium">Details</a>
                    <a href="{{ url('/' . $tenant->slug) }}" target="_blank" class="text-gray-400 text-xs">Site ↗</a>
                    <form method="POST" action="{{ route('super.impersonate', $tenant->slug) }}">
                        @csrf
                        <button type="submit" class="text-xs text-orange-600 font-medium">Impersonate</button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-6 py-12 text-center text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            <p class="font-medium">No tenants found</p>
        </div>
        @endforelse
        @if($tenants->hasPages())
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-4 py-3">{{ $tenants->withQueryString()->links() }}</div>
        @endif
    </div>

    {{-- Desktop: table --}}
    <div class="hidden md:block bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <h3 class="font-semibold text-gray-800">{{ $tenants->total() }} {{ Str::plural('Tenant', $tenants->total()) }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Business</th>
                        <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Owner</th>
                        <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                        <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($tenants as $tenant)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <div>
                                <p class="font-medium text-gray-800">{{ $tenant->name }}</p>
                                <p class="text-xs text-gray-400 font-mono">{{ $tenant->slug }}</p>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if($tenant->users->first())
                            <div>
                                <p class="text-gray-700">{{ $tenant->users->first()->name }}</p>
                                <p class="text-xs text-gray-400">{{ $tenant->users->first()->email }}</p>
                            </div>
                            @else
                            <span class="text-gray-400 text-xs">No owner</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $tenant->plan === 'pro' ? 'bg-purple-100 text-purple-700' : ($tenant->plan === 'starter' ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700') }}">
                                {{ ucfirst($tenant->plan) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @if($tenant->stripe_subscription_status === 'active')
                                <span class="inline-flex items-center gap-1 text-xs text-green-600 font-medium">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Active
                                </span>
                            @elseif($tenant->plan === 'trial' && $tenant->trial_ends_at?->isFuture())
                                <span class="inline-flex items-center gap-1 text-xs text-yellow-600 font-medium">
                                    <span class="w-1.5 h-1.5 rounded-full bg-yellow-500"></span> Trial
                                </span>
                            @elseif($tenant->stripe_subscription_status === 'canceled' || ($tenant->plan === 'trial' && !$tenant->trial_ends_at?->isFuture()))
                                <span class="inline-flex items-center gap-1 text-xs text-red-500 font-medium">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Expired
                                </span>
                            @else
                                <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-500 text-xs">{{ $tenant->created_at->format('M j, Y') }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <a href="{{ route('super.tenants.show', $tenant->slug) }}" class="text-blue-600 hover:text-blue-800 text-xs font-medium">Details</a>
                                <a href="{{ url('/' . $tenant->slug) }}" target="_blank" class="text-gray-400 hover:text-gray-600 text-xs">Site ↗</a>
                                <form method="POST" action="{{ route('super.impersonate', $tenant->slug) }}">
                                    @csrf
                                    <button type="submit" class="text-xs text-orange-600 hover:text-orange-800 font-medium">Impersonate</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center text-gray-400">
                            <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            <p class="font-medium">No tenants found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($tenants->hasPages())
        <div class="px-6 py-4 border-t">{{ $tenants->withQueryString()->links() }}</div>
        @endif
    </div>
</div>
@endsection
