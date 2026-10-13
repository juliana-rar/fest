@extends('layouts.admin')

@section('title', __('admin.dashboard'))

@section('content')
    <header class="capcalera">
        <h1>{{ __('admin.dashboard') }}</h1>
        <p>{{ ucfirst(today()->locale(app()->getLocale())->isoFormat('dddd, D MMMM YYYY')) }}</p>
        <a href="{{ route('admin.fiestas.create') }}"><x-icon name="plus" size="18" /> {{ __('admin.new_fiesta') }}</a>
    </header>

    <dl class="stats">
        <dt>{{ __('admin.stat_month') }}</dt><dd>{{ $stats['mes'] }}</dd>
        <dt>{{ __('admin.stat_now') }}</dt><dd>{{ $stats['en_curso'] }}</dd>
        <dt>{{ __('admin.stat_published') }}</dt><dd>{{ $stats['publicadas'] }}</dd>
        <dt>{{ __('admin.stat_drafts') }}</dt><dd>{{ $stats['borradores'] }}</dd>
        <dt>{{ __('admin.stat_views') }}</dt><dd>{{ number_format($stats['visitas'], 0, ',', '.') }}</dd>
        <dt>{{ __('admin.stat_shares') }}</dt><dd>{{ number_format($stats['compartidos'], 0, ',', '.') }}</dd>
    </dl>

    <section class="ranking" aria-labelledby="mes-vistes">
        <h2 id="mes-vistes">{{ __('admin.most_viewed') }}</h2>
        <ol>
            @php $max = max(1, $masVistas->max('visitas')); @endphp
            @foreach ($masVistas as $f)
                <li style="--v: {{ round($f->visitas / $max * 100) }}%">
                    <a href="{{ route('admin.fiestas.edit', $f) }}">{{ $f->nombre }}</a>
                    <small>{{ $f->municipio->nombre }} · {{ $f->fecha_inicio->year }}</small>
                    <data value="{{ $f->visitas }}">{{ number_format($f->visitas, 0, ',', '.') }}</data>
                </li>
            @endforeach
        </ol>
    </section>

    <section class="ranking" aria-labelledby="mes-compartides">
        <h2 id="mes-compartides">{{ __('admin.most_shared') }}</h2>
        <ol>
            @php $max = max(1, $masCompartidas->max('compartidos')); @endphp
            @foreach ($masCompartidas as $f)
                <li style="--v: {{ round($f->compartidos / $max * 100) }}%">
                    <a href="{{ route('admin.fiestas.edit', $f) }}">{{ $f->nombre }}</a>
                    <small>{{ $f->municipio->nombre }} · {{ $f->fecha_inicio->year }}</small>
                    <data value="{{ $f->compartidos }}">{{ number_format($f->compartidos, 0, ',', '.') }}</data>
                </li>
            @endforeach
        </ol>
    </section>

    <section class="taula" aria-labelledby="del-mes">
        <h2 id="del-mes">{{ __('admin.this_month') }}</h2>
        <table>
            <thead>
                <tr><th scope="col">{{ __('admin.name') }}</th><th scope="col">{{ __('admin.municipio') }}</th><th scope="col">{{ __('admin.dates') }}</th><th scope="col">{{ __('admin.status') }}</th></tr>
            </thead>
            <tbody>
                @forelse ($delMes as $f)
                    <tr>
                        <th scope="row"><a href="{{ route('admin.fiestas.edit', $f) }}">{{ $f->nombre }}</a></th>
                        <td>{{ $f->municipio->nombre }}</td>
                        <td><time datetime="{{ $f->fecha_inicio->toDateString() }}">{{ $f->rangoFechas() }}</time></td>
                        <td><mark data-estat="{{ $f->publicada ? 'si' : 'no' }}">{{ $f->publicada ? __('admin.published') : __('admin.draft') }}</mark></td>
                    </tr>
                @empty
                    <tr><td colspan="4">{{ __('admin.nothing_this_month') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
@endsection
