<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Etape extends Model
{
    use HasFactory;

    protected $fillable = ['lot_id', 'acteur_id', 'type_etape', 'date_heure', 'lieu', 'mode_transport', 'remarques'];

    protected function casts(): array
    {
        return [
            'date_heure' => 'datetime',
        ];
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    public function acteur(): BelongsTo
    {
        return $this->belongsTo(Acteur::class);
    }

    public function typeLabel(): string
    {
        return config('nutritrace.options.etape_types')[$this->type_etape] ?? ucfirst($this->type_etape);
    }

    public function transportLabel(): string
    {
        return config('nutritrace.options.modes_transport')[$this->mode_transport] ?? $this->mode_transport;
    }
}
