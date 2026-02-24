@extends('layouts.tenant')
@section('title', 'Properties — ' . ($settings->site_title ?? 'BusyRealtor'))

@section('content')
@php $account = $tenant->slug; @endphp
<div class="relative">

    {{-- Floating Filter Panel --}}
    <div class="absolute top-4 left-4 z-10 bg-white rounded-2xl shadow-xl w-72 max-h-[calc(100vh-120px)] flex flex-col" x-data="{ open: true }">
        <div class="flex items-center justify-between p-5 pb-3 flex-shrink-0">
            <h3 class="font-bold text-gray-800">Filter Properties</h3>
            <button @click="open = !open" class="text-gray-400 hover:text-gray-600">
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
        <div x-show="open" class="p-5 pt-3 flex-shrink-0 border-t space-y-2">
            <button type="submit" form="gallery-filter" class="btn-primary w-full py-2.5 rounded-xl font-semibold text-sm hover:opacity-90 transition">Apply Filters</button>
            @if(request()->hasAny(['search','type','status','price_min','price_max','beds','baths','sqft_min','sqft_max','year_min','year_max','garage_spaces','hoa','hoa_max','features']))
            <a href="{{ route('tenant.gallery', $account) }}" class="block text-center text-sm text-gray-500 hover:text-gray-700 transition">Clear Filters</a>
            @endif
            <p class="text-xs text-gray-500 pt-1">{{ $properties->total() }} {{ Str::plural('property', $properties->total()) }} found</p>
        </div>
    </div>

    {{-- Property Grid --}}
    <div class="ml-80 px-6 py-10">
        <div class="flex items-center justify-end mb-6">
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

</div>
@endsection
