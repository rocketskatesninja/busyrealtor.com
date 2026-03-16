@extends('layouts.super-admin')
@section('title', $tenant->name)
@section('content')
<div class="max-w-4xl space-y-6">
    {{-- Header Card --}}
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <div class="flex items-start justify-between flex-wrap gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ $tenant->name }}</h2>
                <p class="text-gray-500 font-mono text-sm mt-1">{{ $tenant->slug }}</p>
                <div class="flex items-center gap-3 mt-3">
                    <span class="px-3 py-1 rounded-full text-sm font-semibold {{ $tenant->plan === 'pro' ? 'bg-purple-100 text-purple-700' : ($tenant->plan === 'starter' ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700') }}">
                        {{ ucfirst($tenant->plan) }} Plan
                    </span>
                    @if($tenant->stripe_subscription_status)
                    <span class="text-sm text-gray-500 capitalize">{{ str_replace('_', ' ', $tenant->stripe_subscription_status) }}</span>
                    @endif
                    @if($tenant->plan === 'trial' && $tenant->trial_ends_at)
                    <span class="text-sm text-yellow-600">Trial ends {{ $tenant->trial_ends_at->diffForHumans() }}</span>
                    @endif
                </div>
            </div>
            <div class="flex gap-3 flex-wrap">
                <a href="{{ url('/' . $tenant->slug) }}" target="_blank" class="border border-gray-200 text-gray-700 px-4 py-2 rounded-xl text-sm font-medium hover:bg-gray-50 transition">
                    View Site ↗
                </a>
                <form method="POST" action="{{ route('super.impersonate', $tenant->slug) }}">
                    @csrf
                    <button type="submit" class="bg-orange-500 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-orange-600 transition">
                        Impersonate Admin
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Stats --}}
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Properties</p>
            <p class="text-3xl font-bold text-gray-900">{{ $stats['properties'] }}</p>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Messages</p>
            <p class="text-3xl font-bold text-gray-900">{{ $stats['messages'] }}</p>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Staff Members</p>
            <p class="text-3xl font-bold text-gray-900">{{ $stats['staff'] }}</p>
        </div>
    </div>

    {{-- Edit Form --}}
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <h3 class="font-semibold text-gray-800 mb-5">Edit Tenant</h3>
        <form method="POST" action="{{ route('super.tenants.update', $tenant->slug) }}" class="space-y-4">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4" x-data="{ plan: '{{ $tenant->plan }}' }">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Business Name</label>
                    <input type="text" name="name" value="{{ old('name', $tenant->name) }}" required class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Slug (URL)</label>
                    <input type="text" name="slug" value="{{ old('slug', $tenant->slug) }}" required class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono">
                    <p class="text-xs text-gray-400 mt-1">Changing slug will break existing links</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Plan Override</label>
                    <select name="plan" x-model="plan" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="trial" {{ $tenant->plan === 'trial' ? 'selected' : '' }}>Trial</option>
                        <option value="starter" {{ $tenant->plan === 'starter' ? 'selected' : '' }}>Starter</option>
                        <option value="pro" {{ $tenant->plan === 'pro' ? 'selected' : '' }}>Pro</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Trial Ends At</label>
                    <input type="date" name="trial_ends_at" value="{{ old('trial_ends_at', $tenant->trial_ends_at?->format('Y-m-d')) }}"
                           :disabled="plan !== 'trial'"
                           :class="plan !== 'trial' ? 'opacity-50 cursor-not-allowed' : ''"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            @if($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3">
                <ul class="text-sm text-red-600 space-y-1">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <div class="flex justify-end">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2.5 rounded-xl text-sm font-semibold hover:bg-blue-700 transition">Save Changes</button>
            </div>
        </form>
    </div>

    {{-- Owner Info --}}
    @if($owner = $tenant->users->first())
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <h3 class="font-semibold text-gray-800 mb-4">Account Owner</h3>

        <div class="flex items-center gap-4 mb-5">
            <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-lg">
                {{ strtoupper(substr($owner->name, 0, 1)) }}
            </div>
            <div>
                <p class="font-medium text-gray-800">{{ $owner->name }}</p>
                <p class="text-xs text-gray-400 mt-0.5">Joined {{ $owner->created_at->format('M j, Y') }}</p>
            </div>
            <div class="ml-auto">
                @if($owner->email_verified_at)
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Verified
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Unverified
                    </span>
                @endif
            </div>
        </div>

        {{-- Email Edit Form --}}
        <form method="POST" action="{{ route('super.tenants.owner.update', $tenant->slug) }}" class="mb-4">
            @csrf @method('PUT')
            <label class="block text-sm font-medium text-gray-700 mb-1">Owner Email</label>
            <div class="flex gap-3">
                <input type="email" name="email" value="{{ old('email', $owner->email) }}" required
                       class="flex-1 border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <button type="submit" class="bg-blue-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-blue-700 transition whitespace-nowrap">
                    Update Email
                </button>
            </div>
            <p class="text-xs text-gray-400 mt-1">Changing the email will reset verification status.</p>
        </form>

        {{-- Verification Actions --}}
        @unless($owner->email_verified_at)
        <div class="flex gap-3 pt-3 border-t border-gray-100">
            <form method="POST" action="{{ route('super.tenants.owner.verify', $tenant->slug) }}">
                @csrf
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-green-700 transition">
                    Verify Now
                </button>
            </form>
            <form method="POST" action="{{ route('super.tenants.owner.resend', $tenant->slug) }}">
                @csrf
                <button type="submit" class="border border-gray-200 text-gray-700 px-4 py-2 rounded-xl text-sm font-medium hover:bg-gray-50 transition">
                    Resend Verification Email
                </button>
            </form>
        </div>
        @endunless
    </div>
    @endif

    {{-- Subscription Info --}}
    @if($tenant->stripe_id)
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <h3 class="font-semibold text-gray-800 mb-4">Stripe Subscription</h3>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-gray-500 text-xs font-medium uppercase tracking-wide mb-1">Customer ID</p>
                <p class="font-mono text-gray-700 text-xs">{{ $tenant->stripe_id }}</p>
            </div>
            @if($tenant->stripe_subscription_id)
            <div>
                <p class="text-gray-500 text-xs font-medium uppercase tracking-wide mb-1">Subscription ID</p>
                <p class="font-mono text-gray-700 text-xs">{{ $tenant->stripe_subscription_id }}</p>
            </div>
            @endif
            @if($tenant->stripe_subscription_status)
            <div>
                <p class="text-gray-500 text-xs font-medium uppercase tracking-wide mb-1">Status</p>
                <p class="text-gray-700 capitalize">{{ str_replace('_', ' ', $tenant->stripe_subscription_status) }}</p>
            </div>
            @endif
            @if($tenant->stripe_current_period_end)
            <div>
                <p class="text-gray-500 text-xs font-medium uppercase tracking-wide mb-1">Period End</p>
                <p class="text-gray-700">{{ $tenant->stripe_current_period_end->format('M j, Y') }}</p>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Danger Zone --}}
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-red-100">
        <h3 class="font-semibold text-red-700 mb-2">Danger Zone</h3>
        <p class="text-sm text-gray-500 mb-4">Permanently delete this tenant and all associated data. This cannot be undone.</p>
        <form method="POST" action="{{ route('super.tenants.destroy', $tenant->slug) }}" onsubmit="return confirm('Delete ' + {{ json_encode($tenant->name) }} + ' and ALL data? This cannot be undone.')">
            @csrf @method('DELETE')
            <button type="submit" class="bg-red-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-red-700 transition">
                Delete Tenant &amp; All Data
            </button>
        </form>
    </div>
</div>
@endsection
