/**
 * Instant preview for image inputs inside #imatge (festival cover, logo).
 * The server resizes and converts to WebP; the preview is local only.
 */
export function initImagePreview() {
    const fieldset = document.getElementById('imatge');
    if (!fieldset) return;

    const input = fieldset.querySelector('input[type="file"]');
    const preview = fieldset.querySelector(':scope > img');
    const remove = fieldset.querySelector('input[type="checkbox"]');
    const original = preview.getAttribute('src');
    let objectUrl = null;

    input.addEventListener('change', () => {
        const file = input.files?.[0];
        if (objectUrl) URL.revokeObjectURL(objectUrl);

        if (!file) {
            preview.src = original || '';
            preview.hidden = !original;
            return;
        }

        objectUrl = URL.createObjectURL(file);
        preview.src = objectUrl;
        preview.hidden = false;
        if (remove) remove.checked = false;
    });

    remove?.addEventListener('change', () => {
        preview.style.opacity = remove.checked ? '0.3' : '';
    });
}

/** Shows the auto-generated slug as a placeholder while typing the name. */
export function initSlugPreview() {
    const source = document.querySelector('[data-slug-source]');
    const target = document.querySelector('[data-slug-target]');
    if (!source || !target) return;

    const yearInput = target.form?.elements[target.dataset.yearSource];

    const slugify = (text) =>
        text
            .normalize('NFD')
            .replace(/[̀-ͯ]/g, '')
            .replace(/l·l/gi, 'll')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');

    const update = () => {
        if (target.value) return;
        const year = (yearInput?.value || '').slice(0, 4);
        const base = slugify(source.value);
        target.placeholder = base ? `${base}${year && !base.endsWith(year) ? `-${year}` : ''}` : target.dataset.placeholder ?? target.placeholder;
    };

    target.dataset.placeholder = target.placeholder;
    source.addEventListener('input', update);
    yearInput?.addEventListener('change', update);
}

/** Close button of the flash message box. */
export function initFlash() {
    const flash = document.getElementById('flash');
    flash?.querySelector(':scope > button')?.addEventListener('click', () => flash.remove());
}
