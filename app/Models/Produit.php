<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Produit extends Model
{
    use HasFactory;

    protected $fillable = ['categorie_id', 'nom', 'code_barres', 'origine', 'description', 'composition', 'image'];

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Categorie::class);
    }
}
