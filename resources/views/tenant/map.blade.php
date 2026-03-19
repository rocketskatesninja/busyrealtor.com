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
        'address'    => $p->address . ($p->address_line_2 ? ' ' . $p->address_line_2 : ''),
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
                '<div style="width:240px;font-family:system-ui,-apple-system,sans-serif;border-radius:8px;overflow:hidden">' +
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
    var status     = form.querySelector('[name=status]')?.value || '';
    var priceMin   = parseFloat(form.querySelector('[name=price_min]').value) || 0;
    var priceMax   = parseFloat(form.querySelector('[name=price_max]').value) || Infinity;
    var beds       = parseFloat(form.querySelector('[name=beds]').value) || 0;
    var baths      = parseFloat(form.querySelector('[name=baths]').value) || 0;
    var sqftMin    = parseFloat(form.querySelector('[name=sqft_min]').value) || 0;
    var sqftMax    = parseFloat(form.querySelector('[name=sqft_max]').value) || Infinity;
    var yearMin    = parseInt(form.querySelector('[name=year_min]')?.value) || 0;
    var yearMax    = parseInt(form.querySelector('[name=year_max]')?.value) || Infinity;
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

// Mobile drawer: copy values from mobile form into desktop form, then apply
function applyMapFilterFromMobile() {
    var mobileForm  = document.getElementById('mobile-map-filter');
    var desktopForm = document.getElementById('map-filter');
    if (!mobileForm || !desktopForm) return;
    var fields = ['type','status','price_min','price_max','beds','baths','sqft_min','sqft_max','year_min','year_max','garage_spaces','hoa','hoa_max'];
    fields.forEach(function(name) {
        var src  = mobileForm.querySelector('[name=' + name + ']');
        var dest = desktopForm.querySelector('[name=' + name + ']');
        if (src && dest) dest.value = src.value;
    });
    // checkboxes
    var destChecks = desktopForm.querySelectorAll('[name="features[]"]');
    destChecks.forEach(function(cb) { cb.checked = false; });
    mobileForm.querySelectorAll('[name="features[]"]:checked').forEach(function(cb) {
        var dest = desktopForm.querySelector('[name="features[]"][value="' + cb.value + '"]');
        if (dest) dest.checked = true;
    });
    applyMapFilter();
    // close the Alpine drawer
    var wrapper = document.querySelector('[x-data*="mobileOpen"]');
    if (wrapper) {
        Alpine.$data(wrapper).mobileOpen = false;
    }
}
</script>
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
<script src="https://maps.googleapis.com/maps/api/js?key={{ $mapsKey }}&callback=initMap" async defer></script>
@endif
<script>
document.addEventListener('DOMContentLoaded', function () {
    var panel  = document.getElementById('map-filter-panel');
    var handle = document.getElementById('map-filter-handle');
    if (!panel || !handle) return;
    var dragging = false, startX, startY, startLeft, startTop;
    handle.addEventListener('mousedown', function (e) {
        if (e.target.closest('button')) return;
        dragging  = true;
        startX    = e.clientX;
        startY    = e.clientY;
        startLeft = panel.offsetLeft;
        startTop  = panel.offsetTop;
        handle.style.cursor = 'grabbing';
        e.preventDefault();
    });
    document.addEventListener('mousemove', function (e) {
        if (!dragging) return;
        var container = panel.parentElement;
        var maxLeft = container.offsetWidth  - panel.offsetWidth;
        var maxTop  = container.offsetHeight - panel.offsetHeight;
        panel.style.left = Math.max(0, Math.min(maxLeft, startLeft + e.clientX - startX)) + 'px';
        panel.style.top  = Math.max(0, Math.min(maxTop,  startTop  + e.clientY - startY)) + 'px';
    });
    document.addEventListener('mouseup', function () {
        if (!dragging) return;
        dragging = false;
        handle.style.cursor = 'grab';
    });
});
</script>
@endsection

@section('content')
@php
$activeFilters = collect(['type','status','price_min','price_max','beds','baths','sqft_min','sqft_max','year_min','year_max','garage_spaces','hoa','hoa_max'])
    ->filter(fn($k) => request($k) !== null && request($k) !== '')->count()
    + count(request('features', []));
@endphp
<div class="relative" style="height: calc(100vh - 80px)" x-data="{ mobileOpen: false }">
    {{-- Desktop Map Filter Panel (hidden on mobile) --}}
    <div id="map-filter-panel" class="hidden md:flex absolute top-16 left-4 z-10 bg-white rounded-2xl shadow-xl w-72 max-h-[calc(100vh-120px)] flex-col">
        <div id="map-filter-handle" class="flex items-center gap-2 p-4 flex-shrink-0 cursor-grab select-none">
            <svg class="w-4 h-4 text-gray-300 flex-shrink-0" viewBox="0 0 16 16" fill="currentColor"><circle cx="4" cy="3" r="1.5"/><circle cx="12" cy="3" r="1.5"/><circle cx="4" cy="8" r="1.5"/><circle cx="12" cy="8" r="1.5"/><circle cx="4" cy="13" r="1.5"/><circle cx="12" cy="13" r="1.5"/></svg>
            <button type="button" onclick="applyMapFilter()" class="btn-primary flex-1 py-2 rounded-xl font-semibold text-sm hover:opacity-90 transition">Apply Filters</button>
            <button type="button" onclick="clearMapFilter()" class="px-3 py-2 rounded-xl text-sm font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition">Clear</button>
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
        <svg class="w-20 h-20 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
        <h3 class="text-xl font-semibold text-gray-600 mb-2">Map Not Configured</h3>
        <p class="text-gray-500 text-sm mb-4">Add a Google Maps API key in Settings → Integrations.</p>
        <a href="{{ route('tenant.gallery', $account) }}" class="btn-primary px-6 py-2.5 rounded-xl font-semibold text-sm hover:opacity-90 transition">View as List Instead</a>
    </div>
    @endif
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
                <h2 class="text-lg font-bold text-gray-900">Filter Map</h2>
                <button @click="mobileOpen = false" class="p-2 rounded-lg hover:bg-gray-100 text-gray-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
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
                        onclick="clearMapFilter(); Alpine.$data(document.querySelector('[x-data*="mobileOpen"]')).mobileOpen = false"
                        class="flex-1 py-3 text-center border border-gray-300 rounded-xl font-medium text-gray-700 hover:bg-gray-100 transition">Clear All</button>
                <button type="button"
                        onclick="applyMapFilterFromMobile()"
                        class="flex-1 py-3 text-center rounded-xl font-semibold text-white transition hover:opacity-90"
                        style="background-color: var(--primary)">Apply Filters</button>
            </div>
        </div>
    </div>
</div>
@endsection
