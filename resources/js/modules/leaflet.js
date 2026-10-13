/**
 * Lazily loads Leaflet (code-split chunk) and provides the basemap.
 */
let leafletPromise = null;

export function loadLeaflet() {
    leafletPromise ??= import('leaflet').then((module) => module.default ?? module);
    return leafletPromise;
}

/**
 * OpenStreetMap standard tiles (no API key needed). In dark mode the tile
 * pane is inverted with a CSS filter (see map.css), so a single tile set
 * serves both themes and switching themes needs no reload.
 */
const TILES = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';
const ATTRIBUTION = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>';

/** Catalonia's bounding box. */
export const CATALONIA = [
    [40.52, 0.15],
    [42.87, 3.33],
];

export function addBasemap(L, map) {
    L.tileLayer(TILES, { attribution: ATTRIBUTION, maxZoom: 19 }).addTo(map);
}

/** Run `callback` the first time `element` gets close to the viewport. */
export function whenVisible(element, callback, rootMargin = '300px') {
    if (!('IntersectionObserver' in window)) {
        callback();
        return;
    }
    const observer = new IntersectionObserver((entries) => {
        if (entries.some((e) => e.isIntersecting)) {
            observer.disconnect();
            callback();
        }
    }, { rootMargin });
    observer.observe(element);
}
