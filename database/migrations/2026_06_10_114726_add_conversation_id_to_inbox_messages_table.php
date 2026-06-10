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
        Schema::table('inbox_messages', function (Blueprint $table) {
            $table->foreignId('conversation_id')->nullable()->after('tenant_id')->constrained('conversations')->nullOnDelete();
            $table->string('zernio_message_id')->nullable()->after('conversation_id'); // Zernio's message ID
            $table->string('direction', 20)->default('incoming')->after('type'); // incoming | outgoing
            $table->timestamp('sent_at')->nullable()->after('received_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inbox_messages', function (Blueprint $table) {
            $table->dropColumn(['conversation_id', 'zernio_message_id', 'direction', 'sent_at']);
        });
    }
};
