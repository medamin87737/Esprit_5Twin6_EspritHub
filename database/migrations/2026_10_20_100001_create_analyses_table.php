<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->constrained('lots')->cascadeOnDelete();
            $table->foreignId('laboratoire_id')->constrained('laboratoires')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('numero', 20)->unique();
            $table->string('type', 30)->index();
            $table->date('date_prelevement');
            $table->date('date_resultat')->nullable();
            $table->string('resultat', 20)->default('en_attente')->index();
            $table->text('commentaire')->nullable();
            $table->string('rapport')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analyses');
    }
};
