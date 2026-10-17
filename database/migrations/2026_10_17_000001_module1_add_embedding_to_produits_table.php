<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 1 (Ghada) — Ajout des colonnes embedding et embedding_hash
 * pour les recommandations IA3 (cache des vecteurs d'embedding).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produits', function (Blueprint $table): void {
            $table->json('embedding')->nullable()->after('image');
            $table->string('embedding_hash', 64)->nullable()->after('embedding');
        });
    }

    public function down(): void
    {
        Schema::table('produits', function (Blueprint $table): void {
            $table->dropColumn(['embedding', 'embedding_hash']);
        });
    }
};
