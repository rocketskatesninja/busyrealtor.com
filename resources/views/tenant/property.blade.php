@extends('layouts.tenant')
@section('hide_header')@endsection
@section('title', $property->title . ' — ' . ($settings->site_title ?? 'BusyRealtor'))

@section('head')
@php
$mapsKeyRecord = \App\Models\Integration::where('tenant_id', $tenant->id)->where('integration_type', 'google_maps')->first();
$mapsKey = $mapsKeyRecord ? $mapsKeyRecord->decryptKey() : null;
@endphp
@if($mapsKey && $property->latitude && $property->longitude)
<script>
function initPropertyMap() {
    var loc = { lat: {{ (float)$property->latitude }}, lng: {{ (float)$property->longitude }} };

    var map = new google.maps.Map(document.getElementById('propertyMap'), {
        center: loc, zoom: 15,
        mapTypeControl: false, streetViewControl: true, fullscreenControl: true
    });
    new google.maps.Marker({ position: loc, map: map, title: {!! json_encode($property->title) !!} });

    var streetViewDiv = document.getElementById('propertyStreetView');
    var panorama = new google.maps.StreetViewPanorama(streetViewDiv, {
        position: loc,
        pov: { heading: 0, pitch: 0 },
        zoom: 1,
        addressControl: false,
        fullscreenControl: true,
        motionTracking: false,
        motionTrackingControl: false
    });
    map.setStreetView(panorama);

    var svc = new google.maps.StreetViewService();
    svc.getPanorama({ location: loc, radius: 50 }, function(data, status) {
        if (status !== 'OK') {
            streetViewDiv.innerHTML = '<div class="h-full flex items-center justify-center bg-gray-100 text-gray-500 text-center p-4"><div><svg class=\'w-12 h-12 mx-auto mb-2 text-gray-400\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M15 12a3 3 0 11-6 0 3 3 0 016 0z\'/><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z\'/></svg><p class=\'font-medium\'>Street View unavailable</p><p class=\'text-sm\'>No imagery for this location</p></div></div>';
        }
    });
}
</script>
<script async defer src="https://maps.googleapis.com/maps/api/js?key={{ $mapsKey }}&callback=initPropertyMap"></script>
@endif
@endsection


