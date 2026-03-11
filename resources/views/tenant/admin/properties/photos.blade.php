@extends('layouts.admin')
@section('title', 'Bulk Photo Upload')
@section('page-subtitle', 'Upload photos for multiple properties at once')
@section('content')
@php
    $account    = $tenant->slug;
    $totalCount = $properties->count();
    $withPhotos = $properties->filter(fn($p) => $p->images->count() > 0)->count();
    $apiUrl     = route('tenant.admin.api.property-images.store', $account);
    $sorted     = $properties->sortBy(fn($p) => $p->images->count() > 0 ? 1 : 0);
@endphp

<div class="max-w-5xl mx-auto px-4">

    {{-- Header bar --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <a href="{{ route('tenant.admin.properties.index', $account) }}"
           class="inline-flex items-center gap-1.5 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-semibold px-4 py-2 rounded-lg text-sm transition-colors w-fit">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Properties
        </a>
        <div class="bg-white border border-gray-100 rounded-xl px-4 py-2.5 shadow-sm flex items-center gap-3">
            <span id="global-count" class="text-sm text-gray-600 font-medium">
                <span id="with-photos-num">{{ $withPhotos }}</span> of {{ $totalCount }} {{ Str::plural('property', $totalCount) }} have photos
            </span>
            @if($totalCount > 0)
            <div class="w-28 bg-gray-100 rounded-full h-2">
                <div id="progress-bar" class="h-2 rounded-full bg-blue-500 transition-all"
                     style="width: {{ $totalCount > 0 ? round($withPhotos / $totalCount * 100) : 0 }}%"></div>
            </div>
            @endif
        </div>
    </div>

    @if($properties->isEmpty())
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-16 text-center">
        <p class="text-gray-500 text-sm">No properties yet.
            <a href="{{ route('tenant.admin.properties.create', $account) }}" class="text-blue-600 hover:underline">Add a property</a> first.
        </p>
    </div>
    @else

    <div class="space-y-4" id="property-list">
        @foreach($sorted as $property)
        @php
            $imgCount    = $property->images->count();
            $hasPhotos   = $imgCount > 0;
            $statusColor = match($property->listing_status) {
                'active'  => 'bg-green-100 text-green-700',
                'pending' => 'bg-amber-100 text-amber-700',
                'sold'    => 'bg-gray-100 text-gray-600',
                default   => 'bg-slate-100 text-slate-600',
            };
            $typeLabel = ucwords(str_replace(['_','-'], ' ', $property->property_type));
            $inputId   = 'file-' . $property->id;
        @endphp

        <div class="bg-white rounded-2xl shadow-sm border {{ $hasPhotos ? 'border-gray-100' : 'border-amber-200' }} p-5 property-card"
             data-property-id="{{ $property->id }}"
             data-total="{{ $totalCount }}">

            {{-- File input lives here, OUTSIDE the label/drop-zone --}}
            <input type="file" id="{{ $inputId }}" class="file-input sr-only"
                   multiple accept="image/jpeg,image/jpg,image/png,image/webp,image/gif"
                   data-property-id="{{ $property->id }}">

            {{-- Card header --}}
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 mb-4">
                <div class="flex items-start gap-3 min-w-0">
                    <div class="w-16 h-12 rounded-lg bg-gray-100 overflow-hidden flex-shrink-0 border border-gray-200 primary-thumb">
                        @if($property->primaryImage)
                        <img src="{{ asset('storage/' . $property->primaryImage->image_path) }}"
                             class="w-full h-full object-cover" alt="">
                        @else
                        <div class="w-full h-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <h3 class="font-semibold text-gray-800 text-sm leading-snug">{{ $property->title }}</h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ $property->address_street }}{{ $property->address_city ? ', ' . $property->address_city : '' }}
                        </p>
                        <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $statusColor }}">{{ ucfirst($property->listing_status) }}</span>
                            <span class="text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">{{ $typeLabel }}</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3 flex-shrink-0">
                    <span class="photo-count text-xs font-semibold px-2.5 py-1 rounded-full {{ $hasPhotos ? 'bg-blue-50 text-blue-700' : 'bg-amber-50 text-amber-700' }}"
                          data-count="{{ $imgCount }}">
                        {{ $imgCount === 0 ? 'No photos' : $imgCount . ' ' . Str::plural('photo', $imgCount) }}
                    </span>
                    <a href="{{ route('tenant.admin.properties.edit', [$account, $property->id]) }}"
                       class="text-xs text-gray-400 hover:text-blue-600 transition-colors whitespace-nowrap">Edit →</a>
                </div>
            </div>

            {{-- Drop zone — a <label> that natively opens the file picker for its <input> --}}
            <label for="{{ $inputId }}"
                   class="drop-zone flex flex-col items-center justify-center gap-2 border-2 border-dashed border-gray-200 rounded-xl p-6 text-center cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-colors block"
                   data-property-id="{{ $property->id }}">
                <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                <span class="text-sm text-gray-500">Drop photos here or <span class="text-blue-600 font-medium">click to select</span></span>
                <span class="text-xs text-gray-400">JPEG, PNG, WebP — up to 10 MB each</span>
            </label>

            {{-- Per-file upload status --}}
            <ul class="upload-status mt-3 space-y-1 text-xs hidden"></ul>

            {{-- Thumbnail strip --}}
            <div class="thumb-strip mt-3 flex flex-wrap gap-2 {{ $imgCount === 0 ? 'hidden' : '' }}">
                @foreach($property->images->sortBy('sort_order') as $img)
                <div class="w-16 h-12 rounded-lg overflow-hidden border border-gray-200 flex-shrink-0">
                    <img src="{{ asset('storage/' . $img->image_path) }}" class="w-full h-full object-cover" alt="">
                </div>
                @endforeach
            </div>

        </div>
        @endforeach
    </div>
    @endif

