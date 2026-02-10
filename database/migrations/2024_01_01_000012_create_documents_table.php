<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('fhir_document_reference_id')->unique();
            $table->foreignUuid('patient_id')->constrained('patients');
            $table->foreignUuid('visit_id')->nullable()->constrained('visits');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('document_type', ['lab_report', 'imaging_report', 'discharge_summary', 'progress_note', 'referral']);
            $table->enum('content_type', ['pdf', 'docx', 'txt', 'html', 'dicom']);
            $table->string('file_path');
            $table->bigInteger('file_size');
            $table->string('file_hash');
            $table->enum('status', ['current', 'superseded', 'entered-in-error']);
            $table->date('document_date');
            $table->enum('confidentiality_level', ['U', 'L', 'M', 'H', 'R']);
            $table->foreignUuid('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->date('retention_until')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
