<?php

namespace Database\Factories;

use App\Models\Tipo;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tipo>
 */
class TipoFactory extends Factory
{
    public function definition(): array
    {
        $nombre = ucfirst(fake()->unique()->word());

        return [
            'nombre' => $nombre,
            'nombre_es' => $nombre,
            'slug' => Str::slug($nombre),
            'color' => fake()->hexColor(),
            'orden' => fake()->numberBetween(0, 50),
        ];
    }
}
