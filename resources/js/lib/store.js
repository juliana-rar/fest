/**
 * Shared state that keeps the map and the calendar in sync.
 *
 *   provincia, comarca, mes  → public filters (also reflected in the URL)
 *   tipos                    → selected festival types (slugs, OR semantics)
 *   calMonth                 → month shown by the calendar ("YYYY-MM")
 *   day                      → day selected in the calendar ("YYYY-MM-DD" | null)
 *   geo                      → { lat, lng } once the user shares the location
 */
const params = new URLSearchParams(window.location.search);
const today = new Date();
const currentMonth = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}`;

const state = {
    provincia: params.get('provincia') || '',
    comarca: params.get('comarca') || '',
    mes: params.get('mes') || '',
    tipos: (params.get('tipo') || '').split(',').filter(Boolean),
    calMonth: params.get('mes') || currentMonth,
    day: null,
    geo: null,
    focus: null,
};

const listeners = new Set();

export const store = {
    get: () => ({ ...state }),

    /**
     * @param {Partial<typeof state>} patch
     * @param {string} source  Who triggered the change (listeners can ignore their own updates).
     */
    set(patch, source = 'unknown') {
        const changed = Object.keys(patch).filter((key) => !same(state[key], patch[key]));
        if (!changed.length) return;
        Object.assign(state, patch);
        syncUrl();
        listeners.forEach((fn) => fn({ ...state }, changed, source));
    },

    subscribe(fn) {
        listeners.add(fn);
        return () => listeners.delete(fn);
    },
};

function same(a, b) {
    return Array.isArray(a) || Array.isArray(b) ? (a ?? []).join(',') === (b ?? []).join(',') : a === b;
}

function syncUrl() {
    const url = new URL(window.location.href);
    ['provincia', 'comarca', 'mes'].forEach((key) => {
        if (state[key]) url.searchParams.set(key, state[key]);
        else url.searchParams.delete(key);
    });
    if (state.tipos.length) url.searchParams.set('tipo', state.tipos.join(','));
    else url.searchParams.delete('tipo');
    history.replaceState(history.state, '', url);
}

/** Filter keys whose change requires reloading data. */
export const FILTER_KEYS = ['provincia', 'comarca', 'mes', 'tipos'];

/** Filters for the API, optionally overriding the month. */
export function apiFilters(s = state, mes = s.mes) {
    return { provincia: s.provincia, comarca: s.comarca, mes, tipo: s.tipos.join(',') };
}
