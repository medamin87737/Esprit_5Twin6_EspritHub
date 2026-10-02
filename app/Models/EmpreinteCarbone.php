<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmpreinteCarbone extends Model
{
    use HasFactory;

    /**
     * Seuils supérieurs (exclus) en kg CO₂e, alignés sur config('nutritrace.options.scores').
     */
    private const SEUILS = ['A' => 1, 'B' => 2, 'C' => 4, 'D' => 7];

    protected $fillable = ['lot_id', 'co2_total', 'methode', 'date_calcul'];

    protected function casts(): array
    {
        return [
            'co2_total' => 'float',
            'date_calcul' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(fn (self $empreinte) => $empreinte->score = self::scorePour($empreinte->co2_total));
    }

    public static function scorePour(float $co2): string
    {
        foreach (self::SEUILS as $score => $seuil) {
            if ($co2 < $seuil) {
                return $score;
            }
        }

        return 'E';
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    public function indicateurs(): HasMany
    {
        return $this->hasMany(Indicateur::class);
    }
}
