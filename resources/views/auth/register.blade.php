@extends('layouts.auth')
@section('title', 'Create Account')
@section('content')
<h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Create Your Account</h2>
<form method="POST" action="{{ route('register.submit') }}" class="space-y-4" x-data="{ slug: '' }">
    @csrf
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Your Name</label>
            <input type="text" name="name" value="{{ old('name') }}" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Business Name</label>
            <input type="text" name="business_name" value="{{ old('business_name') }}" required
                   x-on:input="slug = $event.target.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '')"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
        <input type="email" name="email" value="{{ old('email') }}" required
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
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
            <input type="password" name="password" required minlength="8"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Confirm</label>
            <input type="password" name="password_confirmation" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
    </div>
    <div class="flex items-start">
        <input type="checkbox" name="terms" id="terms" required class="mt-1 mr-2 rounded">
        <label for="terms" class="text-sm text-gray-600">I agree to the <a href="/terms" class="text-blue-600 hover:underline">Terms of Service</a> and <a href="/privacy-policy" class="text-blue-600 hover:underline">Privacy Policy</a></label>
    </div>
    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-lg transition text-sm">
        Start Free 14-Day Trial
    </button>
</form>
<div class="mt-4 text-center text-sm text-gray-600">
    Already have an account? <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-700 font-medium">Sign in</a>
</div>
@endsection
