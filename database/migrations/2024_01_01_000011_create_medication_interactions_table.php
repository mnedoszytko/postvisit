<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medication_interactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('drug_a_id')->constrained('medications');
            $table->foreignUuid('drug_b_id')->constrained('medications');
            $table->enum('severity', ['minor', 'moderate', 'major', 'contraindicated']);
            $table->text('description');
            $table->text('management');
            $table->enum('source_database', ['drugbank', 'rxnorm', 'fda', 'local']);
            $table->boolean('should_alert')->default(true);
            $table->timestamp('created_at')->nullable();

            $table->unique(['drug_a_id', 'drug_b_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medication_interactions');
    }
};
