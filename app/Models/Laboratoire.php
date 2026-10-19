<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Laboratoire extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'ville', 'pays', 'latitude', 'longitude', 'accreditation', 'email', 'telephone'];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * Recherche libre sur tous les champs utiles du laboratoire.
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);
        if ($term === '') {
            return;
        }

        $like = '%'.$term.'%';

        $query->where(function (Builder $w) use ($like) {
            $w->where('nom', 'like', $like)
                ->orWhere('ville', 'like', $like)
                ->orWhere('pays', 'like', $like)
                ->orWhere('accreditation', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('telephone', 'like', $like);
        });
    }

    public function analyses(): HasMany
    {
        return $this->hasMany(Analyse::class);
    }

    /**
     * Lots analysés par ce laboratoire (N-N, la table analyses sert de pivot).
     */
    public function lots(): BelongsToMany
    {
        return $this->belongsToMany(Lot::class, 'analyses')
            ->withPivot(['numero', 'type', 'resultat', 'date_prelevement'])
            ->withTimestamps();
    }
}