@section('content')
@php $account = $tenant->slug; @endphp
<div class="max-w-7xl mx-auto px-4 py-10">
    <div class="mb-6">
        <nav class="flex items-center gap-2 text-sm text-gray-500">
            <a href="{{ route('tenant.home', $account) }}" class="hover-primary transition">Home</a>
            <span>/</span>
            <a href="{{ route('tenant.gallery', $account) }}" class="hover-primary transition">Properties</a>
            <span>/</span>
            <span class="text-gray-800">{{ Str::limit($property->title, 40) }}</span>
        </nav>
    </div>

    <div>
        {{-- Left: Images + Details --}}
        <div>
            {{-- Image Gallery --}}
            @php $images = $property->images->sortByDesc('is_primary'); @endphp
            @if($images->count())
            <div x-data="{ current: 0, lightbox: false, lightboxIdx: 0 }" class="mb-8">
                {{-- Main image --}}
                <div class="relative rounded-2xl overflow-hidden bg-gray-100 mb-3 cursor-pointer" style="height: 450px" @click="lightbox = true; lightboxIdx = current">
                    <template x-for="(img, idx) in {{ json_encode($images->values()->map(fn($i) => asset('storage/'.$i->image_path))) }}" :key="idx">
                        <img :src="img" x-show="current === idx" class="w-full h-full object-cover absolute inset-0">
                    </template>
                    @if($images->count() > 1)
                    <button @click.stop="current = current > 0 ? current - 1 : {{ $images->count() - 1 }}" class="absolute left-3 top-1/2 -translate-y-1/2 bg-black/50 text-white p-2 rounded-full hover:bg-black/70">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <button @click.stop="current = current < {{ $images->count() - 1 }} ? current + 1 : 0" class="absolute right-3 top-1/2 -translate-y-1/2 bg-black/50 text-white p-2 rounded-full hover:bg-black/70">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                    <div class="absolute bottom-3 right-3 bg-black/50 text-white text-xs px-3 py-1 rounded-full" x-text="`${current + 1} / {{ $images->count() }}`"></div>
                    @endif
                </div>
                {{-- Thumbnails --}}
                @if($images->count() > 1)
                <div class="flex gap-2 overflow-x-auto pb-1">
                    @foreach($images as $i => $img)
                    <button @click="current = {{ $i }}" class="flex-shrink-0 w-20 h-16 rounded-lg overflow-hidden border-2 transition" :class="current === {{ $i }} ? 'border-blue-500' : 'border-transparent'">
                        <img src="{{ asset('storage/'.$img->image_path) }}" class="w-full h-full object-cover">
                    </button>
                    @endforeach
                </div>
                @endif
                {{-- Lightbox --}}
                <div x-show="lightbox" x-cloak @keydown.escape.window="lightbox = false" class="fixed inset-0 bg-black/95 z-50 flex items-center justify-center p-4">
                    <button @click="lightbox = false" class="absolute top-4 right-4 text-white/70 hover:text-white">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                    <button @click="lightboxIdx = lightboxIdx > 0 ? lightboxIdx - 1 : {{ $images->count() - 1 }}" class="absolute left-4 top-1/2 -translate-y-1/2 text-white/70 hover:text-white">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <template x-for="(img, idx) in {{ json_encode($images->values()->map(fn($i) => asset('storage/'.$i->image_path))) }}" :key="idx">
                        <img :src="img" x-show="lightboxIdx === idx" class="max-w-full max-h-screen object-contain rounded-xl">
                    </template>
                    <button @click="lightboxIdx = lightboxIdx < {{ $images->count() - 1 }} ? lightboxIdx + 1 : 0" class="absolute right-4 top-1/2 -translate-y-1/2 text-white/70 hover:text-white">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>
            @else
            <div class="h-72 bg-gray-100 rounded-2xl flex items-center justify-center mb-8">
                <svg class="w-20 h-20 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            </div>
            @endif

            {{-- Property Details --}}
            <div class="bg-white rounded-2xl p-6 mb-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4 mb-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 mb-1">{{ $property->title }}</h1>
                        @if($property->address) <p class="text-gray-500">{{ $property->address }}{{ $property->city ? ', ' . $property->city : '' }}{{ $property->state ? ', ' . $property->state : '' }} {{ $property->zip }}</p> @endif
                    </div>
                    <div class="text-right">
                        <p class="text-3xl font-bold" style="color: var(--primary)">${{ number_format($property->price) }}</p>
                        <span class="inline-block text-xs font-semibold text-white px-3 py-1 rounded-full mt-1" style="background-color: {{ $property->listing_status === 'active' ? '#10b981' : ($property->listing_status === 'pending' ? '#f59e0b' : '#6b7280') }}">
                            {{ ucfirst($property->listing_status) }}
                        </span>
                    </div>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 py-4 border-y border-gray-100">
                    @if($property->bedrooms) <div class="text-center"><p class="text-2xl font-bold text-gray-800">{{ $property->bedrooms }}</p><p class="text-xs text-gray-500">Bedrooms</p></div> @endif
                    @if($property->bathrooms) <div class="text-center"><p class="text-2xl font-bold text-gray-800">{{ $property->bathrooms }}</p><p class="text-xs text-gray-500">Bathrooms</p></div> @endif
                    @if($property->sqft) <div class="text-center"><p class="text-2xl font-bold text-gray-800">{{ number_format($property->sqft) }}</p><p class="text-xs text-gray-500">Sq Ft</p></div> @endif
                    @if($property->property_type) <div class="text-center"><p class="text-lg font-bold text-gray-800 capitalize">{{ str_replace('-', ' ', $property->property_type) }}</p><p class="text-xs text-gray-500">Type</p></div> @endif
                </div>
                @if($property->description)
                <div class="mt-4">
                    <h3 class="font-semibold text-gray-800 mb-3">About This Property</h3>
                    <p class="text-gray-600 leading-relaxed whitespace-pre-wrap">{{ $property->description }}</p>
                </div>
                @endif
            </div>

            @if($property->amenities && count($property->amenities))
            <div class="bg-white rounded-2xl p-6 mb-6 shadow-sm">
                <h3 class="font-semibold text-gray-800 mb-4">Amenities & Features</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach((array)$property->amenities as $amenity)
                    <span class="px-3 py-1.5 rounded-full text-sm font-medium" style="background-color: rgba(var(--primary-rgb), 0.1); color: var(--primary)">{{ $amenity }}</span>
                    @endforeach
                </div>
            </div>
            @endif

            @if($property->virtual_tour_url)
            <div class="bg-white rounded-2xl p-6 mb-6 shadow-sm">
                <h3 class="font-semibold text-gray-800 mb-3">Virtual Tour</h3>
                <a href="{{ $property->virtual_tour_url }}" target="_blank" class="btn-primary inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-sm hover:opacity-90 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    View Virtual Tour
                </a>
            </div>
            @endif
        </div>


    {{-- Location: Map & Street View --}}
    @if($property->latitude && $property->longitude)
    <div class="bg-white rounded-2xl p-6 mt-8 shadow-sm border border-gray-100">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Location</h2>
        @if(isset($mapsKey) && $mapsKey)
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <p class="text-sm font-medium text-gray-600 mb-2">Map View</p>
                <div id="propertyMap" class="h-72 rounded-lg overflow-hidden border border-gray-200"></div>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600 mb-2">Street View</p>
                <div id="propertyStreetView" class="h-72 rounded-lg overflow-hidden border border-gray-200"></div>
            </div>
        </div>
        @else
        <div class="h-48 rounded-lg bg-gray-100 flex items-center justify-center">
            <div class="text-center text-gray-500">
                <svg class="w-10 h-10 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <p class="font-medium text-sm">{{ $property->address }}</p>
                @if($property->city)<p class="text-xs">{{ $property->city }}@if($property->state), {{ $property->state }}@endif</p>@endif
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- Related Properties --}}
    @if($related->count())
    <div class="mt-12">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Similar Properties</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($related as $rel)
            <a href="{{ route('tenant.property', [$account, $rel->id]) }}" class="bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-lg transition-shadow group">
                <div class="h-40 bg-gray-100 overflow-hidden">
                    @if($rel->primaryImage)
                        <img src="{{ asset('storage/'.$rel->primaryImage->image_path) }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    @else
                        <div class="w-full h-full flex items-center justify-center bg-gray-100"><svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg></div>
                    @endif
                </div>
                <div class="p-4">
                    <p class="font-bold text-lg mb-1" style="color: var(--primary)">${{ number_format($rel->price) }}</p>
                    <p class="font-medium text-gray-800 text-sm truncate">{{ $rel->title }}</p>
                    <p class="text-gray-500 text-xs truncate">{{ $rel->address }}</p>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
