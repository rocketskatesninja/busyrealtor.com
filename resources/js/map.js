// The property map: the Google Maps setup, the marker layer, the filter panel and the
// draggable panel handle.
//
// The Maps API is loaded from here rather than by a script tag in the view. The tag used
// &callback=initMap, which needs window.initMap to exist before an async script runs --
// a race this module would have lost, since it is deferred too. Loading it here removes
// the ordering question entirely, and lets us pass loading=async, which the tag did not
// and which Google warns about on every page load.
//
// Markers are still google.maps.Marker, which Google has deprecated in favour of
// AdvancedMarkerElement. Migrating needs a Map ID and changes how the custom per-tenant
// pin is drawn, so it is deliberately left for its own change.

const root = document.getElementById('map-root');

function config() {
    try {
        return JSON.parse(root?.dataset.config || '{}');
    } catch (e) {
        return {};
    }
}

const cfg = config();

function closeDrawer() {
    // By id, not by matching the text of an x-data attribute.
    try {
        if (root && window.Alpine) window.Alpine.$data(root).mobileOpen = false;
    } catch (e) {
        // The drawer only exists on small screens.
    }
}

function loadMapsApi(key) {
    return new Promise((resolve, reject) => {
        window.__busyMapReady = resolve;
        const s = document.createElement('script');
        s.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(key)}&loading=async&callback=__busyMapReady`;
        s.async = true;
        s.onerror = reject;
        document.head.appendChild(s);
    });
}

var _allMarkers = [];
var _map = null;

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
    // Use a data-URI SVG instead of google.maps.Symbol path. The Symbol
    // path parser in newer marker.js builds chokes on the lowercase 'z'
    // close-path command ("Expected number at position 109, found z"),
    // even though z is valid SVG. The data-URI route uses the browser's
    // native SVG renderer so the parser bug is bypassed, and we still get
    // dynamic per-tenant primary-color theming via string substitution.
    var primary = getComputedStyle(document.documentElement).getPropertyValue('--primary').trim() || '#3b82f6';
    var svg =
        '<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24">' +
            '<path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 110-5 2.5 2.5 0 010 5z" ' +
                  'fill="' + primary + '" stroke="#ffffff" stroke-width="1.2"/>' +
        '</svg>';
    return {
        url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg),
        scaledSize: new google.maps.Size(36, 36),
        anchor:     new google.maps.Point(18, 36)
    };
}

function initMap() {
    var mapEl = document.getElementById('main-map');
    if (!mapEl) return;
    _map = new google.maps.Map(mapEl, { zoom: 11, center: { lat: 33.749, lng: -84.388 }, styles: getMapStyles() });

    // React to dark-mode toggle without reloading
    new MutationObserver(function() {
        _map.setOptions({ styles: getMapStyles() });
        var icon = getBrandMarkerIcon();
        _allMarkers.forEach(function(m) { m.marker.setIcon(icon); });
    }).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

    var propertiesData = cfg.properties;
    var infoWindow = new google.maps.InfoWindow();
    var bounds = new google.maps.LatLngBounds();
    propertiesData.forEach(function(p) {
        if (!p.lat || !p.lng) return;
        var marker = new google.maps.Marker({ map: _map, position: { lat: p.lat, lng: p.lng }, title: p.title, icon: getBrandMarkerIcon() });
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
                (p.image ? '<img src="'+p.image+'" loading="lazy" style="width:100%;height:130px;object-fit:cover;display:block" onerror="this.style.display=\'none\'">' : '') +
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
    closeDrawer();
}

// ── Filter panel controls ────────────────────────────────────────────────────
document.addEventListener('click', (e) => {
    if (e.target.closest?.('[data-map-apply]')) return applyMapFilter();
    if (e.target.closest?.('[data-map-clear]')) return clearMapFilter();
    if (e.target.closest?.('[data-map-apply-mobile]')) return applyMapFilterFromMobile();
    if (e.target.closest?.('[data-map-clear-mobile]')) {
        clearMapFilter();
        closeDrawer();
    }
});

// ── Draggable filter panel ───────────────────────────────────────────────────
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

if (root && cfg.key) {
    loadMapsApi(cfg.key).then(initMap).catch(() => {
        // Nothing to draw without the API; the page still lists the filters.
    });
}
