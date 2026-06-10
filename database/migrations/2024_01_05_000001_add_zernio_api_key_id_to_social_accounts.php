<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->foreignId('zernio_api_key_id')
                ->nullable()
                ->after('tenant_id')
                ->constrained('zernio_api_keys')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->dropForeign(['zernio_api_key_id']);
            $table->dropColumn('zernio_api_key_id');
        });
    }
};
