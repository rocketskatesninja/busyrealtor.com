@extends('layouts.tenant')
@section('title', 'Map — ' . ($settings->site_title ?? 'BusyRealtor'))

@section('head')
{{-- Moved inside @section('head'): sitting above @extends, this was echoed
     before the layout's doctype, which put the whole page in quirks mode. --}}
<style>
/* Soften Google Maps default UI controls in dark mode (avoids glaring white) */
.dark .gm-style .gm-bundled-control,
.dark .gm-style .gm-bundled-control-on-bottom,
.dark .gm-style .gm-fullscreen-control,
.dark .gm-style .gm-svpc,
.dark .gm-style .gm-svpc > *,
.dark .gm-style .gm-style-mtc > button,
.dark .gm-style .gm-style-mtc > div,
.dark .gm-style div[draggable="true"][title*="Street"],
.dark .gm-style div[title*="Street View"] {
    filter: invert(0.82) hue-rotate(180deg);
}
</style>
@if($mapsKey ?? null)
<style>
/* Google Maps InfoWindow — remove default scrollbars and padding */
.gm-style-iw-d { overflow: hidden !important; }
.gm-style-iw-c { padding: 0 !important; }
/* Dark mode overrides */
.dark .gm-style-iw-c {
    background-color: #1e293b !important;
    box-shadow: 0 4px 24px rgba(0,0,0,0.6) !important;
}
.dark .gm-style-iw-t::after { background: #1e293b !important; }
.dark .gm-ui-hover-effect > span { background-color: #94a3b8 !important; }
</style>
@endif
@endsection

@section('content')
@php
$activeFilters = collect(['type','status','price_min','price_max','beds','baths','sqft_min','sqft_max','year_min','year_max','garage_spaces','hoa','hoa_max'])
    ->filter(fn($k) => request($k) !== null && request($k) !== '')->count()
    + count(request('features', []));
@endphp
@php
$mapConfig = [
    'key' => $mapsKey,
    'properties' => $properties->map(fn ($p) => [
        'id' => $p->id,
        'lat' => (float) $p->latitude,
        'lng' => (float) $p->longitude,
        'title' => $p->title,
        'price' => (int) $p->price,
        'price_disp' => '$'.number_format($p->price),
        'address' => $p->address.($p->address_line_2 ? ' '.$p->address_line_2 : ''),
        'image' => $p->primaryImage ? asset('storage/'.$p->primaryImage->thumb_path) : null,
        'url' => route('tenant.property', [$account, $p->id]),
        'type' => $p->property_type,
        'status' => $p->listing_status,
        'beds' => (int) $p->bedrooms,
        'baths' => (float) $p->bathrooms,
        'sqft' => (int) $p->sqft,
        'year_built' => (int) $p->year_built,
        'garage' => (int) $p->garage,
        'hoa_fee' => (float) $p->hoa_fee,
        'amenities' => (array) $p->amenities,
    ])->values(),
];
@endphp
<div id="map-root"
     data-config='@json($mapConfig, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG)'
     class="relative" style="height: calc(100vh - 80px)" x-data="{ mobileOpen: false }">
    {{-- Desktop Map Filter Panel (hidden on mobile) --}}
    <div id="map-filter-panel" class="hidden md:flex absolute top-16 left-4 z-10 bg-white rounded-2xl shadow-xl w-72 max-h-[calc(100vh-120px)] flex-col">
        <div id="map-filter-handle" class="flex items-center gap-2 p-4 flex-shrink-0 cursor-grab select-none">
            <svg width="16" height="16" class="w-4 h-4 text-gray-300 flex-shrink-0" viewBox="0 0 16 16" fill="currentColor"><circle cx="4" cy="3" r="1.5"/><circle cx="12" cy="3" r="1.5"/><circle cx="4" cy="8" r="1.5"/><circle cx="12" cy="8" r="1.5"/><circle cx="4" cy="13" r="1.5"/><circle cx="12" cy="13" r="1.5"/></svg>
            <button type="button" data-map-apply class="btn-primary flex-1 py-2 rounded-xl font-semibold text-sm hover:opacity-90 transition">Apply Filters</button>
            <button type="button" data-map-clear class="px-3 py-2 rounded-xl text-sm font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition">Clear</button>
        </div>
        <div class="overflow-y-auto flex-1 px-5 pb-2 scrollbar-hide">
            <form id="map-filter" class="space-y-4">
                @include('tenant.partials.filter-fields', ['filterSuffix' => '_map'])
            </form>
        </div>
        <div class="px-5 pb-5 pt-3 flex-shrink-0 border-t">
            <p class="text-xs text-gray-500 font-medium">Properties on map: <span id="prop-count">{{ $properties->count() }}</span></p>
        </div>
    </div>

    {{-- Map --}}
    @if($mapsKey)
    <div id="main-map" class="w-full h-full"></div>
    @else
    <div class="w-full h-full bg-gray-200 flex flex-col items-center justify-center">
        <svg width="80" height="80" class="w-20 h-20 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
        <h3 class="text-xl font-semibold text-gray-600 mb-2">Map Not Configured</h3>
        <p class="text-gray-500 text-sm mb-4">The map is not currently available. Please contact your administrator.</p>
        <a href="{{ route('tenant.gallery', $account) }}" class="btn-primary px-6 py-2.5 rounded-xl font-semibold text-sm hover:opacity-90 transition">View as List Instead</a>
    </div>
    @endif
    {{-- Mobile: floating filter button --}}
    <button @click="mobileOpen = true"
            class="md:hidden fixed bottom-6 left-1/2 -translate-x-1/2 z-40 flex items-center gap-2 px-6 py-3 text-white font-semibold rounded-full shadow-lg transition-transform hover:scale-105"
            style="background-color: var(--primary)">
        <svg width="20" height="20" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
        </svg>
        Filters
        @if($activeFilters > 0)
        <span class="absolute -top-1.5 -right-1.5 w-5 h-5 flex items-center justify-center text-xs font-bold bg-red-500 text-white rounded-full">{{ $activeFilters }}</span>
        @endif
    </button>

    {{-- Mobile: slide-up filter drawer --}}
    <div x-show="mobileOpen" x-cloak class="md:hidden fixed inset-0 z-50" @keydown.escape.window="mobileOpen = false">
        {{-- Backdrop --}}
        <div x-show="mobileOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="mobileOpen = false"
             class="absolute inset-0 bg-black/50"></div>
        {{-- Drawer --}}
        <div x-show="mobileOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-y-full"
             x-transition:enter-end="translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-y-0"
             x-transition:leave-end="translate-y-full"
             class="absolute bottom-0 left-0 right-0 bg-white rounded-t-2xl shadow-2xl max-h-[85vh] overflow-hidden flex flex-col">
            {{-- Handle --}}
            <div class="flex-shrink-0 pt-3 pb-1 flex justify-center">
                <div class="w-12 h-1.5 bg-gray-300 rounded-full"></div>
            </div>
            {{-- Header --}}
            <div class="flex-shrink-0 px-4 py-3 flex items-center justify-between border-b">
                <h2 class="text-lg font-bold text-gray-900">Filter Map</h2>
                <button @click="mobileOpen = false" class="p-2 rounded-lg hover:bg-gray-100 text-gray-500">
                    <x-icon name="x-mark" class="w-5 h-5" />
                </button>
            </div>
            {{-- Scrollable content --}}
            <div class="flex-1 overflow-y-auto p-4">
                <form id="mobile-map-filter" class="space-y-4">
                    @include('tenant.partials.filter-fields', ['filterSuffix' => '_mapmob'])
                </form>
            </div>
            {{-- Fixed footer --}}
            <div class="flex-shrink-0 p-4 border-t bg-gray-50 flex gap-3">
                <button type="button"
                        data-map-clear-mobile
                        class="flex-1 py-3 text-center border border-gray-300 rounded-xl font-medium text-gray-700 hover:bg-gray-100 transition">Clear All</button>
                <button type="button"
                        data-map-apply-mobile
                        class="flex-1 py-3 text-center rounded-xl font-semibold text-white transition hover:opacity-90"
                        style="background-color: var(--primary)">Apply Filters</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('foot')
@vite('resources/js/map.js')
@endsection
