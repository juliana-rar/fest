@php
    $title = trim($__env->yieldContent('title')) ?: $site['seo_title'];
    $description = trim($__env->yieldContent('description')) ?: $site['seo_description'];
    $ogImage = trim($__env->yieldContent('og_image')) ?: ($site['og_image'] ? url('/uploads/'.$site['og_image']) : asset('img/og-default.jpg'));
    $locale = app()->getLocale();
@endphp
<!doctype html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="alternate" hreflang="ca" href="{{ url()->current() }}">

    {{-- Open Graph / Twitter Card --}}
    <meta property="og:site_name" content="Festes Majors de Catalunya">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:locale" content="{{ $locale === 'es' ? 'es_ES' : 'ca_ES' }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $description }}">
    <meta name="twitter:image" content="{{ $ogImage }}">
    @stack('meta')

    {{-- PWA --}}
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <meta name="theme-color" content="{{ $site['color_background'] }}">
    <link rel="icon" href="{{ asset('icons/icon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-192.png') }}">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500..800&family=Inter:wght@400..700&display=swap">

    {{-- Theme: apply the stored preference before first paint --}}
    <script>try{const t=localStorage.getItem('theme');if(t==='light'||t==='dark')document.documentElement.dataset.theme=t}catch(e){}</script>

    {{-- Brand colours from the admin settings --}}
    <style>:root{--brand-red:{{ $site['color_primary'] }};--brand-gold:{{ $site['color_secondary'] }};--brand-night:{{ $site['color_background'] }}}</style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body>
    @include('partials.icons')
    <a href="#contingut" id="skip">{{ __('app.skip') }}</a>

    <header id="top">
        <a href="{{ route('home') }}" aria-label="{{ __('app.home_label') }}">
            @if ($site['logo'])
                <img src="{{ asset('uploads/'.$site['logo']) }}" alt="" height="34">
            @else
                <x-icon name="logo" size="32" />
            @endif
            <span>Festes Majors <small>de Catalunya</small></span>
        </a>

        <nav id="menu" popover aria-label="{{ __('app.nav_main') }}">
            <a href="{{ route('home') }}#explora" data-section="explora">{{ __('app.nav_map') }}</a>
            <a href="{{ route('home') }}#properes" data-section="properes">{{ __('app.nav_upcoming') }}</a>
            <a href="{{ route('home') }}#agenda" data-section="agenda">{{ __('app.nav_calendar') }}</a>
        </nav>

        <nav aria-label="{{ __('app.footer_language') }}">
            <a href="{{ route('locale', 'ca') }}" hreflang="ca" lang="ca" title="Català" @if (app()->getLocale() === 'ca') aria-current="true" @endif>CA</a>
            <a href="{{ route('locale', 'es') }}" hreflang="es" lang="es" title="Castellano" @if (app()->getLocale() === 'es') aria-current="true" @endif>ES</a>
        </nav>

        <button type="button" id="theme" aria-label="{{ __('app.theme_toggle') }}" data-label-auto="{{ __('app.theme_auto') }}" data-label-light="{{ __('app.theme_light') }}" data-label-dark="{{ __('app.theme_dark') }}">
            <x-icon name="auto" size="18" />
        </button>

        <button type="button" popovertarget="menu" aria-label="{{ __('app.open_menu') }}">
            <x-icon name="menu" size="20" />
        </button>
    </header>

    <main id="contingut" tabindex="-1">
        @yield('content')
    </main>

    @include('partials.footer')

    <script type="application/json" id="i18n">@json(trans('app.js'))</script>
</body>
</html>
