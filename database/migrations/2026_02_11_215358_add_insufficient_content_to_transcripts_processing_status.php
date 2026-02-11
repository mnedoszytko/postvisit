<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // No-op: 'insufficient_content' is already included in the enum definition
        // in create_transcripts_table migration. Laravel uses varchar + CHECK constraint
        // on PostgreSQL, not a named enum type, so ALTER TYPE does not apply.
    }

    public function down(): void
    {
        // Enum values cannot be removed in PostgreSQL
    }
};
