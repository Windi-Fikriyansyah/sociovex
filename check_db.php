<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tenants = \App\Models\Tenant::all();
foreach ($tenants as $tenant) {
    echo "Tenant: id={$tenant->id} | name={$tenant->business_name}\n";
    
    $apiKeys = $tenant->zernioApiKeys;
    foreach ($apiKeys as $key) {
        echo "  API Key: id={$key->id} | label={$key->label} | profile_id={$key->zernio_profile_id} | status=" . ($key->is_active ? 'active' : 'inactive') . "\n";
    }
    
    $accounts = \App\Models\SocialAccount::where('tenant_id', $tenant->id)->get();
    foreach ($accounts as $acc) {
        echo "  Account: id={$acc->id} | platform={$acc->platform} | zernio_id={$acc->zernio_account_id} | status={$acc->status} | username={$acc->username} | key_id={$acc->zernio_api_key_id}\n";
    }
}
