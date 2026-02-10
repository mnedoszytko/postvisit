<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('fhir_medication_request_id')->unique();
            $table->foreignUuid('patient_id')->constrained('patients');
            $table->foreignUuid('practitioner_id')->constrained('practitioners');
            $table->foreignUuid('visit_id')->nullable()->constrained('visits');
            $table->foreignUuid('medication_id')->constrained('medications');
            $table->enum('status', ['active', 'on-hold', 'completed', 'stopped', 'cancelled']);
            $table->enum('intent', ['order', 'plan', 'proposal']);

            // Dosage
            $table->decimal('dose_quantity', 10, 4);
            $table->string('dose_unit');
            $table->string('frequency');
            $table->string('frequency_text')->nullable();
            $table->string('route');

            // Duration
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->integer('duration_days')->nullable();

            // Refills
            $table->integer('number_of_refills')->default(0);
            $table->integer('refills_remaining')->default(0);

            // Instructions
            $table->text('special_instructions')->nullable();
            $table->string('indication')->nullable();
            $table->string('indication_code')->nullable();
            $table->boolean('substitution_allowed')->default(true);

            // Audit
            $table->foreignUuid('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
