<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Produit extends Model
{
    use HasFactory;

    protected $fillable = ['categorie_id', 'fournisseur_id', 'nom', 'code_barres', 'origine', 'description', 'composition', 'image'];

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Categorie::class);
    }

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fournisseur_id');
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
}
