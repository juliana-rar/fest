/**
 * Public site entry point. Every module checks for its own root element,
 * so the same bundle serves the home page and the festival page.
 */
import { initTheme } from './modules/theme.js';
import { initNav } from './modules/nav.js';
import { initSearch } from './modules/search.js';
import { initCarousel } from './modules/carousel.js';
import { initNearby } from './modules/nearby.js';
import { initFilters } from './modules/filters.js';
import { initMap } from './modules/map.js';
import { initCalendar } from './modules/calendar.js';
import { initStory } from './modules/story.js';
import { initMiniMap } from './modules/minimap.js';

initTheme();
initNav();
initSearch();
initCarousel();
initNearby();
initFilters();
initMap();
initCalendar();
initStory();
initMiniMap();

// PWA: register the service worker in production builds only
// (in development it would cache Vite's hot-reloaded assets).
if ('serviceWorker' in navigator && import.meta.env.PROD) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}
