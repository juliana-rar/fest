@extends('layouts.app')

@push('head')
    <link rel="preconnect" href="https://tile.openstreetmap.org" crossorigin>
    <link rel="preload" href="{{ asset('geo/comarques.geojson') }}" as="fetch" type="application/geo+json" crossorigin>
    @php
        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => 'Festes Majors de Catalunya',
            'url' => route('home'),
            'inLanguage' => app()->getLocale(),
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
    {{-- 1. Map hero: the map fills the screen; title, search, filters and the
           list of visible festivals float on top of it (stacked on phones). --}}
    <section id="explora" aria-labelledby="hero-titol">
        <header>
            <p>{{ __('app.hero_eyebrow', ['year' => now()->year]) }}</p>
            <h1 id="hero-titol">{{ \App\Models\Setting::localized('hero_title') }}</h1>
            <p>{{ \App\Models\Setting::localized('hero_subtitle') }}</p>

            <form id="cerca" role="search" action="{{ route('home') }}#mapa" autocomplete="off">
                <label for="cerca-input">{{ __('app.search_label') }}</label>
                <input id="cerca-input" name="q" type="search" placeholder="{{ __('app.search_placeholder') }}"
                       role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="cerca-llista" minlength="2">
                <button type="submit" aria-label="{{ __('app.search_submit') }}"><x-icon name="search" /></button>
                <ul id="cerca-llista" role="listbox" aria-label="{{ __('app.search_results') }}" hidden></ul>
                <button type="button" data-geo><x-icon name="locate" /> {{ __('app.use_location') }}</button>
            </form>
        </header>

        <form id="filtres" aria-label="{{ __('app.filters') }}">
            <fieldset>
                <legend>{{ __('app.festival_type') }}</legend>
                @foreach ($tipos as $tipo)
                    <label style="--c: {{ $tipo->color }}"><input type="checkbox" name="tipo" value="{{ $tipo->slug }}"> {{ $tipo->nombreLocal() }}</label>
                @endforeach
            </fieldset>
            <label>{{ __('app.province') }}
                <select name="provincia">
                    <option value="">{{ __('app.all_f') }}</option>
                    @foreach (\App\Models\Comarca::PROVINCIAS as $provincia)
                        <option value="{{ $provincia }}">{{ $provincia }}</option>
                    @endforeach
                </select>
            </label>
            <label>{{ __('app.comarca') }}
                <select name="comarca">
                    <option value="">{{ __('app.all_f') }}</option>
                    @foreach ($comarcas->groupBy('provincia') as $provincia => $grupo)
                        <optgroup label="{{ $provincia }}">
                            @foreach ($grupo as $comarca)
                                <option value="{{ $comarca->slug }}" data-provincia="{{ $provincia }}" data-codi="{{ $comarca->geojson_id }}">{{ $comarca->nombre }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </label>
            <label>{{ __('app.month') }}
                <select name="mes">
                    <option value="">{{ __('app.next_12_months') }}</option>
                    @foreach ($meses as $mes)
                        <option value="{{ $mes->format('Y-m') }}">{{ ucfirst($mes->locale(app()->getLocale())->isoFormat('MMMM YYYY')) }}</option>
                    @endforeach
                </select>
            </label>
            <button type="reset">{{ __('app.clear_filters') }}</button>
            <output aria-live="polite"></output>
        </form>

        {{-- Breadcrumb of the selected zone; also zooms back out --}}
        <nav id="zona" aria-label="{{ __('app.zone') }}" hidden>
            <button type="button" data-zone="all">{{ __('app.all_catalonia') }}</button>
            <button type="button" data-zone="provincia"></button>
            <button type="button" data-zone="comarca" aria-current="location"></button>
        </nav>

        <figure id="mapa" aria-label="{{ __('app.map_label') }}" tabindex="-1" data-geojson="{{ asset('geo/comarques.geojson') }}">
            <noscript>{{ __('app.map_noscript') }}</noscript>
        </figure>

        <aside id="al-mapa" aria-labelledby="al-mapa-titol">
            <h2 id="al-mapa-titol">{{ __('app.in_view_title') }}</h2>
            <p aria-live="polite">{{ __('app.loading') }}</p>
            <ol aria-busy="true"></ol>
        </aside>

        <ul id="llegenda" aria-label="{{ __('app.legend') }}">
            <li data-heat="now">{{ __('app.heat_now') }}</li>
            <li data-heat="week">{{ __('app.heat_week') }}</li>
            <li data-heat="month">{{ __('app.heat_month') }}</li>
            <li data-heat="later">{{ __('app.heat_later') }}</li>
            <li data-heat="comarca">{{ __('app.heat_comarca') }}</li>
        </ul>
    </section>

    {{-- 2. Upcoming festivals carousel ------------------------------------- --}}
    <section id="properes" aria-labelledby="properes-titol">
        <header>
            <h2 id="properes-titol">{{ __('app.upcoming_title') }}</h2>
            <p>{{ __('app.upcoming_subtitle') }}</p>
            <nav aria-label="{{ __('app.carousel_controls') }}">
                <button type="button" data-dir="-1" aria-label="{{ __('app.previous') }}"><x-icon name="left" /></button>
                <button type="button" data-dir="1" aria-label="{{ __('app.next') }}"><x-icon name="right" /></button>
            </nav>
        </header>
        <ol tabindex="0" aria-label="{{ __('app.upcoming_title') }}">
            @forelse ($proximas as $fiesta)
                <li><x-fiesta-card :fiesta="$fiesta" :transition="true" :eager="$loop->index < 2" /></li>
            @empty
                <li><p>{{ __('app.no_upcoming') }}</p></li>
            @endforelse
        </ol>
    </section>

    {{-- 3. Near you (shown once geolocation is granted) -------------------- --}}
    <section id="aprop" aria-labelledby="aprop-titol" hidden>
        <header>
            <h2 id="aprop-titol">{{ __('app.nearby_title') }}</h2>
            <p aria-live="polite">{{ __('app.nearby_subtitle') }}</p>
            <label>{{ __('app.radius') }}
                <select name="radio">
                    @foreach ([10, 25, 50, 100] as $km)
                        <option value="{{ $km }}" @selected($km === 50)>{{ $km }} km</option>
                    @endforeach
                </select>
            </label>
        </header>
        <ol aria-busy="true" aria-label="{{ __('app.nearby_title') }}">
            @for ($i = 0; $i < 3; $i++)
                <li aria-hidden="true"><article class="fiesta"></article></li>
            @endfor
        </ol>
    </section>

    {{-- 4. Calendar (synchronised with the map through the shared filters) --}}
    <section id="agenda" aria-labelledby="agenda-titol">
        <header>
            <h2 id="agenda-titol">{{ __('app.calendar_title') }}</h2>
            <p>{{ __('app.explore_subtitle') }}</p>
        </header>

        <section id="calendari" aria-labelledby="cal-titol" data-view="mes" data-month="{{ today()->format('Y-m') }}" data-today="{{ today()->toDateString() }}">
            <header>
                <h3 id="cal-titol" aria-live="polite">{{ ucfirst(today()->locale(app()->getLocale())->isoFormat('MMMM YYYY')) }}</h3>
                <button type="button" data-nav="-1" aria-label="{{ __('app.prev_month') }}"><x-icon name="left" /></button>
                <button type="button" data-nav="0">{{ __('app.today') }}</button>
                <button type="button" data-nav="1" aria-label="{{ __('app.next_month') }}"><x-icon name="right" /></button>
                <menu aria-label="{{ __('app.view') }}">
                    <li><button type="button" data-view="mes" aria-pressed="true"><x-icon name="grid" size="18" /> {{ __('app.view_month') }}</button></li>
                    <li><button type="button" data-view="llista" aria-pressed="false"><x-icon name="list" size="18" /> {{ __('app.view_list') }}</button></li>
                </menu>
            </header>
            <p aria-hidden="true">
                @foreach (__('app.weekdays_short') as $dia)<abbr>{{ $dia }}</abbr>@endforeach
            </p>
            <ol aria-label="{{ __('app.calendar_days') }}" aria-busy="true"></ol>
            <ul aria-label="{{ __('app.view_list') }}" hidden></ul>
            <aside aria-live="polite" aria-labelledby="cal-dia">
                <h4 id="cal-dia">{{ __('app.pick_a_day') }}</h4>
                <ul></ul>
            </aside>
        </section>
    </section>
@endsection
