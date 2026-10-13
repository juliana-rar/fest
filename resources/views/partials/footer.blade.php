<footer id="peu">
    <section aria-labelledby="peu-titol">
        <h2 id="peu-titol"><x-icon name="logo" size="28" /> Festes Majors de Catalunya</h2>
        <p>{{ __('app.footer_about') }}</p>
    </section>

    <nav aria-labelledby="peu-explora">
        <h2 id="peu-explora">{{ __('app.footer_explore') }}</h2>
        <ul>
            <li><a href="{{ route('home') }}#properes">{{ __('app.nav_upcoming') }}</a></li>
            <li><a href="{{ route('home') }}#aprop">{{ __('app.nearby_title') }}</a></li>
            <li><a href="{{ route('home') }}#mapa">{{ __('app.nav_map') }}</a></li>
            <li><a href="{{ route('home') }}#calendari">{{ __('app.nav_calendar') }}</a></li>
            <li><a href="{{ route('sitemap') }}">Sitemap</a></li>
        </ul>
    </nav>

    <nav aria-labelledby="peu-idioma">
        <h2 id="peu-idioma">{{ __('app.footer_language') }}</h2>
        <ul>
            <li><a href="{{ route('locale', 'ca') }}" hreflang="ca" lang="ca" @if (app()->getLocale() === 'ca') aria-current="true" @endif>Català</a></li>
            <li><a href="{{ route('locale', 'es') }}" hreflang="es" lang="es" @if (app()->getLocale() === 'es') aria-current="true" @endif>Castellano</a></li>
        </ul>
    </nav>

    <nav aria-labelledby="peu-xarxes">
        <h2 id="peu-xarxes">{{ __('app.footer_social') }}</h2>
        <ul>
            @foreach (array_filter((array) $site['social']) as $network => $url)
                <li><a href="{{ $url }}" rel="noopener me" target="_blank" aria-label="{{ ucfirst($network) }}"><x-icon :name="$network" size="22" /></a></li>
            @endforeach
        </ul>
    </nav>

    <p>
        <small>© {{ now()->year }} Festes Majors de Catalunya · {{ __('app.footer_rights') }}</small>
        <a href="{{ route('admin.dashboard') }}">{{ __('app.footer_admin') }}</a>
    </p>
</footer>
