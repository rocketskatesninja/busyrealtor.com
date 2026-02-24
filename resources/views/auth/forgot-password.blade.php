@extends('layouts.auth')
@section('title', 'Forgot Password')
@section('content')
<h2 class="text-2xl font-bold text-gray-800 mb-2 text-center">Reset Password</h2>
<p class="text-gray-500 text-sm text-center mb-6">Enter your email and we'll send you a reset link.</p>
<form method="POST" action="{{ route('password.email') }}" class="space-y-4">
    @csrf
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
        <input type="email" name="email" value="{{ old('email') }}" required autofocus
               class="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-lg transition text-sm">
        Send Reset Link
    </button>
</form>
<div class="mt-4 text-center">
    <a href="{{ route('login') }}" class="text-sm text-blue-600 hover:text-blue-700">&larr; Back to Login</a>
</div>
@endsection
