import { api } from '../lib/api.js';
import { store } from '../lib/store.js';
import { renderCardItems } from '../lib/card.js';
import { t, plural } from '../lib/i18n.js';

/**
 * "A prop teu": asks for the user's position (only on demand, or silently
 * when permission was already granted) and lists festivals by distance.
 * Distances are computed server-side with the Haversine formula.
 */
export function initNearby() {
    const section = document.getElementById('aprop');
    const button = document.querySelector('#cerca [data-geo]');
    if (!section || !button) return;

    const list = section.querySelector(':scope > ol');
    const status = section.querySelector(':scope > header > p');
    const radius = section.querySelector('select[name="radio"]');
    let position = null;

    const skeleton = () => {
        list.setAttribute('aria-busy', 'true');
        list.replaceChildren(
            ...Array.from({ length: 3 }, () => {
                const li = document.createElement('li');
                li.setAttribute('aria-hidden', 'true');
                const card = document.createElement('article');
                card.className = 'fiesta';
                li.append(card);
                return li;
            }),
        );
    };

    const load = async () => {
        if (!position) return;
        const km = Number(radius.value);
        skeleton();
        try {
            const fiestas = await api.cercanas(position.lat, position.lng, km);
            if (!fiestas.length) {
                const li = document.createElement('li');
                const p = document.createElement('p');
                p.textContent = t('nearby_empty', { km });
                li.append(p);
                list.replaceChildren(li);
                status.textContent = t('nearby_empty', { km });
            } else {
                list.replaceChildren(...renderCardItems(fiestas, { distance: true }));
                status.textContent = plural('nearby_found', fiestas.length, { km });
            }
        } catch {
            list.replaceChildren();
            status.textContent = t('map_error');
        } finally {
            list.setAttribute('aria-busy', 'false');
        }
    };

    const locate = ({ scroll = true } = {}) => {
        if (!('geolocation' in navigator)) {
            section.hidden = false;
            status.textContent = t('geo_unsupported');
            list.replaceChildren();
            return;
        }

        button.setAttribute('aria-busy', 'true');
        section.hidden = false;
        status.textContent = t('geo_locating');
        skeleton();
        if (scroll) section.scrollIntoView({ behavior: 'smooth', block: 'start' });

        navigator.geolocation.getCurrentPosition(
            ({ coords }) => {
                button.removeAttribute('aria-busy');
                position = { lat: coords.latitude, lng: coords.longitude };
                store.set({ geo: position }, 'nearby');
                load();
            },
            (error) => {
                button.removeAttribute('aria-busy');
                list.replaceChildren();
                list.setAttribute('aria-busy', 'false');
                status.textContent = error.code === error.PERMISSION_DENIED ? t('geo_denied') : t('geo_error');
            },
            { enableHighAccuracy: false, timeout: 12000, maximumAge: 10 * 60 * 1000 },
        );
    };

    button.addEventListener('click', () => locate());
    radius.addEventListener('change', load);

    // If the user already granted geolocation in a previous visit, use it straight away.
    navigator.permissions?.query({ name: 'geolocation' })
        .then((permission) => {
            if (permission.state === 'granted') locate({ scroll: false });
        })
        .catch(() => {});
}
