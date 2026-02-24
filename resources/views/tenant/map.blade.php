@extends('layouts.tenant')
@section('title', 'Map — ' . ($settings->site_title ?? 'BusyRealtor'))

@section('head')
@php
$account = $tenant->slug;
$mapsKeyRecord = \App\Models\Integration::where('tenant_id', $tenant->id)->where('integration_type', 'google_maps')->first();
$mapsKey = $mapsKeyRecord ? $mapsKeyRecord->decryptKey() : null;
@endphp
@if($mapsKey)
<script>
var _allMarkers = [];
var _map = null;

function initMap() {
    var mapEl = document.getElementById('main-map');
    if (!mapEl) return;
    _map = new google.maps.Map(mapEl, { zoom: 11, center: { lat: 33.749, lng: -84.388 }, styles: [] });
    var propertiesData = {!! json_encode($properties->map(fn($p) => [
        'id'         => $p->id,
        'lat'        => (float)$p->latitude,
        'lng'        => (float)$p->longitude,
        'title'      => $p->title,
        'price'      => (int)$p->price,
        'price_disp' => '$' . number_format($p->price),
        'address'    => $p->address,
        'image'      => $p->primaryImage ? asset('storage/'.$p->primaryImage->image_path) : null,
        'url'        => route('tenant.property', [$account, $p->id]),
        'type'       => $p->property_type,
        'status'     => $p->listing_status,
        'beds'       => (int)$p->bedrooms,
        'baths'      => (float)$p->bathrooms,
        'sqft'       => (int)$p->sqft,
        'year_built' => (int)$p->year_built,
        'garage'     => (int)$p->garage,
        'hoa_fee'    => (float)$p->hoa_fee,
        'amenities'  => (array)$p->amenities,
    ])) !!};
    var infoWindow = new google.maps.InfoWindow();
    var bounds = new google.maps.LatLngBounds();
    propertiesData.forEach(function(p) {
        if (!p.lat || !p.lng) return;
        var marker = new google.maps.Marker({ map: _map, position: { lat: p.lat, lng: p.lng }, title: p.title });
        bounds.extend({ lat: p.lat, lng: p.lng });
        marker.addListener('click', function() {
            var dark    = document.documentElement.classList.contains('dark');
            var txt     = dark ? '#f1f5f9' : '#111827';
            var sub     = dark ? '#94a3b8' : '#6b7280';
            var primary = getComputedStyle(document.documentElement).getPropertyValue('--primary').trim() || '#3b82f6';
            var statusColors = { active: '#10b981', pending: '#f59e0b', sold: '#6b7280' };
            var statusBg = statusColors[p.status] || '#6b7280';
            var details = [p.beds ? p.beds+'bd' : '', p.baths ? p.baths+'ba' : '', p.sqft ? p.sqft.toLocaleString()+' sqft' : ''].filter(Boolean).join(' · ');
            infoWindow.setContent(
                '<div style="width:240px;font-family:system-ui,-apple-system,sans-serif;border-radius:8px;overflow:hidden;margin:-11px -12px">' +
                (p.image ? '<img src="'+p.image+'" style="width:100%;height:130px;object-fit:cover;display:block" onerror="this.style.display=\'none\'">' : '') +
                '<div style="padding:12px">' +
                '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">' +
                '<span style="font-weight:700;font-size:1rem;color:'+primary+'">'+p.price_disp+'</span>' +
                '<span style="font-size:0.7rem;font-weight:600;color:#fff;background:'+statusBg+';padding:2px 8px;border-radius:20px;text-transform:capitalize">'+p.status+'</span>' +
                '</div>' +
                '<p style="font-weight:600;font-size:0.875rem;color:'+txt+';margin:0 0 2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">'+p.title+'</p>' +
                '<p style="font-size:0.75rem;color:'+sub+';margin:0 0 6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">'+(p.address||'')+'</p>' +
                (details ? '<p style="font-size:0.75rem;color:'+sub+';margin:0 0 10px">'+details+'</p>' : '') +
                '<a href="'+p.url+'" style="display:block;text-align:center;background:'+primary+';color:#fff;padding:7px 12px;border-radius:8px;text-decoration:none;font-size:0.8rem;font-weight:600">View Details →</a>' +
                '</div></div>'
            );
            infoWindow.open(_map, marker);
        });
        _allMarkers.push({ marker: marker, data: p });
    });
    if (!bounds.isEmpty()) _map.fitBounds(bounds);
    document.getElementById('prop-count').textContent = _allMarkers.length;
}

