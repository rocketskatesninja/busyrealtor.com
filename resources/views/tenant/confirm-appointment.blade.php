@extends('layouts.tenant')
@section('title', 'Appointment Confirmed')
@section('content')
<div class="max-w-lg mx-auto px-4 py-20 text-center">
    <div class="bg-white rounded-2xl shadow-sm p-10">
        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 mb-3">Appointment Confirmed!</h1>
        <p class="text-gray-600 mb-6">Your appointment for {{ $appointment->appointment_date ? \Carbon\Carbon::parse($appointment->appointment_date)->format('F j, Y') : 'the requested date' }} has been confirmed.</p>
        @if($appointment->visitor_name)<p class="text-gray-500 text-sm">Name: <span class="font-medium text-gray-700">{{ $appointment->visitor_name }}</span></p>@endif
        @if($appointment->message_type)<p class="text-gray-500 text-sm mt-1">Type: <span class="font-medium text-gray-700 capitalize">{{ $appointment->message_type }}</span></p>@endif
        <div class="mt-8">
            <a href="{{ route('tenant.home', $tenant->slug) }}" class="btn-primary px-8 py-3 rounded-xl font-semibold hover:opacity-90 transition">Back to Home</a>
        </div>
    </div>
</div>
@endsection
