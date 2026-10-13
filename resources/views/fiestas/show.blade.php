@extends('layouts.app')

@section('title', $fiesta->nombre.' '.$fiesta->fecha_inicio->year.' · '.$fiesta->municipio->nombre.' — Festes Majors de Catalunya')
@section('description', $fiesta->descripcionTexto(155))
@section('og_type', 'article')
@section('og_image', url($fiesta->imagenUrl()))

@push('meta')
    <meta property="article:published_time" content="{{ $fiesta->created_at?->toIso8601String() }}">
    @unless ($fiesta->publicada)
        <meta name="robots" content="noindex">
    @endunless
@endpush

@push('head')
    @php
        $jsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'Event',
        'name' => $fiesta->nombre,
        'description' => $fiesta->descripcionTexto(300),
        'startDate' => $fiesta->fecha_inicio->toDateString(),
        'endDate' => $fiesta->fecha_fin->toDateString(),
        'eventStatus' => 'https://schema.org/EventScheduled',
        'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
        'isAccessibleForFree' => true,
        'image' => [url($fiesta->imagenUrl())],
        'url' => route('fiestas.show', $fiesta),
        'location' => [
            '@type' => 'Place',
            'name' => $fiesta->municipio->nombre,
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => $fiesta->municipio->nombre,
                'addressRegion' => $fiesta->municipio->comarca->provincia,
                'addressCountry' => 'ES',
            ],
            'geo' => ['@type' => 'GeoCoordinates', 'latitude' => $fiesta->municipio->lat, 'longitude' => $fiesta->municipio->lng],
        ],
        'organizer' => ['@type' => 'Organization', 'name' => 'Ajuntament de '.$fiesta->municipio->nombre],
        'subEvent' => $fiesta->actos->take(30)->map(fn ($a) => [
            '@type' => 'Event',
            'name' => $a->titulo,
            'startDate' => $a->dia->toDateString().($a->hora ? 'T'.$a->horaCorta() : ''),
            'location' => ['@type' => 'Place', 'name' => ($a->lugar ?: $fiesta->municipio->nombre).', '.$fiesta->municipio->nombre],
        ])->values(),
    ];
    @endphp
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
    <article id="festa" data-estat="{{ $fiesta->estaEnCurso() ? 'en-curs' : ($fiesta->finalizada() ? 'passada' : 'propera') }}">
        <header>
            <img src="{{ $fiesta->imagenUrl() }}" alt="{{ __('app.image_of', ['name' => $fiesta->nombre]) }}" width="1600" height="900"
                 fetchpriority="high" decoding="async" style="view-transition-name: festa-{{ $fiesta->id }}">
            <nav aria-label="{{ __('app.breadcrumb') }}">
                <a href="{{ route('home') }}">{{ __('app.home') }}</a>
                <a href="{{ route('home') }}?comarca={{ $fiesta->municipio->comarca->slug }}#mapa">{{ $fiesta->municipio->comarca->nombre }}</a>
            </nav>
            <h1>{{ $fiesta->nombre }}</h1>
            <p>{{ $fiesta->municipio->nombre }}</p>
            <mark>{{ $fiesta->cuentaAtras() }}</mark>
            @if ($fiesta->tipos->isNotEmpty())
                <ul aria-label="{{ __('app.festival_type') }}">
                    @foreach ($fiesta->tipos as $tipo)
                        <li style="--c: {{ $tipo->color }}"><a href="{{ route('home') }}?tipo={{ $tipo->slug }}#mapa">{{ $tipo->nombreLocal() }}</a></li>
                    @endforeach
                </ul>
            @endif
        </header>

        <dl>
            <dt>{{ __('app.dates') }}</dt>
            <dd>
                <time datetime="{{ $fiesta->fecha_inicio->toDateString() }}">{{ $fiesta->rangoFechas() }}</time>
                {{ $fiesta->fecha_inicio->year }}
            </dd>
            <dt>{{ __('app.municipality') }}</dt>
            <dd>{{ $fiesta->municipio->nombre }}</dd>
            <dt>{{ __('app.comarca') }}</dt>
            <dd>{{ $fiesta->municipio->comarca->nombre }}</dd>
            <dt>{{ __('app.province') }}</dt>
            <dd>{{ $fiesta->municipio->comarca->provincia }}</dd>
        </dl>

        <section aria-labelledby="sobre">
            <h2 id="sobre">{{ __('app.about_fiesta') }}</h2>
            {!! $fiesta->descripcion !!}
        </section>

        <section id="programa" aria-labelledby="programa-titol">
            <h2 id="programa-titol">{{ __('app.programme') }}</h2>
            @forelse ($programa as $dia => $actos)
                <h3><time datetime="{{ $dia }}">{{ ucfirst(\Illuminate\Support\Carbon::parse($dia)->locale(app()->getLocale())->isoFormat(__('app.day_format'))) }}</time></h3>
                <ol>
                    @foreach ($actos as $acto)
                        <li>
                            <time datetime="{{ $dia }}{{ $acto->hora ? 'T'.$acto->horaCorta() : '' }}">{{ $acto->horaCorta() ?? '—' }}</time>
                            <strong>{{ $acto->titulo }}</strong>
                            @if ($acto->lugar)<span>{{ $acto->lugar }}</span>@endif
                        </li>
                    @endforeach
                </ol>
            @empty
                <p>{{ __('app.programme_soon') }}</p>
            @endforelse
        </section>

        <aside id="accions" aria-label="{{ __('app.actions') }}">
            <button type="button" data-story-open><x-icon name="instagram" /> {{ __('app.share_story') }}</button>
            <a href="{{ route('fiestas.ics', $fiesta) }}" download><x-icon name="calendar" /> {{ __('app.add_calendar') }}</a>
            <figure id="minimapa" data-lat="{{ $fiesta->municipio->lat }}" data-lng="{{ $fiesta->municipio->lng }}" data-nom="{{ $fiesta->municipio->nombre }}"
                    aria-label="{{ __('app.location_of', ['name' => $fiesta->municipio->nombre]) }}"></figure>
        </aside>

        @if ($cercanas->isNotEmpty())
            <section id="tambe" aria-labelledby="tambe-titol">
                <h2 id="tambe-titol">{{ __('app.also_nearby') }}</h2>
                <ol>
                    @foreach ($cercanas as $cercana)
                        <li><x-fiesta-card :fiesta="$cercana" /></li>
                    @endforeach
                </ol>
            </section>
        @endif
    </article>

    {{-- Instagram Story dialog -------------------------------------------- --}}
    <dialog id="story" aria-labelledby="story-titol"
            data-src="{{ route('story', $fiesta) }}" data-share-url="{{ route('api.fiestas.share', $fiesta) }}"
            data-title="{{ $fiesta->nombre }}" data-url="{{ route('fiestas.show', $fiesta) }}" data-slug="{{ $fiesta->slug }}">
        <form method="dialog">
            <h2 id="story-titol">{{ __('app.share_story') }}</h2>
            <fieldset>
                <legend>{{ __('app.story_template') }}</legend>
                @foreach ($plantillas as $key => $plantilla)
                    <label><input type="radio" name="t" value="{{ $key }}" @checked($key === $plantillaDefecto)> {{ $plantilla['name'] }}</label>
                @endforeach
            </fieldset>
            <img src="" alt="{{ __('app.story_preview', ['name' => $fiesta->nombre]) }}" width="1080" height="1920" decoding="async">
            <p aria-live="polite">{{ __('app.story_hint') }}</p>
            <button type="button" data-story-share><x-icon name="share" /> {{ __('app.share') }}</button>
            <a href="" download data-story-download><x-icon name="download" /> {{ __('app.download_image') }}</a>
            <button value="close" aria-label="{{ __('app.close') }}"><x-icon name="close" /></button>
        </form>
    </dialog>
@endsection
