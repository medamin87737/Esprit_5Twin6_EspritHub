<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organismes', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 120)->unique();
            $table->string('pays', 80);
            $table->string('site_web');
            $table->string('accreditation', 100);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organismes');
    }
};
