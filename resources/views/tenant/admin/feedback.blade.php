@extends('layouts.admin')
@section('title', 'Submit Feedback')
@section('content')
@php $account = app('tenant')->slug; @endphp
<div class="max-w-2xl mx-auto px-4">

    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('tenant.admin.dashboard', $account) }}" class="text-gray-400 hover:text-gray-600 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Submit Feedback</h1>
            <p class="text-gray-500 text-sm mt-0.5">Report a bug, request a feature, or share any feedback with the BusyRealtor team.</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <form method="POST" action="{{ route('tenant.admin.feedback.store', $account) }}" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Subject <span class="text-red-500">*</span></label>
                <input type="text" name="subject" value="{{ old('subject') }}" required maxlength="200"
                       placeholder="e.g. Map not loading, Feature request: bulk delete…"
                       class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('subject') border-red-400 @enderror">
                @error('subject') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Message <span class="text-red-500">*</span></label>
                <textarea name="message" required rows="6" maxlength="5000"
                          placeholder="Describe the issue or feedback in detail. Include steps to reproduce if it's a bug."
                          class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none @error('message') border-red-400 @enderror">{{ old('message') }}</textarea>
                @error('message') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div x-data="{ fileName: null }">
                <label class="block text-sm font-medium text-gray-700 mb-1">Screenshot <span class="text-gray-400 font-normal">(optional)</span></label>
                <label class="flex items-center gap-3 border-2 border-dashed border-gray-200 rounded-xl px-4 py-5 cursor-pointer hover:border-blue-400 hover:bg-blue-50/30 transition-colors">
                    <svg class="w-8 h-8 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <div>
                        <p class="text-sm font-medium text-gray-700" x-text="fileName ?? 'Click to upload a screenshot'"></p>
                        <p class="text-xs text-gray-400 mt-0.5">PNG, JPG, GIF, WebP — max 8 MB</p>
                    </div>
                    <input type="file" name="screenshot" accept="image/*" class="sr-only"
                           @change="fileName = $event.target.files[0]?.name ?? null">
                </label>
                @error('screenshot') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="pt-2 flex justify-end">
                <button type="submit" class="btn-primary px-8 py-2.5 rounded-xl font-semibold text-sm hover:opacity-90 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    Send Feedback
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
