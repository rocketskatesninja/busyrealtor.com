@extends('layouts.super-admin')
@section('title', 'System Settings')
@section('page-title', 'System Settings')

@section('content')
<div class="max-w-2xl space-y-6">

    {{-- Page header --}}
    <div>
        <h1 class="text-2xl font-bold text-white">System Settings</h1>
        <p class="mt-1 text-gray-400 text-sm">Global controls for the BusyRealtor platform.</p>
    </div>

    <form method="POST" action="{{ route('super.settings.update') }}">
        @csrf
        @method('PUT')

        {{-- Registrations --}}
        <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden mb-6">
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
                <label class="flex items-center justify-between cursor-pointer">
                    <div>
                        <span class="text-sm font-medium text-gray-200">Enable Registrations</span>
                        <p class="text-gray-500 text-xs mt-0.5">When disabled, the /register page shows a "not accepting signups" message.</p>
                    </div>
                    <div class="relative ml-6 flex-shrink-0" x-data="{ on: {{ $settings->registrations_enabled ? 'true' : 'false' }} }">
                        <input type="hidden" name="registrations_enabled" value="0">
                        <input type="checkbox" name="registrations_enabled" value="1" x-model="on"
                               class="sr-only peer" id="reg_toggle" {{ $settings->registrations_enabled ? 'checked' : '' }}>
                        <label for="reg_toggle"
                               class="relative inline-flex h-6 w-11 items-center rounded-full cursor-pointer transition-colors"
                               :class="on ? 'bg-blue-600' : 'bg-gray-600'"
                               @click="on = !on">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
                                  :class="on ? 'translate-x-6' : 'translate-x-1'"></span>
                        </label>
                    </div>
                </label>
            </div>
        </div>

        {{-- Site Lock --}}
        <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden mb-6" x-data="{ locked: {{ $settings->site_locked ? 'true' : 'false' }} }">
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
                <label class="flex items-center justify-between cursor-pointer">
                    <div>
                        <span class="text-sm font-medium text-gray-200">Lock Site</span>
                        <p class="text-gray-500 text-xs mt-0.5">All tenant public pages will show the maintenance message below.</p>
                    </div>
                    <div class="relative ml-6 flex-shrink-0">
                        <input type="hidden" name="site_locked" value="0">
                        <input type="checkbox" name="site_locked" value="1" x-model="locked"
                               class="sr-only peer" id="lock_toggle" {{ $settings->site_locked ? 'checked' : '' }}>
                        <label for="lock_toggle"
                               class="relative inline-flex h-6 w-11 items-center rounded-full cursor-pointer transition-colors"
                               :class="locked ? 'bg-red-600' : 'bg-gray-600'"
                               @click="locked = !locked">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
                                  :class="locked ? 'translate-x-6' : 'translate-x-1'"></span>
                        </label>
                    </div>
                </label>

                <div x-show="locked" x-cloak>
                    <div class="rounded-lg bg-red-900/30 border border-red-700/50 px-4 py-3 mb-4 flex items-start gap-3">
                        <svg class="w-5 h-5 text-red-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <p class="text-red-300 text-sm">Site is currently <strong>LOCKED</strong>. All public tenant pages are showing the maintenance message.</p>
                    </div>
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
