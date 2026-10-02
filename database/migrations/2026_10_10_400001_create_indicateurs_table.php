<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicateurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empreinte_carbone_id')->constrained('empreinte_carbones')->cascadeOnDelete();
            $table->foreignId('etape_id')->nullable()->constrained('etapes')->nullOnDelete();
            $table->string('type', 20)->index();
            $table->decimal('valeur', 12, 2);
            $table->string('unite', 10);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicateurs');
    }
};
