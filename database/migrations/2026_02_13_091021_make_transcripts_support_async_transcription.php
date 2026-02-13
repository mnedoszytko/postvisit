<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Make raw_transcript nullable — transcript is created BEFORE Whisper runs
        Schema::table('transcripts', function (Blueprint $table) {
            $table->text('raw_transcript')->nullable()->change();
        });

        // Add 'transcribing' to processing_status CHECK constraint (PostgreSQL only)
        // SQLite doesn't enforce enum CHECK constraints the same way
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE transcripts DROP CONSTRAINT IF EXISTS transcripts_processing_status_check');
            DB::statement("ALTER TABLE transcripts ADD CONSTRAINT transcripts_processing_status_check CHECK (processing_status IN ('pending', 'transcribing', 'processing', 'completed', 'failed', 'insufficient_content'))");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE transcripts DROP CONSTRAINT IF EXISTS transcripts_processing_status_check');
            DB::statement("ALTER TABLE transcripts ADD CONSTRAINT transcripts_processing_status_check CHECK (processing_status IN ('pending', 'processing', 'completed', 'failed', 'insufficient_content'))");
        }

        Schema::table('transcripts', function (Blueprint $table) {
            $table->text('raw_transcript')->nullable(false)->change();
        });
    }
};
