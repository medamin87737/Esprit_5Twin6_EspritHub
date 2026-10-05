<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organisme extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'pays', 'site_web', 'accreditation'];

    public function certifications(): HasMany
    {
        return $this->hasMany(Certification::class);
    }

    /**
     * Produits certifiés par cet organisme (N-N, la table certifications sert de pivot).
     */
    public function produits(): BelongsToMany
    {
        return $this->belongsToMany(Produit::class, 'certifications')
            ->withPivot(['numero', 'type', 'statut', 'date_expiration'])
            ->withTimestamps();
    }
}
