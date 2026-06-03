<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$key  = config('services.zernio.api_key');
$base = config('services.zernio.base_url', 'https://api.zernio.com');

function test_api_delete_with_body(string $url, string $key, array $body): void {
    $client = \Illuminate\Support\Facades\Http::withHeaders([
        'Authorization' => "Bearer $key",
        'Accept'        => 'application/json',
        'Content-Type'  => 'application/json',
    ])->withoutRedirecting();

    $resp = $client->withBody(json_encode($body), 'application/json')->send('DELETE', $url);

    echo "DELETE with withBody $url\n";
    echo "  HTTP: " . $resp->status() . "\n";
    $json = $resp->json();
    if ($json) {
        echo "  Body: " . json_encode($json) . "\n";
    } else {
        echo "  Body (raw): " . substr($resp->body(), 0, 200) . "\n";
    }
    echo "\n";
}

$webhookId = '6a1f9380b977c6ea5607e63c';
test_api_delete_with_body("$base/v1/webhooks/settings", $key, ['id' => $webhookId]);
