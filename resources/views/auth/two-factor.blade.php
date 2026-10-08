@extends('layouts.auth')
@section('title', 'Sign-in code')
@section('content')

<div class="mb-6 text-center">
    <span style="font-size:1.875rem;font-weight:800;line-height:1;"><span style="color:#7dd3fc;">Busy</span><span style="color:#fb923c;">Realtor</span></span>
    <p class="text-gray-500 dark:text-gray-400 text-sm font-normal mt-1">Enter your sign-in code</p>
</div>

@if(session('error'))
<div class="mb-4 rounded-xl border border-gray-200 bg-red-100 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
@endif
@if(session('success'))
<div class="mb-4 rounded-xl border border-gray-200 bg-green-100 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
@endif

<p class="text-sm text-gray-600 dark:text-gray-400 mb-5">
    We sent a six-digit code to <span class="font-medium">{{ $email }}</span>. It expires in
    {{ \App\Services\TwoFactor::LIFETIME_MINUTES }} minutes and can be used once.
</p>

<form method="POST" action="{{ route('two-factor.verify') }}" class="space-y-4">
    @csrf
    <div>
        <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Code</label>
        {{-- inputmode numeric and one-time-code: phones offer the code from the mail app
             rather than making it a copy-and-paste exercise. --}}
        <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code"
               maxlength="6" required autofocus
               class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-center text-2xl font-mono tracking-[0.4em] focus:outline-none focus:ring-2 focus:ring-blue-200">
        @error('code')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
    </div>

    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-lg transition-colors">
        Sign in
    </button>
</form>

<div class="mt-5 flex items-center justify-between text-xs">
    <form method="POST" action="{{ route('two-factor.resend') }}">
        @csrf
        <button type="submit" class="text-blue-600 hover:text-blue-700 font-medium">Send another code</button>
    </form>
    <a href="{{ route('login') }}" class="text-gray-500 hover:text-gray-700">Start again</a>
</div>
@endsection
