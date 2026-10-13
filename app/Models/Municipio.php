<?php

namespace App\Models;

use App\Support\FiestaCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Municipio extends Model
{
    use HasFactory;

    protected $fillable = ['comarca_id', 'nombre', 'slug', 'lat', 'lng'];

    protected function casts(): array
    {
        return [
            'lat' => 'float',
            'lng' => 'float',
        ];
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

    public function comarca(): BelongsTo
    {
        return $this->belongsTo(Comarca::class);
    }

    public function fiestas(): HasMany
    {
        return $this->hasMany(Fiesta::class);
    }
}
