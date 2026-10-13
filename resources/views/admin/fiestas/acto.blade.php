{{-- One row of the programme repeater. $i is a number, or "__INDEX__" inside the <template>. --}}
<li>
    <label>{{ __('admin.acto_dia') }}
        <input type="date" name="actos[{{ $i }}][dia]" value="{{ $acto['dia'] ?? '' }}" required>
        @error("actos.$i.dia")<small role="alert">{{ $message }}</small>@enderror
    </label>
    <label>{{ __('admin.acto_hora') }}
        <input type="time" name="actos[{{ $i }}][hora]" value="{{ $acto['hora'] ?? '' }}">
        @error("actos.$i.hora")<small role="alert">{{ $message }}</small>@enderror
    </label>
    <label>{{ __('admin.acto_titulo') }}
        <input name="actos[{{ $i }}][titulo]" value="{{ $acto['titulo'] ?? '' }}" maxlength="255" required>
        @error("actos.$i.titulo")<small role="alert">{{ $message }}</small>@enderror
    </label>
    <label>{{ __('admin.acto_lugar') }}
        <input name="actos[{{ $i }}][lugar]" value="{{ $acto['lugar'] ?? '' }}" maxlength="255">
    </label>
    <button type="button" data-remove aria-label="{{ __('admin.remove_acto') }}" title="{{ __('admin.remove_acto') }}"><x-icon name="trash" size="18" /></button>
</li>
