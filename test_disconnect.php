<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$key  = config('services.zernio.api_key');
$base = 'https://api.zernio.com';

echo "KEY: " . substr($key, 0, 20) . "...\nBASE: $base\n\n";

// Helper
function api(string $method, string $url, string $key, array $body = []): void {
    $client = \Illuminate\Support\Facades\Http::withHeaders([
        'Authorization' => "Bearer $key",
        'Accept'        => 'application/json',
        'Content-Type'  => 'application/json',
    ])->withoutRedirecting();

    $resp = match(strtoupper($method)) {
        'GET'    => $client->get($url),
        'DELETE' => $client->delete($url, $body),
        default  => $client->get($url),
    };

    echo "[$method] $url\n";
    echo "  HTTP: " . $resp->status() . "\n";
    echo "  Content-Type: " . ($resp->header('Content-Type') ?: '(none)') . "\n";
    $json = $resp->json();
    if ($json) {
        echo "  Body: " . json_encode($json) . "\n";
    } else {
        echo "  Body (raw): " . substr($resp->body(), 0, 200) . "\n";
    }
    echo "\n";
}

// 1. Get profiles
api('GET', "$base/v1/profiles", $key);

// 2. Get platforms for the known profile with a connected account
api('GET', "$base/v1/platforms/6a1fabcbdf7a0edc59add698", $key);

// 3. Try the disconnect endpoint - test with the instagram account
// First let's see what profileId is stored for the test tenant
$apiKey = \App\Models\ZernioApiKey::whereNotNull('zernio_profile_id')->first();
if ($apiKey) {
    echo "Local API Key: id={$apiKey->id} | label={$apiKey->label} | zernio_profile_id={$apiKey->zernio_profile_id}\n\n";
    api('GET', "$base/v1/platforms/{$apiKey->zernio_profile_id}", $key);

    $accounts = \App\Models\SocialAccount::where('zernio_api_key_id', $apiKey->id)->get();
    foreach ($accounts as $acc) {
        echo "Local account: id={$acc->id} | platform={$acc->platform} | zernio_id={$acc->zernio_account_id} | status={$acc->status}\n";
        if ($acc->zernio_account_id) {
            api('DELETE', "$base/v1/connect/{$acc->platform}/{$acc->zernio_account_id}", $key, [
                'profileId' => $apiKey->zernio_profile_id,
            ]);
        }
    }
}
