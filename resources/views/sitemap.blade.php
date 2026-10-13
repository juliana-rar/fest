{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ route('home') }}</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
@foreach ($fiestas as $fiesta)
    <url>
        <loc>{{ route('fiestas.show', $fiesta) }}</loc>
        <lastmod>{{ $fiesta->updated_at?->toAtomString() }}</lastmod>
        <changefreq>{{ $fiesta->fecha_fin->isPast() ? 'yearly' : 'weekly' }}</changefreq>
        <priority>{{ $fiesta->fecha_fin->isPast() ? '0.4' : '0.8' }}</priority>
    </url>
@endforeach
</urlset>
