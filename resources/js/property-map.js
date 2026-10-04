// A single listing's map and Street View panel.
//
// The dark style sheet, the pin and the loader are duplicated from map.js. Sharing them
// is worth doing and is deliberately not done here -- that is an edit to a working file,
// and this change is already a move.

const root = document.getElementById('property-map-root');

function config() {
    try {
        return JSON.parse(root?.dataset.config || '{}');
    } catch (e) {
        return {};
    }
}

const cfg = config();

function loadMapsApi(key) {
    return new Promise((resolve, reject) => {
        window.__busyPropertyMapReady = resolve;
        const s = document.createElement('script');
        s.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(key)}`
            + '&loading=async&libraries=marker&callback=__busyPropertyMapReady';
        s.async = true;
        s.onerror = reject;
        document.head.appendChild(s);
    });
}

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
    // A data-URI SVG rather than a google.maps.Symbol path, the same fix map.blade.php
    // already carries: the Symbol path parser rejects the lowercase 'z' in this path
    // ("Expected number at position 109, found z"), so the listing's pin never drew.
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

function initPropertyMap() {
    var loc = { lat: cfg.lat, lng: cfg.lng };

    var map = new google.maps.Map(document.getElementById('propertyMap'), {
        center: loc, zoom: 15,
        mapTypeControl: false, streetViewControl: true, fullscreenControl: true,
        styles: getMapStyles()
    });
    var marker = new google.maps.Marker({ position: loc, map: map, title: cfg.title, icon: getBrandMarkerIcon() });

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

if (root && cfg.key && cfg.lat && cfg.lng) {
    loadMapsApi(cfg.key).then(initPropertyMap).catch(() => {
        // Without the API there is no map; the rest of the listing is unaffected.
    });
}
