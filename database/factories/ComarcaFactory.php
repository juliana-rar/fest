<?php

namespace Database\Factories;

use App\Models\Comarca;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Comarca>
 */
class ComarcaFactory extends Factory
{
    public function definition(): array
    {
        $nombre = 'Comarca '.fake()->unique()->lexify('??????');

        return [
            'nombre' => $nombre,
            'provincia' => fake()->randomElement(Comarca::PROVINCIAS),
            'slug' => Str::slug($nombre),
            'geojson_id' => (string) fake()->numberBetween(1, 43),
        ];
    }
}
