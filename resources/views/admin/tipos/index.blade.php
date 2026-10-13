@extends('layouts.admin')

@section('title', __('admin.tipos'))

@section('content')
    <header class="capcalera">
        <h1>{{ __('admin.tipos') }}</h1>
        <p>{{ __('admin.tipos_help') }}</p>
        <a href="{{ route('admin.tipos.create') }}"><x-icon name="plus" size="18" /> {{ __('admin.new_tipo') }}</a>
    </header>

    <section class="taula" aria-label="{{ __('admin.tipos') }}">
        <table>
            <thead>
                <tr>
                    <th scope="col">{{ __('admin.name') }}</th>
                    <th scope="col">Castellano</th>
                    <th scope="col">Slug</th>
                    <th scope="col">{{ __('admin.fiestas') }}</th>
                    <th scope="col"><span>{{ __('admin.actions') }}</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tipos as $t)
                    <tr>
                        <th scope="row"><mark data-color style="--c: {{ $t->color }}">{{ $t->nombre }}</mark></th>
                        <td>{{ $t->nombre_es ?: '—' }}</td>
                        <td><small>{{ $t->slug }}</small></td>
                        <td>{{ $t->fiestas_count }}</td>
                        <td>
                            <menu aria-label="{{ __('admin.actions_for', ['name' => $t->nombre]) }}">
                                <li><a href="{{ route('admin.tipos.edit', $t) }}" aria-label="{{ __('admin.edit') }}" title="{{ __('admin.edit') }}"><x-icon name="edit" size="18" /></a></li>
                                <li>
                                    <form method="post" action="{{ route('admin.tipos.destroy', $t) }}" data-confirm="{{ __('admin.confirm_delete_tipo', ['name' => $t->nombre, 'count' => $t->fiestas_count]) }}">
                                        @csrf @method('DELETE')
                                        <button type="submit" aria-label="{{ __('admin.delete') }}" title="{{ __('admin.delete') }}"><x-icon name="trash" size="18" /></button>
                                    </form>
                                </li>
                            </menu>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">{{ __('admin.no_results') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
@endsection
