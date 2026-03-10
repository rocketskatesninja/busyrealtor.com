@extends('layouts.admin')
@section('title', 'Billing')
@section('page-subtitle', 'Manage your plan and subscription')
@section('content')
@php
    $account      = $tenant->slug;
    $planData     = [
        'starter' => ['label' => 'Starter', 'price' => '$' . number_format($sys->starter_price ?? 12.99, 2), 'features' => ['Up to 10 listings', 'Public website, gallery & map', 'Contact forms & messaging', 'SMTP custom email', 'Custom branding', 'Email support']],
        'pro'     => ['label' => 'Pro',     'price' => '$' . number_format($sys->pro_price ?? 24.99, 2),     'features' => ['Unlimited listings', 'Appointment scheduling', 'AI chatbot (Claude / OpenAI)', 'AI listing descriptions', 'Google Maps & Analytics', 'Staff management', 'Priority support']],
    ];
    $isSubscribed  = $tenant->stripe_subscription_status === 'active' && $tenant->stripe_subscription_id;
    $isCanceling   = $isSubscribed && $tenant->stripe_cancel_at;
    $isTrialing    = $tenant->plan === 'trial' && $tenant->trial_ends_at?->isFuture();
    $trialExpired  = $tenant->plan === 'trial' && !$isTrialing;
@endphp

