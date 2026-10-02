<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etapes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->constrained('lots')->cascadeOnDelete();
            $table->foreignId('acteur_id')->constrained('acteurs')->restrictOnDelete();
            $table->string('type_etape', 20)->index();
            $table->dateTime('date_heure');
            $table->string('lieu', 150);
            $table->string('mode_transport', 30);
            $table->text('remarques')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etapes');
    }
};
