<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Etape extends Model
{
    use HasFactory;

    public const ICONES = [
        'production' => 'bi-flower3',
        'transformation' => 'bi-gear-wide-connected',
        'distribution' => 'bi-truck',
        'vente' => 'bi-shop',
    ];

    protected $fillable = ['lot_id', 'acteur_id', 'type_etape', 'date_heure', 'lieu', 'mode_transport', 'remarques'];

    protected function casts(): array
    {
        return [
            'date_heure' => 'datetime',
        ];
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    public function acteur(): BelongsTo
    {
        return $this->belongsTo(Acteur::class);
    }

    public function indicateurs(): HasMany
    {
        return $this->hasMany(Indicateur::class);
    }

    public function typeLabel(): string
    {
        return config('nutritrace.options.etape_types')[$this->type_etape] ?? ucfirst($this->type_etape);
    }

    public function transportLabel(): string
    {
        return config('nutritrace.options.modes_transport')[$this->mode_transport] ?? $this->mode_transport;
    }

    public function icone(): string
    {
        return self::ICONES[$this->type_etape] ?? 'bi-geo-alt';
    }

    /**
     * Une étape n'est plus modifiable dès que son acteur a transféré le lot.
     */
    public function estVerrouillee(): bool
    {
        return (int) $this->lot?->acteur_courant_id !== (int) $this->acteur_id;
    }
}
