@extends('layouts.admin')
@section('title', 'Billing')
@section('page-subtitle', 'Manage your plan and subscription')
@section('content')
@php $account = $tenant->slug; @endphp
<div class="max-w-3xl mx-auto px-4">

    {{-- Success message after subscribing --}}
    @if(request('subscribed'))
    <div class="mb-6 bg-green-50 border border-green-200 rounded-xl px-5 py-4 flex items-center gap-3 text-green-800">
        <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span class="font-medium">Subscription activated! Welcome to BusyRealtor {{ ucfirst($tenant->plan) }}.</span>
    </div>
    @endif

    {{-- Current Plan --}}
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-6">
        <h3 class="font-semibold text-gray-800 mb-4">Current Plan</h3>
        <div class="flex flex-wrap items-center gap-4">
            <div class="flex-1">
                <p class="text-2xl font-bold text-gray-900 capitalize">{{ $tenant->plan }} Plan</p>
                @if($tenant->plan === 'trial')
                    @if($tenant->trial_ends_at && $tenant->trial_ends_at->isFuture())
                    <p class="text-sm text-yellow-600 mt-1">Trial ends {{ $tenant->trial_ends_at->diffForHumans() }}</p>
                    @else
                    <p class="text-sm text-red-600 mt-1">Trial expired</p>
                    @endif
                @elseif($tenant->stripe_subscription_status)
                <p class="text-sm mt-1 capitalize
                    {{ $tenant->stripe_subscription_status === 'active' ? 'text-green-600' : '' }}
                    {{ $tenant->stripe_subscription_status === 'past_due' ? 'text-red-600' : '' }}
                    {{ $tenant->stripe_subscription_status === 'canceled' ? 'text-gray-500' : '' }}">
                    {{ str_replace('_', ' ', $tenant->stripe_subscription_status) }}
                </p>
                @endif
            </div>
            @if($tenant->stripe_id)
            <a href="{{ route('tenant.admin.billing.portal', $account) }}" class="btn-primary px-5 py-2.5 rounded-xl font-semibold text-sm hover:opacity-90 transition">
                Manage Subscription
            </a>
            @endif
        </div>
    </div>

    {{-- Plan cards (only shown if not on a paid plan) --}}
    @if(!$tenant->stripe_id || $tenant->plan === 'trial')
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        @php
            $plans = [
                'starter' => ['price' => '$' . number_format($sys->starter_price ?? 29, 2), 'features' => ['Up to 10 listings', 'Public website, gallery & map', 'Contact forms & messaging', 'SMTP custom email', 'Custom branding', 'Email support']],
                'pro'     => ['price' => '$' . number_format($sys->pro_price ?? 59, 2),     'features' => ['Unlimited listings', 'Appointment scheduling', 'AI chatbot (Claude / OpenAI)', 'AI listing descriptions', 'Google Maps & Analytics', 'Staff management', 'Priority support']],
            ];
        @endphp
        @foreach($plans as $plan => $details)
        <div class="bg-white rounded-2xl p-6 shadow-sm border {{ $plan === 'pro' ? 'border-blue-500 ring-2 ring-blue-500' : 'border-gray-100' }} relative">
            @if($plan === 'pro')<span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-blue-500 text-white text-xs font-bold px-4 py-1 rounded-full">Most Popular</span>@endif
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

    {{-- Billing Portal shortcut --}}
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-6">
        <h3 class="font-semibold text-gray-800 mb-2">Manage Billing</h3>
        <p class="text-gray-500 text-sm mb-4">Update your payment method, change plans, or cancel through Stripe's secure portal.</p>
        @if($tenant->stripe_id)
        <a href="{{ route('tenant.admin.billing.portal', $account) }}" class="btn-primary px-6 py-2.5 rounded-xl font-semibold text-sm hover:opacity-90 transition inline-block">Open Billing Portal</a>
        @else
        <span class="inline-block px-6 py-2.5 rounded-xl font-semibold text-sm bg-gray-100 text-gray-400 cursor-not-allowed">Open Billing Portal</span>
        <p class="text-gray-400 text-xs mt-2">Available once you have an active subscription.</p>
        @endif
    </div>

    {{-- Billing History --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800">Billing History</h3>
        </div>
        @if(count($invoices) > 0)
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs tracking-wide">
                <tr>
                    <th class="px-6 py-3 text-left">Date</th>
                    <th class="px-6 py-3 text-left">Description</th>
                    <th class="px-6 py-3 text-left">Amount</th>
                    <th class="px-6 py-3 text-left">Status</th>
                    <th class="px-6 py-3 text-right"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($invoices as $invoice)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4 text-gray-700">{{ $invoice->date()->format('M j, Y') }}</td>
                    <td class="px-6 py-4 text-gray-700">{{ ucfirst($tenant->plan) }} Plan</td>
                    <td class="px-6 py-4 font-medium text-gray-900">{{ $invoice->total() }}</td>
                    <td class="px-6 py-4">
                        @php $status = $invoice->status ?? 'paid'; @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            {{ $status === 'paid' ? 'bg-green-100 text-green-700' : '' }}
                            {{ $status === 'open' ? 'bg-yellow-100 text-yellow-700' : '' }}
                            {{ in_array($status, ['void', 'uncollectible']) ? 'bg-red-100 text-red-700' : '' }}">
                            {{ ucfirst($status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('tenant.admin.billing.invoice', [$account, $invoice->id]) }}"
                           class="text-xs font-medium text-blue-600 hover:text-blue-800 transition">
                            Download PDF
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
        @else
        <div class="px-6 py-8 text-center text-gray-400 text-sm">
            No billing history yet.
        </div>
        @endif
    </div>

</div>
@endsection
