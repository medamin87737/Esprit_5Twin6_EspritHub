<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisme_id')->constrained('organismes')->restrictOnDelete();
            $table->foreignId('produit_id')->constrained('produits')->cascadeOnDelete();
            $table->string('numero', 40)->unique();
            $table->string('type', 20)->index();
            $table->string('statut', 20)->index();
            $table->date('date_obtention');
            $table->date('date_expiration');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certifications');
    }
};
