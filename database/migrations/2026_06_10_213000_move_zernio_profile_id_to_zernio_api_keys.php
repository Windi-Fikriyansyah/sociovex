<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add zernio_profile_id and profile_created_at to zernio_api_keys table
        Schema::table('zernio_api_keys', function (Blueprint $table) {
            $table->string('zernio_profile_id')->nullable()->after('webhook_secret');
            $table->timestamp('profile_created_at')->nullable()->after('zernio_profile_id');
        });

        // 2. Migrate existing zernio_profile_id data from tenants to zernio_api_keys
        $tenants = DB::table('tenants')
            ->whereNotNull('zernio_profile_id')
            ->where('zernio_profile_id', '!=', '')
            ->get(['id', 'zernio_profile_id']);

        foreach ($tenants as $tenant) {
            // Find the first active API key for this tenant to host the profile
            $key = DB::table('zernio_api_keys')
                ->where('tenant_id', $tenant->id)
                ->orderByDesc('is_active')
                ->orderBy('id', 'asc')
                ->first();

            if ($key) {
                DB::table('zernio_api_keys')
                    ->where('id', $key->id)
                    ->update([
                        'zernio_profile_id' => $tenant->zernio_profile_id,
                        'profile_created_at' => now(),
                    ]);
            }
        }

        // 3. Drop zernio_profile_id column from tenants table
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('zernio_profile_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Re-add zernio_profile_id column to tenants table
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('zernio_profile_id')->nullable()->after('uuid');
        });

        // 2. Migrate data back (from the first key that has a profile ID)
        $keys = DB::table('zernio_api_keys')
            ->whereNotNull('zernio_profile_id')
            ->where('zernio_profile_id', '!=', '')
            ->get(['tenant_id', 'zernio_profile_id']);

        foreach ($keys as $key) {
            // Check if tenant doesn't already have one set
            $tenant = DB::table('tenants')->where('id', $key->tenant_id)->first();
            if ($tenant && empty($tenant->zernio_profile_id)) {
                DB::table('tenants')
                    ->where('id', $key->tenant_id)
                    ->update([
                        'zernio_profile_id' => $key->zernio_profile_id,
                    ]);
            }
        }

        // 3. Drop columns from zernio_api_keys table
        Schema::table('zernio_api_keys', function (Blueprint $table) {
            $table->dropColumn(['zernio_profile_id', 'profile_created_at']);
        });
    }
};
