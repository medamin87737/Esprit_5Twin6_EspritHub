<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeActeur extends Model
{
    use HasFactory;

    protected $fillable = ['libelle', 'role_chaine', 'description'];

    public function acteurs(): HasMany
    {
        return $this->hasMany(Acteur::class);
    }
}
