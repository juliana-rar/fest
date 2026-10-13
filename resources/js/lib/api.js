import { lang } from './i18n.js';

/**
 * Thin fetch wrapper for the internal JSON API.
 * Identical in-flight/recent GET requests are de-duplicated, so the map and
 * the calendar can ask for the same month without hitting the server twice.
 */
const cache = new Map();
const TTL = 60_000;

export async function getJSON(path, params = {}) {
    const url = new URL(path, window.location.origin);
    Object.entries({ ...params, lang: lang() }).forEach(([key, value]) => {
        if (value !== '' && value !== null && value !== undefined) url.searchParams.set(key, value);
    });

    const key = url.toString();
    const hit = cache.get(key);
    if (hit && Date.now() - hit.time < TTL) return hit.promise;

    const promise = fetch(key, { headers: { Accept: 'application/json' } }).then(async (response) => {
        if (!response.ok) {
            cache.delete(key);
            throw new Error(`HTTP ${response.status}`);
        }
        return response.json();
    });

    cache.set(key, { promise, time: Date.now() });
    return promise;
}

export function postJSON(path) {
    return fetch(path, {
        method: 'POST',
        headers: { Accept: 'application/json' },
        keepalive: true,
    }).catch(() => null);
}

export const api = {
    fiestas: (filters) => getJSON('/api/fiestas', filters).then((r) => r.data),
    proximas: (limit = 12) => getJSON('/api/fiestas/proximas', { limit }).then((r) => r.data),
    cercanas: (lat, lng, radio = 50) => getJSON('/api/fiestas/cercanas', { lat, lng, radio }).then((r) => r.data),
    municipios: (q) => getJSON('/api/municipios', { q }).then((r) => r.data),
};
