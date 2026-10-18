<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('analyses')) {
            return;
        }

        Schema::table('analyses', function (Blueprint $table) {
            $table->text('resume_ia')->nullable()->after('rapport');
            $table->timestamp('ia_genere_at')->nullable()->after('resume_ia');
        });
    }

    public function down(): void
    {
        Schema::table('analyses', function (Blueprint $table) {
            $table->dropColumn(['resume_ia', 'ia_genere_at']);
        });
    }
};
