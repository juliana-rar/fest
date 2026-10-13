@extends('layouts.admin')

@section('title', __('admin.municipios'))

@section('content')
    <header class="capcalera">
        <h1>{{ __('admin.municipios') }}</h1>
        <p>{{ trans_choice('admin.n_municipios', $municipios->total(), ['count' => $municipios->total()]) }}</p>
        <a href="{{ route('admin.municipios.create') }}"><x-icon name="plus" size="18" /> {{ __('admin.new_municipio') }}</a>
    </header>

    <form class="eines" method="get" role="search" aria-label="{{ __('admin.filter') }}">
        <label>{{ __('admin.search') }}
            <input type="search" name="q" value="{{ $q }}">
        </label>
        <label>{{ __('admin.comarca') }}
            <select name="comarca">
                <option value="">{{ __('admin.all') }}</option>
                @foreach ($comarcas as $c)
                    <option value="{{ $c->slug }}" @selected($comarca === $c->slug)>{{ $c->nombre }}</option>
                @endforeach
            </select>
        </label>
        <button type="submit">{{ __('admin.filter') }}</button>
    </form>

    <section class="taula" aria-label="{{ __('admin.municipios') }}">
        <table>
            <thead>
                <tr>
                    <th scope="col">{{ __('admin.name') }}</th>
                    <th scope="col">{{ __('admin.comarca') }}</th>
                    <th scope="col">{{ __('admin.coordinates') }}</th>
                    <th scope="col">{{ __('admin.fiestas') }}</th>
                    <th scope="col"><span>{{ __('admin.actions') }}</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($municipios as $m)
                    <tr>
                        <th scope="row"><a href="{{ route('admin.municipios.edit', $m) }}">{{ $m->nombre }}</a></th>
                        <td>{{ $m->comarca->nombre }} <small>{{ $m->comarca->provincia }}</small></td>
                        <td><small>{{ number_format($m->lat, 4) }}, {{ number_format($m->lng, 4) }}</small></td>
                        <td>{{ $m->fiestas_count }}</td>
                        <td>
                            <menu aria-label="{{ __('admin.actions_for', ['name' => $m->nombre]) }}">
                                <li><a href="{{ route('admin.municipios.edit', $m) }}" aria-label="{{ __('admin.edit') }}" title="{{ __('admin.edit') }}"><x-icon name="edit" size="18" /></a></li>
                                <li>
                                    <form method="post" action="{{ route('admin.municipios.destroy', $m) }}" data-confirm="{{ __('admin.confirm_delete_named', ['name' => $m->nombre]) }}">
                                        @csrf @method('DELETE')
                                        <button type="submit" aria-label="{{ __('admin.delete') }}" title="{{ __('admin.delete') }}" @disabled($m->fiestas_count > 0)><x-icon name="trash" size="18" /></button>
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

    {{ $municipios->links() }}
@endsection
