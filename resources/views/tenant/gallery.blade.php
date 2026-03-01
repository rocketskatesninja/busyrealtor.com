@extends('layouts.tenant')
@section('title', 'Properties — ' . ($settings->site_title ?? 'BusyRealtor'))

@section('content')
@php
$account = $tenant->slug;
$activeFilters = collect(['search','type','status','price_min','price_max','beds','baths','sqft_min','sqft_max','year_min','year_max','garage_spaces','hoa','hoa_max'])
    ->filter(fn($k) => request($k) !== null && request($k) !== '')->count()
    + count(request('features', []));
@endphp
<div class="relative" x-data="{ mobileOpen: false }">

    {{-- Desktop Floating Filter Panel (hidden on mobile) --}}
    <div class="hidden md:flex absolute top-4 left-4 z-10 bg-white rounded-2xl shadow-xl w-72 max-h-[calc(100vh-120px)] flex-col" x-data="{ open: true }">
        <div class="flex items-center gap-2 p-4 flex-shrink-0">
            <button type="submit" form="gallery-filter" class="btn-primary flex-1 py-2 rounded-xl font-semibold text-sm hover:opacity-90 transition">Apply Filters</button>
            <a href="{{ route('tenant.gallery', $account) }}" class="px-3 py-2 rounded-xl text-sm font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition">Clear</a>
            <button @click="open = !open" class="text-gray-400 hover:text-gray-600 flex-shrink-0">
                <svg class="w-5 h-5 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
        </div>
        <div x-show="open" x-transition class="overflow-y-auto flex-1 px-5 pb-2">
            <form id="gallery-filter" method="GET" action="{{ route('tenant.gallery', $account) }}" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <div class="relative">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Address, city, zip..."
                               class="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                </div>
                @include('tenant.partials.filter-fields')
            </form>
        </div>

    </div>

    {{-- Property Grid --}}
    <div class="md:ml-80 px-4 md:px-6 py-10">
        <div class="flex items-center justify-between mb-6">
            <p class="text-sm text-gray-500">{{ $properties->total() }} {{ Str::plural('property', $properties->total()) }} found</p>
            <form method="GET" action="{{ route('tenant.gallery', $account) }}" class="flex items-center gap-2">
                @foreach(request()->except('sort') as $k => $v)
                    @if(is_array($v))
                        @foreach($v as $item)
                            <input type="hidden" name="{{ $k }}[]" value="{{ $item }}">
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                    @endif
                @endforeach
                <label class="text-sm text-gray-600">Sort:</label>
                <select name="sort" onchange="this.form.submit()" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none">
                    <option value="newest" {{ request('sort','newest') === 'newest' ? 'selected' : '' }}>Newest</option>
                    <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                    <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                    <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Oldest</option>
                </select>
            </form>
        </div>

        @if($properties->count())
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach($properties as $property)
            <a href="{{ route('tenant.property', [$account, $property->id]) }}" class="bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-shadow group">
                <div class="relative h-48 bg-gray-200 overflow-hidden">
                    @if($property->primaryImage)
                        <img src="{{ asset('storage/'.$property->primaryImage->image_path) }}" alt="{{ $property->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    @else
                        <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200">
                            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        </div>
                    @endif
                    <span class="absolute top-3 left-3 text-xs font-semibold text-white px-3 py-1 rounded-full" style="background-color: {{ $property->listing_status === 'active' ? '#10b981' : ($property->listing_status === 'pending' ? '#f59e0b' : '#6b7280') }}">
                        {{ ucfirst($property->listing_status) }}
                    </span>
                </div>
                <div class="p-4">
                    <p class="text-xl font-bold mb-1" style="color: var(--primary)">${{ number_format($property->price) }}</p>
                    <h3 class="font-semibold text-gray-800 mb-1 truncate text-sm">{{ $property->title }}</h3>
                    <p class="text-gray-500 text-xs mb-3 truncate">{{ $property->address }}{{ $property->city ? ', ' . $property->city : '' }}</p>
                    <div class="flex items-center gap-3 text-xs text-gray-500 border-t pt-3">
                        @if($property->bedrooms) <span>{{ $property->bedrooms }} bed</span> @endif
                        @if($property->bathrooms) <span>{{ $property->bathrooms }} bath</span> @endif
                        @if($property->sqft) <span>{{ number_format($property->sqft) }} sqft</span> @endif
                        <span class="ml-auto text-gray-400 capitalize">{{ str_replace('-', ' ', $property->property_type) }}</span>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
        <div class="mt-8">{{ $properties->links() }}</div>
        @else
        <div class="text-center py-24 bg-white rounded-2xl border border-gray-100">
            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">No Properties Found</h3>
            <p class="text-gray-400 mb-6">Try adjusting your filters.</p>
            <a href="{{ route('tenant.gallery', $account) }}" class="btn-primary px-6 py-2.5 rounded-xl font-semibold text-sm hover:opacity-90 transition">Clear All Filters</a>
        </div>
        @endif
    </div>

    {{-- Mobile: floating filter button --}}
    <button @click="mobileOpen = true"
            class="md:hidden fixed bottom-6 left-1/2 -translate-x-1/2 z-40 flex items-center gap-2 px-6 py-3 text-white font-semibold rounded-full shadow-lg transition-transform hover:scale-105"
            style="background-color: var(--primary)">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                <h2 class="text-lg font-bold text-gray-900">Filter Properties</h2>
                <button @click="mobileOpen = false" class="p-2 rounded-lg hover:bg-gray-100 text-gray-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            {{-- Scrollable content --}}
            <div class="flex-1 overflow-y-auto p-4">
                <form id="mobile-gallery-filter" method="GET" action="{{ route('tenant.gallery', $account) }}" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                        <div class="relative">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Address, city, zip..."
                                   class="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                    </div>
                    @include('tenant.partials.filter-fields', ['filterSuffix' => '_mob'])
                </form>
            </div>
            {{-- Fixed footer --}}
            <div class="flex-shrink-0 p-4 border-t bg-gray-50 flex gap-3">
                <a href="{{ route('tenant.gallery', $account) }}"
                   class="flex-1 py-3 text-center border border-gray-300 rounded-xl font-medium text-gray-700 hover:bg-gray-100 transition">Clear All</a>
                <button type="submit" form="mobile-gallery-filter"
                        class="flex-1 py-3 text-center rounded-xl font-semibold text-white transition hover:opacity-90"
                        style="background-color: var(--primary)">Apply Filters</button>
            </div>
        </div>
    </div>


</div>
@endsection
