<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            if (! Schema::hasColumn('lots', 'audit_ia')) {
                $table->json('audit_ia')->nullable()->after('recit_ia');
            }
            if (! Schema::hasColumn('lots', 'conseil_ia')) {
                $table->json('conseil_ia')->nullable()->after('audit_ia');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            if (Schema::hasColumn('lots', 'audit_ia')) {
                $table->dropColumn('audit_ia');
            }
            if (Schema::hasColumn('lots', 'conseil_ia')) {
                $table->dropColumn('conseil_ia');
            }
        });
    }
};
