@extends('layouts.auth')
@section('title', 'Login')
@section('content')
<h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Welcome Back</h2>
<form method="POST" action="{{ route('login.submit') }}" class="space-y-4">
    @csrf
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
        <input type="email" name="email" value="{{ old('email') }}" required autofocus
               class="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
        <input type="password" name="password" required
               class="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
    </div>
    <div class="flex items-center justify-between">
        <label class="flex items-center text-sm text-gray-600">
            <input type="checkbox" name="remember" class="mr-2 rounded">
            Remember me
        </label>
        <a href="{{ route('password.request') }}" class="text-sm text-blue-600 hover:text-blue-700">Forgot password?</a>
    </div>
    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-lg transition text-sm">
        Sign In
    </button>
</form>
<div class="mt-6 text-center text-sm text-gray-600">
    Don't have an account? <a href="{{ route('register') }}" class="text-blue-600 hover:text-blue-700 font-medium">Sign up free</a>
</div>
@endsection
