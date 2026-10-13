<?php

namespace App\Models;

use App\Support\FiestaCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Festival type (Festa Major, Barraques, Fira, Focs…). Many-to-many with Fiesta.
 */
class Tipo extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'nombre_es', 'slug', 'color', 'orden'];

    protected function casts(): array
    {
        return ['orden' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => FiestaCache::flush());
        static::deleted(fn () => FiestaCache::flush());
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function fiestas(): BelongsToMany
    {
        return $this->belongsToMany(Fiesta::class);
    }

    public function scopeOrdenados(Builder $query): Builder
    {
        return $query->orderBy('orden')->orderBy('nombre');
    }

    /** Name in the current locale (Catalan is the source). */
    public function nombreLocal(): string
    {
        return app()->getLocale() === 'es' && $this->nombre_es ? $this->nombre_es : $this->nombre;
    }
}
