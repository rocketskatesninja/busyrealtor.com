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
        <x-password-input name="password" required minlength="10" autocomplete="new-password" />
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
        <x-password-input name="password_confirmation" required autocomplete="new-password" />
    </div>
    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-lg transition text-sm">
        Reset Password
    </button>
</form>
@endsection
