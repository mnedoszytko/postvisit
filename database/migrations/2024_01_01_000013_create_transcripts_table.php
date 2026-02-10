<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transcripts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('visit_id')->constrained('visits');
            $table->foreignUuid('patient_id')->constrained('patients');

            // Source
            $table->enum('source_type', ['ambient_phone', 'ambient_device', 'manual_upload']);
            $table->string('stt_provider');
            $table->integer('audio_duration_seconds');
            $table->string('audio_file_path')->nullable();

            // Content
            $table->text('raw_transcript');
            $table->jsonb('diarized_transcript')->nullable();

            // AI Processing
            $table->jsonb('entities_extracted')->nullable();
            $table->jsonb('soap_note')->nullable();
            $table->text('summary')->nullable();
            $table->enum('processing_status', ['pending', 'processing', 'completed', 'failed']);

            // Consent
            $table->boolean('patient_consent_given');
            $table->timestamp('consent_timestamp');

            // Audit
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transcripts');
    }
};
