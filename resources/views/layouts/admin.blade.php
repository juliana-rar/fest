<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', __('admin.panel')) — Festes Majors</title>
    <link rel="icon" href="{{ asset('icons/icon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500..800&family=Inter:wght@400..700&display=swap">
    <script>try{const t=localStorage.getItem('theme');if(t==='light'||t==='dark')document.documentElement.dataset.theme=t}catch(e){}</script>
    <style>:root{--brand-red:{{ $site['color_primary'] }};--brand-gold:{{ $site['color_secondary'] }};--brand-night:{{ $site['color_background'] }}}</style>
    @vite(['resources/css/app.css', 'resources/js/admin.js'])
</head>
<body id="admin">
    @include('partials.icons')
    <a href="#contingut" id="skip">{{ __('app.skip') }}</a>

    <header id="admin-top">
        <a href="{{ route('admin.dashboard') }}"><x-icon name="logo" size="30" /> <span>Festes <b>Admin</b></span></a>
        <nav aria-label="{{ __('admin.panel') }}">
            <a href="{{ route('admin.dashboard') }}" @if (request()->routeIs('admin.dashboard')) aria-current="page" @endif>{{ __('admin.dashboard') }}</a>
            <a href="{{ route('admin.fiestas.index') }}" @if (request()->routeIs('admin.fiestas.*')) aria-current="page" @endif>{{ __('admin.fiestas') }}</a>
            <a href="{{ route('admin.municipios.index') }}" @if (request()->routeIs('admin.municipios.*')) aria-current="page" @endif>{{ __('admin.municipios') }}</a>
            <a href="{{ route('admin.comarcas.index') }}" @if (request()->routeIs('admin.comarcas.*')) aria-current="page" @endif>{{ __('admin.comarcas') }}</a>
            <a href="{{ route('admin.tipos.index') }}" @if (request()->routeIs('admin.tipos.*')) aria-current="page" @endif>{{ __('admin.tipos') }}</a>
            <a href="{{ route('admin.settings.edit') }}" @if (request()->routeIs('admin.settings.*')) aria-current="page" @endif>{{ __('admin.settings') }}</a>
        </nav>
        <a href="{{ route('home') }}" target="_blank" rel="noopener"><x-icon name="eye" size="18" /> {{ __('admin.view_site') }}</a>
        <button type="button" id="theme" aria-label="{{ __('app.theme_toggle') }}" data-label-auto="{{ __('app.theme_auto') }}" data-label-light="{{ __('app.theme_light') }}" data-label-dark="{{ __('app.theme_dark') }}"><x-icon name="auto" /></button>
        <form method="post" action="{{ route('logout') }}">
            @csrf
            <button type="submit"><x-icon name="logout" size="18" /> {{ __('admin.logout') }}</button>
        </form>
    </header>

    <main id="contingut" tabindex="-1">
        @if (session('status') || session('error') || $errors->any() || session('import_errors'))
            <section id="flash" aria-label="{{ __('admin.messages') }}">
                @if (session('status'))
                    <p role="status">{{ session('status') }}</p>
                @endif
                @if (session('error'))
                    <p role="alert">{{ session('error') }}</p>
                @endif
                @if ($errors->any())
                    <p role="alert">{{ __('admin.form_errors', ['count' => $errors->count()]) }}</p>
                @endif
                @foreach ((array) session('import_errors') as $importError)
                    <p role="alert">{{ $importError }}</p>
                @endforeach
                <button type="button" aria-label="{{ __('app.close') }}"><x-icon name="close" size="16" /></button>
            </section>
        @endif

        @yield('content')
    </main>

    {{-- Shared confirmation dialog for every [data-confirm] form --}}
    <dialog id="confirmar" aria-labelledby="confirmar-titol">
        <form method="dialog">
            <h2 id="confirmar-titol">{{ __('admin.confirm_title') }}</h2>
            <p></p>
            <button value="cancel" autofocus>{{ __('admin.cancel') }}</button>
            <button value="ok">{{ __('admin.confirm_delete') }}</button>
        </form>
    </dialog>
</body>
</html>
