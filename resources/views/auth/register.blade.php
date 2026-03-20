@extends('layouts.auth')
@section('title', 'Create Account')
@section('content')
<h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">BusyRealtor Registration</h2>

<div class="flex flex-col gap-2">
<a href="{{ route('auth.google') }}" class="google-btn w-full flex items-center justify-center gap-3 border border-gray-300 rounded-lg px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
    <svg class="w-5 h-5" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
    Continue with Google
</a>
</div>

<div class="relative my-2">
    <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-200"></div></div>
    <div class="relative flex justify-center text-xs text-gray-400"><span class="bg-white dark:bg-slate-800 px-3">or sign up with email</span></div>
</div>

<form method="POST" action="{{ route('register.submit') }}" class="space-y-4" x-data="{ slug: '{{ old('slug') }}', agreed: false }">
    @csrf
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
            <input type="text" name="first_name" value="{{ old('first_name') }}" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
            <input type="text" name="last_name" value="{{ old('last_name') }}" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Business Name</label>
        <input type="text" name="business_name" value="{{ old('business_name') }}" required
               x-on:input="slug = $event.target.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '')"
               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
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
            <input type="text" name="slug" x-model="slug" required pattern="[a-z0-9\-]+"
                   class="flex-1 px-3 py-2.5 text-sm focus:outline-none">
        </div>
        <p class="text-xs text-gray-500 mt-1">Only lowercase letters, numbers, and hyphens</p>
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
            <div class="relative">
                <input id="reg-password" type="password" name="password" required minlength="8"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <button type="button" onclick="togglePw('reg-password')"
                        class="absolute inset-y-0 right-0 flex items-center px-2.5 text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5 eye-on" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <svg class="w-5 h-5 eye-off hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 012.071-3.454m3.084-2.757A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.97 9.97 0 01-1.938 3.259M3 3l18 18"/>
                    </svg>
                </button>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Confirm</label>
            <div class="relative">
                <input id="reg-password-confirm" type="password" name="password_confirmation" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <button type="button" onclick="togglePw('reg-password-confirm')"
                        class="absolute inset-y-0 right-0 flex items-center px-2.5 text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5 eye-on" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <svg class="w-5 h-5 eye-off hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 012.071-3.454m3.084-2.757A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.97 9.97 0 01-1.938 3.259M3 3l18 18"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    <script>
    function togglePw(id) {
        const input = document.getElementById(id);
        const btn = input.nextElementSibling;
        const showing = input.type === 'text';
        input.type = showing ? 'password' : 'text';
        btn.querySelector('.eye-on').classList.toggle('hidden', !showing);
        btn.querySelector('.eye-off').classList.toggle('hidden', showing);
    }
    </script>
    <div class="flex items-start">
        <input type="checkbox" name="terms" id="terms" required x-model="agreed" class="mt-1 mr-2 rounded">
        <label for="terms" class="text-sm text-gray-600">I agree to the <a href="/terms" class="text-blue-600 hover:underline">Terms of Service</a> and <a href="/privacy-policy" class="text-blue-600 hover:underline">Privacy Policy</a></label>
    </div>
    <button type="submit" :disabled="!agreed" :class="agreed ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-300 dark:bg-gray-600 cursor-not-allowed'" class="w-full text-white font-semibold py-3 rounded-lg transition text-sm">
        Start Free 14-Day Trial
    </button>
</form>
<div class="mt-4 text-center text-sm text-gray-600">
    Already have an account? <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-700 font-medium">Sign in</a>
</div>
@endsection
