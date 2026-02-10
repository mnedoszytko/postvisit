<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('fhir_encounter_id')->unique();
            $table->foreignUuid('patient_id')->constrained('patients');
            $table->foreignUuid('practitioner_id')->constrained('practitioners');
            $table->foreignUuid('organization_id')->constrained('organizations');
            $table->enum('visit_type', ['office_visit', 'telehealth', 'emergency', 'inpatient']);
            $table->string('class');
            $table->enum('visit_status', ['planned', 'in_progress', 'completed', 'cancelled']);
            $table->string('service_type')->nullable();
            $table->text('reason_for_visit')->nullable();
            $table->jsonb('reason_codes')->nullable();
            $table->text('summary')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->text('provider_notes_followup')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
