<?php

namespace App\Models;

use App\Support\FiestaCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Comarca extends Model
{
    use HasFactory;

    public const PROVINCIAS = ['Barcelona', 'Girona', 'Lleida', 'Tarragona'];

    protected $fillable = ['nombre', 'provincia', 'slug', 'geojson_id'];

    protected static function booted(): void
    {
        static::saved(fn () => FiestaCache::flush());
        static::deleted(fn () => FiestaCache::flush());
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function municipios(): HasMany
    {
        return $this->hasMany(Municipio::class)->orderBy('nombre');
    }

    public function fiestas(): HasManyThrough
    {
        return $this->hasManyThrough(Fiesta::class, Municipio::class);
    }
}
