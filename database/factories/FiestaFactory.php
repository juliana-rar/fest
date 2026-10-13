<?php

namespace Database\Factories;

use App\Models\Fiesta;
use App\Models\Municipio;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Fiesta>
 */
class FiestaFactory extends Factory
{
    public function definition(): array
    {
        $inicio = Carbon::instance(fake()->dateTimeBetween('+5 days', '+10 months'))->startOfDay();

        return [
            'municipio_id' => Municipio::factory(),
            'nombre' => 'Festa Major '.fake()->unique()->words(2, true),
            'descripcion' => '<p>'.fake()->paragraph().'</p>',
            'fecha_inicio' => $inicio,
            'fecha_fin' => $inicio->copy()->addDays(fake()->numberBetween(0, 6)),
            'imagen' => null,
            'destacada' => false,
            'publicada' => true,
        ];
    }

    public function between(string $start, string $end): static
    {
        return $this->state(fn () => ['fecha_inicio' => $start, 'fecha_fin' => $end]);
    }

    public function enCurso(): static
    {
        return $this->between(today()->subDay()->toDateString(), today()->addDays(2)->toDateString());
    }

    public function pasada(): static
    {
        return $this->between(today()->subDays(20)->toDateString(), today()->subDays(15)->toDateString());
    }

    public function borrador(): static
    {
        return $this->state(fn () => ['publicada' => false]);
    }
}
