<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produit_id')->constrained('produits')->restrictOnDelete();
            $table->string('numero_lot', 30)->unique();
            $table->unsignedInteger('quantite');
            $table->date('date_production');
            $table->date('date_peremption');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lots');
    }
};
