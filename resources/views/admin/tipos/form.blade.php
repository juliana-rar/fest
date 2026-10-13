@extends('layouts.admin')

@php $editing = $tipo->exists; @endphp

@section('title', $editing ? $tipo->nombre : __('admin.new_tipo'))

@section('content')
    <header class="capcalera">
        <h1>{{ $editing ? $tipo->nombre : __('admin.new_tipo') }}</h1>
        <p>{{ __('admin.tipos_help') }}</p>
    </header>

    <form class="formulari" method="post" novalidate
          action="{{ $editing ? route('admin.tipos.update', $tipo) : route('admin.tipos.store') }}">
        @csrf
        @if ($editing) @method('PUT') @endif

        <fieldset>
            <legend>{{ __('admin.general') }}</legend>
            <label>{{ __('admin.name') }} (Català)
                <input name="nombre" value="{{ old('nombre', $tipo->nombre) }}" required maxlength="60" lang="ca" @error('nombre') aria-invalid="true" @enderror>
                @error('nombre')<small role="alert">{{ $message }}</small>@enderror
            </label>
            <label>{{ __('admin.name') }} (Castellano)
                <input name="nombre_es" value="{{ old('nombre_es', $tipo->nombre_es) }}" maxlength="60" lang="es">
                @error('nombre_es')<small role="alert">{{ $message }}</small>@enderror
            </label>
            <label>{{ __('admin.slug') }}
                <input name="slug" value="{{ old('slug', $tipo->slug) }}" maxlength="60" placeholder="{{ __('admin.slug_auto') }}" @error('slug') aria-invalid="true" @enderror>
                <small>{{ __('admin.tipo_slug_help') }}</small>
                @error('slug')<small role="alert">{{ $message }}</small>@enderror
            </label>
            <label>{{ __('admin.accent') }}
                <input type="color" name="color" value="{{ old('color', $tipo->color) }}" required>
                @error('color')<small role="alert">{{ $message }}</small>@enderror
            </label>
            <label>{{ __('admin.order') }}
                <input type="number" name="orden" min="0" max="999" value="{{ old('orden', $tipo->orden) }}">
                @error('orden')<small role="alert">{{ $message }}</small>@enderror
            </label>
        </fieldset>

        <footer>
            <button type="submit">{{ $editing ? __('admin.save_changes') : __('admin.create') }}</button>
            <a href="{{ route('admin.tipos.index') }}">{{ __('admin.cancel') }}</a>
        </footer>
    </form>
@endsection
