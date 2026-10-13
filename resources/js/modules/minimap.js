import { loadLeaflet, addBasemap, whenVisible } from './leaflet.js';

/** Small location map on the festival page. */
export function initMiniMap() {
    const element = document.getElementById('minimapa');
    if (!element) return;

    whenVisible(element, async () => {
        const L = await loadLeaflet();
        const lat = Number(element.dataset.lat);
        const lng = Number(element.dataset.lng);

        const map = L.map(element, {
            center: [lat, lng],
            zoom: 12,
            scrollWheelZoom: false,
            attributionControl: true,
        });
        addBasemap(L, map);

        L.marker([lat, lng], {
            icon: L.divIcon({ className: 'pin', html: '<b data-heat="week"></b>', iconSize: [24, 24] }),
            title: element.dataset.nom,
            keyboard: false,
        }).addTo(map);
    });
}
