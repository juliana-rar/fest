<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Acto extends Model
{
    use HasFactory;

    protected $fillable = ['fiesta_id', 'dia', 'hora', 'titulo', 'lugar'];

    protected function casts(): array
    {
        return [
            'dia' => 'date',
        ];
    }

    public function fiesta(): BelongsTo
    {
        return $this->belongsTo(Fiesta::class);
    }

    /** "21:30" instead of the database "21:30:00". */
    public function horaCorta(): ?string
    {
        return $this->hora ? substr($this->hora, 0, 5) : null;
    }
}
