<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acteur_produit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acteur_id')->constrained('acteurs')->cascadeOnDelete();
            $table->foreignId('produit_id')->constrained('produits')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['acteur_id', 'produit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acteur_produit');
    }
};
