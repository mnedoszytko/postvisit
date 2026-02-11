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
        Schema::table('documents', function (Blueprint $table) {
            $table->jsonb('ai_analysis')->nullable()->after('file_hash');
            $table->string('analysis_status', 20)->default('pending')->after('ai_analysis');
            $table->timestamp('analyzed_at')->nullable()->after('analysis_status');
            $table->text('analysis_error')->nullable()->after('analyzed_at');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['ai_analysis', 'analysis_status', 'analyzed_at', 'analysis_error']);
        });
    }
};
