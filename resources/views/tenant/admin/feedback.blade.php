@extends('layouts.admin')
@section('title', 'Submit Feedback')
@section('page-subtitle', 'Report a bug, suggest a feature, or share your thoughts')
@section('foot')
@vite('resources/js/feedback.js')
@endsection

@section('content')
@php $account = app('tenant')->slug; @endphp
<div class="max-w-2xl mx-auto px-4">

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <form id="feedback-form" method="POST" action="{{ route('tenant.admin.feedback.store', $account) }}" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <div>
                <label for="f-subject" class="block text-sm font-medium text-gray-700 mb-1">Subject <span class="text-red-500">*</span></label>
                <input type="text" id="f-subject" name="subject" value="{{ old('subject') }}" required maxlength="200"
                       placeholder="e.g. Map not loading, Feature request: bulk delete…"
                       class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)] @error('subject') border-red-400 @enderror">
                @error('subject') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Message <span class="text-red-500">*</span></label>
                <textarea name="message" required rows="6" maxlength="5000"
                          placeholder="Describe the issue or feedback in detail. Include steps to reproduce if it's a bug."
                          class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)] resize-none @error('message') border-red-400 @enderror">{{ old('message') }}</textarea>
                @error('message') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Multi-photo upload --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Screenshots <span class="text-gray-400 font-normal">(optional — up to 10)</span></label>

                <label for="screenshots" class="flex flex-col items-center justify-center gap-2 border-2 border-dashed border-gray-200 rounded-2xl p-6 text-center hover:border-blue-400 hover:bg-blue-50 transition-colors cursor-pointer">
                    <svg width="32" height="32" class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span class="text-gray-500 text-sm">Click to select screenshots <span class="text-gray-400">(PNG, JPG, GIF, WebP — max 8 MB each)</span></span>
                    <input type="file" id="screenshots" name="screenshots[]" multiple accept="image/*" class="sr-only">
                </label>

                {{-- Sortable preview grid --}}
                <div id="fb-preview-header" class="flex items-center justify-between mt-4 mb-2" style="display:none!important">
                    <span class="text-sm font-medium text-gray-700">Selected Screenshots</span>
                    <span class="text-xs text-gray-400 flex items-center gap-1">
                        <svg width="12" height="12" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                        Drag to reorder
                    </span>
                </div>
                <div class="grid grid-cols-4 md:grid-cols-6 gap-3" id="fb-preview-grid"></div>
            </div>

            <div class="pt-2 flex justify-end">
                <button type="submit" class="btn-primary px-8 py-2.5 rounded-xl font-semibold text-sm hover:opacity-90 transition flex items-center gap-2">
                    <svg width="16" height="16" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    Send Feedback
                </button>
            </div>
        </form>
    </div>

</div>
@endsection

