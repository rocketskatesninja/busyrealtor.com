@extends('layouts.tenant')
@section('title', 'Contact Us — ' . ($settings->site_title ?? 'BusyRealtor'))

@section('hide_header')@endsection
@section('hide_chatbot')@endsection
@section('hide_contact')@endsection

@section('content')
@php $account = $tenant->slug; @endphp
<div class="min-h-screen bg-gray-50 py-10 px-4">
    <div class="max-w-lg mx-auto">

        {{-- Back link --}}
        <a href="{{ route('tenant.home', $account) }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 mb-6 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back
        </a>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            {{-- Header --}}
            <div class="p-6 text-white" style="background-color: var(--primary)">
                <div class="flex items-center gap-3 mb-1">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <h1 class="text-xl font-bold">Contact Us</h1>
                </div>
                <p class="text-sm opacity-90">Send us a message and we'll get back to you within 24 hours.</p>
            </div>

            {{-- Contact details --}}
            @if($settings->contact_phone || $settings->contact_email || $settings->contact_address)
            <div class="px-6 pt-5 pb-2 flex flex-wrap gap-4 text-sm text-gray-600 border-b border-gray-100">
                @if($settings->contact_phone)
                <a href="tel:{{ $settings->contact_phone }}" class="flex items-center gap-2 hover-primary transition">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    {{ $settings->contact_phone }}
                </a>
                @endif
                @if($settings->contact_email)
                <a href="mailto:{{ $settings->contact_email }}" class="flex items-center gap-2 hover-primary transition">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    {{ $settings->contact_email }}
                </a>
                @endif
            </div>
            @endif

            {{-- Form --}}
            <div class="p-6" x-data="{
                submitting: false, success: false, error: '',
                form: { name: '', email: '', phone: '', message: '' },
                async submit() {
                    this.submitting = true; this.error = '';
                    try {
                        const res = await fetch('{{ route('tenant.api.contact', $account) }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                            body: JSON.stringify(this.form)
                        });
                        const data = await res.json();
                        if (data.success) { this.success = true; this.form = { name: '', email: '', phone: '', message: '' }; }
                        else this.error = data.error || 'Failed to send. Please try again.';
                    } catch(e) { this.error = 'Network error. Please try again.'; }
                    finally { this.submitting = false; }
                }
            }">
                {{-- Success --}}
                <div x-show="success" x-cloak class="text-center py-8">
                    <div class="w-14 h-14 rounded-full mx-auto flex items-center justify-center mb-3" style="background-color: rgba(var(--primary-rgb), 0.1)">
                        <svg class="w-7 h-7" style="color: var(--primary)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <p class="font-semibold text-gray-800 mb-1">Message Sent!</p>
                    <p class="text-sm text-gray-500">We'll be in touch soon.</p>
                    <button @click="success = false" class="mt-4 text-sm font-medium transition hover-primary">Send another message</button>
                </div>

                {{-- Form --}}
                <form x-show="!success" @submit.prevent="submit" class="space-y-4">
                    <div x-show="error" x-cloak class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg" x-text="error"></div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Your Name <span class="text-red-500">*</span></label>
                        <input type="text" x-model="form.name" required placeholder="Jane Smith"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:border-transparent"
                               style="--tw-ring-color: var(--primary)">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                        <input type="email" x-model="form.email" required placeholder="jane@example.com"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:border-transparent"
                               style="--tw-ring-color: var(--primary)">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input type="tel" x-model="form.phone" placeholder="(555) 123-4567"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:border-transparent"
                               style="--tw-ring-color: var(--primary)">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Message <span class="text-red-500">*</span></label>
                        <textarea x-model="form.message" required rows="4" placeholder="How can we help you?"
                                  class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:border-transparent resize-none"
                                  style="--tw-ring-color: var(--primary)"></textarea>
                    </div>
                    <button type="submit" :disabled="submitting"
                            class="w-full py-3 rounded-xl font-semibold text-white text-sm transition hover:opacity-90 disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                            style="background-color: var(--primary)">
                        <svg x-show="submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        <span x-text="submitting ? 'Sending...' : 'Send Message'"></span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
