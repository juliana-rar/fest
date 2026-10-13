import { store, FILTER_KEYS } from '../lib/store.js';
import { formatMonth } from '../lib/i18n.js';

/**
 * Binds the #filtres form to the shared store (two-way), so changing a
 * filter refreshes both the map and the calendar, and navigating the
 * calendar updates the month filter.
 */
export function initFilters() {
    const form = document.getElementById('filtres');
    if (!form) return;

    const provincia = form.elements.provincia;
    const comarca = form.elements.comarca;
    const mes = form.elements.mes;
    const tipos = () => [...form.querySelectorAll('input[name="tipo"]')];

    /** Only show the comarques of the selected province. */
    const limitComarcas = () => {
        [...comarca.querySelectorAll('optgroup')].forEach((group) => {
            const visible = !provincia.value || group.label === provincia.value;
            group.hidden = !visible;
            group.disabled = !visible;
        });
        const selected = comarca.selectedOptions[0];
        if (selected?.dataset.provincia && provincia.value && selected.dataset.provincia !== provincia.value) {
            comarca.value = '';
        }
    };

    /** Make sure the month select can show any month the calendar navigates to. */
    const ensureMonthOption = (value) => {
        if (!value || [...mes.options].some((o) => o.value === value)) return;
        const [y, m] = value.split('-').map(Number);
        const option = new Option(formatMonth(y, m), value);
        const after = [...mes.options].find((o) => o.value && o.value > value);
        mes.add(option, after ?? null);
    };

    const fromStore = (state) => {
        provincia.value = state.provincia;
        ensureMonthOption(state.mes);
        mes.value = state.mes;
        limitComarcas();
        comarca.value = state.comarca;
        tipos().forEach((input) => (input.checked = state.tipos.includes(input.value)));
    };

    fromStore(store.get());

    form.addEventListener('change', (event) => {
        if (event.target === provincia) limitComarcas();

        // Picking a comarca implies its province.
        if (event.target === comarca && comarca.selectedOptions[0]?.dataset.provincia) {
            provincia.value = comarca.selectedOptions[0].dataset.provincia;
            limitComarcas();
        }

        const patch = {
            provincia: provincia.value,
            comarca: comarca.value,
            mes: mes.value,
            tipos: tipos().filter((input) => input.checked).map((input) => input.value),
            day: null,
        };
        if (mes.value) patch.calMonth = mes.value;
        store.set(patch, 'filters');
    });

    form.addEventListener('reset', () => {
        // Let the browser reset the controls first.
        setTimeout(() => {
            limitComarcas();
            store.set({ provincia: '', comarca: '', mes: '', tipos: [], day: null }, 'filters');
        });
    });

    store.subscribe((state, changed, source) => {
        if (source !== 'filters' && changed.some((k) => FILTER_KEYS.includes(k))) {
            fromStore(state);
        }
    });
}

/** Writes a short summary ("12 festes") in the form's <output>. */
export function setFilterSummary(text) {
    const output = document.querySelector('#filtres > output');
    if (output) output.value = text;
}
