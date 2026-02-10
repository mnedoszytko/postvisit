<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('visit_id')->unique()->constrained('visits');
            $table->foreignUuid('patient_id')->constrained('patients');
            $table->foreignUuid('author_practitioner_id')->constrained('practitioners');
            $table->enum('composition_type', ['progress_note', 'discharge_summary', 'clinic_note']);
            $table->enum('status', ['preliminary', 'final', 'amended']);

            // SOAP Sections
            $table->text('chief_complaint')->nullable();
            $table->text('history_of_present_illness')->nullable();
            $table->text('review_of_systems')->nullable();
            $table->text('physical_exam')->nullable();
            $table->text('assessment')->nullable();
            $table->jsonb('assessment_codes')->nullable();
            $table->text('plan')->nullable();
            $table->text('follow_up')->nullable();
            $table->string('follow_up_timeframe')->nullable();

            // Specialty sections
            $table->jsonb('additional_sections')->nullable();

            // Signing
            $table->boolean('is_signed')->default(false);
            $table->timestamp('signed_at')->nullable();

            // Audit
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_notes');
    }
};
