<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('observations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('fhir_observation_id')->unique();
            $table->foreignUuid('patient_id')->constrained('patients');
            $table->foreignUuid('visit_id')->nullable()->constrained('visits');
            $table->foreignUuid('practitioner_id')->nullable()->constrained('practitioners');

            // Coding
            $table->enum('code_system', ['LOINC', 'SNOMED-CT', 'LOCAL']);
            $table->string('code')->index();
            $table->string('code_display');
            $table->string('category');
            $table->enum('status', ['registered', 'preliminary', 'final', 'amended', 'cancelled']);

            // Value (polymorphic)
            $table->enum('value_type', ['quantity', 'string', 'boolean', 'codeable']);
            $table->decimal('value_quantity', 12, 4)->nullable();
            $table->string('value_unit')->nullable();
            $table->text('value_string')->nullable();
            $table->boolean('value_boolean')->nullable();

            // Reference range
            $table->decimal('reference_range_low', 12, 4)->nullable();
            $table->decimal('reference_range_high', 12, 4)->nullable();
            $table->text('reference_range_text')->nullable();
            $table->enum('interpretation', ['L', 'LL', 'H', 'HH', 'N'])->nullable();

            // Timing
            $table->date('effective_date');
            $table->timestamp('issued_at')->nullable();

            // Specialty extension
            $table->jsonb('specialty_data')->nullable();

            // Audit
            $table->foreignUuid('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('observations');
    }
};
