@extends('layouts.auth')
@section('title', 'Set New Password')
@section('content')
<h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Set New Password</h2>
<form method="POST" action="{{ route('password.update') }}" class="space-y-4">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <input type="hidden" name="email" value="{{ $email }}">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
        <input type="password" name="password" required minlength="8"
               class="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
        <input type="password" name="password_confirmation" required
               class="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-lg transition text-sm">
        Reset Password
    </button>
</form>
@endsection
