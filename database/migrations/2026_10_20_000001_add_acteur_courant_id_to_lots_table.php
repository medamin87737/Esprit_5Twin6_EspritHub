<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->foreignId('acteur_courant_id')->nullable()->after('produit_id')
                ->constrained('acteurs')->nullOnDelete();
        });

        // Lots déjà enregistrés : l'acteur courant est celui de la dernière étape.
        DB::table('lots')->orderBy('id')->each(function (object $lot) {
            $acteurId = DB::table('etapes')->where('lot_id', $lot->id)->orderByDesc('date_heure')->value('acteur_id');

            if ($acteurId) {
                DB::table('lots')->where('id', $lot->id)->update(['acteur_courant_id' => $acteurId]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->dropConstrainedForeignId('acteur_courant_id');
        });
    }
};
