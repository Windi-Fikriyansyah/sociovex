<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->integer('max_social_accounts')->default(1);
            $table->integer('max_users')->default(1);
            $table->integer('max_ai_replies')->default(0);
            $table->decimal('price', 12, 2)->default(0);
            $table->boolean('has_ai_reply')->default(false);
            $table->boolean('has_analytics')->default(false);
            $table->boolean('has_inbox')->default(false);
            $table->boolean('has_multi_user')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
