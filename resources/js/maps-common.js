// What both map pages need: the dark style sheet, the brand pin, the config element and
// the Maps API loader.
//
// The API is loaded from JavaScript rather than by a script tag in the view. The tag used
// &callback=initMap, which needs window.initMap to exist before an async script runs -- a
// race these modules would have lost, since they are deferred too. Loading it here removes
// the ordering question entirely, and lets us pass loading=async, which the tag did not and
// which Google warns about on every page load.
//
// Markers are still google.maps.Marker, which Google has deprecated in favour of
// AdvancedMarkerElement. Migrating needs a Map ID and changes how the pin below is drawn,
// so it is deliberately left for its own change.

const DARK_MAP_STYLES = [
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

/** Map styling follows the dark-mode class on <html>, with no reload. */
export function getMapStyles() {
    return document.documentElement.classList.contains('dark') ? DARK_MAP_STYLES : [];
}

export function getBrandMarkerIcon() {
    // A data-URI SVG rather than a google.maps.Symbol path. The Symbol path parser in newer
    // marker.js builds chokes on the lowercase 'z' close-path command ("Expected number at
    // position 109, found z") even though z is valid SVG, so the pin simply never drew. The
    // data-URI route goes through the browser's own SVG renderer and keeps the per-tenant
    // primary colour via string substitution.
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

/** The JSON a map view hands its module on the root element's data-config. */
export function readConfig(root) {
    try {
        return JSON.parse(root?.dataset.config || '{}');
    } catch (e) {
        return {};
    }
}

let loads = 0;

export function loadMapsApi(key, { libraries = [] } = {}) {
    return new Promise((resolve, reject) => {
        // A callback name per call. Google needs a global to call back into, and a single
        // shared name would have two callers on one page overwriting each other's resolve.
        const name = `__busyMapsReady${++loads}`;
        const settle = (fn) => (arg) => {
            delete window[name];
            fn(arg);
        };
        window[name] = settle(resolve);

        const params = new URLSearchParams({ key, loading: 'async', callback: name });
        if (libraries.length) params.set('libraries', libraries.join(','));

        const s = document.createElement('script');
        s.src = `https://maps.googleapis.com/maps/api/js?${params}`;
        s.async = true;
        s.onerror = settle(reject);
        document.head.appendChild(s);
    });
}
