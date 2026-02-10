<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('patient_id')->constrained('patients');
            $table->enum('consent_type', ['privacy', 'data_sharing', 'research', 'telehealth', 'recording']);
            $table->enum('status', ['active', 'withdrawn', 'expired']);
            $table->timestamp('consented_at');
            $table->timestamp('withdrawn_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->string('version');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consents');
    }
};
