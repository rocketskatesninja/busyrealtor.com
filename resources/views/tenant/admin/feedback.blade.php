@extends('layouts.admin')
@section('title', 'Submit Feedback')
@section('page-subtitle', 'Report a bug, suggest a feature, or share your thoughts')
@section('content')
@php $account = app('tenant')->slug; @endphp
<div class="max-w-2xl mx-auto px-4">

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <form id="feedback-form" method="POST" action="{{ route('tenant.admin.feedback.store', $account) }}" enctype="multipart/form-data" class="space-y-5">
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

            {{-- Multi-photo upload --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Screenshots <span class="text-gray-400 font-normal">(optional — up to 10)</span></label>

                <label for="screenshots" class="flex flex-col items-center justify-center gap-2 border-2 border-dashed border-gray-200 rounded-2xl p-6 text-center hover:border-blue-400 hover:bg-blue-50 transition-colors cursor-pointer">
                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span class="text-gray-500 text-sm">Click to select screenshots <span class="text-gray-400">(PNG, JPG, GIF, WebP — max 8 MB each)</span></span>
                    <input type="file" id="screenshots" name="screenshots[]" multiple accept="image/*" class="sr-only" onchange="fbHandleFiles(this.files)">
                </label>

                {{-- Sortable preview grid --}}
                <div id="fb-preview-header" class="flex items-center justify-between mt-4 mb-2" style="display:none!important">
                    <span class="text-sm font-medium text-gray-700">Selected Screenshots</span>
                    <span class="text-xs text-gray-400 flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                        Drag to reorder
                    </span>
                </div>
                <div class="grid grid-cols-4 md:grid-cols-6 gap-3" id="fb-preview-grid"></div>
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

@section('scripts')
let _fbSortable = null;

function fbHandleFiles(files) {
    Array.from(files).forEach(file => fbAddPreview(file));
    document.getElementById('screenshots').value = '';
}

function fbAddPreview(file) {
    const grid = document.getElementById('fb-preview-grid');
    const reader = new FileReader();
    reader.onload = e => {
        const card = document.createElement('div');
        card.className = 'relative group rounded-xl overflow-hidden aspect-square bg-gray-100 cursor-grab active:cursor-grabbing select-none';
        card.dataset.preview = '1';
        card._file = file;
        card.innerHTML = `
            <img src="${e.target.result}" class="w-full h-full object-cover pointer-events-none">
            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center pointer-events-none">
                <button type="button" onclick="fbRemove(this)" class="bg-red-500 hover:bg-red-600 text-white p-1.5 rounded-lg transition-colors pointer-events-auto" title="Remove">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="absolute bottom-1 right-1 text-white/70 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M8 6a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4zm8-16a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4z"/></svg>
            </div>`;
        grid.appendChild(card);

        const hdr = document.getElementById('fb-preview-header');
        if (hdr) hdr.style.removeProperty('display');

        if (!_fbSortable && typeof Sortable !== 'undefined') {
            _fbSortable = Sortable.create(grid, { animation: 150, ghostClass: 'opacity-30', dragClass: 'shadow-xl' });
        }
    };
    reader.readAsDataURL(file);
}

function fbRemove(btn) {
    btn.closest('[data-preview]').remove();
    const remaining = document.querySelectorAll('#fb-preview-grid [data-preview]').length;
    const hdr = document.getElementById('fb-preview-header');
    if (hdr && !remaining) hdr.style.setProperty('display', 'none', 'important');
}

// Rebuild sorted FileList before submit
document.getElementById('feedback-form').addEventListener('submit', function() {
    const grid = document.getElementById('fb-preview-grid');
    const input = document.getElementById('screenshots');
    if (!grid || !input) return;
    const dt = new DataTransfer();
    grid.querySelectorAll('[data-preview]').forEach(card => { if (card._file) dt.items.add(card._file); });
    input.files = dt.files;
});
@endsection
