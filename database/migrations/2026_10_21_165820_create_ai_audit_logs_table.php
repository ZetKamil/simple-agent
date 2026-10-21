<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ai_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tool_name');
            $table->string('action_type')->default('read'); // 'read' or 'write'
            $table->json('arguments');
            $table->json('result')->nullable();
            $table->string('status')->default('executed'); // 'executed', 'pending_approval', 'approved', 'rejected'
            $table->timestamps();

            // Index for fast dashboard querying of pending actions
            $table->index(['status', 'action_type']);
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_audit_logs');
    }
};
