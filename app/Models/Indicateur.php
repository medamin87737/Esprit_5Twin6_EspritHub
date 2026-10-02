<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Indicateur extends Model
{
    use HasFactory;

    /**
     * Unité attendue pour chaque type d'indicateur.
     */
    public const UNITES = ['eau' => 'L', 'energie' => 'kWh', 'transport' => 'km', 'emballage' => 'kg'];

    public const ICONES = ['eau' => 'bi-droplet', 'energie' => 'bi-lightning-charge', 'transport' => 'bi-truck', 'emballage' => 'bi-box2'];

    protected $fillable = ['empreinte_carbone_id', 'etape_id', 'type', 'valeur', 'unite'];

    protected function casts(): array
    {
        return [
            'valeur' => 'float',
        ];
    }

    public function empreinteCarbone(): BelongsTo
    {
        return $this->belongsTo(EmpreinteCarbone::class);
    }

    public function etape(): BelongsTo
    {
        return $this->belongsTo(Etape::class);
    }

    public function typeLabel(): string
    {
        return config('nutritrace.options.indicateur_types')[$this->type] ?? ucfirst($this->type);
    }

    public function icone(): string
    {
        return self::ICONES[$this->type] ?? 'bi-speedometer2';
    }
}
