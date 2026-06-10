<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ZernioService
{
    protected string $baseUrl;
    protected string $apiKey;

    protected const CONNECT_TIMEOUT = 10;
    protected const READ_TIMEOUT = 60;
    protected const MAX_RETRIES = 3;
    protected const RETRY_DELAY = 500;

    public function __construct(?string $apiKey = null)
    {
        $this->baseUrl = rtrim(config('services.zernio.base_url', 'https://api.zernio.com'), '/');
        $this->apiKey  = $apiKey ?? config('services.zernio.api_key', '');
    }

    public function setApiKey(string $apiKey): static
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    public static function forTenant(Tenant $tenant): static
    {
        $firstKey = $tenant->zernioApiKeys()->where('is_active', true)->first();
        return new static($firstKey?->api_key);
    }

    protected function client(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ])
            ->timeout(self::READ_TIMEOUT)
            ->connectTimeout(self::CONNECT_TIMEOUT);
    }

    protected function url(string $path): string
    {
        return $this->baseUrl . '/' . ltrim($path, '/');
    }

    protected function executeWithRetry(callable $callback, string $method)
    {
        $attempt = 1;
        $lastException = null;

        while ($attempt <= self::MAX_RETRIES) {
            try {
                return $callback();
            } catch (\Exception $e) {
                $lastException = $e;
                $isTimeoutOrConnectionError = str_contains($e->getMessage(), 'timed out') ||
                    str_contains($e->getMessage(), 'Connection') ||
                    str_contains($e->getMessage(), 'curl error 28');

                if (!$isTimeoutOrConnectionError || $attempt >= self::MAX_RETRIES) {
                    throw $e;
                }

                $delay = self::RETRY_DELAY * (2 ** ($attempt - 1));
                Log::warning("Zernio {$method} timeout/connection error (attempt {$attempt}/" . self::MAX_RETRIES . "), retrying in {$delay}ms", [
                    'error' => $e->getMessage(),
                    'delay_ms' => $delay,
                ]);

                usleep($delay * 1000);
                $attempt++;
            }
        }

        throw $lastException ?? new RuntimeException("Zernio {$method} failed after {$attempt} attempts");
    }

    // ─── Profiles API ────────────────────────────────────────────────────────────
    // Endpoint: POST /v1/profiles
    public function createProfile(string $name): array
    {
        $response = $this->client()->post($this->url('v1/profiles'), ['name' => $name]);
        $this->throwIfFailed($response, 'createProfile');

        $data = $response->json();

        if (empty($data['profile']['_id'])) {
            Log::error('Zernio createProfile: unexpected response shape', [
                'url'      => $this->url('v1/profiles'),
                'status'   => $response->status(),
                'response' => $data,
            ]);
            throw new RuntimeException('Zernio createProfile: response missing profile._id');
        }

        return $data;
    }

    // Endpoint: GET /v1/profiles
    public function getProfiles(): array
    {
        $response = $this->client()->get($this->url('v1/profiles'));
        $this->throwIfFailed($response, 'getProfiles');
        return $response->json();
    }

    // Endpoint: DELETE /v1/profiles/{profileId}
    public function deleteProfile(string $profileId): array
    {
        $response = $this->client()->delete($this->url("v1/profiles/{$profileId}"));
        $this->throwIfFailed($response, 'deleteProfile');
        return $response->json();
    }

    // ─── Connect API ────────────────────────────────────────────────────────────
    // Endpoint: GET /v1/connect/{platform}
    public function getConnectUrl(string $platform, string $profileId, string $redirectUrl): array
    {
        $response = $this->client()->get($this->url("v1/connect/{$platform}"), [
            'profileId'    => $profileId,
            'redirect_url' => $redirectUrl,
        ]);
        $this->throwIfFailed($response, 'getConnectUrl');
        return $response->json();
    }

    // ─── Accounts API ────────────────────────────────────────────────────────────
    // Endpoint: GET /v1/accounts?profileId={profileId}
    public function getAccounts(string $profileId): array
    {
        return $this->executeWithRetry(function () use ($profileId) {
            $response = $this->client()->get($this->url('v1/accounts'), [
                'profileId' => $profileId
            ]);
            $this->throwIfFailed($response, 'getAccounts');
            return $response->json();
        }, 'getAccounts');
    }

    // Endpoint: GET /v1/accounts/{accountId}
    public function getAccount(string $accountId): array
    {
        $response = $this->client()->get($this->url("v1/accounts/{$accountId}"));
        $this->throwIfFailed($response, 'getAccount');
        return $response->json();
    }

    // Endpoint: DELETE /v1/accounts/{accountId}
    public function deleteAccount(string $accountId): array
    {
        $response = $this->client()->delete($this->url("v1/accounts/{$accountId}"));
        $this->throwIfFailed($response, 'deleteAccount');
        return $response->json();
    }

    // ─── Posts API ───────────────────────────────────────────────────────────────
    // Endpoint: POST /v1/posts
    public function publishPost(array $payload): string
    {
        $response = $this->client()->post($this->url('v1/posts'), $payload);
        $this->throwIfFailed($response, 'publishPost');

        $postId = $response->json('post._id');
        if (!$postId) {
            throw new RuntimeException('Zernio publishPost did not return a post ID.');
        }

        return $postId;
    }

    // Endpoint: POST /v1/posts (with scheduleAt parameter)
    public function schedulePost(array $payload): string
    {
        $response = $this->client()->post($this->url('v1/posts'), $payload);
        $this->throwIfFailed($response, 'schedulePost');

        $postId = $response->json('post._id');
        if (!$postId) {
            throw new RuntimeException('Zernio schedulePost did not return a post ID.');
        }

        return $postId;
    }

    // Endpoint: GET /v1/posts/{postId}
    public function getPost(string $postId): array
    {
        $response = $this->client()->get($this->url("v1/posts/{$postId}"));
        $this->throwIfFailed($response, 'getPost');
        return $response->json();
    }

    // Endpoint: DELETE /v1/posts/{postId}
    public function deletePost(string $postId): array
    {
        $response = $this->client()->delete($this->url("v1/posts/{$postId}"));
        $this->throwIfFailed($response, 'deletePost');
        return $response->json();
    }

    // ─── Webhooks API ────────────────────────────────────────────────────────────
    // Endpoint: POST /v1/webhooks
    public function registerWebhook(string $profileId, string $url, array $events): array
    {
        $response = $this->client()->post($this->url('v1/webhooks'), [
            'profileId' => $profileId,
            'url'       => $url,
            'events'    => $events,
        ]);
        $this->throwIfFailed($response, 'registerWebhook');
        return $response->json();
    }

    // Endpoint: GET /v1/webhooks
    public function listWebhooks(): array
    {
        $response = $this->client()->get($this->url('v1/webhooks'));
        $this->throwIfFailed($response, 'listWebhooks');
        return $response->json();
    }

    // Endpoint: PUT /v1/webhooks/{webhookId}
    public function updateWebhook(string $webhookId, array $data): array
    {
        $response = $this->client()->put($this->url("v1/webhooks/{$webhookId}"), $data);
        $this->throwIfFailed($response, 'updateWebhook');
        return $response->json();
    }

    // Endpoint: DELETE /v1/webhooks/{webhookId}
    public function deleteWebhook(string $webhookId): array
    {
        $response = $this->client()->delete($this->url("v1/webhooks/{$webhookId}"));
        $this->throwIfFailed($response, 'deleteWebhook');
        return $response->json();
    }

    // ─── Analytics API ───────────────────────────────────────────────────────────
    // Endpoint: GET /v1/analytics?profileId={profileId}
    public function getAnalytics(string $profileId): array
    {
        $response = $this->client()->get($this->url('v1/analytics'), [
            'profileId' => $profileId
        ]);
        $this->throwIfFailed($response, 'getAnalytics');
        return $response->json();
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────────
    protected function throwIfFailed(\Illuminate\Http\Client\Response $response, string $method): void
    {
        if ($response->failed()) {
            $status = $response->status();
            $body   = $response->json();
            $reason = $body['message'] ?? $body['error'] ?? $response->body();

            if ($status >= 500) {
                Log::error("Zernio {$method} server error [{$status}]", [
                    'method' => $method,
                    'status' => $status,
                    'reason' => $reason,
                ]);
            }

            throw new RuntimeException("Zernio {$method} failed [{$status}]: {$reason}");
        }

        $contentType = $response->header('Content-Type') ?? '';
        if (!str_contains($contentType, 'application/json') && !str_contains($contentType, 'text/json')) {
            Log::warning("Zernio {$method}: unexpected content type", [
                'method' => $method,
                'content_type' => $contentType,
                'body_preview' => substr($response->body(), 0, 200),
            ]);

            throw new RuntimeException(
                "Zernio {$method}: expected JSON response but got Content-Type '{$contentType}'"
            );
        }
    }

    public function getAdsConnectUrl(string $platform, string $profileId, ?string $accountId = null, ?string $redirectUrl = null, ?array $adAccountIds = null): array
{
    $queryParams = [
        'profileId' => $profileId,
    ];
    
    if ($accountId) {
        $queryParams['accountId'] = $accountId;
    }
    
    if ($redirectUrl) {
        $queryParams['redirect_url'] = $redirectUrl;
    }
    
    if ($adAccountIds && !empty($adAccountIds)) {
        $queryParams['adAccountIds'] = $adAccountIds;
    }
    
    $response = $this->client()->get($this->url("v1/connect/{$platform}/ads"), $queryParams);
    $this->throwIfFailed($response, 'getAdsConnectUrl');
    return $response->json();
}

// Endpoint: GET /v1/connect/tiktok-ads (untuk konfigurasi Brand Identity)
public function configureTiktokAdsBrandIdentity(string $profileId, array $brandIdentity): array
{
    $response = $this->client()->patch($this->url('v1/connect/tiktok-ads'), [
        'profileId' => $profileId,
        'brandIdentity' => $brandIdentity,
    ]);
    $this->throwIfFailed($response, 'configureTiktokAdsBrandIdentity');
    return $response->json();
}

// Endpoint: GET /v1/connect/{platform}/status (cek status koneksi ads)
public function getAdsConnectionStatus(string $platform, string $profileId): array
{
    $response = $this->client()->get($this->url("v1/connect/{$platform}/status"), [
        'profileId' => $profileId
    ]);
    $this->throwIfFailed($response, 'getAdsConnectionStatus');
    return $response->json();
}
}