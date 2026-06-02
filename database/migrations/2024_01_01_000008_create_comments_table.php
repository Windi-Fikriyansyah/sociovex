<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('social_account_id')->nullable()->constrained('social_accounts')->nullOnDelete();
            $table->string('zernio_comment_id')->nullable();
            $table->string('post_id')->nullable();
            $table->string('username')->nullable();
            $table->longText('comment_text');
            $table->string('platform', 50)->nullable();
            $table->dateTime('commented_at')->nullable();
            $table->tinyInteger('is_replied')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
