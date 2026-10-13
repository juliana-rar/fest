import { postJSON } from '../lib/api.js';
import { t, lang } from '../lib/i18n.js';

/**
 * "Compartir a Instagram Stories".
 * The 1080×1920 image is rendered server-side (/story/{slug}?t=template).
 * On mobile the Web Share API Level 2 (navigator.share with files) opens the
 * native share sheet, where Instagram appears; on desktop the image is
 * downloaded instead.
 */
function canShareFiles() {
    try {
        const probe = new File([''], 'probe.jpg', { type: 'image/jpeg' });
        return Boolean(navigator.canShare?.({ files: [probe] }));
    } catch {
        return false;
    }
}

export function initStory() {
    const dialog = document.getElementById('story');
    const opener = document.querySelector('[data-story-open]');
    if (!dialog || !opener) return;

    const form = dialog.querySelector('form');
    const preview = form.querySelector(':scope > img');
    const status = form.querySelector(':scope > p');
    const shareButton = form.querySelector('[data-story-share]');
    const download = form.querySelector('[data-story-download]');
    const { src, shareUrl, title, url, slug } = dialog.dataset;
    const sharesFiles = canShareFiles();
    let counted = false;

    // Without file sharing (desktop), downloading is the primary action.
    if (!sharesFiles) shareButton.hidden = true;

    const template = () => form.querySelector('input[name="t"]:checked')?.value ?? '';
    const imageUrl = (extra = {}) => {
        const u = new URL(src, location.origin);
        u.searchParams.set('t', template());
        u.searchParams.set('lang', lang());
        Object.entries(extra).forEach(([k, v]) => u.searchParams.set(k, v));
        return u.toString();
    };

    const countShare = () => {
        if (counted) return;
        counted = true;
        postJSON(shareUrl);
    };

    const refresh = () => {
        preview.removeAttribute('data-loaded');
        preview.setAttribute('aria-busy', 'true');
        status.textContent = t('story_loading');
        preview.src = imageUrl();
        download.href = imageUrl({ download: 1 });
        download.setAttribute('download', `story-${slug}-${template()}.jpg`);
    };

    preview.addEventListener('load', () => {
        preview.dataset.loaded = '';
        preview.removeAttribute('aria-busy');
        status.textContent = sharesFiles ? t('story_ready') : t('story_no_share');
    });

    const open = () => {
        if (!dialog.open) dialog.showModal();
        if (!preview.getAttribute('src')) refresh();
    };

    opener.addEventListener('click', open);
    form.addEventListener('change', (event) => {
        if (event.target.name === 't') refresh();
    });

    shareButton.addEventListener('click', async () => {
        shareButton.disabled = true;
        try {
            const blob = await fetch(imageUrl()).then((r) => r.blob());
            const file = new File([blob], `story-${slug}.jpg`, { type: 'image/jpeg' });
            await navigator.share({ files: [file], title, text: `${title} · ${url}` });
            countShare();
            status.textContent = t('story_shared');
        } catch (error) {
            if (error?.name !== 'AbortError') {
                status.textContent = t('story_no_share');
                download.click();
            }
        } finally {
            shareButton.disabled = false;
        }
    });

    download.addEventListener('click', () => {
        countShare();
        status.textContent = t('story_downloaded');
    });

    dialog.addEventListener('close', () => {
        if (location.hash === '#story') history.replaceState(history.state, '', location.pathname + location.search);
        opener.focus();
    });

    // Close when clicking the backdrop.
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) dialog.close();
    });

    // Cards link to /festa/{slug}#story to open the dialog directly.
    if (location.hash === '#story') open();
    window.addEventListener('hashchange', () => location.hash === '#story' && open());
}
