<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Create the new zernio_api_keys table
        Schema::create('zernio_api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('label')->default('Default');
            $table->string('api_key');
            $table->string('webhook_secret')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Migrate existing data from tenants table into the new table
        $tenants = DB::table('tenants')
            ->whereNotNull('zernio_api_key')
            ->where('zernio_api_key', '!=', '')
            ->get(['id', 'zernio_api_key', 'zernio_webhook_secret']);

        foreach ($tenants as $tenant) {
            DB::table('zernio_api_keys')->insert([
                'tenant_id'        => $tenant->id,
                'label'            => 'Default',
                'api_key'          => $tenant->zernio_api_key,
                'webhook_secret'   => $tenant->zernio_webhook_secret,
                'is_active'        => true,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }

        // Drop the old columns from tenants
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['zernio_api_key', 'zernio_webhook_secret']);
        });
    }

    public function down(): void
    {
        // Re-add the old columns
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('zernio_api_key')->nullable()->after('zernio_profile_id');
            $table->string('zernio_webhook_secret')->nullable()->after('zernio_api_key');
        });

        // Migrate data back (only the first "Default" key per tenant)
        $keys = DB::table('zernio_api_keys')
            ->where('label', 'Default')
            ->get(['tenant_id', 'api_key', 'webhook_secret']);

        foreach ($keys as $key) {
            DB::table('tenants')
                ->where('id', $key->tenant_id)
                ->update([
                    'zernio_api_key'        => $key->api_key,
                    'zernio_webhook_secret' => $key->webhook_secret,
                ]);
        }

        Schema::dropIfExists('zernio_api_keys');
    }
};
