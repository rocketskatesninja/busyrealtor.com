@extends('layouts.tenant')
@section('hide_header')@endsection
@section('title', $property->title . ' — ' . ($settings->site_title ?? 'BusyRealtor'))
@section('meta_description', Str::limit(strip_tags($property->description ?? $settings->site_description ?? ''), 155))
@section('og_image', $property->images->first() ? asset('storage/' . $property->images->first()->image_url) : '')

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
        mapTypeControl: false, streetViewControl: true, fullscreenControl: true,
        mapId: 'DEMO_MAP_ID'
    });
    new google.maps.marker.AdvancedMarkerElement({ position: loc, map: map, title: {!! json_encode($property->title) !!} });

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
  ,"image": {!! json_encode(asset('storage/' . $property->images->first()->image_url)) !!}
  @endif
}
</script>
@endsection


@section('content')
@php $account = $tenant->slug; @endphp
<div class="max-w-7xl mx-auto px-4 py-10">
    <div class="mb-6">
        <nav class="flex items-center gap-2 text-sm text-gray-500">
            @auth
            @if(auth()->user()->tenant_id === app('tenant')->id || auth()->user()->is_super_admin)
            <a href="{{ route('tenant.admin.dashboard', $account) }}" class="hover-primary transition">Home</a>
            @else
            <a href="{{ route('tenant.home', $account) }}" class="hover-primary transition">Home</a>
            @endif
            @else
            <a href="{{ route('tenant.home', $account) }}" class="hover-primary transition">Home</a>
            @endauth
            <span>/</span>
            @auth
            @if(auth()->user()->tenant_id === app('tenant')->id || auth()->user()->is_super_admin)
            <a href="{{ route('tenant.admin.properties.index', $account) }}" class="hover-primary transition">Properties</a>
            @else
            <a href="{{ route('tenant.gallery', $account) }}" class="hover-primary transition">Properties</a>
            @endif
            @else
            <a href="{{ route('tenant.gallery', $account) }}" class="hover-primary transition">Properties</a>
            @endauth
            <span>/</span>
            <span class="text-gray-800">{{ Str::limit($property->title, 40) }}</span>
        </nav>
    </div>

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
            @php
                $shareUrl  = urlencode(request()->fullUrl());
                $shareText = urlencode(($property->title ?: $property->address) . ($property->price ? ' — $' . number_format($property->price) : ''));
                $shareMailSubject = urlencode('Check out this property: ' . ($property->title ?: $property->address));
                $shareMailBody = urlencode(($property->title ?: $property->address) . ($property->price ? "
$" . number_format($property->price) : '') . "

" . request()->fullUrl());
            @endphp
            <div class="bg-white rounded-2xl p-6 mb-6 shadow border border-gray-200">

                {{-- Status + Share row --}}
                <div class="flex items-center justify-between mb-4">
                    <span class="inline-flex items-center text-xs font-semibold text-white px-3 py-1 rounded-full" style="background-color: {{ $property->listing_status === 'active' ? '#10b981' : ($property->listing_status === 'pending' ? '#f59e0b' : '#6b7280') }}">
                        {{ ucfirst($property->listing_status) }}
                    </span>
                    <div class="flex items-center gap-1.5" x-data="{ copied: false }">
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
                    </div>
                </div>

                {{-- Title + Address + Price --}}
                <div class="flex flex-wrap items-end justify-between gap-3 mb-5">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 leading-tight">{{ $property->title }}</h1>
                        @if($property->address)
                        <p class="flex items-center gap-1.5 text-gray-500 text-sm mt-1">
                            <svg class="w-4 h-4 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            {{ $property->address }}{{ $property->city ? ', ' . $property->city : '' }}{{ $property->state ? ', ' . $property->state : '' }}{{ $property->zip ? ' ' . $property->zip : '' }}
                        </p>
                        @endif
                    </div>
                    @if($property->price)
                    <p class="text-3xl font-bold whitespace-nowrap" style="color: var(--primary)">${{ number_format($property->price) }}</p>
                    @endif
                </div>

                {{-- Quick Stats --}}
                @php $stats = array_filter([
                    $property->bedrooms  ? ['val' => $property->bedrooms,                  'label' => 'Beds']   : null,
                    $property->bathrooms ? ['val' => $property->bathrooms,                 'label' => 'Baths']  : null,
                    $property->sqft      ? ['val' => number_format($property->sqft),       'label' => 'Sq Ft']  : null,
                    $property->year_built? ['val' => $property->year_built,               'label' => 'Built']  : null,
                    $property->garage    ? ['val' => $property->garage,                   'label' => 'Garage'] : null,
                    $property->property_type ? ['val' => ucfirst(str_replace('-',' ',$property->property_type)), 'label' => 'Type'] : null,
                ]); @endphp
                @if(count($stats))
                <div class="grid grid-cols-3 sm:grid-cols-6 gap-px bg-gray-100 rounded-xl overflow-hidden border border-gray-100 mb-5">
                    @foreach($stats as $stat)
                    <div class="bg-white text-center px-3 py-3">
                        <p class="text-lg font-bold text-gray-900">{{ $stat['val'] }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $stat['label'] }}</p>
                    </div>
                    @endforeach
                </div>
                @endif

                {{-- Description --}}
                @if($property->description)
                <div class="border-t border-gray-100 pt-4">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-2">About This Property</h3>
                    <p class="text-gray-700 leading-relaxed whitespace-pre-wrap text-sm">{{ $property->description }}</p>
                </div>
                @endif

            </div>

            @if($property->amenities && count($property->amenities))
            <div class="bg-white rounded-2xl p-6 mb-6 shadow border border-gray-200">
                <h3 class="font-semibold text-gray-800 mb-4">Amenities & Features</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach((array)$property->amenities as $amenity)
                    <span class="px-3 py-1.5 rounded-full text-sm font-medium" style="background-color: rgba(var(--primary-rgb), 0.1); color: var(--primary)">{{ $amenity }}</span>
                    @endforeach
                </div>
            </div>
            @endif

            @if($property->virtual_tour_url)
            <div class="bg-white rounded-2xl p-6 mb-6 shadow border border-gray-200">
                <h3 class="font-semibold text-gray-800 mb-3">Virtual Tour</h3>
                <a href="{{ $property->virtual_tour_url }}" target="_blank" class="btn-primary inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-sm hover:opacity-90 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    View Virtual Tour
                </a>
            </div>
            @endif


    {{-- Your Agent card --}}
    @if($property->staffMember)
    <div class="mt-8">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Your Agent</p>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 mb-6 flex items-start gap-4">
            <div class="w-14 h-14 rounded-full bg-gray-100 overflow-hidden flex-shrink-0">
                @if($property->staffMember->photo_url)
                    <img src="{{ asset('storage/' . $property->staffMember->photo_url) }}" alt="{{ $property->staffMember->name }}" class="w-full h-full object-cover">
                @else
                    <div class="w-full h-full flex items-center justify-center" style="background-color: rgba(var(--primary-rgb), 0.1)">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--primary)"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-gray-800 text-base">{{ $property->staffMember->name }}</p>
                @if($property->staffMember->title)
                    <p class="text-sm text-gray-500 mb-1">{{ $property->staffMember->title }}</p>
                @endif

                @if($property->staffMember->bio)
                    <p class="text-sm text-gray-500 mt-2 leading-relaxed">{{ Str::limit($property->staffMember->bio, 100) }}</p>
                @endif
            </div>
        </div>
    </div>
    @elseif($settings->owner_photo || $settings->owner_bio || $settings->owner_name)
    <div class="bg-white rounded-2xl p-6 mt-8 shadow border border-gray-200">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Your Agent</h3>
        <div class="flex items-start gap-4">
            @if($settings->owner_photo)
            <img src="{{ asset('storage/' . $settings->owner_photo) }}"
                 alt="{{ $settings->owner_name }}"
                 class="w-16 h-16 rounded-full object-cover flex-shrink-0">
            @endif
            <div>
                @if($settings->owner_name)
                <p class="font-semibold text-gray-800 text-base">{{ $settings->owner_name }}</p>
                @endif
                @if($settings->owner_bio)
                <p class="text-sm text-gray-500 mt-2 leading-relaxed">{{ Str::limit($settings->owner_bio, 100) }}</p>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Appointment Booking Form --}}
    @php $isPro = $tenant->isPro(); @endphp
    <div class="bg-white rounded-2xl p-6 mt-8 shadow border border-gray-200">
        @if($isPro)
        {{-- Pro / Trial: full booking form --}}
        <div x-data="{
            submitting: false,
            success: false,
            error: '',
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
                    const data = await res.json();
                    if (data.success) {
                        this.success = true;
                    } else {
                        this.error = data.message || 'Something went wrong. Please try again.';
                    }
                } catch (e) {
                    this.error = 'Network error. Please try again.';
                } finally {
                    this.submitting = false;
                }
            }
        }">
            <h2 class="text-xl font-semibold text-gray-800 mb-1">Schedule a Showing</h2>
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

                <button type="submit"
                        :disabled="submitting"
                        class="w-full sm:w-auto btn-primary px-8 py-3 rounded-xl font-semibold text-sm transition hover:opacity-90 disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                    <svg x-show="submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                    </svg>
                    <span x-text="submitting ? 'Sending...' : 'Request Appointment'"></span>
                </button>
            </form>
        </div>

        @else
        {{-- Starter plan: show contact details instead --}}
        <h2 class="text-xl font-semibold text-gray-800 mb-1">Interested in This Property?</h2>
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
    <div class="bg-white rounded-2xl p-6 mt-8 shadow border border-gray-200">
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
