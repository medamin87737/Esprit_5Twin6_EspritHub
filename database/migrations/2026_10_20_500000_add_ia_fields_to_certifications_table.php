<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certifications', function (Blueprint $table) {
            $table->string('document_pdf')->nullable()->after('date_expiration');
            $table->text('ia_verification')->nullable()->after('document_pdf');
            $table->text('ia_incoherences')->nullable()->after('ia_verification');
            $table->timestamp('ia_verifie_at')->nullable()->after('ia_incoherences');
        });
    }

    public function down(): void
    {
        Schema::table('certifications', function (Blueprint $table) {
            $table->dropColumn(['document_pdf', 'ia_verification', 'ia_incoherences', 'ia_verifie_at']);
        });
    }
};
