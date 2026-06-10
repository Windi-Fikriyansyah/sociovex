<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('social_account_id')->nullable()->constrained('social_accounts')->nullOnDelete();
            $table->string('zernio_conversation_id')->unique(); // Zernio's conversation ID
            $table->string('participant_name')->nullable();
            $table->string('participant_picture')->nullable();
            $table->string('participant_id')->nullable(); // Platform-specific participant ID
            $table->string('platform', 50)->nullable();
            $table->string('account_username')->nullable(); // The social account username this conversation belongs to
            $table->string('zernio_account_id')->nullable();
            $table->text('last_message')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->unsignedInteger('unread_count')->default(0);
            $table->string('status', 30)->default('active');
            $table->timestamps();

            $table->index(['tenant_id', 'updated_at']);
            $table->index(['tenant_id', 'unread_count']);
            $table->index('zernio_account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
