<?php

use App\Models\Fiesta;
use App\Models\Tipo;
use Illuminate\Support\Carbon;

beforeEach(function () {
    Carbon::setTestNow('2026-08-10 12:00:00');
    $this->barcelona = municipio('Barcelona', 41.3874, 2.1686);
    $this->badalona = municipio('Badalona', 41.4500, 2.2474);
    $this->lleida = municipio('Lleida', 41.6176, 0.6200, 'Segrià', 'Lleida');
});

afterEach(fn () => Carbon::setTestNow());

it('proximas() returns ongoing and upcoming festivals ordered by start date', function () {
    $pasada = Fiesta::factory()->for($this->barcelona)->between('2026-07-01', '2026-07-05')->create();
    $tardor = Fiesta::factory()->for($this->barcelona)->between('2026-09-19', '2026-09-24')->create();
    $enCurso = Fiesta::factory()->for($this->badalona)->between('2026-08-08', '2026-08-12')->create();
    $agost = Fiesta::factory()->for($this->lleida)->between('2026-08-15', '2026-08-21')->create();

    $ids = Fiesta::query()->proximas()->pluck('id')->all();

    expect($ids)->toBe([$enCurso->id, $agost->id, $tardor->id])
        ->not->toContain($pasada->id);
});

it('enCurso() only returns festivals happening today', function () {
    $hoy = Fiesta::factory()->for($this->barcelona)->between('2026-08-10', '2026-08-10')->create();
    $acaba = Fiesta::factory()->for($this->barcelona)->between('2026-08-01', '2026-08-10')->create();
    Fiesta::factory()->for($this->barcelona)->between('2026-08-11', '2026-08-15')->create();
    Fiesta::factory()->for($this->barcelona)->between('2026-08-01', '2026-08-09')->create();

    expect(Fiesta::query()->enCurso()->pluck('id')->sort()->values()->all())
        ->toBe(collect([$hoy->id, $acaba->id])->sort()->values()->all());
});

it('delMes() includes festivals overlapping the month boundaries', function () {
    $cruza = Fiesta::factory()->for($this->barcelona)->between('2026-08-29', '2026-09-02')->create();
    $dins = Fiesta::factory()->for($this->barcelona)->between('2026-09-19', '2026-09-24')->create();
    $fora = Fiesta::factory()->for($this->barcelona)->between('2026-10-01', '2026-10-03')->create();

    $setembre = Fiesta::query()->delMes(9, 2026)->pluck('id');
    $setembreIso = Fiesta::query()->delMes('2026-09')->pluck('id');

    expect($setembre->all())->toContain($cruza->id, $dins->id)->not->toContain($fora->id)
        ->and($setembreIso->sort()->values()->all())->toBe($setembre->sort()->values()->all());
});

it('cercanas() computes Haversine distances and filters by radius', function () {
    $bcn = Fiesta::factory()->for($this->barcelona)->create();
    $bdn = Fiesta::factory()->for($this->badalona)->create();
    $lle = Fiesta::factory()->for($this->lleida)->create();

    $result = Fiesta::query()->cercanas(41.3874, 2.1686, 20)->get();

    expect($result->pluck('id')->all())->toBe([$bcn->id, $bdn->id])
        ->and($result->first()->distancia)->toBeLessThan(0.1)
        // Barcelona ↔ Badalona is ≈ 9.5 km in a straight line.
        ->and($result->last()->distancia)->toBeGreaterThan(8.5)->toBeLessThan(10.5)
        ->and($result->pluck('id'))->not->toContain($lle->id);

    // Barcelona ↔ Lleida ≈ 130 km.
    $lejos = Fiesta::query()->cercanas(41.3874, 2.1686, 200)->get()->firstWhere('id', $lle->id);
    expect($lejos->distancia)->toBeGreaterThan(120)->toBeLessThan(140);
});

it('publicadas() hides drafts', function () {
    Fiesta::factory()->for($this->barcelona)->create();
    Fiesta::factory()->for($this->barcelona)->borrador()->create();

    expect(Fiesta::query()->publicadas()->count())->toBe(1);
});

it('generates a unique slug with the edition year', function () {
    $a = Fiesta::factory()->for($this->barcelona)->create(['nombre' => 'Festa Major de Gràcia', 'fecha_inicio' => '2026-08-15', 'fecha_fin' => '2026-08-21']);
    $b = Fiesta::factory()->for($this->barcelona)->create(['nombre' => 'Festa Major de Gràcia', 'fecha_inicio' => '2026-08-15', 'fecha_fin' => '2026-08-21']);

    expect($a->slug)->toBe('festa-major-de-gracia-2026')
        ->and($b->slug)->toBe('festa-major-de-gracia-2026-2');
});

it('duplicates a festival and its programme to the following year', function () {
    $fiesta = Fiesta::factory()->for($this->barcelona)->create(['nombre' => 'La Mercè', 'fecha_inicio' => '2026-09-19', 'fecha_fin' => '2026-09-24']);
    $fiesta->actos()->create(['dia' => '2026-09-24', 'hora' => '22:00', 'titulo' => 'Piromusical', 'lugar' => 'Montjuïc']);

    $fiesta->tipos()->attach(Tipo::factory()->create());

    $copia = $fiesta->load('actos')->duplicarAlAnySeguent();

    expect($copia->fecha_inicio->toDateString())->toBe('2027-09-19')
        ->and($copia->slug)->toBe('la-merce-2027')
        ->and($copia->publicada)->toBeFalse()
        ->and($copia->actos)->toHaveCount(1)
        ->and($copia->actos->first()->dia->toDateString())->toBe('2027-09-24')
        ->and($copia->tipos)->toHaveCount(1);
});

it('deTipo() returns festivals having any of the given types', function () {
    [$fm, $barraques, $fira] = Tipo::factory()->count(3)->sequence(['slug' => 'fm'], ['slug' => 'barraques'], ['slug' => 'fira'])->create();
    $a = Fiesta::factory()->for($this->barcelona)->create();
    $b = Fiesta::factory()->for($this->barcelona)->create();
    Fiesta::factory()->for($this->barcelona)->create();
    $a->tipos()->attach([$fm->id, $barraques->id]);
    $b->tipos()->attach($fira);

    expect(Fiesta::query()->deTipo('barraques')->pluck('id')->all())->toBe([$a->id])
        ->and(Fiesta::query()->deTipo(['barraques', 'fira'])->count())->toBe(2);
});
