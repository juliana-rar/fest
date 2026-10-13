<?php

namespace Database\Seeders;

use App\Models\Comarca;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ComarcaSeeder extends Seeder
{
    /**
     * The 43 comarques of Catalonia (42 + Lluçanès, created in 2023).
     * geojson_id is the official ICGC comarca code, so a GeoJSON layer
     * (e.g. ICGC "divisions administratives") can be joined on it.
     */
    public const COMARCAS = [
        ['01', 'Alt Camp', 'Tarragona'],
        ['02', 'Alt Empordà', 'Girona'],
        ['03', 'Alt Penedès', 'Barcelona'],
        ['04', 'Alt Urgell', 'Lleida'],
        ['05', 'Alta Ribagorça', 'Lleida'],
        ['06', 'Anoia', 'Barcelona'],
        ['07', 'Bages', 'Barcelona'],
        ['08', 'Baix Camp', 'Tarragona'],
        ['09', 'Baix Ebre', 'Tarragona'],
        ['10', 'Baix Empordà', 'Girona'],
        ['11', 'Baix Llobregat', 'Barcelona'],
        ['12', 'Baix Penedès', 'Tarragona'],
        ['13', 'Barcelonès', 'Barcelona'],
        ['14', 'Berguedà', 'Barcelona'],
        ['15', 'Cerdanya', 'Girona'],
        ['16', 'Conca de Barberà', 'Tarragona'],
        ['17', 'Garraf', 'Barcelona'],
        ['18', 'Garrigues', 'Lleida'],
        ['19', 'Garrotxa', 'Girona'],
        ['20', 'Gironès', 'Girona'],
        ['21', 'Maresme', 'Barcelona'],
        ['22', 'Montsià', 'Tarragona'],
        ['23', 'Noguera', 'Lleida'],
        ['24', 'Osona', 'Barcelona'],
        ['25', 'Pallars Jussà', 'Lleida'],
        ['26', 'Pallars Sobirà', 'Lleida'],
        ['27', "Pla d'Urgell", 'Lleida'],
        ['28', "Pla de l'Estany", 'Girona'],
        ['29', 'Priorat', 'Tarragona'],
        ['30', "Ribera d'Ebre", 'Tarragona'],
        ['31', 'Ripollès', 'Girona'],
        ['32', 'Segarra', 'Lleida'],
        ['33', 'Segrià', 'Lleida'],
        ['34', 'Selva', 'Girona'],
        ['35', 'Solsonès', 'Lleida'],
        ['36', 'Tarragonès', 'Tarragona'],
        ['37', 'Terra Alta', 'Tarragona'],
        ['38', 'Urgell', 'Lleida'],
        ['39', "Val d'Aran", 'Lleida'],
        ['40', 'Vallès Occidental', 'Barcelona'],
        ['41', 'Vallès Oriental', 'Barcelona'],
        ['42', 'Moianès', 'Barcelona'],
        ['43', 'Lluçanès', 'Barcelona'],
    ];

    public function run(): void
    {
        foreach (self::COMARCAS as [$code, $nombre, $provincia]) {
            Comarca::query()->updateOrCreate(
                ['slug' => Str::slug($nombre)],
                ['nombre' => $nombre, 'provincia' => $provincia, 'geojson_id' => $code],
            );
        }
    }
}
