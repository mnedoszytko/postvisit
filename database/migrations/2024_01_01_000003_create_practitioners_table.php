<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('practitioners', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('fhir_practitioner_id')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('npi')->unique()->index();
            $table->string('license_number');
            $table->string('medical_degree');
            $table->string('primary_specialty');
            $table->jsonb('secondary_specialties')->nullable();
            $table->foreignUuid('organization_id')->constrained('organizations');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('practitioners');
    }
};
