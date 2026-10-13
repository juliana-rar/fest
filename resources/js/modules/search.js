import { api } from '../lib/api.js';
import { store } from '../lib/store.js';
import { t } from '../lib/i18n.js';

/**
 * Hero search: ARIA 1.2 combobox with a listbox popup.
 * Choosing a municipality opens its next festival, or centres the map on it.
 */
export function initSearch() {
    const form = document.getElementById('cerca');
    if (!form) return;

    const input = form.querySelector('input[role="combobox"]');
    const list = form.querySelector('[role="listbox"]');
    let results = [];
    let active = -1;
    let timer = null;
    let controller = 0;

    const close = () => {
        list.hidden = true;
        input.setAttribute('aria-expanded', 'false');
        input.removeAttribute('aria-activedescendant');
        active = -1;
    };

    const highlight = (index) => {
        const options = [...list.querySelectorAll('[role="option"]:not([aria-disabled])')];
        if (!options.length) return;
        active = (index + options.length) % options.length;
        options.forEach((option, i) => option.setAttribute('aria-selected', String(i === active)));
        input.setAttribute('aria-activedescendant', options[active].id);
        options[active].scrollIntoView({ block: 'nearest' });
    };

    const choose = (index) => {
        const result = results[index];
        if (!result) return;
        close();
        input.value = result.nombre;

        if (result.fiesta) {
            window.location.href = result.fiesta.url;
            return;
        }

        store.set({ focus: { lat: result.lat, lng: result.lng, zoom: 12, label: result.nombre } }, 'search');
        document.getElementById('mapa')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    };

    const render = () => {
        list.replaceChildren();

        if (!results.length) {
            const li = document.createElement('li');
            li.setAttribute('role', 'option');
            li.setAttribute('aria-disabled', 'true');
            li.id = 'cerca-buit';
            li.textContent = t('search_empty');
            list.append(li);
        }

        results.forEach((result, i) => {
            const li = document.createElement('li');
            li.id = `cerca-opcio-${i}`;
            li.setAttribute('role', 'option');
            li.setAttribute('aria-selected', 'false');
            li.dataset.index = i;

            const name = document.createElement('strong');
            name.textContent = result.nombre;
            const detail = document.createElement('small');
            detail.textContent = `${result.comarca} · ${
                result.fiesta ? t('search_next', { name: result.fiesta.nombre, dates: result.fiesta.rango }) : t('search_none')
            }`;

            li.append(name, detail);
            list.append(li);
        });

        list.hidden = false;
        input.setAttribute('aria-expanded', 'true');
    };

    input.addEventListener('input', () => {
        clearTimeout(timer);
        const q = input.value.trim();
        if (q.length < 2) {
            close();
            return;
        }
        timer = setTimeout(async () => {
            const id = ++controller;
            try {
                const data = await api.municipios(q);
                if (id !== controller) return; // a newer query is in flight
                results = data;
                active = -1;
                render();
            } catch {
                close();
            }
        }, 180);
    });

    input.addEventListener('keydown', (event) => {
        if (list.hidden && event.key === 'ArrowDown' && results.length) {
            render();
        }
        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                highlight(active + 1);
                break;
            case 'ArrowUp':
                event.preventDefault();
                highlight(active - 1);
                break;
            case 'Enter':
                if (!list.hidden && results.length) {
                    event.preventDefault();
                    choose(active >= 0 ? active : 0);
                }
                break;
            case 'Escape':
                if (!list.hidden) {
                    event.preventDefault();
                    close();
                } else {
                    input.value = '';
                }
                break;
        }
    });

    list.addEventListener('mousedown', (event) => event.preventDefault()); // keep focus in the input
    list.addEventListener('click', (event) => {
        const option = event.target.closest('[role="option"]:not([aria-disabled])');
        if (option) choose(Number(option.dataset.index));
    });

    input.addEventListener('blur', () => setTimeout(close, 120));

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        if (results.length) choose(active >= 0 ? active : 0);
    });
}
