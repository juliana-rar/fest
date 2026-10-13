/**
 * Manual theme selector: auto → light → dark → auto.
 * The choice is stored in localStorage and applied before first paint by an
 * inline script in the layout; "auto" follows prefers-color-scheme.
 */
const ORDER = ['auto', 'light', 'dark'];
const ICONS = { auto: 'auto', light: 'sun', dark: 'moon' };

function current() {
    return document.documentElement.dataset.theme || 'auto';
}

export function isDark() {
    const theme = current();
    if (theme !== 'auto') return theme === 'dark';
    return !window.matchMedia('(prefers-color-scheme: light)').matches;
}

function apply(theme, button) {
    if (theme === 'auto') delete document.documentElement.dataset.theme;
    else document.documentElement.dataset.theme = theme;

    try {
        if (theme === 'auto') localStorage.removeItem('theme');
        else localStorage.setItem('theme', theme);
    } catch {
        /* storage unavailable: the choice lasts for this page only */
    }

    if (button) {
        button.querySelector('use')?.setAttribute('href', `#i-${ICONS[theme]}`);
        const label = button.dataset[`label${theme[0].toUpperCase()}${theme.slice(1)}`];
        button.setAttribute('aria-label', label);
        button.title = label;
    }

    document.dispatchEvent(new CustomEvent('themechange', { detail: { dark: isDark() } }));
}

export function initTheme() {
    const button = document.getElementById('theme');
    if (!button) return;

    apply(current(), button);

    button.addEventListener('click', () => {
        const next = ORDER[(ORDER.indexOf(current()) + 1) % ORDER.length];
        const run = () => apply(next, button);
        if (document.startViewTransition && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            document.startViewTransition(run);
        } else {
            run();
        }
    });

    window.matchMedia('(prefers-color-scheme: light)').addEventListener('change', () => {
        if (current() === 'auto') {
            document.dispatchEvent(new CustomEvent('themechange', { detail: { dark: isDark() } }));
        }
    });
}
