@extends('layouts.auth')
@section('title', 'Complete Your Registration')
@section('content')
<div class="flex items-center gap-3 mb-6">
    <div class="w-8 h-8 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center">
        <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
    </div>
    <div>
        <h2 class="text-xl font-bold text-gray-800">One more step!</h2>
        <p class="text-sm text-gray-500">{{ session('oauth_email') ? 'Signed in as ' . session('oauth_email') : 'Complete your account setup' }}</p>
    </div>
</div>

@if($errors->any())
    <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
        @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
    </div>
@endif

<form method="POST" action="{{ route('register.complete.submit') }}" class="space-y-4" x-data="{ slug: '', agreed: false }">
    @csrf
    @if(!session('oauth_email'))
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
        <input type="email" name="email" value="{{ old('email') }}" required
               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    @endif
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Business Name</label>
        <input type="text" name="business_name" value="{{ old('business_name') }}" required
               x-on:input="slug = $event.target.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '')"
               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Your URL Slug</label>
        <div class="flex rounded-lg border border-gray-300 overflow-hidden focus-within:ring-2 focus-within:ring-blue-500">
            <span class="bg-gray-50 border-r border-gray-300 px-3 py-2.5 text-sm text-gray-500">busyrealtor.com/</span>
            <input type="text" name="slug" x-model="slug" required pattern="[a-z0-9\-]+" value="{{ old('slug') }}"
                   class="flex-1 px-3 py-2.5 text-sm focus:outline-none">
        </div>
        <p class="text-xs text-gray-500 mt-1">Only lowercase letters, numbers, and hyphens</p>
    </div>
    <div class="flex items-start">
        <input type="checkbox" name="terms" id="terms" required x-model="agreed" class="mt-1 mr-2 rounded">
        <label for="terms" class="text-sm text-gray-600">I agree to the <a href="/terms" class="text-blue-600 hover:underline">Terms of Service</a> and <a href="/privacy-policy" class="text-blue-600 hover:underline">Privacy Policy</a></label>
    </div>
    <button type="submit" :disabled="!agreed" :class="agreed ? 'bg-blue-600 hover:bg-blue-700 cursor-pointer' : 'bg-blue-300 cursor-not-allowed'" class="w-full text-white font-semibold py-3 rounded-lg transition text-sm">
        Start Free 14-Day Trial
    </button>
</form>
@endsection