</div>

<script>
(function () {
    const API_URL = {{ Js::from($apiUrl) }};
    const CSRF    = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    let globalWithPhotos = {{ $withPhotos }};
    const globalTotal    = {{ $totalCount }};

    function updateGlobalProgress() {
        const num = document.getElementById('with-photos-num');
        const bar = document.getElementById('progress-bar');
        if (num) num.textContent = globalWithPhotos;
        if (bar) bar.style.width = globalTotal > 0 ? Math.round(globalWithPhotos / globalTotal * 100) + '%' : '0%';
    }

    function updateCount(card, delta) {
        const badge = card.querySelector('.photo-count');
        let count = parseInt(badge.dataset.count ?? '0') + delta;
        if (count < 0) count = 0;
        const wasZero = parseInt(badge.dataset.count) === 0;
        badge.dataset.count = count;
        badge.textContent = count === 0 ? 'No photos' : count + ' ' + (count === 1 ? 'photo' : 'photos');
        if (count > 0) {
            badge.classList.remove('bg-amber-50','text-amber-700');
            badge.classList.add('bg-blue-50','text-blue-700');
            card.classList.remove('border-amber-200');
            card.classList.add('border-gray-100');
            if (wasZero) { globalWithPhotos++; updateGlobalProgress(); }
        }
    }

    function addStatusItem(list, filename) {
        list.classList.remove('hidden');
        const li = document.createElement('li');
        li.className = 'flex items-center gap-2 text-gray-500 min-w-0';
        li.innerHTML =
            '<span class="status-icon shrink-0">' +
            '<svg class="animate-spin w-3.5 h-3.5 text-blue-500" fill="none" viewBox="0 0 24 24">' +
            '<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>' +
            '<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg></span>' +
            '<span class="filename truncate">' + esc(filename) + '</span>';
        list.appendChild(li);
        return li;
    }

    function setOk(li) {
        li.querySelector('.status-icon').innerHTML =
            '<svg class="w-3.5 h-3.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>';
        li.classList.replace('text-gray-500','text-green-700');
    }

    function setErr(li, msg) {
        li.querySelector('.status-icon').innerHTML =
            '<svg class="w-3.5 h-3.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>';
        li.classList.replace('text-gray-500','text-red-600');
        li.querySelector('.filename').textContent += ' — ' + msg;
    }

    function addThumb(strip, url) {
        strip.classList.remove('hidden');
        const wrap = document.createElement('div');
        wrap.className = 'w-16 h-12 rounded-lg overflow-hidden border border-gray-200 flex-shrink-0';
        const img = document.createElement('img');
        img.src = url; img.className = 'w-full h-full object-cover'; img.alt = '';
        wrap.appendChild(img);
        strip.appendChild(wrap);
    }

    function esc(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    async function uploadFile(file, propertyId, card) {
        const list  = card.querySelector('.upload-status');
        const strip = card.querySelector('.thumb-strip');
        const li    = addStatusItem(list, file.name);
        const fd    = new FormData();
        fd.append('image',       file);
        fd.append('property_id', propertyId);
        fd.append('_token',      CSRF);
        try {
            const res  = await fetch(API_URL, { method: 'POST', body: fd });
            const data = await res.json();
            if (!res.ok) {
                setErr(li, data?.message ?? data?.errors?.image?.[0] ?? ('HTTP ' + res.status));
            } else {
                setOk(li);
                addThumb(strip, data.url);
                updateCount(card, 1);
            }
        } catch (e) {
            setErr(li, e.message ?? 'Network error');
        }
    }

    function handleFiles(files, propertyId) {
        if (!files?.length) return;
        const card = document.querySelector('.property-card[data-property-id="' + propertyId + '"]');
        if (!card) return;
        Array.from(files).forEach(f => uploadFile(f, propertyId, card));
    }

    // Wire file inputs
    document.querySelectorAll('.file-input').forEach(input => {
        const propId = input.dataset.propertyId;
        input.addEventListener('change', () => {
            handleFiles(input.files, propId);
            input.value = ''; // reset so same file can be chosen again after an error
        });
    });

    // Wire drag-and-drop on each label/drop-zone
    document.querySelectorAll('.drop-zone').forEach(zone => {
        const propId = zone.dataset.propertyId;
        zone.addEventListener('dragover', e => {
            e.preventDefault();
            zone.classList.add('border-blue-500','bg-blue-50');
        });
        zone.addEventListener('dragleave', e => {
            if (!zone.contains(e.relatedTarget)) {
                zone.classList.remove('border-blue-500','bg-blue-50');
            }
        });
        zone.addEventListener('drop', e => {
            e.preventDefault();
            zone.classList.remove('border-blue-500','bg-blue-50');
            handleFiles(e.dataTransfer.files, propId);
        });
    });
})();
</script>
@endsection