<div class="max-w-3xl mx-auto px-4 space-y-6" x-data="{ open: false, toPlan: '', toPrice: '', isUpgrade: false, showCancel: false }">

    {{-- Status Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-5 flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-1">Current Plan</p>
                <p class="text-2xl font-bold text-gray-900 capitalize">
                    {{ $tenant->plan === 'trial' ? 'Free Trial' : ucfirst($tenant->plan) . ' Plan' }}
                </p>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    @if($isTrialing)
                        <span class="inline-flex items-center gap-1.5 text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200 px-2.5 py-1 rounded-full">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Trial ends {{ $tenant->trial_ends_at->diffForHumans() }}
                        </span>
                        <span class="text-xs text-gray-400">Full Pro access included</span>
                    @elseif($trialExpired)
                        <span class="inline-flex items-center gap-1.5 text-xs font-medium bg-red-50 text-red-700 border border-red-200 px-2.5 py-1 rounded-full">
                            Trial expired — subscribe to reactivate
                        </span>
                    @elseif($isCanceling)
                        <span class="inline-flex items-center gap-1.5 text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200 px-2.5 py-1 rounded-full">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Active until {{ $tenant->stripe_cancel_at->format('M j, Y') }}
                        </span>
                    @elseif($tenant->stripe_subscription_status === 'active')
                        <span class="inline-flex items-center gap-1.5 text-xs font-medium bg-green-50 text-green-700 border border-green-200 px-2.5 py-1 rounded-full">
                            <svg class="w-2 h-2 fill-current" viewBox="0 0 8 8"><circle cx="4" cy="4" r="4"/></svg>
                            Active
                        </span>
                    @elseif($tenant->stripe_subscription_status === 'past_due')
                        <span class="inline-flex items-center gap-1.5 text-xs font-medium bg-red-50 text-red-700 border border-red-200 px-2.5 py-1 rounded-full">
                            Payment past due
                        </span>
                    @endif
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 shrink-0">
                @if($isCanceling)
                    <form method="POST" action="{{ route('tenant.admin.billing.resume', $account) }}">
                        @csrf
                        <button type="submit" class="border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-xl font-semibold text-sm transition">
                            Resume Subscription
                        </button>
                    </form>
                @endif
                @if($isSubscribed && !$isCanceling)
                    <button type="button" @click="showCancel = true"
                            class="border border-red-200 text-red-600 hover:bg-red-50 px-4 py-2 rounded-xl font-semibold text-sm transition">
                        Cancel
                    </button>
                @endif
                @if($tenant->stripe_id)
                    <a href="{{ route('tenant.admin.billing.portal', $account) }}"
                       class="btn-primary px-4 py-2 rounded-xl font-semibold text-sm hover:opacity-90 transition inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        Billing Portal
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Plan Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        @foreach($planData as $plan => $details)
        @php $isCurrent = $isSubscribed && $tenant->plan === $plan; @endphp
        <div class="bg-white rounded-2xl shadow-sm border {{ $plan === 'pro' ? 'border-blue-400 ring-1 ring-blue-400' : 'border-gray-100' }} relative flex flex-col">
            @if($plan === 'pro')
            <div class="absolute -top-px left-0 right-0 h-0.5 bg-gradient-to-r from-blue-400 to-indigo-500 rounded-t-2xl"></div>
            @endif
            <div class="px-6 pt-6 pb-5 flex-1">
                <div class="flex items-center justify-between mb-1">
                    <h3 class="font-bold text-gray-900 text-lg">{{ $details['label'] }}</h3>
                    @if($plan === 'pro')
                        <span class="text-xs font-semibold text-blue-600 bg-blue-50 border border-blue-100 px-2.5 py-0.5 rounded-full">Most Popular</span>
                    @endif
                    @if($isCurrent)
                        <span class="text-xs font-semibold text-green-700 bg-green-50 border border-green-200 px-2.5 py-0.5 rounded-full">Current Plan</span>
                    @endif
                </div>
                <p class="text-3xl font-bold text-gray-900 mb-0.5">{{ $details['price'] }}<span class="text-base font-normal text-gray-400">/mo</span></p>
                <ul class="mt-5 space-y-2.5">
                    @foreach($details['features'] as $f)
                    <li class="flex items-start gap-2.5 text-sm text-gray-600">
                        <svg class="w-4 h-4 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        {{ $f }}
                    </li>
                    @endforeach
                </ul>
            </div>
            <div class="px-6 pb-6">
                @if($isCurrent)
                    <div class="w-full py-2.5 rounded-xl text-sm text-center font-medium text-green-700 bg-green-50 border border-green-200">
                        Your current plan
                    </div>
                @else
                    <form id="plan-form-{{ $plan }}" method="POST" action="{{ route('tenant.admin.billing.subscribe', $account) }}">
                        @csrf
                        <input type="hidden" name="plan" value="{{ $plan }}">
                        @if($isSubscribed)
                            <button type="button"
                                    @click="open = true; toPlan = '{{ $plan }}'; toPrice = '{{ $details['price'] }}'; isUpgrade = {{ $tenant->plan === 'starter' ? 'true' : 'false' }}"
                                    class="{{ $plan === 'pro' ? 'btn-primary' : 'border border-gray-300 text-gray-700 hover:bg-gray-50' }} w-full py-2.5 rounded-xl font-semibold text-sm transition">
                                {{ $tenant->plan === 'starter' ? 'Upgrade to Pro' : 'Switch to Starter' }}
                            </button>
                        @else
                            <button type="submit" class="{{ $plan === 'pro' ? 'btn-primary' : 'border border-gray-300 text-gray-700 hover:bg-gray-50' }} w-full py-2.5 rounded-xl font-semibold text-sm transition">
                                Subscribe — {{ $details['price'] }}/mo
                            </button>
                        @endif
                    </form>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    {{-- Billing History --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800">Billing History</h3>
        </div>
        @if(count($invoices) > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">Status</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($invoices as $invoice)
                    @php $status = $invoice->status ?? 'paid'; @endphp
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 text-gray-600">{{ $invoice->date()->format('M j, Y') }}</td>
                        <td class="px-6 py-4 font-semibold text-gray-900">{{ $invoice->total() }}</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $status === 'paid' ? 'bg-green-50 text-green-700 border border-green-200' : '' }}
                                {{ $status === 'open' ? 'bg-amber-50 text-amber-700 border border-amber-200' : '' }}
                                {{ in_array($status, ['void', 'uncollectible']) ? 'bg-red-50 text-red-700 border border-red-200' : '' }}">
                                {{ ucfirst($status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('tenant.admin.billing.invoice', [$account, $invoice->id]) }}"
                               class="inline-flex items-center gap-1 text-xs font-medium text-blue-600 hover:text-blue-800 transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Download
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="px-6 py-12 text-center">
            <svg class="w-8 h-8 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14H5a2 2 0 01-2-2V5a2 2 0 012-2h8a2 2 0 012 2v3M9 14a2 2 0 002 2h8a2 2 0 002-2V9a2 2 0 00-2-2h-3M9 14l2 2 4-4"/></svg>
            <p class="text-gray-400 text-sm">No invoices yet</p>
        </div>
        @endif
    </div>

    {{-- Plan change confirmation modal --}}
    <template x-if="open">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="open = false">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="open = false"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6" @click.stop>
                <h3 class="text-lg font-bold text-gray-900 mb-1" x-text="isUpgrade ? 'Upgrade your plan' : 'Switch to Starter'"></h3>
                <p class="text-sm text-gray-500 mb-4">You're switching to the <span class="font-semibold text-gray-700 capitalize" x-text="toPlan"></span> plan at <span class="font-semibold text-gray-700" x-text="toPrice"></span>/mo.</p>
                <div class="bg-blue-50 border border-blue-100 rounded-xl px-4 py-3 mb-5 text-sm text-blue-800">
                    <p class="font-semibold mb-1">How billing works</p>
                    <p>Your plan switches immediately. The prorated difference will appear as a credit or charge on your next invoice — you won't be charged right now.</p>
                </div>
                <div class="flex gap-3">
                    <button @click="open = false" class="flex-1 border border-gray-300 text-gray-700 hover:bg-gray-50 py-2.5 rounded-xl font-semibold text-sm transition">
                        Cancel
                    </button>
                    <button @click="document.getElementById('plan-form-' + toPlan).submit()"
                            class="flex-1 btn-primary py-2.5 rounded-xl font-semibold text-sm hover:opacity-90 transition">
                        Confirm switch
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- Cancel subscription confirmation modal --}}
    <template x-if="showCancel">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="showCancel = false">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showCancel = false"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6" @click.stop>
                <h3 class="text-lg font-bold text-gray-900 mb-1">Cancel your subscription?</h3>
                <p class="text-sm text-gray-500 mb-4">You won't lose access right away.</p>
                <div class="bg-amber-50 border border-amber-100 rounded-xl px-4 py-3 mb-5 text-sm text-amber-800">
                    <p class="font-semibold mb-1.5">What happens when you cancel</p>
                    <ul class="space-y-1.5">
                        <li class="flex items-start gap-2"><svg class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Your account stays fully active until your billing period ends</li>
                        <li class="flex items-start gap-2"><svg class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>You can undo this anytime before the period ends</li>
                        <li class="flex items-start gap-2"><svg class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>You can reactivate at any time by subscribing again</li>
                    </ul>
                </div>
                <div class="flex gap-3">
                    <button @click="showCancel = false" class="flex-1 border border-gray-300 text-gray-700 hover:bg-gray-50 py-2.5 rounded-xl font-semibold text-sm transition">
                        Keep Subscription
                    </button>
                    <form method="POST" action="{{ route('tenant.admin.billing.cancel', $account) }}" class="flex-1">
                        @csrf
                        <button type="submit" class="w-full border border-red-300 text-red-600 hover:bg-red-50 py-2.5 rounded-xl font-semibold text-sm transition">
                            Yes, cancel
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </template>

</div>
@endsection
