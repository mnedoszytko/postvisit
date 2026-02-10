<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conditions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('fhir_condition_id')->unique();
            $table->foreignUuid('patient_id')->constrained('patients');
            $table->foreignUuid('visit_id')->nullable()->constrained('visits');
            $table->enum('code_system', ['ICD-10-CM', 'SNOMED-CT', 'ICD-11']);
            $table->string('code')->index();
            $table->string('code_display');
            $table->enum('category', ['problem-list-item', 'encounter-diagnosis', 'chief-complaint']);
            $table->enum('clinical_status', ['active', 'inactive', 'resolved', 'remission']);
            $table->enum('verification_status', ['unconfirmed', 'provisional', 'confirmed', 'refuted']);
            $table->enum('severity', ['mild', 'moderate', 'severe'])->nullable();
            $table->date('onset_date')->nullable();
            $table->date('abatement_date')->nullable();
            $table->text('clinical_notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conditions');
    }
};
