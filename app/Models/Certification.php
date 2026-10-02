<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certification extends Model
{
    use HasFactory;

    public const ICONES = ['bio' => 'bi-flower1', 'local' => 'bi-geo-alt', 'equitable' => 'bi-people'];

    protected $fillable = ['organisme_id', 'produit_id', 'numero', 'type', 'statut', 'date_obtention', 'date_expiration'];

    protected function casts(): array
    {
        return [
            'date_obtention' => 'date',
            'date_expiration' => 'date',
        ];
    }

    public function organisme(): BelongsTo
    {
        return $this->belongsTo(Organisme::class);
    }

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class);
    }

    /**
     * Valide = statut « valide » et date d'expiration non dépassée.
     */
    public function scopeValides(Builder $query): void
    {
        $query->where('statut', 'valide')->whereDate('date_expiration', '>=', today());
    }

    public function estValide(): bool
    {
        return $this->statut === 'valide' && ! $this->date_expiration?->lt(today());
    }

    /**
     * Statut réellement applicable : une certification « valide » dont la date est passée est expirée.
     */
    public function statutEffectif(): string
    {
        return $this->statut === 'valide' && ! $this->estValide() ? 'expiree' : $this->statut;
    }

    public function statutLabel(): string
    {
        return config('nutritrace.options.certification_statuts')[$this->statutEffectif()] ?? $this->statutEffectif();
    }

    public function typeLabel(): string
    {
        return config('nutritrace.options.certification_types')[$this->type] ?? ucfirst($this->type);
    }

    public function icone(): string
    {
        return self::ICONES[$this->type] ?? 'bi-award';
    }

    public function joursRestants(): ?int
    {
        return $this->estValide() ? (int) today()->diffInDays($this->date_expiration) : null;
    }

    public function expireBientot(): bool
    {
        return ($jours = $this->joursRestants()) !== null && $jours <= 30;
    }
}
