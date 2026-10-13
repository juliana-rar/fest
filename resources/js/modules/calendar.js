import { api } from '../lib/api.js';
import { store, apiFilters } from '../lib/store.js';
import { t, plural, formatDay, formatMonth, toISODate, parseISODate } from '../lib/i18n.js';

/**
 * Month calendar built without libraries: a 7-column CSS Grid of day buttons.
 *  - Days with festivals show a counter and a heat background (--n).
 *  - Roving tabindex + arrow keys / Home / End / PageUp / PageDown.
 *  - Month and list views; selected day lists its festivals and filters the map.
 */
export function initCalendar() {
    const root = document.getElementById('calendari');
    if (!root) return;

    const title = root.querySelector(':scope > header > h3');
    const grid = root.querySelector(':scope > ol');
    const list = root.querySelector(':scope > ul');
    const weekdays = root.querySelector(':scope > p');
    const aside = root.querySelector(':scope > aside');
    const asideTitle = aside.querySelector('h4');
    const asideList = aside.querySelector('ul');
    const today = root.dataset.today || toISODate(new Date());

    let fiestas = [];
    let request = 0;
    let shownMonth = store.get().calMonth;
    let focusAfterRender = null;

    const monthParts = (value) => value.split('-').map(Number);

    const fiestasOn = (iso) => fiestas.filter((f) => f.fecha_inicio <= iso && f.fecha_fin >= iso);

    /* ---------- Rendering ---------- */

    const renderGrid = (state) => {
        const [year, month] = monthParts(state.calMonth);
        const first = new Date(year, month - 1, 1);
        const daysInMonth = new Date(year, month, 0).getDate();
        const start = ((first.getDay() + 6) % 7) + 1; // Monday-first column

        const selected = state.day && state.day.startsWith(state.calMonth) ? state.day : null;
        const focusable = selected || (today.startsWith(state.calMonth) ? today : `${state.calMonth}-01`);

        const items = [];
        for (let d = 1; d <= daysInMonth; d++) {
            const iso = toISODate(new Date(year, month - 1, d));
            const count = fiestasOn(iso).length;

            const li = document.createElement('li');
            if (d === 1) li.style.setProperty('--start', start);

            const button = document.createElement('button');
            button.type = 'button';
            button.dataset.date = iso;
            button.tabIndex = iso === focusable ? 0 : -1;
            button.setAttribute('aria-pressed', String(iso === selected));
            button.setAttribute(
                'aria-label',
                `${formatDay(iso)}${iso === today ? ` (${t('cal_today')})` : ''}: ${plural('count', count)}`,
            );
            if (iso === today) button.setAttribute('aria-current', 'date');
            if (iso < today) button.dataset.past = '';
            if (count) button.style.setProperty('--n', count);

            button.append(String(d));
            if (count) {
                const small = document.createElement('small');
                small.textContent = count;
                small.setAttribute('aria-hidden', 'true');
                button.append(small);
            }

            li.append(button);
            items.push(li);
        }

        grid.replaceChildren(...items);
        title.textContent = formatMonth(year, month);
    };

    const renderList = () => {
        if (!fiestas.length) {
            const li = document.createElement('li');
            li.textContent = t('cal_month_empty');
            list.replaceChildren(li);
            return;
        }
        list.replaceChildren(
            ...fiestas.map((fiesta) => {
                const li = document.createElement('li');
                const time = document.createElement('time');
                time.dateTime = fiesta.fecha_inicio;
                time.textContent = parseISODate(fiesta.fecha_inicio).getDate();
                const a = document.createElement('a');
                a.href = fiesta.url;
                a.textContent = fiesta.nombre;
                const small = document.createElement('small');
                small.textContent = `${fiesta.municipio?.nombre ?? ''} · ${fiesta.rango}`;
                li.append(time, a, small);
                return li;
            }),
        );
    };

    const renderDay = (iso) => {
        if (!iso) {
            asideTitle.textContent = asideTitle.dataset.default ??= asideTitle.textContent;
            asideList.replaceChildren();
            return;
        }
        const items = fiestasOn(iso);
        const date = formatDay(iso);
        asideTitle.textContent = items.length
            ? plural('cal_day', items.length, { date })
            : t('cal_day_none', { date });

        asideList.replaceChildren(
            ...items.map((fiesta) => {
                const li = document.createElement('li');
                const a = document.createElement('a');
                a.href = fiesta.url;
                a.append(fiesta.nombre);
                const small = document.createElement('small');
                small.textContent = fiesta.municipio?.nombre ?? '';
                a.append(small);
                li.append(a);
                return li;
            }),
        );
    };

    const render = (state) => {
        renderGrid(state);
        renderList();
        renderDay(state.day && state.day.startsWith(state.calMonth) ? state.day : null);

        if (focusAfterRender) {
            grid.querySelector(`[data-date="${focusAfterRender}"]`)?.focus();
            focusAfterRender = null;
        }
    };

    const load = async (state, direction = 0) => {
        const id = ++request;
        grid.setAttribute('aria-busy', 'true');
        try {
            const data = await api.fiestas(apiFilters(state, state.calMonth));
            if (id !== request) return;
            fiestas = data;

            const swap = () => render(store.get());
            if (direction && document.startViewTransition && !matchMedia('(prefers-reduced-motion: reduce)').matches) {
                document.documentElement.style.setProperty('--cal-dir', direction);
                document.startViewTransition(swap);
            } else {
                swap();
            }
        } catch {
            asideTitle.textContent = t('map_error');
        } finally {
            grid.setAttribute('aria-busy', 'false');
        }
    };

    /* ---------- Navigation ---------- */

    const shiftMonth = (value, delta) => {
        const [year, month] = monthParts(value);
        const date = new Date(year, month - 1 + delta, 1);
        return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
    };

    const goToMonth = (calMonth, focusDate = null) => {
        focusAfterRender = focusDate;
        const state = store.get();
        // Keep the month filter (and therefore the map) in sync with the calendar.
        store.set({ calMonth, mes: calMonth, day: focusDate && state.day ? focusDate : null }, 'calendar');
    };

    root.querySelectorAll('[data-nav]').forEach((button) => {
        button.addEventListener('click', () => {
            const delta = Number(button.dataset.nav);
            const current = store.get().calMonth;
            const target = delta === 0 ? today.slice(0, 7) : shiftMonth(current, delta);
            goToMonth(target, delta === 0 ? today : null);
        });
    });

    root.querySelectorAll('menu [data-view]').forEach((button) => {
        button.addEventListener('click', () => {
            const view = button.dataset.view;
            root.dataset.view = view;
            root.querySelectorAll('menu [data-view]').forEach((b) => b.setAttribute('aria-pressed', String(b === button)));
            const isList = view === 'llista';
            list.hidden = !isList;
            grid.hidden = isList;
            weekdays.hidden = isList;
        });
    });

    grid.addEventListener('click', (event) => {
        const button = event.target.closest('button[data-date]');
        if (!button) return;
        const date = button.dataset.date;
        const current = store.get().day;
        store.set({ day: current === date ? null : date }, 'calendar');
    });

    grid.addEventListener('keydown', (event) => {
        const button = event.target.closest('button[data-date]');
        if (!button) return;

        const date = parseISODate(button.dataset.date);
        const moves = {
            ArrowLeft: () => date.setDate(date.getDate() - 1),
            ArrowRight: () => date.setDate(date.getDate() + 1),
            ArrowUp: () => date.setDate(date.getDate() - 7),
            ArrowDown: () => date.setDate(date.getDate() + 7),
            Home: () => date.setDate(date.getDate() - ((date.getDay() + 6) % 7)),
            End: () => date.setDate(date.getDate() + (6 - ((date.getDay() + 6) % 7))),
            PageUp: () => date.setMonth(date.getMonth() - 1),
            PageDown: () => date.setMonth(date.getMonth() + 1),
        };
        if (!moves[event.key]) return;

        event.preventDefault();
        moves[event.key]();
        const iso = toISODate(date);
        const target = grid.querySelector(`[data-date="${iso}"]`);

        if (target) {
            button.tabIndex = -1;
            target.tabIndex = 0;
            target.focus();
        } else {
            goToMonth(iso.slice(0, 7), iso);
        }
    });

    /* ---------- Store sync ---------- */

    store.subscribe((state, changed) => {
        if (changed.includes('calMonth') || changed.some((k) => ['provincia', 'comarca', 'tipos'].includes(k))) {
            const direction = state.calMonth === shownMonth ? 0 : state.calMonth > shownMonth ? 1 : -1;
            shownMonth = state.calMonth;
            load(state, direction);
        } else if (changed.includes('day')) {
            grid.querySelectorAll('button[aria-pressed]').forEach((b) => {
                const on = b.dataset.date === state.day;
                b.setAttribute('aria-pressed', String(on));
                if (on) {
                    grid.querySelectorAll('button[tabindex="0"]').forEach((x) => (x.tabIndex = -1));
                    b.tabIndex = 0;
                }
            });
            renderDay(state.day);
        }
    });

    load(store.get());
}
