<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lot extends Model
{
    use HasFactory;

    protected $fillable = ['produit_id', 'numero_lot', 'quantite', 'date_production', 'date_peremption'];

    protected function casts(): array
    {
        return [
            'quantite' => 'integer',
            'date_production' => 'date',
            'date_peremption' => 'date',
        ];
    }

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class);
    }

    public function etapes(): HasMany
    {
        return $this->hasMany(Etape::class);
    }

    public function estPerime(): bool
    {
        return (bool) $this->date_peremption?->isPast();
    }
}
