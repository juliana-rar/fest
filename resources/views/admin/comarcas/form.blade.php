@extends('layouts.admin')

@php $editing = $comarca->exists; @endphp

@section('title', $editing ? $comarca->nombre : __('admin.new_comarca'))

@section('content')
    <header class="capcalera">
        <h1>{{ $editing ? $comarca->nombre : __('admin.new_comarca') }}</h1>
        <p>{{ __('admin.comarca_help') }}</p>
    </header>

    <form class="formulari" method="post" novalidate
          action="{{ $editing ? route('admin.comarcas.update', $comarca) : route('admin.comarcas.store') }}">
        @csrf
        @if ($editing) @method('PUT') @endif

        <fieldset>
            <legend>{{ __('admin.general') }}</legend>
            <label>{{ __('admin.name') }}
                <input name="nombre" value="{{ old('nombre', $comarca->nombre) }}" required maxlength="255" @error('nombre') aria-invalid="true" @enderror>
                @error('nombre')<small role="alert">{{ $message }}</small>@enderror
            </label>
            <label>{{ __('admin.slug') }}
                <input name="slug" value="{{ old('slug', $comarca->slug) }}" maxlength="255" placeholder="{{ __('admin.slug_auto') }}" @error('slug') aria-invalid="true" @enderror>
                @error('slug')<small role="alert">{{ $message }}</small>@enderror
            </label>
            <label>{{ __('admin.province') }}
                <select name="provincia" required @error('provincia') aria-invalid="true" @enderror>
                    <option value="">{{ __('admin.choose') }}</option>
                    @foreach (\App\Models\Comarca::PROVINCIAS as $p)
                        <option value="{{ $p }}" @selected(old('provincia', $comarca->provincia) === $p)>{{ $p }}</option>
                    @endforeach
                </select>
                @error('provincia')<small role="alert">{{ $message }}</small>@enderror
            </label>
            <label>{{ __('admin.geojson_id') }}
                <input name="geojson_id" value="{{ old('geojson_id', $comarca->geojson_id) }}" maxlength="10">
                <small>{{ __('admin.geojson_help') }}</small>
                @error('geojson_id')<small role="alert">{{ $message }}</small>@enderror
            </label>
        </fieldset>

        <footer>
            <button type="submit">{{ $editing ? __('admin.save_changes') : __('admin.create') }}</button>
            <a href="{{ route('admin.comarcas.index') }}">{{ __('admin.cancel') }}</a>
        </footer>
    </form>
@endsection
