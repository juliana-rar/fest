/**
 * Header behaviour:
 *  - [data-scrolled] once the page scrolls (compact header with shadow).
 *  - Scroll-spy: the link of the section in view gets aria-current.
 *  - The phone menu (native popover) closes after choosing a link.
 */
export function initNav() {
    const header = document.getElementById('top');
    const menu = document.getElementById('menu');
    if (!header || !menu) return;

    const onScroll = () => header.toggleAttribute('data-scrolled', window.scrollY > 8);
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });

    menu.addEventListener('click', (event) => {
        if (event.target.closest('a') && menu.matches(':popover-open')) menu.hidePopover();
    });

    const links = new Map(
        [...menu.querySelectorAll('a[data-section]')]
            .map((link) => [document.getElementById(link.dataset.section), link])
            .filter(([section]) => section),
    );
    if (!links.size || !('IntersectionObserver' in window)) return;

    const visible = new Map();
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => visible.set(entry.target, entry.intersectionRatio));
        // The section occupying most of the viewport wins.
        const [current] = [...visible.entries()].sort((a, b) => b[1] - a[1])[0] ?? [];
        links.forEach((link, section) => {
            if (section === current && visible.get(section) > 0) link.setAttribute('aria-current', 'true');
            else link.removeAttribute('aria-current');
        });
    }, { threshold: [0, 0.15, 0.35, 0.6, 0.9], rootMargin: '-64px 0px 0px 0px' });

    links.forEach((_, section) => observer.observe(section));
}
