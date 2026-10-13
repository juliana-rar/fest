import { t, plural, formatNumber } from './i18n.js';

/**
 * Builds the same <article class="fiesta"> markup as the Blade component
 * (resources/views/components/fiesta-card.blade.php) from an API resource.
 */
export function countdown(fiesta) {
    if (fiesta.en_curso) return t('now');
    if (fiesta.dias < 0) return t('finished');
    if (fiesta.dias === 1) return t('tomorrow');
    return plural('days_left', fiesta.dias);
}

export function renderCard(fiesta, { distance = false } = {}) {
    const article = document.createElement('article');
    article.className = 'fiesta';
    article.dataset.estat = fiesta.en_curso ? 'en-curs' : 'propera';
    article.dataset.dies = fiesta.dias;

    const img = new Image(640, 360);
    img.src = fiesta.thumb;
    img.alt = t('image_of', { name: fiesta.nombre });
    img.loading = 'lazy';
    img.decoding = 'async';

    const h3 = document.createElement('h3');
    const link = document.createElement('a');
    link.href = fiesta.url;
    link.textContent = fiesta.nombre;
    h3.append(link);

    const place = document.createElement('p');
    place.textContent = [fiesta.municipio?.nombre, fiesta.comarca?.nombre].filter(Boolean).join(' · ');

    const time = document.createElement('time');
    time.dateTime = fiesta.fecha_inicio;
    time.textContent = fiesta.rango;

    const mark = document.createElement('mark');
    mark.textContent = countdown(fiesta);

    article.append(img, h3, place, time, mark);

    if (distance && typeof fiesta.distancia === 'number') {
        const data = document.createElement('data');
        data.value = fiesta.distancia;
        data.textContent = t('km', { n: formatNumber(fiesta.distancia) });
        article.append(data);
    }

    const share = document.createElement('a');
    share.href = `${fiesta.url}#story`;
    share.setAttribute('aria-label', t('share_named', { name: fiesta.nombre }));
    share.innerHTML = '<svg aria-hidden="true" focusable="false" width="18" height="18"><use href="#i-share"/></svg>';
    article.append(share);

    return article;
}

/** Wraps cards in <li> for lists. */
export function renderCardItems(fiestas, options) {
    return fiestas.map((fiesta) => {
        const li = document.createElement('li');
        li.append(renderCard(fiesta, options));
        return li;
    });
}

/** Map colour scale bucket for a festival. */
export function heat(fiesta) {
    if (fiesta.en_curso) return 'now';
    if (fiesta.dias < 0) return 'past';
    if (fiesta.dias <= 7) return 'week';
    if (fiesta.dias <= 31) return 'month';
    return 'later';
}
