<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('visit_notes', function (Blueprint $table) {
            $table->jsonb('medical_terms')->nullable()->after('additional_sections');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visit_notes', function (Blueprint $table) {
            $table->dropColumn('medical_terms');
        });
    }
};
