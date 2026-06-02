<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->longText('caption')->nullable();
            $table->text('media_url')->nullable();
            $table->string('hashtags')->nullable();
            $table->json('platforms')->nullable();
            $table->json('social_account_ids')->nullable();
            $table->dateTime('scheduled_at');
            $table->enum('status', ['pending', 'published', 'failed'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_posts');
    }
};
