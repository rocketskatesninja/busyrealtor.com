// A single listing's map and Street View panel. The dark style sheet, the brand pin and
// the API loader are shared with the gallery map -- see maps-common.js.

import { getBrandMarkerIcon, getMapStyles, loadMapsApi, readConfig } from './maps-common';

const root = document.getElementById('property-map-root');
const cfg = readConfig(root);

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
    loadMapsApi(cfg.key, { libraries: ['marker'] }).then(initPropertyMap).catch(() => {
        // Without the API there is no map; the rest of the listing is unaffected.
    });
}
