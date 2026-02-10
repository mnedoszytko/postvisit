<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users');
            $table->string('user_role');
            $table->enum('action_type', ['create', 'read', 'update', 'delete', 'download', 'export', 'login', 'logout']);
            $table->string('resource_type');
            $table->uuid('resource_id');
            $table->boolean('success');
            $table->string('ip_address');
            $table->uuid('session_id');
            $table->boolean('phi_accessed');
            $table->jsonb('phi_elements')->nullable();
            $table->timestamp('accessed_at');

            // Composite indices
            $table->index(['user_id', 'accessed_at']);
            $table->index(['resource_id', 'accessed_at']);
            $table->index('phi_accessed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
