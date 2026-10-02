<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empreinte_carbones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->unique()->constrained('lots')->cascadeOnDelete();
            $table->decimal('co2_total', 8, 2);
            $table->char('score', 1)->index();
            $table->string('methode', 60);
            $table->date('date_calcul');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empreinte_carbones');
    }
};
