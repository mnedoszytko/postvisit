<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('practitioners', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('npi')->nullable()->change();
            $table->string('license_number')->nullable()->change();
            $table->string('medical_degree')->nullable()->change();
            $table->uuid('organization_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('practitioners', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
            $table->string('npi')->nullable(false)->change();
            $table->string('license_number')->nullable(false)->change();
            $table->string('medical_degree')->nullable(false)->change();
            $table->uuid('organization_id')->nullable(false)->change();
        });
    }
};
