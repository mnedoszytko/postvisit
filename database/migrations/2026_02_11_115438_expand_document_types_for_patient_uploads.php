<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE documents DROP CONSTRAINT IF EXISTS documents_document_type_check');
            DB::statement('ALTER TABLE documents DROP CONSTRAINT IF EXISTS documents_content_type_check');

            Schema::table('documents', function (Blueprint $table) {
                $table->string('document_type', 50)->change();
                $table->string('content_type', 50)->change();
            });
        } elseif ($driver === 'sqlite') {
            // SQLite: recreate table without CHECK constraints
            DB::statement('CREATE TABLE documents_new (
                id varchar NOT NULL PRIMARY KEY,
                fhir_document_reference_id varchar NOT NULL,
                patient_id varchar NOT NULL REFERENCES patients(id),
                visit_id varchar REFERENCES visits(id),
                title varchar NOT NULL,
                description text,
                document_type varchar(50) NOT NULL,
                content_type varchar(50) NOT NULL,
                file_path varchar NOT NULL,
                file_size integer NOT NULL,
                file_hash varchar NOT NULL,
                status varchar NOT NULL DEFAULT \'current\',
                document_date date NOT NULL,
                confidentiality_level varchar NOT NULL DEFAULT \'M\',
                created_by varchar REFERENCES users(id),
                created_at timestamp,
                updated_at timestamp,
                retention_until date
            )');
            DB::statement('INSERT INTO documents_new SELECT * FROM documents');
            DB::statement('DROP TABLE documents');
            DB::statement('ALTER TABLE documents_new RENAME TO documents');
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE documents ADD CONSTRAINT documents_document_type_check CHECK (document_type IN ('lab_report','imaging_report','discharge_summary','progress_note','referral'))");
            DB::statement("ALTER TABLE documents ADD CONSTRAINT documents_content_type_check CHECK (content_type IN ('pdf','docx','txt','html','dicom'))");
        }
    }
};
