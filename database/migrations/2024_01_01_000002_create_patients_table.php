<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('fhir_patient_id')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('dob');
            $table->enum('gender', ['male', 'female', 'other', 'unknown']);
            $table->string('email')->unique();
            $table->string('phone');
            $table->string('preferred_language')->default('en');
            $table->string('timezone')->default('UTC');
            $table->string('mrn')->index();
            $table->string('ssn_encrypted')->nullable();

            // GDPR
            $table->boolean('consent_given')->default(false);
            $table->timestamp('consent_date')->nullable();
            $table->boolean('data_sharing_consent')->default(false);
            $table->boolean('right_to_erasure_requested')->default(false);

            // Audit — FK to users added in later migration
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
