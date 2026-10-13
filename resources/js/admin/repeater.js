/**
 * Dynamic programme repeater (#actes): rows are cloned from a <template>
 * whose field names contain "__INDEX__".
 */
export function initRepeater() {
    const fieldset = document.getElementById('actes');
    if (!fieldset) return;

    const list = fieldset.querySelector(':scope > ol');
    const template = fieldset.querySelector(':scope > template');
    const addButton = fieldset.querySelector('[data-add]');
    const form = fieldset.closest('form');

    let index = list.children.length;

    const add = () => {
        const html = template.innerHTML.replaceAll('__INDEX__', String(index++));
        const fragment = document.createRange().createContextualFragment(html.trim());
        const row = fragment.firstElementChild;

        // Pre-fill the day with the previous row's day, or the festival's start date.
        const previous = list.lastElementChild?.querySelector('input[type="date"]')?.value;
        const day = row.querySelector('input[type="date"]');
        day.value = previous || form.elements.fecha_inicio?.value || '';

        list.append(row);
        row.querySelector('input[name$="[titulo]"]')?.focus();
    };

    addButton.addEventListener('click', add);

    list.addEventListener('click', (event) => {
        const remove = event.target.closest('[data-remove]');
        if (!remove) return;
        const row = remove.closest('li');
        const next = row.nextElementSibling ?? row.previousElementSibling;
        row.remove();
        (next?.querySelector('input') ?? addButton).focus();
    });

    // Keep the date inputs within the festival dates.
    const limit = () => {
        const min = form.elements.fecha_inicio?.value;
        const max = form.elements.fecha_fin?.value;
        list.querySelectorAll('input[type="date"]').forEach((input) => {
            if (min) input.min = min;
            if (max) input.max = max;
        });
    };
    form.addEventListener('change', limit);
    addButton.addEventListener('click', limit);
    limit();
}
