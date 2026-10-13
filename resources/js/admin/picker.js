import { loadLeaflet, addBasemap, CATALONIA } from '../modules/leaflet.js';

/**
 * Latitude/longitude picker: click on the map (or drag the marker) to fill
 * the lat/lng inputs; typing coordinates moves the marker.
 */
export async function initPicker() {
    const element = document.querySelector('[data-picker]');
    if (!element) return;

    const form = element.closest('form');
    const lat = form.elements.lat;
    const lng = form.elements.lng;

    const L = await loadLeaflet();
    const map = L.map(element, { scrollWheelZoom: true }).fitBounds(CATALONIA);
    addBasemap(L, map);

    const start = [Number(lat.value) || 41.8, Number(lng.value) || 1.7];
    const marker = L.marker(start, {
        draggable: true,
        icon: L.divIcon({ className: 'pin', html: '<b data-heat="now"></b>', iconSize: [26, 26] }),
        keyboard: true,
        title: element.getAttribute('aria-label'),
    }).addTo(map);

    if (lat.value && lng.value) map.setView(start, 11);

    const write = ({ lat: la, lng: ln }) => {
        lat.value = la.toFixed(6);
        lng.value = ln.toFixed(6);
    };

    map.on('click', (event) => {
        marker.setLatLng(event.latlng);
        write(event.latlng);
    });
    marker.on('dragend', () => write(marker.getLatLng()));

    [lat, lng].forEach((input) =>
        input.addEventListener('change', () => {
            const point = [Number(lat.value), Number(lng.value)];
            if (point.every(Number.isFinite)) {
                marker.setLatLng(point);
                map.panTo(point);
            }
        }),
    );
}
