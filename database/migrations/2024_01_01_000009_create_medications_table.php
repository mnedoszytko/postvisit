<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('rxnorm_code')->unique()->index();
            $table->string('atc_code')->nullable();
            $table->string('ndc_code')->nullable();
            $table->string('generic_name')->index();
            $table->jsonb('brand_names')->nullable();
            $table->string('display_name');
            $table->string('form');
            $table->decimal('strength_value', 10, 4);
            $table->string('strength_unit');
            $table->jsonb('ingredients')->nullable();
            $table->boolean('black_box_warning')->default(false);
            $table->string('pregnancy_category')->nullable();
            $table->enum('source', ['rxnorm', 'drugbank', 'local']);
            $table->timestamp('source_last_updated')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medications');
    }
};
