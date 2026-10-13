@extends('layouts.admin')

@section('title', __('admin.comarcas'))

@section('content')
    <header class="capcalera">
        <h1>{{ __('admin.comarcas') }}</h1>
        <p>{{ trans_choice('admin.n_comarcas', $comarcas->total(), ['count' => $comarcas->total()]) }}</p>
        <a href="{{ route('admin.comarcas.create') }}"><x-icon name="plus" size="18" /> {{ __('admin.new_comarca') }}</a>
    </header>

    <form class="eines" method="get" aria-label="{{ __('admin.filter') }}">
        <label>{{ __('admin.province') }}
            <select name="provincia">
                <option value="">{{ __('admin.all') }}</option>
                @foreach (\App\Models\Comarca::PROVINCIAS as $p)
                    <option value="{{ $p }}" @selected($provincia === $p)>{{ $p }}</option>
                @endforeach
            </select>
        </label>
        <button type="submit">{{ __('admin.filter') }}</button>
    </form>

    <section class="taula" aria-label="{{ __('admin.comarcas') }}">
        <table>
            <thead>
                <tr>
                    <th scope="col">{{ __('admin.name') }}</th>
                    <th scope="col">{{ __('admin.province') }}</th>
                    <th scope="col">GeoJSON</th>
                    <th scope="col">{{ __('admin.municipios') }}</th>
                    <th scope="col"><span>{{ __('admin.actions') }}</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($comarcas as $c)
                    <tr>
                        <th scope="row"><a href="{{ route('admin.comarcas.edit', $c) }}">{{ $c->nombre }}</a></th>
                        <td>{{ $c->provincia }}</td>
                        <td><small>{{ $c->geojson_id ?: '—' }}</small></td>
                        <td>{{ $c->municipios_count }}</td>
                        <td>
                            <menu aria-label="{{ __('admin.actions_for', ['name' => $c->nombre]) }}">
                                <li><a href="{{ route('admin.comarcas.edit', $c) }}" aria-label="{{ __('admin.edit') }}" title="{{ __('admin.edit') }}"><x-icon name="edit" size="18" /></a></li>
                                <li>
                                    <form method="post" action="{{ route('admin.comarcas.destroy', $c) }}" data-confirm="{{ __('admin.confirm_delete_named', ['name' => $c->nombre]) }}">
                                        @csrf @method('DELETE')
                                        <button type="submit" aria-label="{{ __('admin.delete') }}" title="{{ __('admin.delete') }}" @disabled($c->municipios_count > 0)><x-icon name="trash" size="18" /></button>
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

    {{ $comarcas->links() }}
@endsection
