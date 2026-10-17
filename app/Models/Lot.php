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

    public function joursAvantPeremption(): ?int
    {
        if ($this->date_peremption === null) {
            return null;
        }

        return (int) today()->diffInDays($this->date_peremption, false);
    }

    /**
     * Niveau d'alerte de péremption du lot :
     * - 'expire' : date dépassée
     * - 'j7'     : expire sous 7 jours (critique)
     * - 'j30'    : expire sous 30 jours (attention)
     * - 'valide' : expire dans plus de 30 jours
     */
    public function alertePeremption(): ?string
    {
        $jours = $this->joursAvantPeremption();

        if ($jours === null) {
            return null;
        }

        if ($jours < 0) {
            return 'expire';
        }

        if ($jours <= 7) {
            return 'j7';
        }

        if ($jours <= 30) {
            return 'j30';
        }

        return 'valide';
    }

    /**
     * Filtre les lots selon leur échéance de péremption.
     */
    public function scopePeremption(Builder $query, ?string $filtre): void
    {
        if (blank($filtre)) {
            return;
        }

        $aujourdhui = today();

        match ($filtre) {
            'j7' => $query->whereNotNull('date_peremption')
                ->whereBetween('date_peremption', [$aujourdhui, $aujourdhui->copy()->addDays(7)]),
            'j30' => $query->whereNotNull('date_peremption')
                ->whereBetween('date_peremption', [$aujourdhui, $aujourdhui->copy()->addDays(30)]),
            'expire' => $query->whereNotNull('date_peremption')
                ->whereDate('date_peremption', '<', $aujourdhui),
            'valide' => $query->whereNotNull('date_peremption')
                ->whereDate('date_peremption', '>', $aujourdhui->copy()->addDays(30)),
            default => null,
        };
    }

    /**
     * Ajoute un indicateur à une étape et recalcule l'empreinte du lot
     * (créée si elle n'existe pas encore) : CO₂ par unité += valeur × facteur / quantité.
     */
    public function enregistrerIndicateur(Etape $etape, string $type, float $valeur): EmpreinteCarbone
    {
        return DB::transaction(function () use ($etape, $type, $valeur) {
            $empreinte = $this->empreinteCarbone()->firstOrNew([]);

            if (! $empreinte->exists) {
                $empreinte->fill([
                    'co2_total' => 0,
                    'score' => EmpreinteCarbone::scorePour(0),
                    'methode' => 'Déclaration des acteurs',
                    'date_calcul' => today(),
                ]);
                $empreinte->score = EmpreinteCarbone::scorePour(0);
                $empreinte->save();
            }

            $empreinte->indicateurs()->create([
                'etape_id' => $etape->id,
                'type' => $type,
                'valeur' => $valeur,
                'unite' => Indicateur::UNITES[$type],
            ]);

            return $empreinte->fresh();
        });
    }

    /**
     * Supprime une étape et ses indicateurs en retirant leur part du CO₂ du lot.
     */
    public function supprimerEtape(Etape $etape): void
    {
        DB::transaction(function () use ($etape) {
            $etape->indicateurs()->delete();
            $etape->delete();
        });
    }
}
