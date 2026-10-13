<?php

namespace Database\Factories;

use App\Models\Comarca;
use App\Models\Municipio;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Municipio>
 */
class MunicipioFactory extends Factory
{
    public function definition(): array
    {
        $nombre = fake()->unique()->city();

        return [
            'comarca_id' => Comarca::factory(),
            'nombre' => $nombre,
            'slug' => Str::slug($nombre).'-'.fake()->unique()->numberBetween(1, 99999),
            // Somewhere inside Catalonia's bounding box.
            'lat' => fake()->randomFloat(6, 40.6, 42.7),
            'lng' => fake()->randomFloat(6, 0.3, 3.2),
        ];
    }

    public function at(float $lat, float $lng): static
    {
        return $this->state(fn () => ['lat' => $lat, 'lng' => $lng]);
    }
}
