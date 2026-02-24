@extends('layouts.admin')
@section('title', 'Billing')
@section('content')
@php $account = $tenant->slug; @endphp
<div class="max-w-3xl mx-auto px-4">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Billing & Subscription</h1>

    {{-- Current Plan --}}
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-6">
        <h3 class="font-semibold text-gray-800 mb-4">Current Plan</h3>
        <div class="flex items-center gap-4">
            <div class="flex-1">
                <p class="text-2xl font-bold text-gray-900 capitalize">{{ $tenant->plan }} Plan</p>
                @if($tenant->plan === 'trial')
                    @if($tenant->trial_ends_at && $tenant->trial_ends_at->isFuture())
                    <p class="text-sm text-yellow-600 mt-1">Trial ends {{ $tenant->trial_ends_at->diffForHumans() }}</p>
                    @else
                    <p class="text-sm text-red-600 mt-1">Trial expired</p>
                    @endif
                @elseif($tenant->stripe_subscription_status)
                <p class="text-sm text-green-600 mt-1 capitalize">{{ str_replace('_', ' ', $tenant->stripe_subscription_status) }}</p>
                @endif
            </div>
            @if($tenant->stripe_subscription_id)
            <a href="{{ route('tenant.admin.billing.portal', $account) }}" class="btn-primary px-5 py-2.5 rounded-xl font-semibold text-sm hover:opacity-90 transition">
                Manage Subscription
            </a>
            @endif
        </div>
    </div>

    {{-- Plans --}}
    @if(!$tenant->stripe_subscription_id || $tenant->plan === 'trial')
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        @foreach(['starter' => ['price' => '$29', 'features' => ['Up to 10 listings', 'Public website, gallery & map', 'Contact forms & messaging', 'SMTP custom email', 'Custom branding', 'Email support']], 'pro' => ['price' => '$59', 'features' => ['Unlimited listings', 'Appointment scheduling', 'AI chatbot (Claude / OpenAI)', 'AI listing descriptions', 'Google Maps & Analytics', 'Staff management', 'Priority support']]] as $plan => $details)
        <div class="bg-white rounded-2xl p-6 shadow-sm border {{ $plan === 'pro' ? 'border-blue-500 ring-2 ring-blue-500' : 'border-gray-100' }} relative">
            @if($plan === 'pro') <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-blue-500 text-white text-xs font-bold px-4 py-1 rounded-full">Most Popular</span> @endif
            <h3 class="font-bold text-gray-800 text-xl capitalize mb-1">{{ $plan }}</h3>
            <p class="text-3xl font-bold mb-1 {{ $plan === 'pro' ? '' : 'text-gray-800' }}" style="{{ $plan === 'pro' ? 'color: var(--primary)' : '' }}">{{ $details['price'] }}<span class="text-base font-normal text-gray-500">/mo</span></p>
            <ul class="space-y-2 my-5">
                @foreach($details['features'] as $f)
                <li class="flex items-center gap-2 text-sm text-gray-600">
                    <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ $f }}
                </li>
                @endforeach
            </ul>
            <form method="POST" action="{{ route('tenant.admin.billing.subscribe', $account) }}">
                @csrf
                <input type="hidden" name="plan" value="{{ $plan }}">
                <button type="submit" class="{{ $plan === 'pro' ? 'btn-primary' : 'border border-gray-300 text-gray-700 hover:bg-gray-50' }} w-full py-2.5 rounded-xl font-semibold text-sm transition">
                    Subscribe to {{ ucfirst($plan) }}
                </button>
            </form>
        </div>
        @endforeach
    </div>
    @endif

    @if($tenant->stripe_subscription_id)
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <h3 class="font-semibold text-gray-800 mb-3">Manage Billing</h3>
        <p class="text-gray-500 text-sm mb-4">Update payment method, view invoices, or cancel your subscription through Stripe's secure portal.</p>
        <a href="{{ route('tenant.admin.billing.portal', $account) }}" class="btn-primary px-6 py-2.5 rounded-xl font-semibold text-sm hover:opacity-90 transition inline-block">Open Billing Portal</a>
    </div>
    @endif
</div>
@endsection
