import { api } from '../lib/api.js';
import { store, apiFilters, FILTER_KEYS } from '../lib/store.js';
import { renderCard, heat } from '../lib/card.js';
import { t, plural, locale } from '../lib/i18n.js';
import { loadLeaflet, addBasemap, CATALONIA, whenVisible } from './leaflet.js';
import { setFilterSummary } from './filters.js';
import { isDark } from './theme.js';

const HEAT_ORDER = ['now', 'week', 'month', 'later', 'past'];
const DESKTOP = '(width >= 64rem)';

/**
 * Map hero. Three synchronised layers of interaction:
 *  1. Comarques (ICGC/IGN polygons) shaded by number of festivals; click to filter.
 *  2. One marker per municipality, coloured by how soon its next festival starts.
 *  3. #al-mapa: the festivals inside the current view, updated on pan/zoom.
 *     Hovering a list item highlights its marker and vice versa.
 */
export function initMap() {
    const element = document.getElementById('mapa');
    if (!element) return;

    const panel = document.getElementById('al-mapa');
    const list = panel?.querySelector(':scope > ol');
    const status = panel?.querySelector(':scope > p');
    const header = document.querySelector('#explora > header');

    // ICGC comarca code <-> slug, from the <option data-codi> of the filter.
    const codeToSlug = new Map();
    const slugToCode = new Map();
    const codeToProvince = new Map();
    const slugToName = new Map();
    document.querySelectorAll('#filtres option[data-codi]').forEach((option) => {
        codeToSlug.set(option.dataset.codi, option.value);
        slugToCode.set(option.value, option.dataset.codi);
        codeToProvince.set(option.dataset.codi, option.dataset.provincia);
        slugToName.set(option.value, option.textContent.trim());
    });
    const zone = document.getElementById('zona');

    whenVisible(element, async () => {
        const L = await loadLeaflet();
        element.replaceChildren();

        const map = L.map(element, {
            zoomSnap: 0.25,
            zoomControl: false,
            scrollWheelZoom: false,
            // Generous limits: the floating panels need room to offset the view.
            maxBounds: [
                [39.3, -2.8],
                [44.1, 6.4],
            ],
            minZoom: 7,
        });
        L.control.zoom({ position: 'bottomleft' }).addTo(map);
        addBasemap(L, map);

        /** Keep the floating panels from covering the fitted area (desktop). */
        const padding = () => {
            if (!matchMedia(DESKTOP).matches) return { padding: [24, 24] };
            const left = (header?.offsetWidth ?? 0) + 40;
            const right = (panel?.offsetWidth ?? 0) + 40;
            return { paddingTopLeft: [left, 30], paddingBottomRight: [right, 70] };
        };
        map.fitBounds(CATALONIA, padding());

        // Wheel zoom only after the user interacts with the map (no scroll-jacking).
        map.once('focus click', () => map.scrollWheelZoom.enable());

        let fiestas = [];
        let visible = [];
        let request = 0;
        let meMarker = null;
        const markers = new Map(); // municipio slug -> L.Marker

        /* ---------- 1. Comarques choropleth ---------- */

        const counts = new Map();
        const css = (name) => getComputedStyle(document.documentElement).getPropertyValue(name).trim();

        const comarcaStyle = (feature) => {
            const state = store.get();
            const code = feature.properties.codi;
            const count = counts.get(code) ?? 0;
            const max = Math.max(1, ...counts.values());
            const inProvince = !state.provincia || codeToProvince.get(code) === state.provincia;
            const selected = state.comarca ? slugToCode.get(state.comarca) === code : false;
            const dimmed = state.comarca ? !selected : !inProvince;
            const fill = count ? 0.08 + 0.4 * (count / max) : 0.015;
            const line = isDark() ? 'rgba(245,241,232,0.22)' : 'rgba(21,22,43,0.28)';

            return {
                color: selected ? css('--gold') || '#FFB703' : line,
                weight: selected ? 2.5 : 0.8,
                opacity: dimmed ? 0.35 : 1,
                fillColor: css('--gold') || '#FFB703',
                fillOpacity: selected ? 0.12 : dimmed ? fill * 0.25 : fill,
            };
        };

        const layersByCode = new Map();
        const comarques = L.geoJSON(null, {
            attribution: 'Comarques &copy; <a href="https://www.ign.es">IGN</a>',
            style: comarcaStyle,
            onEachFeature(feature, layer) {
                const { codi, nom } = feature.properties;
                layersByCode.set(codi, layer);
                layer.bindTooltip(() => `${nom} · ${plural('count', counts.get(codi) ?? 0)}`, { sticky: true, direction: 'top', offset: [0, -8] });
                layer.on({
                    mouseover: () => layer.setStyle({ weight: 2, color: css('--gold') || '#FFB703', opacity: 1 }),
                    mouseout: () => comarques.resetStyle(layer),
                    click: () => {
                        const slug = codeToSlug.get(codi);
                        if (!slug) return;
                        const current = store.get().comarca;
                        const option = document.querySelector(`#filtres option[value="${CSS.escape(slug)}"]`);
                        store.set(
                            current === slug
                                ? { comarca: '', day: null }
                                : { comarca: slug, provincia: option?.dataset.provincia ?? '', day: null },
                            'map',
                        );
                    },
                });
            },
        }).addTo(map);

        const geoReady = fetch(element.dataset.geojson)
            .then((response) => response.json())
            .then((geojson) => {
                comarques.addData(geojson);
                comarques.bringToBack();
            })
            .catch(() => {});

        /** Bounds of the selected comarca, or of all comarques of the selected province. */
        const zoneBounds = (state) => {
            let bounds = null;
            layersByCode.forEach((layer, code) => {
                const match = state.comarca
                    ? slugToCode.get(state.comarca) === code
                    : state.provincia && codeToProvince.get(code) === state.provincia;
                if (match) bounds = bounds ? bounds.extend(layer.getBounds()) : L.latLngBounds(layer.getBounds().getSouthWest(), layer.getBounds().getNorthEast());
            });
            return bounds;
        };

        /** Breadcrumb over the map: Catalunya › Província › Comarca. */
        const renderZone = (state) => {
            if (!zone) return;
            const [all, provincia, comarca] = zone.querySelectorAll('button');
            zone.hidden = !state.provincia && !state.comarca;
            provincia.textContent = state.provincia;
            provincia.hidden = !state.provincia;
            provincia.toggleAttribute('aria-current', !state.comarca);
            comarca.textContent = slugToName.get(state.comarca) ?? '';
            comarca.hidden = !state.comarca;
            all.removeAttribute('aria-current');
        };

        zone?.addEventListener('click', (event) => {
            const level = event.target.closest('button')?.dataset.zone;
            if (level === 'all') store.set({ provincia: '', comarca: '', day: null }, 'map');
            if (level === 'provincia') store.set({ comarca: '', day: null }, 'map');
            if (level === 'comarca') {
                const bounds = zoneBounds(store.get());
                if (bounds) map.flyToBounds(bounds, { ...padding(), maxZoom: 11, duration: 0.9 });
            }
        });

        document.addEventListener('themechange', () => comarques.setStyle(comarcaStyle));

        /* ---------- 2. Markers ---------- */

        const setActive = (slug, on) => {
            const marker = markers.get(slug);
            marker?.getElement()?.querySelector('b')?.toggleAttribute('data-active', on);
            marker?.setZIndexOffset(on ? 1000 : 0);
        };

        const drawMarkers = () => {
            markers.forEach((marker) => marker.remove());
            markers.clear();

            const groups = new Map();
            visible.forEach((fiesta) => {
                if (!fiesta.municipio) return;
                const key = fiesta.municipio.slug;
                if (!groups.has(key)) groups.set(key, []);
                groups.get(key).push(fiesta);
            });

            groups.forEach((items, slug) => {
                const { lat, lng, nombre } = items[0].municipio;
                const level = items.map(heat).sort((a, b) => HEAT_ORDER.indexOf(a) - HEAT_ORDER.indexOf(b))[0];
                const size = items.length > 1 ? 30 : 22;
                const label = `${nombre}: ${plural('count', items.length)}`;

                const marker = L.marker([lat, lng], {
                    icon: L.divIcon({
                        className: 'pin',
                        html: `<b data-heat="${level}">${items.length > 1 ? items.length : ''}</b>`,
                        iconSize: [size, size],
                    }),
                    keyboard: true,
                    riseOnHover: true,
                    title: label,
                    alt: nombre,
                });

                marker.bindPopup(() => {
                    const container = document.createElement('section');
                    container.setAttribute('aria-label', nombre);
                    items.forEach((fiesta) => container.append(renderCard(fiesta)));
                    return container;
                }, {
                    minWidth: 240,
                    maxWidth: 300,
                    autoPanPaddingTopLeft: [(matchMedia(DESKTOP).matches ? header?.offsetWidth ?? 0 : 0) + 30, 30],
                    autoPanPaddingBottomRight: [(matchMedia(DESKTOP).matches ? panel?.offsetWidth ?? 0 : 0) + 30, 30],
                });

                marker.on('add', () => {
                    const el = marker.getElement();
                    el?.setAttribute('aria-label', label);
                    el?.setAttribute('role', 'button');
                });
                // From far away, clicking a marker first zooms in, then opens its card.
                marker.on('click', () => {
                    if (map.getZoom() >= 10) return;
                    marker.closePopup();
                    map.once('moveend', () => marker.openPopup());
                    map.flyTo(marker.getLatLng(), 11, { duration: 0.8 });
                });
                marker.on('mouseover', () => highlightItems(slug, true));
                marker.on('mouseout', () => highlightItems(slug, false));

                marker.addTo(map);
                markers.set(slug, marker);
            });
        };

        /* ---------- 3. List of festivals inside the view ---------- */

        const monthShort = new Intl.DateTimeFormat(locale(), { month: 'short' });

        const renderItem = (fiesta) => {
            const li = document.createElement('li');
            li.dataset.municipi = fiesta.municipio.slug;
            if (fiesta.tipos?.[0]) li.style.setProperty('--c', fiesta.tipos[0].color);

            const [y, m, d] = fiesta.fecha_inicio.split('-').map(Number);
            const time = document.createElement('time');
            time.dateTime = fiesta.fecha_inicio;
            const day = document.createElement('b');
            day.textContent = d;
            time.append(day, monthShort.format(new Date(y, m - 1, d)).replace('.', ''));

            const link = document.createElement('a');
            link.href = fiesta.url;
            link.textContent = fiesta.nombre;

            const small = document.createElement('small');
            small.textContent = [fiesta.municipio.nombre, (fiesta.tipos ?? []).map((tipo) => tipo.nombre).join(', ')]
                .filter(Boolean)
                .join(' · ');

            const locate = document.createElement('button');
            locate.type = 'button';
            locate.dataset.locate = fiesta.municipio.slug;
            locate.setAttribute('aria-label', t('show_on_map', { name: fiesta.municipio.nombre }));
            locate.innerHTML = '<svg aria-hidden="true" focusable="false" width="18" height="18"><use href="#i-pin"/></svg>';

            li.append(time, link, small, locate);
            return li;
        };

        const renderList = () => {
            if (!list) return;
            const bounds = map.getBounds();
            const inView = visible
                .filter((f) => f.municipio && bounds.contains([f.municipio.lat, f.municipio.lng]))
                .sort((a, b) => a.fecha_inicio.localeCompare(b.fecha_inicio));

            status.textContent = inView.length ? plural('in_view', inView.length) : t('in_view_empty');

            if (!inView.length) {
                const li = document.createElement('li');
                li.textContent = t('in_view_hint');
                list.replaceChildren(li);
                return;
            }
            list.replaceChildren(...inView.slice(0, 120).map(renderItem));
        };

        const highlightItems = (slug, on) => {
            if (!list) return;
            const items = list.querySelectorAll(`li[data-municipi="${CSS.escape(slug)}"]`);
            items.forEach((li) => li.toggleAttribute('data-active', on));
            const first = items[0];
            if (on && first) {
                // Scroll inside the list only (never the page).
                const top = first.offsetTop - list.offsetTop;
                if (top < list.scrollTop || top > list.scrollTop + list.clientHeight - first.offsetHeight) {
                    list.scrollTo({ top: top - 8, behavior: 'smooth' });
                }
            }
        };

        list?.addEventListener('mouseover', (event) => {
            const li = event.target.closest('li[data-municipi]');
            if (li) setActive(li.dataset.municipi, true);
        });
        list?.addEventListener('mouseout', (event) => {
            const li = event.target.closest('li[data-municipi]');
            if (li && !li.contains(event.relatedTarget)) setActive(li.dataset.municipi, false);
        });
        list?.addEventListener('focusin', (event) => {
            const li = event.target.closest('li[data-municipi]');
            if (li) setActive(li.dataset.municipi, true);
        });
        list?.addEventListener('focusout', (event) => {
            const li = event.target.closest('li[data-municipi]');
            if (li) setActive(li.dataset.municipi, false);
        });
        list?.addEventListener('click', (event) => {
            const button = event.target.closest('button[data-locate]');
            const marker = button && markers.get(button.dataset.locate);
            if (!marker) return;
            map.once('moveend', () => marker.openPopup());
            map.flyTo(marker.getLatLng(), Math.max(map.getZoom(), 11), { duration: 0.8 });
        });

        let frame = 0;
        map.on('moveend', () => {
            cancelAnimationFrame(frame);
            frame = requestAnimationFrame(renderList);
        });

        /* ---------- Data ---------- */

        const draw = (state, { fit = true } = {}) => {
            visible = state.day ? fiestas.filter((f) => f.fecha_inicio <= state.day && f.fecha_fin >= state.day) : fiestas;

            counts.clear();
            visible.forEach((f) => {
                const code = f.comarca?.codi;
                if (code) counts.set(code, (counts.get(code) ?? 0) + 1);
            });
            comarques.setStyle(comarcaStyle);

            drawMarkers();
            setFilterSummary(plural('count', visible.length));

            renderZone(state);
            if (fit) fitView(state);
            renderList();
        };

        /** Zoom to the selected zone (comarca / province) or to the visible festivals. */
        const fitView = async (state) => {
            if (state.comarca || state.provincia) {
                await geoReady;
                const bounds = zoneBounds(state);
                if (bounds) {
                    map.flyToBounds(bounds, { ...padding(), maxZoom: state.comarca ? 11 : 9, duration: 1 });
                    return;
                }
            }
            const points = visible.filter((f) => f.municipio).map((f) => [f.municipio.lat, f.municipio.lng]);
            if (!points.length || (!state.mes && !state.tipos.length && !state.day)) {
                map.flyToBounds(CATALONIA, { ...padding(), duration: 1 });
            } else if (points.length > 1) {
                map.flyToBounds(points, { ...padding(), maxZoom: 10, duration: 0.9 });
            } else {
                map.flyTo(points[0], 11, { duration: 0.9 });
            }
        };

        const load = async (state, options) => {
            const id = ++request;
            list?.setAttribute('aria-busy', 'true');
            try {
                const data = await api.fiestas(apiFilters(state));
                if (id !== request) return;
                fiestas = data;
                draw(state, options);
            } catch {
                setFilterSummary(t('map_error'));
            } finally {
                list?.setAttribute('aria-busy', 'false');
            }
        };

        const showMe = ({ lat, lng }) => {
            meMarker?.remove();
            meMarker = L.marker([lat, lng], {
                icon: L.divIcon({ className: 'pin-me', html: '<b></b>', iconSize: [18, 18] }),
                title: t('you_are_here'),
                keyboard: false,
                zIndexOffset: 1000,
            }).addTo(map);
        };

        store.subscribe((state, changed) => {
            if (changed.some((k) => FILTER_KEYS.includes(k))) {
                load(state);
            } else if (changed.includes('day')) {
                draw(state);
            }
            if (changed.includes('geo') && state.geo) {
                showMe(state.geo);
                map.flyTo([state.geo.lat, state.geo.lng], 10, { duration: 1 });
            }
            if (changed.includes('focus') && state.focus) {
                map.flyTo([state.focus.lat, state.focus.lng], state.focus.zoom ?? 12, { duration: 1 });
                element.focus({ preventScroll: true });
                store.set({ focus: null }, 'map');
            }
        });

        const initial = store.get();
        if (initial.geo) showMe(initial.geo);
        // First load keeps the whole of Catalonia in view unless filters came in the URL.
        load(initial, { fit: Boolean(initial.comarca || initial.provincia || initial.tipos.length || initial.mes) });
    }, '0px');
}
