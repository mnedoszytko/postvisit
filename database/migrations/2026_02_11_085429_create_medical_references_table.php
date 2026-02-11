<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_references', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('authors')->nullable();
            $table->string('journal')->nullable();
            $table->integer('year');
            $table->string('doi')->nullable()->unique();
            $table->string('pmid')->nullable()->unique();
            $table->string('url')->nullable();
            $table->string('source_organization')->nullable(); // ESC, AHA, ACC, WHO, etc.
            $table->string('category'); // guideline, meta_analysis, rct, review, case_report
            $table->string('specialty')->nullable(); // cardiology, general, etc.
            $table->text('summary')->nullable();
            $table->boolean('verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index('category');
            $table->index('specialty');
            $table->index('source_organization');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_references');
    }
};
