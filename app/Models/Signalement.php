<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Signalement extends Model
{
    use HasFactory;

    /**
     * Le statut n'est pas modifiable par le consommateur : il est validé ou rejeté par l'administration.
     */
    protected $fillable = ['produit_id', 'motif', 'description', 'preuve'];

    protected $attributes = ['statut' => 'en_attente'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class);
    }

    public function scopeValides(Builder $query): void
    {
        $query->where('statut', 'valide');
    }

    public function estEnAttente(): bool
    {
        return $this->statut === 'en_attente';
    }

    public function motifLabel(): string
    {
        return config('nutritrace.options.signalement_motifs')[$this->motif] ?? $this->motif;
    }

    public function statutLabel(): string
    {
        return config('nutritrace.options.signalement_statuts')[$this->statut] ?? $this->statut;
    }

    /**
     * Classe de la pastille de statut (status-pill du Front Office).
     */
    public function statutClasse(): string
    {
        return match ($this->statut) {
            'valide' => 'status-valid',
            'rejete' => 'status-expired',
            default => 'status-pending',
        };
    }

    public function preuveEstImage(): bool
    {
        return $this->preuve !== null && in_array(strtolower(pathinfo($this->preuve, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true);
    }
}
