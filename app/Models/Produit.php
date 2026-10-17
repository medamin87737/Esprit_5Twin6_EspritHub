<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\DB;

class Produit extends Model
{
    use HasFactory;

    /**
     * Moyenne du CO₂ (kg CO₂e par unité) des lots du produit, base du score global.
     */
    private const CO2_MOYEN = '(select avg(ec.co2_total) from empreinte_carbones ec inner join lots l on l.id = ec.lot_id where l.produit_id = produits.id)';

    protected $fillable = ['categorie_id', 'proprietaire_id', 'nom', 'code_barres', 'origine', 'description', 'composition', 'image'];

    /**
     * embedding : cast en array, jamais exposé en JSON.
     * embedding_hash : SHA-256 du modèle + texte, pour recalculer si changement.
     */
    protected function casts(): array
    {
        return [
            'embedding' => 'array',
        ];
    }

    protected $hidden = ['embedding'];

    /**
     * Texte concaténé utilisé pour calculer l'embedding IA3.
     * Inclut nom, catégorie, description, composition et origine.
     */
    public function texteEmbedding(): string
    {
        return implode(' ', array_filter([
            $this->nom,
            $this->categorie?->nom,
            $this->description,
            $this->composition,
            $this->origine,
        ]));
    }

    /**
     * Hash du texte d'embedding + nom du modèle, pour savoir si le
     * vecteur doit être recalculé (texte modifié ou modèle changé).
     */
    public function hashEmbeddingAttendu(): string
    {
        return hash('sha256', config('ia.modeles.embeddings', '').'|'.$this->texteEmbedding());
    }

    /**
     * Indique si l'embedding en cache est à jour.
     */
    public function embeddingEstAJour(): bool
    {
        return $this->embedding_hash === $this->hashEmbeddingAttendu()
            && ! empty($this->embedding);
    }

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
     * Les seuils sont écrits en dur dans le SQL pour la compatibilité SQLite (tests).
     */
    public function scopeScore(Builder $query, string $score): void
    {
        [$min, $max] = EmpreinteCarbone::bornes($score);

        // Seuils écrits directement dans le SQL (pas de binding) pour éviter
        // le bug SQLite de comparaison REAL vs paramètre texte (piège connu).
        $minSql = number_format((float) $min, 4, '.', '');
        $query->whereRaw(self::CO2_MOYEN.' >= '.$minSql);

        if ($max !== null) {
            $maxSql = number_format((float) $max, 4, '.', '');
            $query->whereRaw(self::CO2_MOYEN.' < '.$maxSql);
        }
    }

    /**
     * Produits dont le score global est inférieur ou égal à la lettre donnée (« X ou mieux »).
     * Ex. scoreMieux('C') retourne les produits A, B et C.
     * Les seuils sont écrits en dur pour la compatibilité SQLite (tests).
     */
    public function scopeScoreMieux(Builder $query, string $score): void
    {
        // Seuils supérieurs (exclus) : A<1, B<2, C<4, D<7, E = pas de max.
        $maxCo2 = match (strtoupper($score)) {
            'A' => 1,
            'B' => 2,
            'C' => 4,
            'D' => 7,
            default => null, // E → pas de contrainte supérieure
        };

        if ($maxCo2 !== null) {
            // On veut CO₂ < maxCo2 (autrement dit : score ≤ $score)
            $query->whereRaw(self::CO2_MOYEN.' < '.(int) $maxCo2);
        }
        // Si score = E, tous les produits avec une empreinte sont inclus → pas de filtre supplémentaire.
        // On exclut tout de même les produits sans empreinte en s'assurant qu'au moins un lot a une empreinte.
        $query->whereRaw(self::CO2_MOYEN.' IS NOT NULL');
    }

    /**
     * Tri par meilleur éco-score (CO₂ moyen croissant, produits sans score en dernier).
     */
    public function scopeTriEcoScore(Builder $query): void
    {
        $query->orderByRaw('CASE WHEN ('.self::CO2_MOYEN.') IS NULL THEN 1 ELSE 0 END')
            ->orderByRaw('('.self::CO2_MOYEN.') ASC');
    }

    /**
     * Tri par nombre de labels valides décroissant.
     * Compatibilité SQLite (tests) et MySQL (prod) : DATE('now') / CURDATE().
     */
    public function scopeTriLabels(Builder $query): void
    {
        // DB::connection()->getDriverName() permet d'adapter le SQL selon le driver
        $dateAujourdhui = DB::connection()->getDriverName() === 'sqlite'
            ? "date('now')"
            : 'CURDATE()';

        $query->orderByRaw(
            "(SELECT COUNT(*) FROM certifications c WHERE c.produit_id = produits.id AND c.statut = 'valide' AND c.date_expiration >= {$dateAujourdhui}) DESC"
        );
    }

    /**
     * Répartition du catalogue par lettre de score (A→E + sans score).
     * Retourne un tableau associatif ['A' => 12, 'B' => 5, …, 'sans_score' => 3].
     *
     * @return array<string, int>
     */
    public static function repartitionScores(): array
    {
        // Seuils en dur pour compatibilité SQLite (tests)
        $sql = '
            SELECT
                SUM(CASE WHEN co2 IS NULL THEN 1 ELSE 0 END) AS sans_score,
                SUM(CASE WHEN co2 IS NOT NULL AND co2 < 1 THEN 1 ELSE 0 END) AS score_a,
                SUM(CASE WHEN co2 >= 1 AND co2 < 2 THEN 1 ELSE 0 END) AS score_b,
                SUM(CASE WHEN co2 >= 2 AND co2 < 4 THEN 1 ELSE 0 END) AS score_c,
                SUM(CASE WHEN co2 >= 4 AND co2 < 7 THEN 1 ELSE 0 END) AS score_d,
                SUM(CASE WHEN co2 >= 7 THEN 1 ELSE 0 END) AS score_e
            FROM (
                SELECT '.self::CO2_MOYEN.' AS co2 FROM produits
            ) AS sous_requete
        ';

        $row = DB::selectOne($sql);

        return [
            'A' => (int) ($row->score_a ?? 0),
            'B' => (int) ($row->score_b ?? 0),
            'C' => (int) ($row->score_c ?? 0),
            'D' => (int) ($row->score_d ?? 0),
            'E' => (int) ($row->score_e ?? 0),
            'sans_score' => (int) ($row->sans_score ?? 0),
        ];
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
