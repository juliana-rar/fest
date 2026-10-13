<?php

namespace App\Http\Resources;

use App\Models\Fiesta;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Fiesta
 */
class FiestaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $municipio = $this->municipio;
        $comarca = $municipio?->comarca;

        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'slug' => $this->slug,
            'url' => route('fiestas.show', $this->resource),
            'story_url' => route('story', $this->resource),
            'ics_url' => route('fiestas.ics', $this->resource),
            'imagen' => $this->imagenUrl(),
            'thumb' => $this->thumbUrl(),
            'fecha_inicio' => $this->fecha_inicio->toDateString(),
            'fecha_fin' => $this->fecha_fin->toDateString(),
            'rango' => $this->rangoFechas(),
            'en_curso' => $this->estaEnCurso(),
            'dias' => $this->diasRestantes(),
            'cuenta_atras' => $this->cuentaAtras(),
            'destacada' => $this->destacada,
            'resumen' => $this->descripcionTexto(140),
            'distancia' => $this->when(isset($this->distancia), fn () => round((float) $this->distancia, 1)),
            'municipio' => $municipio ? [
                'nombre' => $municipio->nombre,
                'slug' => $municipio->slug,
                'lat' => $municipio->lat,
                'lng' => $municipio->lng,
            ] : null,
            'comarca' => $comarca ? ['nombre' => $comarca->nombre, 'slug' => $comarca->slug, 'codi' => $comarca->geojson_id] : null,
            'tipos' => $this->whenLoaded('tipos', fn () => $this->tipos->map(fn ($t) => [
                'slug' => $t->slug,
                'nombre' => $t->nombreLocal(),
                'color' => $t->color,
            ])->values()->all(), []),
            'provincia' => $comarca?->provincia,
        ];
    }
}
