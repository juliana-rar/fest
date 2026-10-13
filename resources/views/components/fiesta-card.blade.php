@props(['fiesta', 'transition' => false, 'eager' => false])
{{--
    Festival card. One classed parent; children are styled by element type:
    img · h3 (stretched link) · p (place) · time (dates) · mark (countdown) · a:last-child (share)
--}}
<article class="fiesta" data-estat="{{ $fiesta->estaEnCurso() ? 'en-curs' : 'propera' }}" data-dies="{{ $fiesta->diasRestantes() }}">
    <img src="{{ $fiesta->thumbUrl() }}" alt="{{ __('app.image_of', ['name' => $fiesta->nombre]) }}" width="640" height="360"
         loading="{{ $eager ? 'eager' : 'lazy' }}" decoding="async"
         @if ($transition) style="view-transition-name: festa-{{ $fiesta->id }}" @endif>
    <h3><a href="{{ route('fiestas.show', $fiesta) }}">{{ $fiesta->nombre }}</a></h3>
    <p>{{ $fiesta->municipio->nombre }} · {{ $fiesta->municipio->comarca->nombre }}</p>
    <time datetime="{{ $fiesta->fecha_inicio->toDateString() }}">{{ $fiesta->rangoFechas() }}</time>
    <mark>{{ $fiesta->cuentaAtras() }}</mark>
    <a href="{{ route('fiestas.show', $fiesta) }}#story" aria-label="{{ __('app.share_named', ['name' => $fiesta->nombre]) }}"><x-icon name="share" size="18" /></a>
</article>
