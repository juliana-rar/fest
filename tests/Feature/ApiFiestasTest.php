<?php

use App\Models\Fiesta;
use App\Models\Tipo;
use Illuminate\Support\Carbon;

beforeEach(function () {
    Carbon::setTestNow('2026-08-10 12:00:00');
    $this->barcelona = municipio('Barcelona', 41.3874, 2.1686);
    $this->girona = municipio('Girona', 41.9794, 2.8214, 'Gironès', 'Girona');

    $this->gracia = Fiesta::factory()->for($this->barcelona)->between('2026-08-15', '2026-08-21')->create(['nombre' => 'Festa Major de Gràcia']);
    $this->merce = Fiesta::factory()->for($this->barcelona)->between('2026-09-19', '2026-09-24')->create(['nombre' => 'La Mercè']);
    $this->narcis = Fiesta::factory()->for($this->girona)->between('2026-10-23', '2026-11-01')->create(['nombre' => 'Sant Narcís']);
    $this->esborrany = Fiesta::factory()->for($this->girona)->borrador()->between('2026-08-20', '2026-08-22')->create();
});

afterEach(fn () => Carbon::setTestNow());

it('lists upcoming published festivals with the resource shape', function () {
    $this->getJson('/api/fiestas')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.slug', $this->gracia->slug)
        ->assertJsonStructure(['data' => [[
            'id', 'nombre', 'slug', 'url', 'story_url', 'ics_url', 'imagen', 'thumb',
            'fecha_inicio', 'fecha_fin', 'rango', 'en_curso', 'dias', 'cuenta_atras',
            'municipio' => ['nombre', 'slug', 'lat', 'lng'], 'comarca' => ['nombre', 'slug'], 'provincia',
        ]]]);
});

it('filters by month, province and comarca', function () {
    $this->getJson('/api/fiestas?mes=2026-09')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.slug', $this->merce->slug);
    $this->getJson('/api/fiestas?mes=10&any=2026')->assertOk()->assertJsonCount(1, 'data');
    $this->getJson('/api/fiestas?provincia=Girona')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.slug', $this->narcis->slug);
    $this->getJson('/api/fiestas?comarca=barcelones')->assertOk()->assertJsonCount(2, 'data');
});

it('validates the filters', function () {
    $this->getJson('/api/fiestas?mes=13')->assertUnprocessable()->assertJsonValidationErrors('mes');
    $this->getJson('/api/fiestas?provincia=Madrid')->assertUnprocessable()->assertJsonValidationErrors('provincia');
    $this->getJson('/api/fiestas?comarca=no-existeix')->assertUnprocessable()->assertJsonValidationErrors('comarca');
});

it('returns the next festivals limited by ?limit', function () {
    $this->getJson('/api/fiestas/proximas?limit=2')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.slug', $this->gracia->slug)
        ->assertJsonPath('data.1.slug', $this->merce->slug);

    $this->getJson('/api/fiestas/proximas?limit=500')->assertUnprocessable();
});

it('returns nearby festivals with their distance', function () {
    $response = $this->getJson('/api/fiestas/cercanas?lat=41.39&lng=2.17&radio=30')->assertOk();

    expect($response->json('data'))->toHaveCount(2)
        ->and($response->json('data.0.distancia'))->toBeLessThan(1);

    $this->getJson('/api/fiestas/cercanas?lat=41.39&lng=2.17&radio=150')->assertOk()->assertJsonCount(3, 'data');
});

it('requires coordinates for nearby festivals', function () {
    $this->getJson('/api/fiestas/cercanas?lat=41.39')->assertUnprocessable()->assertJsonValidationErrors('lng');
});

it('counts story shares', function () {
    $this->postJson("/api/fiestas/{$this->gracia->slug}/share")->assertOk()->assertJsonPath('compartidos', 1);

    expect($this->gracia->fresh()->compartidos)->toBe(1);

    $this->postJson("/api/fiestas/{$this->esborrany->slug}/share")->assertNotFound();
});

it('localises the countdown with ?lang', function () {
    $this->getJson('/api/fiestas/proximas?limit=1&lang=es')->assertJsonPath('data.0.cuenta_atras', 'Faltan 5 días');
    $this->getJson('/api/fiestas/proximas?limit=1')->assertJsonPath('data.0.cuenta_atras', 'Falten 5 dies');
});

it('searches municipalities ignoring accents', function () {
    $this->getJson('/api/municipios?q=giro')
        ->assertOk()
        ->assertJsonPath('data.0.nombre', 'Girona')
        ->assertJsonPath('data.0.fiesta.nombre', 'Sant Narcís');
});

it('filters by one or more festival types', function () {
    $fm = Tipo::factory()->create(['slug' => 'festa-major', 'nombre' => 'Festa Major', 'orden' => 0]);
    $barraques = Tipo::factory()->create(['slug' => 'barraques', 'nombre' => 'Barraques', 'color' => '#A78BFA', 'orden' => 1]);
    $fira = Tipo::factory()->create(['slug' => 'fira', 'nombre' => 'Fira', 'orden' => 2]);

    $this->gracia->tipos()->attach([$fm->id]);
    $this->merce->tipos()->attach([$fm->id, $barraques->id]);
    $this->narcis->tipos()->attach([$fira->id]);

    $this->getJson('/api/fiestas?tipo=barraques')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', $this->merce->slug)
        ->assertJsonPath('data.0.tipos.1.slug', 'barraques')
        ->assertJsonPath('data.0.tipos.1.color', '#A78BFA');

    // OR semantics across several types.
    $this->getJson('/api/fiestas?tipo=barraques,fira')->assertOk()->assertJsonCount(2, 'data');
    // Combined with other filters.
    $this->getJson('/api/fiestas?tipo=festa-major&mes=2026-08')->assertOk()->assertJsonCount(1, 'data');

    $this->getJson('/api/fiestas?tipo=<script>')->assertUnprocessable()->assertJsonValidationErrors('tipo');
});

it('exposes the comarca ICGC code for the choropleth', function () {
    $this->barcelona->comarca->update(['geojson_id' => '13']);

    $this->getJson('/api/fiestas?comarca=barcelones')->assertJsonPath('data.0.comarca.codi', '13');
});
