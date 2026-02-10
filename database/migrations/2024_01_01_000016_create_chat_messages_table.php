<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('session_id')->constrained('chat_sessions');
            $table->enum('sender_type', ['patient', 'ai', 'doctor', 'system']);
            $table->text('message_text');
            $table->jsonb('referenced_entities')->nullable();
            $table->jsonb('extracted_entities')->nullable();
            $table->string('ai_model_used')->nullable();
            $table->integer('ai_prompt_tokens')->nullable();
            $table->integer('ai_completion_tokens')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
