@extends('layouts.super-admin')
@section('title', 'System Settings')
@section('page-title', 'System Settings')
@section('page-description', 'Global controls for the BusyRealtor platform')

@section('content')
<div class="max-w-2xl space-y-6">

    {{-- Account --}}
    <form method="POST" action="{{ route('super.settings.email') }}" class="mb-6">
        @csrf
        @method('PUT')
        <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-700">
                <h2 class="text-base font-semibold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Account
                </h2>
                <p class="text-gray-400 text-sm mt-1">Update your login email address.</p>
            </div>
            <div class="px-6 py-5">
                <div class="flex items-end gap-3">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-300 mb-1">Email Address</label>
                        <input type="email" name="email" value="{{ auth()->user()->email }}" required
                               class="w-full rounded-lg border border-gray-600 bg-gray-700 text-gray-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder-gray-500">
                        @error('email') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                        Update
                    </button>
                </div>
            </div>
        </div>
    </form>

    <form method="POST" action="{{ route('super.settings.update') }}" enctype="multipart/form-data">
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


        {{-- Google OAuth --}}
        <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-700">
                <h2 class="text-base font-semibold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-red-400" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    Google OAuth
                </h2>
                <p class="text-gray-400 text-sm mt-1">Enable "Continue with Google" on the login and registration pages.</p>
            </div>
            <div class="px-6 py-5 space-y-4">
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Client ID</label>
                        <input type="text" name="google_client_id"
                               value="{{ $settings->google_client_id ? '••••••••' . substr($settings->google_client_id, -8) : '' }}"
                               placeholder="123456789-abc...apps.googleusercontent.com"
                               class="w-full rounded-lg border border-gray-600 bg-gray-700 text-gray-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder-gray-500">
                        <p class="text-gray-500 text-xs mt-1">Leave blank to keep existing. Paste full value to update.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Client Secret</label>
                        <input type="text" name="google_client_secret"
                               value="{{ $settings->google_client_secret ? '••••••••' . substr($settings->google_client_secret, -6) : '' }}"
                               placeholder="GOCSPX-..."
                               class="w-full rounded-lg border border-gray-600 bg-gray-700 text-gray-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder-gray-500">
                        <p class="text-gray-500 text-xs mt-1">Leave blank to keep existing. Paste full value to update.</p>
                    </div>
                </div>
                <div class="bg-gray-900 rounded-lg p-3 text-xs text-gray-400 space-y-1">
                    <p class="font-medium text-gray-300">Setup instructions:</p>
                    <p>1. Go to <span class="text-blue-400">console.cloud.google.com</span> → APIs &amp; Services → Credentials</p>
                    <p>2. Create an OAuth 2.0 Client ID (Web application)</p>
                    <p>3. Add this as an Authorized redirect URI: <code class="text-green-400 bg-gray-800 px-1 rounded">{{ url('/auth/google/callback') }}</code></p>
                </div>
                @if($settings->hasGoogle())
                <div class="flex items-center gap-2 text-green-400 text-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Google OAuth is configured. The "Continue with Google" button is active.
                </div>
                @else
                <div class="flex items-center gap-2 text-yellow-400 text-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Google OAuth is not configured. The button is visible but will error if clicked.
                </div>
                @endif
            </div>
        </div>

        {{-- Google Maps --}}
        <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-700">
                <h2 class="text-base font-semibold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                    </svg>
                    Google Maps
                </h2>
                <p class="text-gray-400 text-sm mt-1">Platform-level Maps API key shared by all tenants.</p>
            </div>
            <div class="px-6 py-5 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Maps API Key <span class="text-gray-500 font-normal">(browser — HTTP referrer restricted)</span></label>
                    <input type="text" name="google_maps_key"
                           value="{{ $settings->google_maps_key ? '••••••••' . substr($settings->google_maps_key, -8) : '' }}"
                           placeholder="AIzaSy..."
                           class="w-full rounded-lg border border-gray-600 bg-gray-700 text-gray-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder-gray-500">
                    <p class="text-gray-500 text-xs mt-1">Leave blank to keep existing. Restrict to HTTP referrers (*.busyrealtor.com/*).</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Places API Key <span class="text-gray-500 font-normal">(server — IP restricted)</span></label>
                    <input type="text" name="google_places_key"
                           value="{{ $settings->google_places_key ? '••••••••' . substr($settings->google_places_key, -8) : '' }}"
                           placeholder="AIzaSy..."
                           class="w-full rounded-lg border border-gray-600 bg-gray-700 text-gray-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder-gray-500">
                    <p class="text-gray-500 text-xs mt-1">Leave blank to keep existing. Restrict to server IPs.</p>
                </div>
                <div class="bg-gray-900 rounded-lg p-3 text-xs text-gray-400 space-y-1">
                    <p class="font-medium text-gray-300">Setup instructions:</p>
                    <p>1. Go to <span class="text-blue-400">console.cloud.google.com</span> &rarr; APIs &amp; Services &rarr; Credentials</p>
                    <p>2. Create an API key and enable the Maps JavaScript API</p>
                    <p>3. Maps key: restrict to HTTP referrers (*.busyrealtor.com/*)</p>
                    <p>4. Places key: restrict to server IP addresses</p>
                </div>
                @if($settings->hasMaps())
                <div class="flex items-center gap-2 text-green-400 text-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Google Maps is configured. All tenant map pages will use this key.
                </div>
                @else
                <div class="flex items-center gap-2 text-yellow-400 text-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Google Maps is not configured. Tenant map pages will show a placeholder.
                </div>
                @endif
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

                <div class="border-t border-gray-700 pt-4">
                    <p class="text-sm font-medium text-gray-300 mb-3">Plan Pricing <span class="text-gray-500 text-xs">(displayed on billing page and marketing home)</span></p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1">Starter Monthly Price ($)</label>
                            <input type="number" name="starter_price" min="0" step="0.01"
                                   value="{{ old('starter_price', $settings->starter_price ?? 29) }}"
                                   class="w-full rounded-lg border border-gray-600 bg-gray-700 text-gray-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent placeholder-gray-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1">Pro Monthly Price ($)</label>
                            <input type="number" name="pro_price" min="0" step="0.01"
                                   value="{{ old('pro_price', $settings->pro_price ?? 59) }}"
                                   class="w-full rounded-lg border border-gray-600 bg-gray-700 text-gray-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent placeholder-gray-500">
                        </div>
                    </div>
                </div>

                @if($settings->hasStripe())
                <div class="flex items-center gap-2 text-green-400 text-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Stripe keys are configured.
                </div>
                @if(!$settings->stripe_webhook_secret)
                <div class="rounded-lg bg-yellow-900/30 border border-yellow-700/50 px-4 py-3 flex items-start gap-3 mt-2">
                    <svg class="w-5 h-5 text-yellow-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <p class="text-yellow-300 text-xs">
                        <strong class="font-semibold">Webhook secret is not configured.</strong>
                        Without it, Stripe webhook signature verification is disabled and the webhook endpoint is unauthenticated.
                        Add the webhook signing secret (<code class="bg-gray-900 px-1 rounded">whsec_...</code>) from your Stripe dashboard.
                    </p>
                </div>
                @endif
                @else
                <div class="flex items-center gap-2 text-yellow-400 text-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Stripe is not yet configured. Realtors cannot subscribe until keys and price IDs are set.
                </div>
                @endif

            </div>
        </div>

        {{-- SMTP / Email --}}
        <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-700">
                <h2 class="text-base font-semibold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    SMTP / Email
                </h2>
                <p class="text-gray-400 text-sm mt-1">Configure outbound email for verification emails, password resets, and notifications.</p>
            </div>
            <div class="px-6 py-5 space-y-4">

                <div class="grid grid-cols-4 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-1">SMTP Host</label>
                        <input type="text" name="smtp_host"
                               value="{{ old('smtp_host', $settings->smtp_host) }}"
                               placeholder="smtp.gmail.com"
                               class="w-full rounded-lg border border-gray-600 bg-gray-700 text-gray-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent placeholder-gray-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Port</label>
                        <input type="number" name="smtp_port" min="1" max="65535"
                               value="{{ old('smtp_port', $settings->smtp_port) }}"
                               placeholder="587"
                               class="w-full rounded-lg border border-gray-600 bg-gray-700 text-gray-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent placeholder-gray-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Encryption</label>
                        <select name="smtp_encryption"
                                class="w-full rounded-lg border border-gray-600 bg-gray-700 text-gray-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="" {{ !$settings->smtp_encryption ? 'selected' : '' }}>None</option>
                            <option value="tls" {{ $settings->smtp_encryption === 'tls' ? 'selected' : '' }}>TLS</option>
                            <option value="ssl" {{ $settings->smtp_encryption === 'ssl' ? 'selected' : '' }}>SSL</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Username</label>
                        <input type="text" name="smtp_username"
                               value="{{ $settings->smtp_username ? '••••••••' . substr($settings->smtp_username, -8) : '' }}"
                               placeholder="user@example.com"
                               class="w-full rounded-lg border border-gray-600 bg-gray-700 text-gray-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent placeholder-gray-500">
                        <p class="text-gray-500 text-xs mt-1">Leave blank to keep existing. Paste full value to update.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Password</label>
                        <input type="password" name="smtp_password"
                               value="{{ $settings->smtp_password ? '••••••••••••' : '' }}"
                               placeholder="••••••••"
                               class="w-full rounded-lg border border-gray-600 bg-gray-700 text-gray-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent placeholder-gray-500">
                        <p class="text-gray-500 text-xs mt-1">Leave blank to keep existing. Paste full value to update.</p>
                    </div>
                </div>

                <div class="border-t border-gray-700 pt-4">
                    <p class="text-sm font-medium text-gray-300 mb-3">From Address</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1">Email Address</label>
                            <input type="email" name="mail_from_address"
                                   value="{{ old('mail_from_address', $settings->mail_from_address) }}"
                                   placeholder="noreply@busyrealtor.com"
                                   class="w-full rounded-lg border border-gray-600 bg-gray-700 text-gray-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent placeholder-gray-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1">Sender Name</label>
                            <input type="text" name="mail_from_name"
                                   value="{{ old('mail_from_name', $settings->mail_from_name) }}"
                                   placeholder="BusyRealtor"
                                   class="w-full rounded-lg border border-gray-600 bg-gray-700 text-gray-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent placeholder-gray-500">
                        </div>
                    </div>
                </div>

                @if($settings->hasMail())
                <div class="flex items-center gap-2 text-green-400 text-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    SMTP is configured. Outbound email is active.
                </div>
                @else
                <div class="flex items-center gap-2 text-yellow-400 text-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    SMTP is not configured. Email verification, password resets, and notifications will not work.
                </div>
                @endif

                <div class="bg-gray-900 rounded-lg p-3 text-xs text-gray-400 space-y-1">
                    <p class="font-medium text-gray-300">Common SMTP providers:</p>
                    <p><span class="text-green-400">Gmail:</span> smtp.gmail.com, port 587, TLS, use an App Password</p>
                    <p><span class="text-blue-400">Mailgun:</span> smtp.mailgun.org, port 587, TLS</p>
                    <p><span class="text-purple-400">SendGrid:</span> smtp.sendgrid.net, port 587, TLS, username: apikey</p>
                    <p><span class="text-orange-400">Amazon SES:</span> email-smtp.[region].amazonaws.com, port 587, TLS</p>
                </div>

            </div>
        </div>

        {{-- Marketing --}}
        <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-700">
                <h2 class="text-base font-semibold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"/></svg>
                    Marketing
                </h2>
                <p class="text-gray-400 text-sm mt-1">Assets used on the public marketing site.</p>
            </div>
            <div class="px-6 py-5 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">
                        OG / Social Share Image
                        <span class="text-gray-500 font-normal text-xs ml-1">(og:image for busyrealtor.com)</span>
                    </label>
                    @if($settings->og_image)
                    <div class="mb-2">
                        <img src="{{ asset('storage/' . $settings->og_image) }}"
                             alt="Current OG image" class="h-20 rounded border border-gray-600 object-cover bg-gray-900">
                    </div>
                    @endif
                    <input type="file" name="og_image" accept="image/jpeg,image/png,image/webp"
                           class="w-full rounded-lg border border-gray-600 bg-gray-700 text-gray-300 px-3 py-2 text-sm file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:font-medium file:bg-gray-600 file:text-gray-200 hover:file:bg-gray-500 cursor-pointer">
                    <p class="text-gray-500 text-xs mt-1">Shown when busyrealtor.com is shared on social media. Recommended: 1200×630 px, JPEG or PNG, max 2 MB.</p>
                </div>
            </div>
        </div>

        {{-- Social Links --}}
        <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-700">
                <h2 class="text-base font-semibold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                    </svg>
                    Marketing Site Social Links
                </h2>
                <p class="text-gray-400 text-sm mt-1">Links shown as icons in the footer of the public marketing site.</p>
            </div>
            <div class="px-6 py-5 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Facebook</label>
                        <input type="url" name="social_facebook" value="{{ old('social_facebook', $settings->social_facebook) }}" placeholder="https://facebook.com/busyrealtor" class="w-full rounded-lg border border-gray-600 bg-gray-700 text-gray-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent placeholder-gray-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Instagram</label>
                        <input type="url" name="social_instagram" value="{{ old('social_instagram', $settings->social_instagram) }}" placeholder="https://instagram.com/busyrealtor" class="w-full rounded-lg border border-gray-600 bg-gray-700 text-gray-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent placeholder-gray-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">X (Twitter)</label>
                        <input type="url" name="social_x" value="{{ old('social_x', $settings->social_x) }}" placeholder="https://x.com/busyrealtor" class="w-full rounded-lg border border-gray-600 bg-gray-700 text-gray-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent placeholder-gray-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">LinkedIn</label>
                        <input type="url" name="social_linkedin" value="{{ old('social_linkedin', $settings->social_linkedin) }}" placeholder="https://linkedin.com/company/busyrealtor" class="w-full rounded-lg border border-gray-600 bg-gray-700 text-gray-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent placeholder-gray-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">YouTube</label>
                        <input type="url" name="social_youtube" value="{{ old('social_youtube', $settings->social_youtube) }}" placeholder="https://youtube.com/@busyrealtor" class="w-full rounded-lg border border-gray-600 bg-gray-700 text-gray-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent placeholder-gray-500">
                    </div>
                </div>
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
