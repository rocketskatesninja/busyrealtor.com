@extends('layouts.admin')
@section('title', 'Thank You')
@section('page-subtitle', 'Your feedback has been received')
@section('content')
@php $account = app('tenant')->slug; @endphp
<div class="max-w-lg mx-auto px-4 text-center py-16">
    <div class="w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6" style="background-color: rgba(var(--primary-rgb), 0.1);">
        <svg class="w-10 h-10" style="color: var(--primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
    </div>
    <p class="text-gray-500 mb-8">We've received your submission. Our team will review it and reach out if we need more information.</p>
    <div class="flex items-center justify-center gap-3">
        <a href="{{ route('tenant.admin.dashboard', $account) }}" class="btn-primary px-6 py-2.5 rounded-xl font-semibold text-sm hover:opacity-90 transition">
            Back to Dashboard
        </a>
        <a href="{{ route('tenant.admin.feedback', $account) }}" class="px-6 py-2.5 border border-gray-200 rounded-xl font-semibold text-sm text-gray-700 hover:bg-gray-50 transition">
            Submit Another
        </a>
    </div>
</div>
@endsection
