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
        Schema::create('patient_context_summaries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignUuid('visit_id')->nullable()->constrained('visits')->nullOnDelete();
            $table->foreignUuid('session_id')->nullable()->constrained('chat_sessions')->nullOnDelete();
            $table->text('summary_text');
            $table->jsonb('key_questions')->default('[]');
            $table->jsonb('concerns_raised')->default('[]');
            $table->jsonb('followup_items')->default('[]');
            $table->text('emotional_context')->nullable();
            $table->integer('token_count')->default(0);
            $table->timestamps();

            $table->index('patient_id');
            $table->index('created_at');
            $table->unique('session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_context_summaries');
    }
};
