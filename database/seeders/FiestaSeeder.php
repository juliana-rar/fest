<?php

namespace Database\Seeders;

use App\Models\Comarca;
use App\Models\Fiesta;
use App\Models\Municipio;
use App\Models\Tipo;
use App\Services\CoverArt;
use App\Services\ImageStore;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class FiestaSeeder extends Seeder
{
    /** Edition year of the seed data. */
    private const YEAR = 2026;

    public function __construct(private CoverArt $art, private ImageStore $images) {}

    public function run(): void
    {
        $data = require database_path('data/fiestas.php');
        $comarcas = Comarca::query()->pluck('id', 'nombre');

        foreach ($data['tipos'] as $orden => [$slug, $nombre, $nombreEs, $color]) {
            Tipo::query()->updateOrCreate(
                ['slug' => $slug],
                ['nombre' => $nombre, 'nombre_es' => $nombreEs, 'color' => $color, 'orden' => $orden],
            );
        }
        $tipos = Tipo::query()->pluck('id', 'slug');

        $municipios = [];
        foreach ($data['municipios'] as $key => [$nombre, $comarca, $lat, $lng]) {
            $municipios[$key] = Municipio::query()->updateOrCreate(
                ['slug' => Str::slug($nombre)],
                ['nombre' => $nombre, 'comarca_id' => $comarcas[$comarca], 'lat' => $lat, 'lng' => $lng],
            );
        }

        foreach ($data['fiestas'] as $i => [$municipio, $nombre, $start, $end, $destacada, $descripcion, $propios]) {
            $inicio = Carbon::parse(self::YEAR.'-'.$start);
            $fin = Carbon::parse(self::YEAR.'-'.$end);

            $fiesta = Fiesta::query()->create([
                'municipio_id' => $municipios[$municipio]->id,
                'nombre' => $nombre,
                'descripcion' => '<p>'.e($descripcion).'</p>',
                'fecha_inicio' => $inicio,
                'fecha_fin' => $fin,
                'imagen' => $this->images->store($this->art->webp($nombre)),
                'destacada' => $destacada,
                'publicada' => true,
                'visitas' => crc32($nombre) % 900 + 50,
                'compartidos' => crc32(strrev($nombre)) % 120,
            ]);

            $this->seedProgramme($fiesta, $propios, $data['actos'], $data['lugares'], $i);

            $slugs = $data['tipos_por_fiesta'][$nombre] ?? ['festa-major'];
            $fiesta->tipos()->sync($tipos->only($slugs)->values());

            // Festivals already over this year also get next year's edition,
            // exactly like the admin "duplicate to next year" action does.
            if ($fiesta->finalizada()) {
                $next = $fiesta->duplicarAlAnySeguent();
                $next->update(['publicada' => true]);
            }
        }
    }

    /**
     * @param  list<string>  $propios  Signature acts of this festival.
     * @param  list<array{string,string}>  $generic
     * @param  list<string>  $lugares
     */
    private function seedProgramme(Fiesta $fiesta, array $propios, array $generic, array $lugares, int $offset): void
    {
        $days = min(5, $fiesta->fecha_inicio->diffInDays($fiesta->fecha_fin) + 1);
        $n = $offset;

        for ($d = 0; $d < $days; $d++) {
            $dia = $fiesta->fecha_inicio->copy()->addDays($d);

            if ($d === 0) {
                $fiesta->actos()->create(['dia' => $dia, 'hora' => '20:00', 'titulo' => 'Pregó de festa major', 'lugar' => "Balcó de l'Ajuntament"]);
            }

            if ($propio = $propios[$d] ?? null) {
                $fiesta->actos()->create(['dia' => $dia, 'hora' => '21:30', 'titulo' => $propio, 'lugar' => $lugares[$n % count($lugares)]]);
            }

            foreach ([0, 3] as $step) {
                [$hora, $titulo] = $generic[($n + $step + $d) % count($generic)];
                $fiesta->actos()->create(['dia' => $dia, 'hora' => $hora, 'titulo' => $titulo, 'lugar' => $lugares[($n + $step) % count($lugares)]]);
            }

            $n++;
        }

        $fiesta->actos()->create([
            'dia' => $fiesta->fecha_fin,
            'hora' => '23:59',
            'titulo' => "Castell de focs de cloenda",
            'lugar' => $lugares[$offset % count($lugares)],
        ]);
    }
}
