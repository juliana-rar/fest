/**
 * Tiny i18n helper. Strings come from <script type="application/json" id="i18n">
 * rendered by Blade from lang/{locale}/app.php → 'js'.
 */
let strings = null;

function load() {
    if (strings) return strings;
    try {
        strings = JSON.parse(document.getElementById('i18n')?.textContent || '{}');
    } catch {
        strings = {};
    }
    return strings;
}

/** t('days_left_other', { n: 3 }) → "Falten 3 dies" */
export function t(key, vars = {}) {
    const value = load()[key] ?? key;
    return value.replace(/\{(\w+)\}/g, (_, name) => (name in vars ? vars[name] : `{${name}}`));
}

/** Picks "<key>_one" or "<key>_other" based on n. */
export function plural(key, n, vars = {}) {
    const one = `${key}_one`;
    const k = n === 1 && one in load() ? one : (`${key}_other` in load() ? `${key}_other` : key);
    return t(k, { n, ...vars });
}

export const locale = () => load().locale || document.documentElement.lang || 'ca';

export const lang = () => document.documentElement.lang || 'ca';

const numberFormat = new Map();

export function formatNumber(n, digits = 1) {
    const key = `${locale()}-${digits}`;
    if (!numberFormat.has(key)) {
        numberFormat.set(key, new Intl.NumberFormat(locale(), { maximumFractionDigits: digits }));
    }
    return numberFormat.get(key).format(n);
}

/** "dimarts, 13 d’octubre" */
export function formatDay(isoDate, options = { weekday: 'long', day: 'numeric', month: 'long' }) {
    return new Intl.DateTimeFormat(locale(), options).format(parseISODate(isoDate));
}

export function formatMonth(year, month) {
    const label = new Intl.DateTimeFormat(locale(), { month: 'long', year: 'numeric' }).format(new Date(year, month - 1, 1));
    return label.charAt(0).toUpperCase() + label.slice(1);
}

/** "2026-10-13" → local Date at midnight (no timezone shift). */
export function parseISODate(iso) {
    const [y, m, d] = iso.split('-').map(Number);
    return new Date(y, m - 1, d);
}

export function toISODate(date) {
    const pad = (n) => String(n).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}
