/**
 * "Properes festes" carousel: native scroll-snap + prev/next buttons that
 * scroll by one page and disable themselves at both ends.
 */
export function initCarousel() {
    const section = document.getElementById('properes');
    const track = section?.querySelector(':scope > ol');
    if (!track) return;

    const [prev, next] = section.querySelectorAll(':scope > header > nav > button');

    const update = () => {
        const max = track.scrollWidth - track.clientWidth - 4;
        prev.disabled = track.scrollLeft <= 4;
        next.disabled = track.scrollLeft >= max;
    };

    const step = () => {
        const item = track.querySelector(':scope > li');
        const gap = parseFloat(getComputedStyle(track).columnGap) || 0;
        const perPage = Math.max(1, Math.floor(track.clientWidth / ((item?.offsetWidth || 300) + gap)));
        return perPage * ((item?.offsetWidth || 300) + gap);
    };

    section.querySelectorAll('[data-dir]').forEach((button) => {
        button.addEventListener('click', () => {
            track.scrollBy({ left: step() * Number(button.dataset.dir), behavior: 'smooth' });
        });
    });

    track.addEventListener('scroll', () => requestAnimationFrame(update), { passive: true });
    window.addEventListener('resize', update, { passive: true });
    update();
}
