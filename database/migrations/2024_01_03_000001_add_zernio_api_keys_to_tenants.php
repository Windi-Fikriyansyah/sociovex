<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('zernio_api_key')->nullable()->after('zernio_profile_id');
            $table->string('zernio_webhook_secret')->nullable()->after('zernio_api_key');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['zernio_api_key', 'zernio_webhook_secret']);
        });
    }
};
