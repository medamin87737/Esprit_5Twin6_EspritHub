<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Acteur extends Model
{
    use HasFactory;

    protected $fillable = [
        'type_acteur_id', 'nom', 'email', 'telephone', 'date_inscription',
        'adresse', 'pays', 'latitude', 'longitude',
    ];

    protected function casts(): array
    {
        return [
            'date_inscription' => 'date',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function typeActeur(): BelongsTo
    {
        return $this->belongsTo(TypeActeur::class);
    }

    public function produits(): BelongsToMany
    {
        return $this->belongsToMany(Produit::class)->withTimestamps();
    }

    public function etapes(): HasMany
    {
        return $this->hasMany(Etape::class);
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }
}
