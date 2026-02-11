<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // PostgreSQL: alter the enum type to add the new value
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TYPE transcripts_processing_status_check ADD VALUE IF NOT EXISTS 'insufficient_content'");
        }
    }

    public function down(): void
    {
        // Enum values cannot be removed in PostgreSQL
    }
};
