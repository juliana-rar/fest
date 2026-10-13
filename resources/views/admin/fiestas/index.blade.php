@extends('layouts.admin')

@section('title', __('admin.fiestas'))

@section('content')
    <header class="capcalera">
        <h1>{{ __('admin.fiestas') }}</h1>
        <p>{{ trans_choice('admin.n_fiestas', $fiestas->total(), ['count' => $fiestas->total()]) }}</p>
        <a href="{{ route('admin.fiestas.create') }}"><x-icon name="plus" size="18" /> {{ __('admin.new_fiesta') }}</a>
    </header>

    <form class="eines" method="get" role="search" aria-label="{{ __('admin.filter') }}">
        <label>{{ __('admin.search') }}
            <input type="search" name="q" value="{{ $q }}" placeholder="{{ __('admin.search_fiestas') }}">
        </label>
        <label>{{ __('admin.status') }}
            <select name="estado">
                <option value="">{{ __('admin.all') }}</option>
                @foreach (['proximas', 'pasadas', 'publicadas', 'borradores', 'destacadas'] as $opt)
                    <option value="{{ $opt }}" @selected($estado === $opt)>{{ __('admin.estado_'.$opt) }}</option>
                @endforeach
            </select>
        </label>
        <button type="submit">{{ __('admin.filter') }}</button>
    </form>

    <details class="csv">
        <summary>{{ __('admin.csv_title') }}</summary>
        <p>{{ __('admin.csv_help', ['columns' => implode(', ', \App\Services\FiestaCsv::COLUMNS)]) }}</p>
        <a href="{{ route('admin.fiestas.export') }}"><x-icon name="download" size="18" /> {{ __('admin.csv_export') }}</a>
        <form method="post" action="{{ route('admin.fiestas.import') }}" enctype="multipart/form-data">
            @csrf
            <label>{{ __('admin.csv_file') }}
                <input type="file" name="csv" accept=".csv,text/csv" required>
                @error('csv')<small role="alert">{{ $message }}</small>@enderror
            </label>
            <button type="submit">{{ __('admin.csv_import') }}</button>
        </form>
    </details>

    <section class="taula" aria-label="{{ __('admin.fiestas') }}">
        <table>
            <thead>
                <tr>
                    <th scope="col">{{ __('admin.name') }}</th>
                    <th scope="col">{{ __('admin.municipio') }}</th>
                    <th scope="col">{{ __('admin.dates') }}</th>
                    <th scope="col">{{ __('admin.status') }}</th>
                    <th scope="col">{{ __('admin.stats_short') }}</th>
                    <th scope="col"><span>{{ __('admin.actions') }}</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($fiestas as $f)
                    <tr>
                        <th scope="row">
                            <img src="{{ $f->thumbUrl() }}" alt="" width="64" height="36" loading="lazy">
                            <a href="{{ route('admin.fiestas.edit', $f) }}">{{ $f->nombre }}</a>
                            @if ($f->destacada)<abbr title="{{ __('admin.featured') }}">★</abbr>@endif
                        </th>
                        <td>{{ $f->municipio->nombre }} <small>{{ $f->municipio->comarca->nombre }}</small></td>
                        <td><time datetime="{{ $f->fecha_inicio->toDateString() }}">{{ $f->rangoFechas() }} {{ $f->fecha_inicio->year }}</time></td>
                        <td><mark data-estat="{{ $f->publicada ? 'si' : 'no' }}">{{ $f->publicada ? __('admin.published') : __('admin.draft') }}</mark></td>
                        <td><small>{{ __('admin.views_shares', ['v' => $f->visitas, 's' => $f->compartidos]) }}</small></td>
                        <td>
                            <menu aria-label="{{ __('admin.actions_for', ['name' => $f->nombre]) }}">
                                <li><a href="{{ route('fiestas.show', $f) }}" target="_blank" rel="noopener" aria-label="{{ __('admin.view') }}" title="{{ __('admin.view') }}"><x-icon name="eye" size="18" /></a></li>
                                <li><a href="{{ route('admin.fiestas.edit', $f) }}" aria-label="{{ __('admin.edit') }}" title="{{ __('admin.edit') }}"><x-icon name="edit" size="18" /></a></li>
                                <li>
                                    <form method="post" action="{{ route('admin.fiestas.duplicate', $f) }}">
                                        @csrf
                                        <button type="submit" aria-label="{{ __('admin.duplicate_next_year') }}" title="{{ __('admin.duplicate_next_year') }}"><x-icon name="copy" size="18" /></button>
                                    </form>
                                </li>
                                <li>
                                    <form method="post" action="{{ route('admin.fiestas.destroy', $f) }}" data-confirm="{{ __('admin.confirm_delete_fiesta', ['name' => $f->nombre]) }}">
                                        @csrf @method('DELETE')
                                        <button type="submit" aria-label="{{ __('admin.delete') }}" title="{{ __('admin.delete') }}"><x-icon name="trash" size="18" /></button>
                                    </form>
                                </li>
                            </menu>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">{{ __('admin.no_results') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    {{ $fiestas->links() }}
@endsection
