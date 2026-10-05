<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laboratoires', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 120)->unique();
            $table->string('ville', 80);
            $table->string('pays', 80);
            $table->string('accreditation', 100);
            $table->string('email')->unique();
            $table->string('telephone', 30);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laboratoires');
    }
};
