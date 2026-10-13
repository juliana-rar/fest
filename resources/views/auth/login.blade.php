<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('admin.login_title') }} — Festes Majors</title>
    <link rel="icon" href="{{ asset('icons/icon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500..800&family=Inter:wght@400..700&display=swap">
    <script>try{const t=localStorage.getItem('theme');if(t==='light'||t==='dark')document.documentElement.dataset.theme=t}catch(e){}</script>
    @vite(['resources/css/app.css'])
</head>
<body id="login">
    @include('partials.icons')
    <main>
        <form method="post" action="{{ route('login.store') }}" class="formulari" novalidate>
            @csrf
            <h1><x-icon name="logo" size="40" /> {{ __('admin.login_title') }}</h1>
            <p>{{ __('admin.login_intro') }}</p>
            <label>{{ __('admin.email') }}
                <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                @error('email')<small id="email-error" role="alert">{{ $message }}</small>@enderror
            </label>
            <label>{{ __('admin.password') }}
                <input type="password" name="password" required autocomplete="current-password">
            </label>
            <label><input type="checkbox" name="remember" value="1"> {{ __('admin.remember') }}</label>
            <button type="submit">{{ __('admin.login') }}</button>
            <a href="{{ route('home') }}">← {{ __('admin.back_to_site') }}</a>
        </form>
    </main>
</body>
</html>
