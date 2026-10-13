<?php

namespace Database\Factories;

use App\Models\Acto;
use App\Models\Fiesta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Acto>
 */
class ActoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'fiesta_id' => Fiesta::factory(),
            'dia' => today()->addDays(10),
            'hora' => fake()->time('H:i'),
            'titulo' => fake()->randomElement(['Pregó', 'Cercavila', 'Diada castellera', 'Correfoc', 'Concert', 'Focs artificials']),
            'lugar' => fake()->randomElement(['Plaça Major', 'Plaça de la Vila', 'Rambla']),
        ];
    }
}
