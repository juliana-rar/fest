@extends('layouts.admin')

@section('title', __('admin.settings'))

@section('content')
    <header class="capcalera">
        <h1>{{ __('admin.settings') }}</h1>
        <p>{{ __('admin.settings_help') }}</p>
    </header>

    <form class="formulari" method="post" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" novalidate>
        @csrf @method('PUT')

        <fieldset>
            <legend>{{ __('admin.hero_texts') }}</legend>
            @foreach (['ca' => 'Català', 'es' => 'Castellano'] as $lang => $label)
                <label>{{ __('admin.hero_title') }} ({{ $label }})
                    <input name="hero_title_{{ $lang }}" value="{{ old("hero_title_$lang", $settings["hero_title_$lang"]) }}" maxlength="120" required lang="{{ $lang }}">
                    @error("hero_title_$lang")<small role="alert">{{ $message }}</small>@enderror
                </label>
                <label>{{ __('admin.hero_subtitle') }} ({{ $label }})
                    <textarea name="hero_subtitle_{{ $lang }}" rows="2" maxlength="300" lang="{{ $lang }}">{{ old("hero_subtitle_$lang", $settings["hero_subtitle_$lang"]) }}</textarea>
                    @error("hero_subtitle_$lang")<small role="alert">{{ $message }}</small>@enderror
                </label>
            @endforeach
        </fieldset>

        <fieldset id="imatge">
            <legend>{{ __('admin.logo') }}</legend>
            <img src="{{ $settings['logo'] ? asset('uploads/'.$settings['logo']) : '' }}" alt="{{ __('admin.image_preview') }}" height="72" @unless ($settings['logo']) hidden @endunless>
            <label>{{ __('admin.upload_logo') }}
                <input type="file" name="logo" accept="image/png,image/webp,image/jpeg">
                <small>{{ __('admin.logo_help') }}</small>
                @error('logo')<small role="alert">{{ $message }}</small>@enderror
            </label>
            @if ($settings['logo'])
                <label><input type="checkbox" name="eliminar_logo" value="1"> {{ __('admin.remove_logo') }}</label>
            @endif
        </fieldset>

        <fieldset class="colors">
            <legend>{{ __('admin.colors') }}</legend>
            @foreach (['color_primary', 'color_secondary', 'color_background'] as $key)
                <label>{{ __('admin.'.$key) }}
                    <input type="color" name="{{ $key }}" value="{{ old($key, $settings[$key]) }}" required>
                    @error($key)<small role="alert">{{ $message }}</small>@enderror
                </label>
            @endforeach
            <small>{{ __('admin.colors_help') }}</small>
        </fieldset>

        <fieldset>
            <legend>{{ __('admin.social') }}</legend>
            @foreach (\App\Models\Setting::SOCIAL_NETWORKS as $network)
                <label>{{ ucfirst($network) }}
                    <input type="url" name="social[{{ $network }}]" value="{{ old("social.$network", $settings['social'][$network] ?? '') }}" placeholder="https://">
                    @error("social.$network")<small role="alert">{{ $message }}</small>@enderror
                </label>
            @endforeach
        </fieldset>

        <fieldset>
            <legend>SEO</legend>
            <label>{{ __('admin.seo_title') }}
                <input name="seo_title" value="{{ old('seo_title', $settings['seo_title']) }}" maxlength="70" required>
                @error('seo_title')<small role="alert">{{ $message }}</small>@enderror
            </label>
            <label>{{ __('admin.seo_description') }}
                <textarea name="seo_description" rows="3" maxlength="170" required>{{ old('seo_description', $settings['seo_description']) }}</textarea>
                @error('seo_description')<small role="alert">{{ $message }}</small>@enderror
            </label>
            <label>{{ __('admin.og_image') }}
                <input type="file" name="og_image" accept="image/png,image/webp,image/jpeg">
                <small>{{ __('admin.og_help') }}</small>
                @error('og_image')<small role="alert">{{ $message }}</small>@enderror
            </label>
        </fieldset>

        <fieldset class="plantilles">
            <legend>{{ __('admin.story_templates') }}</legend>
            <label>{{ __('admin.short_url') }}
                <input name="short_url" value="{{ old('short_url', $settings['short_url']) }}" maxlength="60" required>
                <small>{{ __('admin.short_url_help') }}</small>
                @error('short_url')<small role="alert">{{ $message }}</small>@enderror
            </label>
            @error('story_templates')<small role="alert">{{ $message }}</small>@enderror
            @error('story_default')<small role="alert">{{ $message }}</small>@enderror
            @foreach ($templates as $key => $tpl)
                <fieldset data-template="{{ $key }}">
                    <legend>{{ ucfirst($key) }}</legend>
                    <label><input type="checkbox" name="story_templates[{{ $key }}][enabled]" value="1" @checked(old("story_templates.$key.enabled", $tpl['enabled']))> {{ __('admin.enabled') }}</label>
                    <label><input type="radio" name="story_default" value="{{ $key }}" @checked(old('story_default', $settings['story_default']) === $key)> {{ __('admin.default') }}</label>
                    <label>{{ __('admin.name') }}
                        <input name="story_templates[{{ $key }}][name]" value="{{ old("story_templates.$key.name", $tpl['name']) }}" maxlength="40" required>
                    </label>
                    <label>{{ __('admin.accent') }}
                        <input type="color" name="story_templates[{{ $key }}][accent]" value="{{ old("story_templates.$key.accent", $tpl['accent']) }}">
                    </label>
                </fieldset>
            @endforeach
        </fieldset>

        <footer>
            <button type="submit">{{ __('admin.save_changes') }}</button>
        </footer>
    </form>
@endsection
