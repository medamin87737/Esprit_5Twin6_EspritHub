<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class Analyse extends Model
{
    use HasFactory;

    public const ICONES = [
        'microbiologique' => 'bi-virus',
        'pesticides' => 'bi-bug',
        'metaux_lourds' => 'bi-magnet',
        'mycotoxines' => 'bi-flower2',
    ];

    /**
     * Rapports PDF stockés hors du dossier public : téléchargement réservé aux membres.
     */
    public const DISQUE = 'local';

    protected $table = 'analyses';

    protected $fillable = ['lot_id', 'laboratoire_id', 'numero', 'type', 'date_prelevement', 'date_resultat', 'resultat', 'commentaire'];

    protected $attributes = ['resultat' => 'en_attente'];

    protected function casts(): array
    {
        return [
            'date_prelevement' => 'date',
            'date_resultat' => 'date',
        ];
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    public function laboratoire(): BelongsTo
    {
        return $this->belongsTo(Laboratoire::class);
    }

    /**
     * Compte (admin ou professionnel) qui a déclaré l'analyse.
     */
    public function declarant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeResultat(Builder $query, string $resultat): void
    {
        $query->where('resultat', $resultat);
    }

    public static function prochainNumero(): string
    {
        $prefixe = 'ANA-'.now()->year.'-';
        $dernier = static::where('numero', 'like', $prefixe.'%')->max('numero');

        return $prefixe.str_pad((string) ((int) substr((string) $dernier, -4) + 1), 4, '0', STR_PAD_LEFT);
    }

    public function estEnAttente(): bool
    {
        return $this->resultat === 'en_attente';
    }

    public function typeLabel(): string
    {
        return config('nutritrace.options.analyse_types')[$this->type] ?? ucfirst((string) $this->type);
    }

    public function resultatLabel(): string
    {
        return config('nutritrace.options.analyse_resultats')[$this->resultat] ?? (string) $this->resultat;
    }

    public function icone(): string
    {
        return self::ICONES[$this->type] ?? 'bi-clipboard2-pulse';
    }

    /**
     * Classe de la pastille de résultat (status-pill du Front Office).
     */
    public function resultatClasse(): string
    {
        return match ($this->resultat) {
            'conforme' => 'status-valid',
            'non_conforme' => 'status-expired',
            default => 'status-pending',
        };
    }

    public function remplacerRapport(?UploadedFile $fichier): void
    {
        if (! $fichier) {
            return;
        }

        $this->supprimerRapport();
        $this->rapport = $fichier->store('analyses', self::DISQUE);
    }

    public function supprimerRapport(): void
    {
        if ($this->rapport) {
            Storage::disk(self::DISQUE)->delete($this->rapport);
            $this->rapport = null;
        }
    }
}
