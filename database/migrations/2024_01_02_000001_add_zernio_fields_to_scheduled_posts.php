<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scheduled_posts', function (Blueprint $table) {
            // Link to the specific social account for per-account scheduling
            $table->foreignId('social_account_id')
                ->nullable()
                ->after('tenant_id')
                ->constrained('social_accounts')
                ->nullOnDelete();

            // Zernio's post identifier for status syncing via webhook
            $table->string('zernio_post_id')
                ->nullable()
                ->after('social_account_id')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('scheduled_posts', function (Blueprint $table) {
            $table->dropForeign(['social_account_id']);
            $table->dropColumn(['social_account_id', 'zernio_post_id']);
        });
    }
};
