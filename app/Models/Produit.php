<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Produit extends Model
{
    use HasFactory;

    /**
     * Moyenne du CO₂ (kg CO₂e par unité) des lots du produit, base du score global.
     */
    private const CO2_MOYEN = '(select avg(ec.co2_total) from empreinte_carbones ec inner join lots l on l.id = ec.lot_id where l.produit_id = produits.id)';

    protected $fillable = ['categorie_id', 'proprietaire_id', 'nom', 'code_barres', 'origine', 'description', 'composition', 'image'];

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Categorie::class);
    }

    /**
     * Producteur ou transformateur qui gère ce produit depuis son tableau de bord.
     */
    public function proprietaire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proprietaire_id');
    }

    public function acteurs(): BelongsToMany
    {
        return $this->belongsToMany(Acteur::class)->withTimestamps();
    }

    public function lots(): HasMany
    {
        return $this->hasMany(Lot::class);
    }

    public function etapes(): HasManyThrough
    {
        return $this->hasManyThrough(Etape::class, Lot::class);
    }

    public function empreintes(): HasManyThrough
    {
        return $this->hasManyThrough(EmpreinteCarbone::class, Lot::class);
    }

    public function certifications(): HasMany
    {
        return $this->hasMany(Certification::class);
    }

    public function signalements(): HasMany
    {
        return $this->hasMany(Signalement::class);
    }

    public function organismes(): BelongsToMany
    {
        return $this->belongsToMany(Organisme::class, 'certifications')
            ->withPivot(['numero', 'type', 'statut', 'date_expiration'])
            ->withTimestamps();
    }

    public function scopeAvecScore(Builder $query): void
    {
        $query->withAvg('empreintes', 'co2_total');
    }

    /**
     * Produits dont le score global (CO₂ moyen de leurs lots) vaut la lettre donnée.
     */
    public function scopeScore(Builder $query, string $score): void
    {
        [$min, $max] = EmpreinteCarbone::bornes($score);

        $query->whereRaw(self::CO2_MOYEN.' >= ?', [$min])
            ->when($max !== null, fn (Builder $q) => $q->whereRaw(self::CO2_MOYEN.' < ?', [$max]));
    }

    public function co2Moyen(): ?float
    {
        $moyenne = array_key_exists('empreintes_avg_co2_total', $this->getAttributes())
            ? $this->getAttribute('empreintes_avg_co2_total')
            : $this->empreintes()->avg('co2_total');

        return $moyenne === null ? null : round((float) $moyenne, 2);
    }

    public function scoreGlobal(): ?string
    {
        $co2 = $this->co2Moyen();

        return $co2 === null ? null : EmpreinteCarbone::scorePour($co2);
    }
}
