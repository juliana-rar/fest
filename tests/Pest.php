<?php

use App\Models\Comarca;
use App\Models\Municipio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test case
|--------------------------------------------------------------------------
| Feature tests run against the MariaDB "festes_test" database (see
| phpunit.xml): the Haversine scope relies on SQL math functions.
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/** Creates a municipality at real coordinates inside a given comarca/province. */
function municipio(string $nombre, float $lat, float $lng, string $comarca = 'Barcelonès', string $provincia = 'Barcelona'): Municipio
{
    $c = Comarca::query()->firstOrCreate(
        ['slug' => str($comarca)->slug()->toString()],
        ['nombre' => $comarca, 'provincia' => $provincia],
    );

    return Municipio::factory()->for($c)->create([
        'nombre' => $nombre,
        'slug' => str($nombre)->slug()->toString(),
        'lat' => $lat,
        'lng' => $lng,
    ]);
}