function applyMapFilter() {
    var form = document.getElementById('map-filter');
    var type       = form.querySelector('[name=type]').value;
    var status     = form.querySelector('[name=status]').value;
    var priceMin   = parseFloat(form.querySelector('[name=price_min]').value) || 0;
    var priceMax   = parseFloat(form.querySelector('[name=price_max]').value) || Infinity;
    var beds       = parseFloat(form.querySelector('[name=beds]').value) || 0;
    var baths      = parseFloat(form.querySelector('[name=baths]').value) || 0;
    var sqftMin    = parseFloat(form.querySelector('[name=sqft_min]').value) || 0;
    var sqftMax    = parseFloat(form.querySelector('[name=sqft_max]').value) || Infinity;
    var yearMin    = parseInt(form.querySelector('[name=year_min]').value) || 0;
    var yearMax    = parseInt(form.querySelector('[name=year_max]').value) || Infinity;
    var garage     = parseFloat(form.querySelector('[name=garage_spaces]').value) || 0;
    var hoa        = form.querySelector('[name=hoa]').value;
    var hoaMaxEl   = form.querySelector('[name=hoa_max]');
    var hoaMax     = hoaMaxEl && hoaMaxEl.value ? parseFloat(hoaMaxEl.value) : Infinity;
    var features   = Array.from(form.querySelectorAll('[name="features[]"]:checked')).map(function(el) { return el.value; });

    var visible = 0;
    _allMarkers.forEach(function(item) {
        var p = item.data;
        var show = true;
        if (type   && p.type   !== type)   show = false;
        if (status && p.status !== status) show = false;
        if (p.price < priceMin)  show = false;
        if (p.price > priceMax)  show = false;
        if (beds  && p.beds  < beds)  show = false;
        if (baths && p.baths < baths) show = false;
        if (sqftMin && p.sqft < sqftMin) show = false;
        if (sqftMax < Infinity && p.sqft && p.sqft > sqftMax) show = false;
        if (yearMin && p.year_built && p.year_built < yearMin) show = false;
        if (yearMax < Infinity && p.year_built && p.year_built > yearMax) show = false;
        if (garage && p.garage < garage) show = false;
        if (hoa === 'yes' && !(p.hoa_fee > 0)) show = false;
        if (hoa === 'no'  && p.hoa_fee > 0)    show = false;
        if (hoaMax < Infinity && p.hoa_fee > hoaMax) show = false;
        if (features.length > 0) {
            var hasAll = features.every(function(f) { return (p.amenities || []).indexOf(f) !== -1; });
            if (!hasAll) show = false;
        }
        item.marker.setVisible(show);
        if (show) visible++;
    });
    document.getElementById('prop-count').textContent = visible;
}

function clearMapFilter() {
    var form = document.getElementById('map-filter');
    form.reset();
    var hoaWrap = document.getElementById('hoaMaxWrap_map');
    if (hoaWrap) hoaWrap.style.display = 'none';
    _allMarkers.forEach(function(item) { item.marker.setVisible(true); });
    document.getElementById('prop-count').textContent = _allMarkers.length;
}
</script>
<style>
/* Google Maps InfoWindow — dark mode overrides */
.dark .gm-style-iw-c {
    background-color: #1e293b !important;
    box-shadow: 0 4px 24px rgba(0,0,0,0.6) !important;
}
.dark .gm-style-iw-d { overflow: auto !important; }
.dark .gm-style-iw-t::after { background: #1e293b !important; }
.dark .gm-ui-hover-effect > span { background-color: #94a3b8 !important; }
</style>
<script src="https://maps.googleapis.com/maps/api/js?key={{ $mapsKey }}&callback=initMap" async defer></script>
@endif
@endsection

@section('content')
<div class="relative" style="height: calc(100vh - 80px)">
    {{-- Map Filter Panel --}}
    <div class="absolute top-4 left-4 z-10 bg-white rounded-2xl shadow-xl w-72 max-h-[calc(100vh-120px)] flex flex-col" x-data="{ open: true }">
        <div class="flex items-center justify-between p-5 pb-3 flex-shrink-0">
            <h3 class="font-bold text-gray-800">Filter Map</h3>
            <button @click="open = !open" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
        </div>
        <div x-show="open" x-transition class="overflow-y-auto flex-1 px-5 pb-2">
            <form id="map-filter" class="space-y-4">
                @include('tenant.partials.filter-fields', ['filterSuffix' => '_map'])
            </form>
        </div>
        <div x-show="open" class="p-5 pt-3 flex-shrink-0 border-t">
            <button type="button" onclick="applyMapFilter()" class="btn-primary w-full py-2.5 rounded-xl font-semibold text-sm hover:opacity-90 transition mb-2">Apply Filters</button>
            <button type="button" onclick="clearMapFilter()" class="w-full py-2 rounded-xl text-sm text-gray-500 hover:text-gray-700 transition">Clear Filters</button>
            <p class="text-xs text-gray-500 font-medium mt-3 mb-1">Properties on map: <span id="prop-count">{{ $properties->count() }}</span></p>
            <a href="{{ route('tenant.gallery', $account) }}" class="text-xs transition" style="color: var(--primary)">View as list →</a>
        </div>
    </div>

    {{-- Map --}}
    @if($mapsKey)
    <div id="main-map" class="w-full h-full"></div>
    @else
    <div class="w-full h-full bg-gray-200 flex flex-col items-center justify-center">
        <svg class="w-20 h-20 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
        <h3 class="text-xl font-semibold text-gray-600 mb-2">Map Not Configured</h3>
        <p class="text-gray-500 text-sm mb-4">Add a Google Maps API key in Settings → Integrations.</p>
        <a href="{{ route('tenant.gallery', $account) }}" class="btn-primary px-6 py-2.5 rounded-xl font-semibold text-sm hover:opacity-90 transition">View as List Instead</a>
    </div>
    @endif
</div>
@endsection
