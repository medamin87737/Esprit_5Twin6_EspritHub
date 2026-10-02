<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acteurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_acteur_id')->constrained('type_acteurs')->restrictOnDelete();
            $table->string('nom', 150);
            $table->string('email')->unique();
            $table->string('telephone', 30);
            $table->date('date_inscription');
            $table->string('adresse');
            $table->string('pays', 80)->index();
            $table->decimal('latitude', 9, 6)->nullable();
            $table->decimal('longitude', 9, 6)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acteurs');
    }
};
