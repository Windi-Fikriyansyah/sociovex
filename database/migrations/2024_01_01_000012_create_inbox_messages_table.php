<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inbox_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('social_account_id')->nullable()->constrained('social_accounts')->nullOnDelete();
            $table->string('sender_name')->nullable();
            $table->string('sender_id')->nullable();
            $table->longText('message_text');
            $table->string('platform', 50)->nullable();
            $table->string('type', 50)->default('dm'); // dm, comment, mention
            $table->tinyInteger('is_read')->default(0);
            $table->dateTime('received_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inbox_messages');
    }
};
