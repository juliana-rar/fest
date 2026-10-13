@extends('layouts.admin')

@php
    $editing = $fiesta->exists;
    $actos = old('actos', $fiesta->actos->map(fn ($a) => [
        'dia' => $a->dia->toDateString(),
        'hora' => $a->horaCorta(),
        'titulo' => $a->titulo,
        'lugar' => $a->lugar,
    ])->all());
@endphp

@section('title', $editing ? $fiesta->nombre : __('admin.new_fiesta'))

@section('content')
    <header class="capcalera">
        <h1>{{ $editing ? $fiesta->nombre : __('admin.new_fiesta') }}</h1>
        <p>
            @if ($editing)
                {{ __('admin.views_shares', ['v' => $fiesta->visitas, 's' => $fiesta->compartidos]) }}
            @else
                {{ __('admin.new_fiesta_help') }}
            @endif
        </p>
        @if ($editing)
            <a href="{{ route('fiestas.show', $fiesta) }}" target="_blank" rel="noopener"><x-icon name="eye" size="18" /> {{ __('admin.preview') }}</a>
        @endif
    </header>

    <form class="formulari" method="post" enctype="multipart/form-data" novalidate
          action="{{ $editing ? route('admin.fiestas.update', $fiesta) : route('admin.fiestas.store') }}">
        @csrf
        @if ($editing) @method('PUT') @endif

        <fieldset>
            <legend>{{ __('admin.general') }}</legend>
            <label>{{ __('admin.name') }}
                <input name="nombre" value="{{ old('nombre', $fiesta->nombre) }}" required maxlength="255" data-slug-source @error('nombre') aria-invalid="true" @enderror>
                @error('nombre')<small role="alert">{{ $message }}</small>@enderror
            </label>
            <label>{{ __('admin.slug') }}
                <input name="slug" value="{{ old('slug', $fiesta->slug) }}" maxlength="255" pattern="[a-z0-9\-]+" data-slug-target data-year-source="fecha_inicio" placeholder="{{ __('admin.slug_auto') }}" @error('slug') aria-invalid="true" @enderror>
                <small>{{ __('admin.slug_help') }}</small>
                @error('slug')<small role="alert">{{ $message }}</small>@enderror
            </label>
            <label>{{ __('admin.municipio') }}
                <select name="municipio_id" required @error('municipio_id') aria-invalid="true" @enderror>
                    <option value="">{{ __('admin.choose') }}</option>
                    @foreach ($comarcas as $comarca)
                        <optgroup label="{{ $comarca->nombre }}">
                            @foreach ($comarca->municipios as $m)
                                <option value="{{ $m->id }}" @selected((int) old('municipio_id', $fiesta->municipio_id) === $m->id)>{{ $m->nombre }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                @error('municipio_id')<small role="alert">{{ $message }}</small>@enderror
            </label>
            <label>{{ __('admin.start_date') }}
                <input type="date" name="fecha_inicio" value="{{ old('fecha_inicio', $fiesta->fecha_inicio?->toDateString()) }}" required @error('fecha_inicio') aria-invalid="true" @enderror>
                @error('fecha_inicio')<small role="alert">{{ $message }}</small>@enderror
            </label>
            <label>{{ __('admin.end_date') }}
                <input type="date" name="fecha_fin" value="{{ old('fecha_fin', $fiesta->fecha_fin?->toDateString()) }}" required @error('fecha_fin') aria-invalid="true" @enderror>
                @error('fecha_fin')<small role="alert">{{ $message }}</small>@enderror
            </label>
            <label><input type="checkbox" name="destacada" value="1" @checked(old('destacada', $fiesta->destacada))> {{ __('admin.featured') }}</label>
            <label><input type="checkbox" name="publicada" value="1" @checked(old('publicada', $fiesta->publicada))> {{ __('admin.published') }}</label>
        </fieldset>

        <fieldset class="tipus">
            <legend>{{ __('admin.tipos') }}</legend>
            @php $seleccionats = array_map('intval', old('tipos', $fiesta->exists ? $fiesta->tipos->pluck('id')->all() : [])); @endphp
            @foreach ($tipos as $tipo)
                <label style="--c: {{ $tipo->color }}"><input type="checkbox" name="tipos[]" value="{{ $tipo->id }}" @checked(in_array($tipo->id, $seleccionats, true))> {{ $tipo->nombre }}</label>
            @endforeach
            @error('tipos')<small role="alert">{{ $message }}</small>@enderror
            @error('tipos.*')<small role="alert">{{ $message }}</small>@enderror
        </fieldset>

        <fieldset id="editor">
            <legend id="editor-label">{{ __('admin.description') }}</legend>
            <menu aria-label="{{ __('admin.editor_toolbar') }}" hidden>
                <li><button type="button" data-cmd="bold" aria-label="{{ __('admin.bold') }}" title="{{ __('admin.bold') }}"><b>B</b></button></li>
                <li><button type="button" data-cmd="italic" aria-label="{{ __('admin.italic') }}" title="{{ __('admin.italic') }}"><i>I</i></button></li>
                <li><button type="button" data-cmd="formatBlock" data-value="h3" aria-label="{{ __('admin.heading') }}" title="{{ __('admin.heading') }}">H</button></li>
                <li><button type="button" data-cmd="insertUnorderedList" aria-label="{{ __('admin.bullets') }}" title="{{ __('admin.bullets') }}">•</button></li>
                <li><button type="button" data-cmd="insertOrderedList" aria-label="{{ __('admin.numbers') }}" title="{{ __('admin.numbers') }}">1.</button></li>
                <li><button type="button" data-cmd="createLink" aria-label="{{ __('admin.link') }}" title="{{ __('admin.link') }}" data-prompt="{{ __('admin.link_prompt') }}">🔗</button></li>
                <li><button type="button" data-cmd="removeFormat" aria-label="{{ __('admin.clear_format') }}" title="{{ __('admin.clear_format') }}">⌫</button></li>
            </menu>
            <div contenteditable="true" role="textbox" aria-multiline="true" aria-labelledby="editor-label" hidden></div>
            <textarea name="descripcion" rows="8" aria-labelledby="editor-label">{{ old('descripcion', $fiesta->descripcion) }}</textarea>
            @error('descripcion')<small role="alert">{{ $message }}</small>@enderror
        </fieldset>

        <fieldset id="imatge">
            <legend>{{ __('admin.image') }}</legend>
            <img src="{{ $fiesta->imagen ? $fiesta->imagenUrl() : '' }}" alt="{{ __('admin.image_preview') }}" width="640" height="360" @unless ($fiesta->imagen) hidden @endunless>
            <label>{{ __('admin.upload_image') }}
                <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp" @error('imagen') aria-invalid="true" @enderror>
                <small>{{ __('admin.image_help') }}</small>
                @error('imagen')<small role="alert">{{ $message }}</small>@enderror
            </label>
            @if ($fiesta->imagen)
                <label><input type="checkbox" name="eliminar_imagen" value="1"> {{ __('admin.remove_image') }}</label>
            @endif
        </fieldset>

        <fieldset id="actes">
            <legend>{{ __('admin.programme') }}</legend>
            <ol>
                @foreach ($actos as $i => $acto)
                    @include('admin.fiestas.acto', ['i' => $i, 'acto' => $acto])
                @endforeach
            </ol>
            <template>
                @include('admin.fiestas.acto', ['i' => '__INDEX__', 'acto' => []])
            </template>
            <button type="button" data-add><x-icon name="plus" size="18" /> {{ __('admin.add_acto') }}</button>
        </fieldset>

        <footer>
            <button type="submit">{{ $editing ? __('admin.save_changes') : __('admin.create') }}</button>
            <a href="{{ route('admin.fiestas.index') }}">{{ __('admin.cancel') }}</a>
        </footer>
    </form>

    @if ($editing)
        <section class="perill" aria-labelledby="altres-accions">
            <h2 id="altres-accions">{{ __('admin.other_actions') }}</h2>
            <form method="post" action="{{ route('admin.fiestas.duplicate', $fiesta) }}">
                @csrf
                <button type="submit"><x-icon name="copy" size="18" /> {{ __('admin.duplicate_next_year') }}</button>
            </form>
            <form method="post" action="{{ route('admin.fiestas.destroy', $fiesta) }}" data-confirm="{{ __('admin.confirm_delete_fiesta', ['name' => $fiesta->nombre]) }}">
                @csrf @method('DELETE')
                <button type="submit"><x-icon name="trash" size="18" /> {{ __('admin.delete') }}</button>
            </form>
        </section>
    @endif
@endsection
