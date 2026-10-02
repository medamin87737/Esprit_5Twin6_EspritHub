<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categorie_id')->constrained('categories')->restrictOnDelete();
            $table->string('nom', 150);
            $table->string('code_barres', 13)->unique();
            $table->string('origine', 120);
            $table->text('description')->nullable();
            $table->text('composition')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produits');
    }
};
