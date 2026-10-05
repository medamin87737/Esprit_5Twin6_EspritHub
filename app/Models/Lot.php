<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class Lot extends Model
{
    use HasFactory;

    protected $fillable = ['produit_id', 'acteur_courant_id', 'numero_lot', 'quantite', 'date_production', 'date_peremption'];

    protected static function booted(): void
    {
        static::deleting(fn (Lot $lot) => $lot->analyses->each->supprimerRapport());
    }

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

    /**
     * Acteur qui détient le lot : lui seul peut ajouter des étapes ou le transférer.
     */
    public function acteurCourant(): BelongsTo
    {
        return $this->belongsTo(Acteur::class, 'acteur_courant_id');
    }

    public function etapes(): HasMany
    {
        return $this->hasMany(Etape::class);
    }

    public function empreinteCarbone(): HasOne
    {
        return $this->hasOne(EmpreinteCarbone::class);
    }

    public function scans(): HasMany
    {
        return $this->hasMany(Scan::class);
    }

    public function analyses(): HasMany
    {
        return $this->hasMany(Analyse::class);
    }

    /**
     * Bilan des analyses chargées : une seule non-conformité suffit à rendre le lot non conforme.
     */
    public function syntheseAnalyses(): ?string
    {
        return match (true) {
            $this->analyses->isEmpty() => null,
            $this->analyses->contains('resultat', 'non_conforme') => 'non_conforme',
            $this->analyses->contains('resultat', 'conforme') => 'conforme',
            default => 'en_attente',
        };
    }

    /**
     * Lots détenus par l'acteur ou sur lesquels il a déjà saisi une étape.
     */
    public function scopeConcernant(Builder $query, Acteur $acteur): void
    {
        $query->where(fn (Builder $q) => $q
            ->where('acteur_courant_id', $acteur->id)
            ->orWhereHas('etapes', fn (Builder $e) => $e->where('acteur_id', $acteur->id)));
    }

    public function estChez(?Acteur $acteur): bool
    {
        return $acteur !== null && (int) $this->acteur_courant_id === (int) $acteur->id;
    }

    public function estPerime(): bool
    {
        return (bool) $this->date_peremption?->isPast();
    }

    /**
     * Ajoute un indicateur à une étape et recalcule l'empreinte du lot
     * (créée si elle n'existe pas encore) : CO₂ par unité += valeur × facteur / quantité.
     */
    public function enregistrerIndicateur(Etape $etape, string $type, float $valeur): EmpreinteCarbone
    {
        return DB::transaction(function () use ($etape, $type, $valeur) {
            $empreinte = $this->empreinteCarbone()->firstOrNew([], [
                'co2_total' => 0,
                'methode' => 'Déclaration des acteurs',
            ]);

            $facteur = (float) config("nutritrace.facteurs_co2.{$type}", 0);
            $empreinte->co2_total = round((float) $empreinte->co2_total + $valeur * $facteur / max($this->quantite, 1), 2);
            $empreinte->score = EmpreinteCarbone::scorePour($empreinte->co2_total);
            $empreinte->date_calcul = today();
            $empreinte->save();

            $empreinte->indicateurs()->create([
                'etape_id' => $etape->id,
                'type' => $type,
                'valeur' => $valeur,
                'unite' => Indicateur::UNITES[$type],
            ]);

            return $empreinte;
        });
    }

    /**
     * Supprime une étape et ses indicateurs en retirant leur part du CO₂ du lot.
     */
    public function supprimerEtape(Etape $etape): void
    {
        DB::transaction(function () use ($etape) {
            $empreinte = $this->empreinteCarbone;

            if ($empreinte) {
                $retrait = $etape->indicateurs->sum(
                    fn (Indicateur $i) => $i->valeur * (float) config("nutritrace.facteurs_co2.{$i->type}", 0) / max($this->quantite, 1)
                );

                if ($retrait > 0) {
                    $empreinte->co2_total = max(round($empreinte->co2_total - $retrait, 2), 0);
                    $empreinte->score = EmpreinteCarbone::scorePour($empreinte->co2_total);
                    $empreinte->date_calcul = today();
                    $empreinte->save();
                }
            }

            $etape->indicateurs()->delete();
            $etape->delete();
        });
    }
}
