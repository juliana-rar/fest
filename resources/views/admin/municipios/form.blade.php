@extends('layouts.admin')

@php $editing = $municipio->exists; @endphp

@section('title', $editing ? $municipio->nombre : __('admin.new_municipio'))

@section('content')
    <header class="capcalera">
        <h1>{{ $editing ? $municipio->nombre : __('admin.new_municipio') }}</h1>
        <p>{{ __('admin.municipio_help') }}</p>
    </header>

    <form class="formulari" method="post" novalidate
          action="{{ $editing ? route('admin.municipios.update', $municipio) : route('admin.municipios.store') }}">
        @csrf
        @if ($editing) @method('PUT') @endif

        <fieldset>
            <legend>{{ __('admin.general') }}</legend>
            <label>{{ __('admin.name') }}
                <input name="nombre" value="{{ old('nombre', $municipio->nombre) }}" required maxlength="255" @error('nombre') aria-invalid="true" @enderror>
                @error('nombre')<small role="alert">{{ $message }}</small>@enderror
            </label>
            <label>{{ __('admin.slug') }}
                <input name="slug" value="{{ old('slug', $municipio->slug) }}" maxlength="255" placeholder="{{ __('admin.slug_auto') }}" @error('slug') aria-invalid="true" @enderror>
                @error('slug')<small role="alert">{{ $message }}</small>@enderror
            </label>
            <label>{{ __('admin.comarca') }}
                <select name="comarca_id" required @error('comarca_id') aria-invalid="true" @enderror>
                    <option value="">{{ __('admin.choose') }}</option>
                    @foreach ($comarcas as $c)
                        <option value="{{ $c->id }}" @selected((int) old('comarca_id', $municipio->comarca_id) === $c->id)>{{ $c->nombre }} ({{ $c->provincia }})</option>
                    @endforeach
                </select>
                @error('comarca_id')<small role="alert">{{ $message }}</small>@enderror
            </label>
        </fieldset>

        <fieldset id="ubicacio">
            <legend>{{ __('admin.location') }}</legend>
            <figure data-picker aria-label="{{ __('admin.pick_on_map') }}"></figure>
            <label>{{ __('admin.lat') }}
                <input type="number" name="lat" step="0.000001" min="40.4" max="42.95" value="{{ old('lat', $municipio->lat) }}" required @error('lat') aria-invalid="true" @enderror>
                @error('lat')<small role="alert">{{ $message }}</small>@enderror
            </label>
            <label>{{ __('admin.lng') }}
                <input type="number" name="lng" step="0.000001" min="0.1" max="3.4" value="{{ old('lng', $municipio->lng) }}" required @error('lng') aria-invalid="true" @enderror>
                @error('lng')<small role="alert">{{ $message }}</small>@enderror
            </label>
            <small>{{ __('admin.pick_on_map') }}</small>
        </fieldset>

        <footer>
            <button type="submit">{{ $editing ? __('admin.save_changes') : __('admin.create') }}</button>
            <a href="{{ route('admin.municipios.index') }}">{{ __('admin.cancel') }}</a>
        </footer>
    </form>
@endsection
