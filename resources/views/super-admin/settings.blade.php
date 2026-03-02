@extends('layouts.super-admin')
@section('title', 'System Settings')
@section('page-title', 'System Settings')

@section('content')
<div class="max-w-2xl space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-white">System Settings</h1>
        <p class="mt-1 text-gray-400 text-sm">Global controls for the BusyRealtor platform.</p>
    </div>

    <form method="POST" action="{{ route('super.settings.update') }}">
        @csrf
        @method('PUT')

        {{-- Registrations --}}
        <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden mb-6"
             x-data="{ on: {{ $settings->registrations_enabled ? 'true' : 'false' }} }">
            <div class="px-6 py-4 border-b border-gray-700">
                <h2 class="text-base font-semibold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                    New Registrations
                </h2>
                <p class="text-gray-400 text-sm mt-1">Allow new realtors to sign up for an account.</p>
            </div>
            <div class="px-6 py-5">
                <input type="hidden" name="registrations_enabled" :value="on ? '1' : '0'">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-sm font-medium text-gray-200">Enable Registrations</span>
                        <p class="text-gray-500 text-xs mt-0.5">When disabled, the /register page shows a "not accepting signups" message.</p>
                    </div>
                    <button type="button" @click="on = !on"
                            class="relative ml-6 flex-shrink-0 inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-gray-800"
                            :class="on ? 'bg-blue-600' : 'bg-gray-600'"
                            :aria-checked="on.toString()" role="switch">
                        <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
                              :class="on ? 'translate-x-6' : 'translate-x-1'"></span>
                    </button>
                </div>
                <p class="mt-2 text-xs" :class="on ? 'text-green-400' : 'text-yellow-400'">
                    <span x-text="on ? 'Registrations are open.' : 'Registrations are closed.'"></span>
                </p>
            </div>
        </div>

        {{-- Site Lock --}}
        <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden mb-6"
             x-data="{ locked: {{ $settings->site_locked ? 'true' : 'false' }} }">
            <div class="px-6 py-4 border-b border-gray-700">
                <h2 class="text-base font-semibold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    Emergency Site Lock
                </h2>
                <p class="text-gray-400 text-sm mt-1">Immediately blocks all tenant public pages for non-super-admin users.</p>
            </div>
            <div class="px-6 py-5 space-y-4">
                <input type="hidden" name="site_locked" :value="locked ? '1' : '0'">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-sm font-medium text-gray-200">Lock Site</span>
                        <p class="text-gray-500 text-xs mt-0.5">All tenant public pages will show the maintenance message below.</p>
                    </div>
                    <button type="button" @click="locked = !locked"
                            class="relative ml-6 flex-shrink-0 inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:ring-offset-gray-800"
                            :class="locked ? 'bg-red-600' : 'bg-gray-600'"
                            :aria-checked="locked.toString()" role="switch">
                        <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
                              :class="locked ? 'translate-x-6' : 'translate-x-1'"></span>
                    </button>
                </div>
                <p class="text-xs" :class="locked ? 'text-red-400' : 'text-green-400'">
                    <span x-text="locked ? 'Site is LOCKED. Visitors see the maintenance message.' : 'Site is live and accessible.'"></span>
                </p>

                <div x-show="locked" x-cloak class="rounded-lg bg-red-900/30 border border-red-700/50 px-4 py-3 flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <p class="text-red-300 text-sm">Site is currently <strong>LOCKED</strong>. All public tenant pages are showing the maintenance message below.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Maintenance Message</label>
                    <textarea name="lock_message" rows="3"
                              class="w-full rounded-lg border border-gray-600 bg-gray-700 text-gray-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder-gray-500"
                              placeholder="We are currently performing maintenance. Please check back soon.">{{ old('lock_message', $settings->lock_message) }}</textarea>
                    <p class="text-gray-500 text-xs mt-1">Shown to visitors when the site is locked. Max 500 characters.</p>
                </div>
            </div>
        </div>


        {{-- Stripe / Billing --}}
        <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-700">
                <h2 class="text-base font-semibold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    Stripe / Billing
                </h2>
                <p class="text-gray-400 text-sm mt-1">Configure Stripe API keys and subscription price IDs.</p>
            </div>
            <div class="px-6 py-5 space-y-4">

                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Publishable Key <span class="text-gray-500 text-xs">(pk_live_... or pk_test_...)</span></label>
                        <input type="text" name="stripe_key"
                               value="{{ $settings->stripe_key ? '••••••••' . substr($settings->stripe_key, -6) : '' }}"
                               placeholder="pk_live_..."
                               class="w-full rounded-lg border border-gray-600 bg-gray-700 text-gray-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent placeholder-gray-500">
                        <p class="text-gray-500 text-xs mt-1">Leave blank to keep existing key. Paste full key to update.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Secret Key <span class="text-gray-500 text-xs">(sk_live_... or sk_test_...)</span></label>
                        <input type="text" name="stripe_secret"
                               value="{{ $settings->stripe_secret ? '••••••••' . substr($settings->stripe_secret, -6) : '' }}"
                               placeholder="sk_live_..."
                               class="w-full rounded-lg border border-gray-600 bg-gray-700 text-gray-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent placeholder-gray-500">
                        <p class="text-gray-500 text-xs mt-1">Leave blank to keep existing key. Paste full key to update.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Webhook Secret <span class="text-gray-500 text-xs">(whsec_...)</span></label>
                        <input type="text" name="stripe_webhook_secret"
                               value="{{ $settings->stripe_webhook_secret ? '••••••••' . substr($settings->stripe_webhook_secret, -6) : '' }}"
                               placeholder="whsec_..."
                               class="w-full rounded-lg border border-gray-600 bg-gray-700 text-gray-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent placeholder-gray-500">
                        <p class="text-gray-500 text-xs mt-1">Found in Stripe Dashboard → Webhooks. Point webhook to <code class="text-purple-300 bg-gray-900 px-1 rounded">{{ url('/stripe/webhook') }}</code></p>
                    </div>
                </div>

                <div class="border-t border-gray-700 pt-4">
                    <p class="text-sm font-medium text-gray-300 mb-3">Subscription Price IDs <span class="text-gray-500 text-xs">(from Stripe Dashboard → Products)</span></p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1">Starter Plan Price ID</label>
                            <input type="text" name="stripe_starter_price_id"
                                   value="{{ $settings->stripe_starter_price_id }}"
                                   placeholder="price_..."
                                   class="w-full rounded-lg border border-gray-600 bg-gray-700 text-gray-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent placeholder-gray-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1">Pro Plan Price ID</label>
                            <input type="text" name="stripe_pro_price_id"
                                   value="{{ $settings->stripe_pro_price_id }}"
                                   placeholder="price_..."
                                   class="w-full rounded-lg border border-gray-600 bg-gray-700 text-gray-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent placeholder-gray-500">
                        </div>
                    </div>
                </div>

                @if($settings->hasStripe())
                <div class="flex items-center gap-2 text-green-400 text-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Stripe keys are configured.
                </div>
                @else
                <div class="flex items-center gap-2 text-yellow-400 text-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Stripe is not yet configured. Realtors cannot subscribe until keys and price IDs are set.
                </div>
                @endif

            </div>
        </div>

        {{-- Save --}}
        <div class="flex justify-end">
            <button type="submit"
                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Save Settings
            </button>
        </div>
    </form>

</div>
@endsection
