<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Laboratoire extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'ville', 'pays', 'accreditation', 'email', 'telephone'];

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
