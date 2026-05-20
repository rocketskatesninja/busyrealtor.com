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
@extends('layouts.tenant')
@section('hide_header')@endsection
@section('title', $property->title . ' — ' . ($settings->site_title ?? 'BusyRealtor'))
@section('meta_description', Str::limit(strip_tags($property->description ?? $settings->site_description ?? ''), 155))
@section('og_image', $property->images->first() ? asset('storage/' . $property->images->first()->image_path) : '')

@section('head')
@if(($mapsKey ?? null) && $property->latitude && $property->longitude)
<script>
var DARK_MAP_STYLES = [
    { elementType: 'geometry', stylers: [{ color: '#242f3e' }] },
    { elementType: 'labels.text.stroke', stylers: [{ color: '#242f3e' }] },
    { elementType: 'labels.text.fill', stylers: [{ color: '#746855' }] },
    { featureType: 'administrative.locality', elementType: 'labels.text.fill', stylers: [{ color: '#d59563' }] },
    { featureType: 'poi', elementType: 'labels.text.fill', stylers: [{ color: '#d59563' }] },
    { featureType: 'poi.park', elementType: 'geometry', stylers: [{ color: '#263c3f' }] },
    { featureType: 'poi.park', elementType: 'labels.text.fill', stylers: [{ color: '#6b9a76' }] },
    { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#38414e' }] },
    { featureType: 'road', elementType: 'geometry.stroke', stylers: [{ color: '#212a37' }] },
    { featureType: 'road', elementType: 'labels.text.fill', stylers: [{ color: '#9ca5b3' }] },
    { featureType: 'road.highway', elementType: 'geometry', stylers: [{ color: '#746855' }] },
    { featureType: 'road.highway', elementType: 'geometry.stroke', stylers: [{ color: '#1f2835' }] },
    { featureType: 'road.highway', elementType: 'labels.text.fill', stylers: [{ color: '#f3d19c' }] },
    { featureType: 'transit', elementType: 'geometry', stylers: [{ color: '#2f3948' }] },
    { featureType: 'transit.station', elementType: 'labels.text.fill', stylers: [{ color: '#d59563' }] },
    { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#17263c' }] },
    { featureType: 'water', elementType: 'labels.text.fill', stylers: [{ color: '#515c6d' }] },
    { featureType: 'water', elementType: 'labels.text.stroke', stylers: [{ color: '#17263c' }] }
];

function getMapStyles() {
    return document.documentElement.classList.contains('dark') ? DARK_MAP_STYLES : [];
}

function getBrandMarkerIcon() {
    var primary = getComputedStyle(document.documentElement).getPropertyValue('--primary').trim() || '#3b82f6';
    return {
        path: 'M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 110-5 2.5 2.5 0 010 5z',
        fillColor: primary,
        fillOpacity: 1,
        strokeColor: '#ffffff',
        strokeWeight: 2,
        scale: 1.8,
        anchor: new google.maps.Point(12, 22)
    };
}

function initPropertyMap() {
    var loc = { lat: {{ (float)$property->latitude }}, lng: {{ (float)$property->longitude }} };

    var map = new google.maps.Map(document.getElementById('propertyMap'), {
        center: loc, zoom: 15,
        mapTypeControl: false, streetViewControl: true, fullscreenControl: true,
        styles: getMapStyles()
    });
    var marker = new google.maps.Marker({ position: loc, map: map, title: {!! json_encode($property->title) !!}, icon: getBrandMarkerIcon() });

    // React to dark-mode toggle without reloading
    new MutationObserver(function() {
        map.setOptions({ styles: getMapStyles() });
        marker.setIcon(getBrandMarkerIcon());
    }).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

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
<script async defer src="https://maps.googleapis.com/maps/api/js?key={{ $mapsKey }}&callback=initPropertyMap&loading=async&libraries=marker"></script>
@endif
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "RealEstateListing",
  "name": {!! json_encode($property->title ?? $property->address_street) !!},
  "url": "{{ url()->current() }}",
  "description": {!! json_encode(Str::limit(strip_tags($property->description ?? ''), 200)) !!},
  "address": {
    "@@type": "PostalAddress",
    "streetAddress": {!! json_encode($property->address_street) !!},
    "addressLocality": {!! json_encode($property->address_city) !!},
    "addressRegion": {!! json_encode($property->address_state) !!},
    "postalCode": {!! json_encode($property->address_zip) !!}
  },
  "numberOfRooms": {{ $property->bedrooms ?? 'null' }},
  "numberOfBathroomsTotal": {{ $property->bathrooms ?? 'null' }}
  @if($property->square_feet)
  ,"floorSize": {
    "@@type": "QuantitativeValue",
    "value": {{ $property->square_feet }},
    "unitCode": "FTK"
  }
  @endif
  @if($property->price)
  ,"offers": {
    "@@type": "Offer",
    "price": "{{ $property->price }}",
    "priceCurrency": "USD"
  }
  @endif
  @if($property->images->first())
  ,"image": {!! json_encode(asset('storage/' . $property->images->first()->image_path)) !!}
  @endif
}
</script>
<style>
@keyframes kenBurnsSlide {
    0%   { transform: scale(1)    translate(0,     0);     }
    25%  { transform: scale(1.04) translate(-0.5%, -0.3%); }
    75%  { transform: scale(1.06) translate( 0.5%,  0.3%); }
    100% { transform: scale(1)    translate(0,     0);     }
}
.carousel-img-active {
    animation: kenBurnsSlide 12s ease-in-out infinite;
}
@media print {
    header, footer, nav, .no-print,
    #booking-section, #map-section, #nearby-section, #related-section { display: none !important; }
    .shadow, .shadow-sm { box-shadow: none !important; }
    .max-w-7xl { max-width: 100% !important; padding: 0 !important; }
    @page { margin: 1.5cm; size: letter; }
    body { font-size: 11pt; color: #111; }
    a { color: inherit !important; text-decoration: none !important; }
}
</style>
@endsection


@section('content')
@php $account = $tenant->slug; @endphp
<div class="max-w-7xl mx-auto px-4 py-10">


            {{-- Image Gallery --}}
            @php $images = $property->images->sortByDesc('is_primary'); $imageUrls = $images->values()->map(fn($i) => asset('storage/'.$i->image_path))->all(); @endphp
            @if($images->count())
            <div x-data="{
                current: 0, lightbox: false, lightboxIdx: 0, touchX: 0,
                swipe(e) {
                    const diff = this.touchX - e.changedTouches[0].clientX;
                    if (Math.abs(diff) > 40) {
                        const last = {{ $images->count() - 1 }};
                        this.current = diff > 0
                            ? (this.current < last ? this.current + 1 : 0)
                            : (this.current > 0 ? this.current - 1 : last);
                    }
                }
            }" class="mb-8">
                {{-- Main image --}}
                <div class="relative">
                <div class="relative rounded-2xl overflow-hidden bg-gray-100 cursor-pointer" style="height: 720px" @click="lightbox = true; lightboxIdx = current" @touchstart.passive="touchX = $event.changedTouches[0].clientX" @touchend.passive="swipe($event)">
                    <template x-for="(img, idx) in {{ json_encode($imageUrls) }}" :key="idx">
                        <img :src="img" :class="current === idx ? 'opacity-100 carousel-img-active' : 'opacity-0'" class="w-full h-full object-cover absolute inset-0 transition-opacity duration-700 ease-in-out">
                    </template>
                    @if($images->count() > 1)
                    <button @click.stop="current = current > 0 ? current - 1 : {{ $images->count() - 1 }}" class="absolute left-3 top-1/2 -translate-y-1/2 bg-black/40 backdrop-blur-sm text-white p-2.5 rounded-full hover:bg-black/60 transition-all hover:scale-110 shadow-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <button @click.stop="current = current < {{ $images->count() - 1 }} ? current + 1 : 0" class="absolute right-3 top-1/2 -translate-y-1/2 bg-black/40 backdrop-blur-sm text-white p-2.5 rounded-full hover:bg-black/60 transition-all hover:scale-110 shadow-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>

                    <div class="absolute bottom-14 right-4 bg-black/40 backdrop-blur-sm text-white text-xs px-2.5 py-1 rounded-full shadow" x-text="`${current + 1} / {{ $images->count() }}`"></div>
                    @endif
                </div>
                {{-- Thumbnails --}}
                @if($images->count() > 1)
                <div x-data="{
                    scales: Array({{ $images->count() }}).fill(1),
                    calcScales(e) {
                        const buttons = e.currentTarget.querySelectorAll('[data-thumb]');
                        buttons.forEach((btn, i) => {
                            const rect = btn.getBoundingClientRect();
                            const center = rect.left + rect.width / 2;
                            const dist = e.clientX - center;
                            this.scales[i] = 1 + 0.45 * Math.exp(-(dist * dist) / 3000);
                        });
                    },
                    resetScales() {
                        this.scales = Array({{ $images->count() }}).fill(1);
                    }
                }" @mousemove="calcScales($event)" @mouseleave="resetScales()" class="flex justify-center gap-2 pb-2 pt-2 items-end absolute bottom-4 left-0 right-0 z-10">
                    @foreach($images as $i => $img)
                    <button data-thumb @click.stop="current = {{ $i }}" class="flex-shrink-0 w-20 h-16 rounded-lg overflow-hidden border-2 transition-transform duration-150 ease-out" :class="current === {{ $i }} ? '' : 'border-transparent'" :style="`transform: scale(${scales[{{ $i }}]}); transform-origin: bottom; ${current === {{ $i }} ? 'border-color: var(--primary)' : ''}`">
                        <img src="{{ asset('storage/'.$img->image_path) }}" class="w-full h-full object-cover pointer-events-none">
                    </button>
                    @endforeach
                </div>
                @endif
                </div>
                {{-- Lightbox --}}
                <div x-show="lightbox" x-cloak @keydown.escape.window="lightbox = false" class="fixed inset-0 bg-black/95 z-50 flex items-center justify-center p-4">
                    <button @click="lightbox = false" class="absolute top-4 right-4 text-white/70 hover:text-white">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                    <button @click="lightboxIdx = lightboxIdx > 0 ? lightboxIdx - 1 : {{ $images->count() - 1 }}" class="absolute left-4 top-1/2 -translate-y-1/2 text-white/70 hover:text-white">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <template x-for="(img, idx) in {{ json_encode($imageUrls) }}" :key="idx">
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
            @php
                $shareUrl  = urlencode(request()->fullUrl());
                $shareText = urlencode(($property->title ?: $property->address) . ($property->price ? ' — $' . number_format($property->price) : ''));
                $shareMailSubject = urlencode('Check out this property: ' . ($property->title ?: $property->address));
                $shareMailBody = urlencode(($property->title ?: $property->address) . ($property->price ? "
$" . number_format($property->price) : '') . "

" . request()->fullUrl());
            @endphp
            <div class="bg-white rounded-2xl p-6 md:p-8 mb-6 shadow border border-gray-200">

                {{-- Status + Share row --}}
                @php $statusColor = match($property->listing_status) { 'active' => '#10b981', 'pending' => '#f59e0b', 'sold' => '#ef4444', default => '#6b7280' }; @endphp
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center text-xs font-semibold text-white px-3 py-1 rounded-full" style="background-color: {{ $statusColor }}">
                            {{ ucfirst($property->listing_status) }}
                        </span>
                        <span id="fav-badge" class="inline-flex items-center gap-1 text-xs font-semibold text-yellow-900 bg-yellow-400 px-3 py-1 rounded-full" style="display:none">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                            Favorite
                        </span>
                    </div>
                    <div class="flex items-center gap-1.5 no-print" x-data="{ copied: false }">
                        <span class="text-xs text-gray-400 mr-1 hidden sm:inline">Share:</span>
                        <button type="button" title="Copy link"
                                @click="navigator.clipboard.writeText(window.location.href).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                                class="relative p-2 rounded-full bg-gray-100 hover:bg-gray-200 transition-colors">
                            <svg x-show="!copied" class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            <svg x-show="copied" x-cloak class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span x-show="copied" x-cloak class="absolute -bottom-8 left-1/2 -translate-x-1/2 text-xs bg-gray-800 text-white px-2 py-1 rounded whitespace-nowrap z-10">Copied!</span>
                        </button>
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener" title="Share on Facebook" class="p-2 rounded-full bg-blue-50 hover:bg-blue-100 transition-colors">
                            <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 24 24"><path d="M18.77 7.46H14.5v-1.9c0-.9.6-1.1 1-1.1h3V.5h-4.33C10.24.5 9.5 3.44 9.5 5.32v2.15h-3v4h3v12h5v-12h3.85l.42-4z"/></svg>
                        </a>
                        <a href="https://twitter.com/intent/tweet?text={{ $shareText }}&amp;url={{ $shareUrl }}" target="_blank" rel="noopener" title="Share on X" class="p-2 rounded-full bg-gray-100 hover:bg-gray-200 transition-colors">
                            <svg class="w-4 h-4 text-gray-700" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                        </a>
                        <a href="mailto:?subject={{ $shareMailSubject }}&amp;body={{ $shareMailBody }}" title="Share via Email" class="p-2 rounded-full bg-green-50 hover:bg-green-100 transition-colors">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </a>
                        <div class="w-px h-5 bg-gray-200 mx-0.5"></div>
                        <button type="button" onclick="window.print()" title="Print property details" class="p-2 rounded-full bg-gray-100 hover:bg-gray-200 transition-colors">
                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        </button>
                    </div>
                </div>


                {{-- Price + Title + Address --}}
                <div class="mb-6">
                    @if($property->price)
                    <p class="text-4xl font-bold mb-2" style="color: var(--primary)">${{ number_format($property->price) }}</p>
                    @endif
                    <h1 class="text-2xl font-bold text-gray-900 leading-tight">{{ $property->title }}</h1>
                    @if($property->address)
                    <p class="flex items-center gap-1.5 text-gray-500 mt-1.5">
                        <svg class="w-4 h-4 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        {{ $property->address }}@if($property->address_line_2), {{ $property->address_line_2 }}@endif{{ $property->city ? ', ' . $property->city : '' }}{{ $property->state ? ', ' . $property->state : '' }}{{ $property->zip ? ' ' . $property->zip : '' }}
                    </p>
                    @endif
                </div>

                {{-- Quick Stats --}}
                <div class="grid grid-cols-3 sm:grid-cols-6 gap-3 mb-6">
                    @if($property->bedrooms)
                    <div class="text-center p-3 bg-gray-50 rounded-xl">
                        <svg class="w-5 h-5 mx-auto mb-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0h4m-4 0v-4a1 1 0 011-1h2a1 1 0 011 1v4"/></svg>
                        <p class="text-xl font-bold text-gray-900">{{ $property->bedrooms }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">Beds</p>
                    </div>
                    @endif
                    @if($property->bathrooms)
                    <div class="text-center p-3 bg-gray-50 rounded-xl">
                        <svg class="w-5 h-5 mx-auto mb-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16h16M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M4 16l-1-9h1a2 2 0 012 2v1m14 6V9a2 2 0 00-2-2h-1"/></svg>
                        <p class="text-xl font-bold text-gray-900">{{ $property->bathrooms }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">Baths</p>
                    </div>
                    @endif
                    @if($property->sqft)
                    <div class="text-center p-3 bg-gray-50 rounded-xl">
                        <svg class="w-5 h-5 mx-auto mb-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5v-4m0 4h-4m4 0l-5-5"/></svg>
                        <p class="text-xl font-bold text-gray-900">{{ number_format($property->sqft) }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">Sq Ft</p>
                    </div>
                    @endif
                    @if($property->year_built)
                    <div class="text-center p-3 bg-gray-50 rounded-xl">
                        <svg class="w-5 h-5 mx-auto mb-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <p class="text-xl font-bold text-gray-900">{{ $property->year_built }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">Built</p>
                    </div>
                    @endif
                    @if($property->garage)
                    <div class="text-center p-3 bg-gray-50 rounded-xl">
                        <svg class="w-5 h-5 mx-auto mb-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 17h8M8 17v4h8v-4M8 17H4l2-9h12l2 9h-4M9 7h6"/></svg>
                        <p class="text-xl font-bold text-gray-900">{{ $property->garage }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">Garage</p>
                    </div>
                    @endif
                    @if($property->property_type)
                    <div class="text-center p-3 bg-gray-50 rounded-xl">
                        <svg class="w-5 h-5 mx-auto mb-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        <p class="text-xl font-bold text-gray-900">{{ ucfirst(str_replace('-',' ',$property->property_type)) }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">Type</p>
                    </div>
                    @endif
                </div>

                {{-- Description --}}
                @if($property->description)
                <div class="border-t border-gray-100 pt-5 mt-1">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">About This Property</h3>
                    <p class="text-gray-700 leading-relaxed whitespace-pre-wrap">{{ $property->description }}</p>
                </div>
                @endif

            </div>

            @if($property->amenities && count($property->amenities))
            <div class="bg-white rounded-2xl p-6 mb-6 shadow border border-gray-200">
                <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2"><svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5a1.99 1.99 0 011.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>Amenities & Features</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach((array)$property->amenities as $amenity)
                    <span class="px-3 py-1.5 rounded-full text-sm font-medium" style="background-color: rgba(var(--primary-rgb), 0.1); color: var(--primary)">{{ $amenity }}</span>
                    @endforeach
                </div>
            </div>
            @endif

            @if($property->virtual_tour_url)
            <div class="bg-white rounded-2xl p-6 mb-6 shadow border border-gray-200">
                <h3 class="font-semibold text-gray-800 mb-3 flex items-center gap-2"><svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>Virtual Tour</h3>
                <a href="{{ $property->virtual_tour_url }}" target="_blank" class="btn-primary inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-sm hover:opacity-90 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    View Virtual Tour
                </a>
            </div>
            @endif


    {{-- Mortgage Calculator + Nearby Places --}}
    <div class="grid md:grid-cols-2 gap-6 mb-6">

    {{-- Mortgage Calculator --}}
    @if($property->price)
    <div class="bg-white rounded-2xl p-6 shadow border border-gray-200 min-w-0 overflow-hidden"
         x-data="{
             price: {{ (int)$property->price }},
             downPct: 20,
             downAmt: {{ (int)round($property->price * 0.2) }},
             downMode: 'pct',
             rate: 7.0,
             term: 30,
             get loanAmt() {
                 return Math.max(0, this.price - (this.downMode === 'pct' ? this.price * this.downPct / 100 : this.downAmt));
             },
             get monthly() {
                 const r = this.rate / 100 / 12;
                 const n = this.term * 12;
                 if (r === 0 || n === 0) return this.loanAmt / (n || 1);
                 return this.loanAmt * r * Math.pow(1+r, n) / (Math.pow(1+r, n) - 1);
             },
             get totalPaid() { return this.monthly * this.term * 12; },
             get totalInterest() { return Math.max(0, this.totalPaid - this.loanAmt); },
             get principalPct() { return this.totalPaid > 0 ? (this.loanAmt / this.totalPaid * 100) : 0; },
             get totalMonthly() { return this.monthly + (this.price * 0.012 / 12) + (this.price * 0.004 / 12) + {{ $property->hoa_fee ?? 0 }} + (this.downPct < 20 ? this.loanAmt * 0.005 / 12 : 0); },
             fmt(n) { return isNaN(n) ? '$0' : new Intl.NumberFormat('en-US', {style:'currency',currency:'USD',maximumFractionDigits:0}).format(n); },
             syncDown() {
                 if (this.downMode === 'pct') {
                     this.downAmt = Math.round(this.price * this.downPct / 100);
                 } else {
                     this.downPct = this.price > 0 ? Math.round(this.downAmt / this.price * 100) : 0;
                 }
             }
         }">
        <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            Mortgage Calculator
        </h3>

        <div class="grid sm:grid-cols-2 gap-4 mb-5">
            {{-- Home Price --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Home Price</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-medium">$</span>
                    <input type="number" x-model.number="price" @input="syncDown()"
                           class="w-full pl-7 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:border-transparent transition"
                           style="--tw-ring-color: var(--primary)" min="0" step="1000">
                </div>
            </div>

            {{-- Down Payment --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Down Payment</label>
                <div class="flex gap-2">
                    <div class="relative flex-1">
                        <span x-show="downMode === 'pct'" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">%</span>
                        <span x-show="downMode === 'amt'" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-medium">$</span>
                        <input x-show="downMode === 'pct'" type="number" x-model.number="downPct" @input="syncDown()"
                               class="w-full pr-7 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:border-transparent transition"
                               style="--tw-ring-color: var(--primary)" min="0" max="100" step="1">
                        <input x-show="downMode === 'amt'" type="number" x-model.number="downAmt" @input="syncDown()"
                               class="w-full pl-7 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:border-transparent transition"
                               style="--tw-ring-color: var(--primary)" min="0" step="1000">
                    </div>
                    <button type="button" @click="downMode = downMode === 'pct' ? 'amt' : 'pct'; syncDown()"
                            class="px-3 py-2 rounded-lg border border-gray-300 text-xs font-semibold text-gray-600 hover:bg-gray-50 transition whitespace-nowrap">
                        <span x-text="downMode === 'pct' ? '$ Amt' : '% Pct'"></span>
                    </button>
                </div>
            </div>

            {{-- Interest Rate --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Interest Rate</label>
                <div class="relative">
                    <input type="number" x-model.number="rate"
                           class="w-full pr-7 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:border-transparent transition"
                           style="--tw-ring-color: var(--primary)" min="0" max="30" step="0.05">
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">%</span>
                </div>
            </div>

            {{-- Loan Term --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Loan Term</label>
                <div class="flex rounded-lg border border-gray-300 overflow-hidden text-sm">
                    <button type="button" @click="term = 30"
                            class="flex-1 py-2 font-medium transition"
                            :class="term === 30 ? 'text-white' : 'bg-white text-gray-600 hover:bg-gray-50'"
                            :style="term === 30 ? 'background-color: var(--primary)' : ''">30 yr</button>
                    <button type="button" @click="term = 20"
                            class="flex-1 py-2 font-medium border-l border-gray-300 transition"
                            :class="term === 20 ? 'text-white' : 'bg-white text-gray-600 hover:bg-gray-50'"
                            :style="term === 20 ? 'background-color: var(--primary)' : ''">20 yr</button>
                    <button type="button" @click="term = 15"
                            class="flex-1 py-2 font-medium border-l border-gray-300 transition"
                            :class="term === 15 ? 'text-white' : 'bg-white text-gray-600 hover:bg-gray-50'"
                            :style="term === 15 ? 'background-color: var(--primary)' : ''">15 yr</button>
                </div>
            </div>
        </div>

        {{-- Results --}}
        <div class="rounded-xl p-4" style="background-color: rgba(var(--primary-rgb), 0.06)">
            <div class="flex items-end justify-between mb-3">
                <div>
                    <p class="text-xs text-gray-500 mb-0.5">Est. Monthly Payment</p>
                    <p class="text-3xl font-bold" style="color: var(--primary)" x-text="fmt(monthly)"></p>
                    <p class="text-xs text-gray-400 mt-0.5">Principal &amp; interest only</p>
                </div>
                <div class="text-right">
                    <p class="text-gray-500 text-xs">Loan Amount</p>
                    <p class="font-semibold text-gray-800 text-sm" x-text="fmt(loanAmt)"></p>
                </div>
            </div>

            {{-- Principal vs Interest bar --}}
            <div class="mb-3">
                <div class="h-2 rounded-full bg-gray-200 overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-300" style="background-color: var(--primary)"
                         :style="`width: ${Math.max(2, principalPct)}%`"></div>
                </div>
                <div class="flex justify-between text-xs text-gray-500 mt-1.5">
                    <span>Principal <strong class="text-gray-700" x-text="fmt(loanAmt)"></strong></span>
                    <span>Interest <strong class="text-gray-700" x-text="fmt(totalInterest)"></strong></span>
                </div>
            </div>

            <p class="text-xs text-gray-400">
                Total of <span class="font-medium text-gray-500" x-text="term * 12 + ' payments'"></span>:
                <span class="font-medium text-gray-600" x-text="fmt(totalPaid)"></span>
            </p>
        </div>

        {{-- Cost Breakdown --}}
        <div class="mt-4 pt-4 border-t border-gray-200">
            <h4 class="text-sm font-semibold text-gray-700 mb-3">Estimated Monthly Costs</h4>
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Principal & Interest</span>
                    <span class="font-medium text-gray-800" x-text="fmt(monthly)"></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Property Tax <span class="text-gray-400 text-xs">(est.)</span></span>
                    <span class="font-medium text-gray-800" x-text="fmt(price * 0.012 / 12)"></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Home Insurance <span class="text-gray-400 text-xs">(est.)</span></span>
                    <span class="font-medium text-gray-800" x-text="fmt(price * 0.004 / 12)"></span>
                </div>
                @if($property->hoa_fee > 0)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">HOA Fee</span>
                    <span class="font-medium text-gray-800">${{ number_format($property->hoa_fee, 0) }}</span>
                </div>
                @endif
                <div x-show="downPct < 20" x-cloak class="flex justify-between text-sm">
                    <span class="text-gray-500">PMI <span class="text-gray-400 text-xs">(est.)</span></span>
                    <span class="font-medium text-gray-800" x-text="fmt(loanAmt * 0.005 / 12)"></span>
                </div>
                <div class="flex justify-between text-sm pt-2 border-t border-gray-100">
                    <span class="font-semibold text-gray-700">Total Monthly</span>
                    <span class="font-bold" style="color: var(--primary)"
                          x-text="fmt(totalMonthly)"></span>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-3">* Tax (1.2%) and insurance (0.4%) are national averages. PMI applies when down payment is under 20%.</p>
        </div>
    </div>
    @endif

    {{-- Nearby Places --}}
    @if($nearbyPlaces ?? null)
        @include('tenant.partials.nearby-places', ['nearbyPlaces' => $nearbyPlaces])
    @else
    <div class="bg-white rounded-2xl p-6 shadow border border-gray-200 min-w-0 overflow-hidden">
        <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2"><svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>Nearby Places</h2>
        <div class="flex flex-col items-center justify-center py-6 text-center">
            <svg class="w-10 h-10 mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <p class="text-sm text-gray-400">Nearby places unavailable</p>
        </div>
    </div>
    @endif

    </div>{{-- end grid --}}

    {{-- Your Agent card --}}
    @php
        $agent = $property->staffMember
            ? (object)['name' => $property->staffMember->name, 'title' => $property->staffMember->title, 'photo' => $property->staffMember->photo_url ? asset('storage/' . $property->staffMember->photo_url) : null, 'bio' => $property->staffMember->bio]
            : (($settings->owner_name || $settings->owner_photo || $settings->owner_bio)
                ? (object)['name' => $settings->owner_name, 'title' => null, 'photo' => $settings->owner_photo ? asset('storage/' . $settings->owner_photo) : null, 'bio' => $settings->owner_bio]
                : null);
    @endphp
    @if($agent)
    <div class="bg-white rounded-2xl mt-8 shadow border border-gray-200 overflow-hidden">
        <div class="px-6 pt-5 pb-4 flex items-center gap-4" style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.05), rgba(var(--primary-rgb), 0.02))">
            <div class="w-14 h-14 rounded-full bg-white overflow-hidden flex-shrink-0 shadow-sm border-2" style="border-color: var(--primary)">
                @if($agent->photo)
                    <img src="{{ $agent->photo }}" alt="{{ $agent->name }}" class="w-full h-full object-cover">
                @else
                    <div class="w-full h-full flex items-center justify-center" style="background-color: rgba(var(--primary-rgb), 0.1)">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--primary)"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Your Agent</p>
                @if($agent->name)
                <p class="font-semibold text-gray-900 text-lg leading-tight">{{ $agent->name }}</p>
                @endif
                @if($agent->title)
                    <p class="text-sm" style="color: var(--primary)">{{ $agent->title }}</p>
                @endif
            </div>
        </div>
        @if($agent->bio)
        <div class="px-6 py-4">
            <p class="text-sm text-gray-600 leading-relaxed">{{ $agent->bio }}</p>
        </div>
        @endif
    </div>
    @endif

    {{-- Appointment Booking Form --}}
    @php $isPro = $tenant->isPro(); @endphp
    <div id="booking-section" class="bg-white rounded-2xl p-6 mt-8 shadow border border-gray-200">
        @if($isPro)
        {{-- Pro / Trial: full booking form --}}
        <div x-data="{
            submitting: false,
            success: false,
            error: '',
            consent: false,
            form: {
                visitor_name: '',
                visitor_email: '',
                visitor_phone: '',
                appointment_date: '',
                appointment_time: '09:00',
                appointment_type: 'showing',
                message: '',
                property_id: {{ $property->id }}
            },
            async submit() {
                if (!this.consent) { this.error = 'Please check the consent box to continue.'; return; }
                this.submitting = true;
                this.error = '';
                try {
                    const res = await fetch('{{ route('tenant.appointments.store', $account) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(this.form)
                    });
                    if (res.status === 429) {
                        try {
                            const data = await res.json();
                            this.error = data.message || 'Too many requests. Please wait a few minutes and try again.';
                        } catch {
                            this.error = 'Too many requests. Please wait a few minutes and try again.';
                        }
                        return;
                    }
                    const data = await res.json();
                    if (data.success) {
                        this.success = true;
                    } else {
                        this.error = data.message || 'Something went wrong. Please try again.';
                    }
                } catch (e) {
                    this.error = 'Something went wrong. Please try again.';
                } finally {
                    this.submitting = false;
                }
            }
        }">
            <h2 class="text-xl font-semibold text-gray-800 mb-1 flex items-center gap-2"><svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>Schedule a Showing</h2>
            <p class="text-sm text-gray-500 mb-6">Fill out the form below and we'll be in touch to confirm your appointment.</p>

            {{-- Success state --}}
            <div x-show="success" x-cloak class="flex flex-col items-center justify-center py-10 text-center">
                <div class="w-16 h-16 rounded-full flex items-center justify-center mb-4" style="background-color: rgba(var(--primary-rgb), 0.1)">
                    <svg class="w-8 h-8" style="color: var(--primary)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 mb-1">Request Sent!</h3>
                <p class="text-gray-500 text-sm">We've received your appointment request and will be in touch shortly to confirm.</p>
            </div>

            {{-- Form --}}
            <form x-show="!success" @submit.prevent="submit" class="space-y-4">
                {{-- Error banner --}}
                <div x-show="error" x-cloak class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg" x-text="error"></div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                        <input type="text" x-model="form.visitor_name" required placeholder="Jane Smith"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:border-transparent transition"
                               style="--tw-ring-color: var(--primary)">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                        <input type="email" x-model="form.visitor_email" required placeholder="jane@example.com"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:border-transparent transition"
                               style="--tw-ring-color: var(--primary)">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input type="tel" x-model="form.visitor_phone" placeholder="(555) 123-4567"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:border-transparent transition"
                               style="--tw-ring-color: var(--primary)">

                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Request Type</label>
                        <select x-model="form.appointment_type"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:border-transparent transition"
                                style="--tw-ring-color: var(--primary)">
                            <option value="showing">In-Person Showing</option>
                            <option value="virtual">Virtual Tour</option>
                            <option value="info">More Information</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Preferred Date <span class="text-red-500">*</span></label>
                        <input type="date" x-model="form.appointment_date" required
                               :min="new Date().toISOString().split('T')[0]"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:border-transparent transition"
                               style="--tw-ring-color: var(--primary)">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Preferred Time</label>
                        <select x-model="form.appointment_time"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:border-transparent transition"
                                style="--tw-ring-color: var(--primary)">
                            <option value="08:00">8:00 AM</option>
                            <option value="09:00">9:00 AM</option>
                            <option value="10:00">10:00 AM</option>
                            <option value="11:00">11:00 AM</option>
                            <option value="12:00">12:00 PM</option>
                            <option value="13:00">1:00 PM</option>
                            <option value="14:00">2:00 PM</option>
                            <option value="15:00">3:00 PM</option>
                            <option value="16:00">4:00 PM</option>
                            <option value="17:00">5:00 PM</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                    <textarea x-model="form.message" rows="3" placeholder="Any questions or special requests..."
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:border-transparent transition resize-none"
                              style="--tw-ring-color: var(--primary)"></textarea>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <p class="text-xs text-gray-400">By providing your phone number, you consent to receive calls or texts regarding your inquiry. <a href="{{ route('tenant.privacy', $account) }}" class="underline hover:text-gray-800 dark:hover:text-gray-200" target="_blank">Privacy Policy</a>. <input type="checkbox" id="appt-consent" x-model="consent" class="w-3.5 h-3.5 rounded border-gray-400" style="vertical-align:-3px;accent-color: var(--primary)"> <label for="appt-consent" class="cursor-pointer underline">I agree</label> <span class="text-red-500">*</span></p>
                    <button type="submit"
                            :disabled="submitting || !consent"
                            class="w-full sm:w-auto btn-primary px-8 py-3 rounded-xl font-semibold text-sm transition enabled:hover:opacity-90 disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2 shrink-0">
                        <svg x-show="submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        <span x-text="submitting ? 'Sending...' : 'Request Appointment'"></span>
                    </button>
                </div>
            </form>
        </div>

        @else
        {{-- Starter plan: show contact details instead --}}
        <h2 class="text-xl font-semibold text-gray-800 mb-1 flex items-center gap-2"><svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>Interested in This Property?</h2>
        <p class="text-sm text-gray-500 mb-6">Contact us directly to schedule a showing or ask any questions.</p>
        <div class="flex flex-col sm:flex-row gap-3">
            <a href="{{ route('tenant.contact', $account) }}"
               class="inline-flex items-center gap-2 px-5 py-3 rounded-xl font-semibold text-sm text-white transition hover:opacity-90"
               style="background-color: var(--primary)">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                Contact Us
            </a>
        </div>
        @endif
    </div>

    {{-- Location: Map & Street View --}}
    @if($property->latitude && $property->longitude)
    <div id="map-section" class="bg-white rounded-2xl p-6 mt-8 shadow border border-gray-200">
        <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2"><svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>Location</h2>
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
                @if($property->address_line_2)<p class="text-xs">{{ $property->address_line_2 }}</p>@endif
                @if($property->city)<p class="text-xs">{{ $property->city }}@if($property->state), {{ $property->state }}@endif</p>@endif
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- Related Properties --}}
    @if($related->count())
    <div id="related-section" class="mt-12">
        <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-2"><svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>Similar Properties</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($related as $rel)
            <a href="{{ route('tenant.property', [$account, $rel->id]) }}" class="bg-white rounded-2xl overflow-hidden shadow border border-gray-200 hover:shadow-lg transition-shadow group">
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

@section('scripts')
(function() {
    try {
        var key  = 'br_favs_{{ $tenant->slug }}';
        var id   = {{ $property->id }};
        var favs = JSON.parse(localStorage.getItem(key) || '[]');
        if (favs.includes(id)) document.getElementById('fav-badge').style.display = '';
    } catch(e) {}
})();
@endsection
