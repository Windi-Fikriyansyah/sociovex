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

    // Timeout constants (in seconds)
    protected const CONNECT_TIMEOUT = 10;  // Connection timeout
    protected const READ_TIMEOUT = 60;     // Read/response timeout
    protected const MAX_RETRIES = 3;       // Max retry attempts
    protected const RETRY_DELAY = 500;     // Initial retry delay in ms (exponential backoff)

    public function __construct(?string $apiKey = null)
    {
        $this->baseUrl = rtrim(config('services.zernio.base_url', 'https://api.zernio.com'), '/');
        $this->apiKey  = $apiKey ?? config('services.zernio.api_key', '');
    }

    /**
     * Set the API key at runtime.
     */
    public function setApiKey(string $apiKey): static
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    /**
     * Create a ZernioService instance configured with the tenant's first active API key.
     * Falls back to the global config key if the tenant has no keys.
     */
    public static function forTenant(Tenant $tenant): static
    {
        $firstKey = $tenant->zernioApiKeys()->where('is_active', true)->first();

        return new static($firstKey?->api_key);
    }

    /**
     * Return a configured HTTP client (no baseUrl — we build full URLs explicitly).
     * Redirects are disabled so we get the actual API response rather than
     * following a redirect to the Zernio marketing site on unknown endpoint paths.
     *
     * Includes timeout configuration:
     * - connectTimeout: 10 seconds for establishing connection
     * - timeout: 60 seconds for receiving response
     */
    protected function client(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ])
            ->withoutRedirecting()
            ->timeout(self::READ_TIMEOUT)  // 60 second read timeout
            ->connectTimeout(self::CONNECT_TIMEOUT);  // 10 second connect timeout
    }

    /**
     * Build the absolute URL for a given path segment.
     */
    protected function url(string $path): string
    {
        return $this->baseUrl . '/' . ltrim($path, '/');
    }

    /**
     * Execute a request with automatic retry on timeout/connection errors.
     *
     * @param callable $callback Function that returns a Response object
     * @param string $method Method name for logging
     * @return mixed Response from the callback
     */
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

                // Log retry attempt
                $delay = self::RETRY_DELAY * (2 ** ($attempt - 1)); // Exponential backoff: 500ms, 1s, 2s
                Log::warning("Zernio {$method} timeout/connection error (attempt {$attempt}/{self::MAX_RETRIES}), retrying in {$delay}ms", [
                    'error' => $e->getMessage(),
                    'delay_ms' => $delay,
                ]);

                // Wait before retry (exponential backoff)
                usleep($delay * 1000);
                $attempt++;
            }
        }

        throw $lastException ?? new RuntimeException("Zernio {$method} failed after {$attempt} attempts");
    }

    // ─── Profiles ────────────────────────────────────────────────────────────

    /**
     * Create a new Zernio profile.
     *
     * Response: { "message": "...", "profile": { "_id": "...", "name": "...", ... } }
     */
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
            throw new RuntimeException('Zernio createProfile: response missing profile._id. Raw: ' . json_encode($data));
        }

        return $data;
    }

    /**
     * List all profiles.
     */
    public function getProfiles(): array
    {
        $response = $this->client()->get($this->url('v1/profiles'));
        $this->throwIfFailed($response, 'getProfiles');
        return $response->json();
    }

    /**
     * Delete a profile.
     */
    public function deleteProfile(string $profileId): array
    {
        $response = $this->client()->delete($this->url("v1/profiles/{$profileId}"));
        $this->throwIfFailed($response, 'deleteProfile');
        return $response->json();
    }

    // ─── Connect / Disconnect ────────────────────────────────────────────────

    /**
     * Get OAuth URL for connecting a social platform.
     *
     * Response: { "success": true, "authUrl": "https://..." }
     */
    public function getConnectUrl(string $platform, string $profileId, string $redirectUrl): array
    {
        $response = $this->client()->get($this->url("v1/connect/{$platform}"), [
            'profileId'    => $profileId,
            'redirect_url' => $redirectUrl,
        ]);
        $this->throwIfFailed($response, 'getConnectUrl');
        return $response->json();
    }

    /**
     * Disconnect a social account from a profile (legacy endpoint).
     *
     * @deprecated Use deleteAccount() instead
     * DELETE /v1/connect/{platform}/{accountId}?profileId={profileId}
     */
    public function disconnectAccount(string $platform, string $accountId, string $profileId): array
    {
        $response = $this->client()->delete(
            $this->url("v1/connect/{$platform}/{$accountId}"),
            ['profileId' => $profileId]
        );
        $this->throwIfFailed($response, 'disconnectAccount');
        return $response->json();
    }

    /**
     * Disconnect and remove a connected social account.
     *
     * DELETE /v1/accounts/{accountId}
     *
     * Response: { "message": "Disconnected" }
     */
    public function deleteAccount(string $accountId): array
    {
        $response = $this->client()->delete($this->url("v1/accounts/{$accountId}"));
        $this->throwIfFailed($response, 'deleteAccount');
        return $response->json();
    }

    // ─── Platforms ────────────────────────────────────────────────────────────

    /**
     * Get all connected platforms/accounts for a profile.
     *
     * Response: { "success": true, "platforms": [ { "_id": "acc_...", "platform": "instagram", "username": "...", ... } ] }
     */
    public function getPlatforms(string $profileId): array
    {
        return $this->executeWithRetry(function () use ($profileId) {
            $response = $this->client()->get($this->url("v1/platforms/{$profileId}"));
            $this->throwIfFailed($response, 'getPlatforms');
            return $response->json();
        }, 'getPlatforms');
    }

    /**
     * Get a specific connected account.
     */
    public function getAccount(string $profileId, string $accountId): array
    {
        return $this->executeWithRetry(function () use ($profileId, $accountId) {
            $response = $this->client()->get($this->url("v1/platforms/{$profileId}/{$accountId}"));
            $this->throwIfFailed($response, 'getAccount');
            return $response->json();
        }, 'getAccount');
    }

    // ─── Posts ───────────────────────────────────────────────────────────────

    /**
     * Publish a post immediately via Zernio.
     *
     * @param  array{profileId: string, accountIds: string[], content: string, mediaUrls?: string[]} $payload
     * @return string  The Zernio post _id.
     */
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

    /**
     * Schedule a post via Zernio (same endpoint as publish, with scheduleAt).
     *
     * @param  array{profileId: string, accountIds: string[], content: string, scheduleAt: string, mediaUrls?: string[]} $payload
     * @return string  The Zernio post _id.
     */
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

    /**
     * Get a post by ID.
     */
    public function getPost(string $postId): array
    {
        $response = $this->client()->get($this->url("v1/posts/{$postId}"));
        $this->throwIfFailed($response, 'getPost');
        return $response->json();
    }

    /**
     * Delete a post.
     */
    public function deletePost(string $postId): array
    {
        $response = $this->client()->delete($this->url("v1/posts/{$postId}"));
        $this->throwIfFailed($response, 'deletePost');
        return $response->json();
    }

    // ─── Inbox ───────────────────────────────────────────────────────────────

    /**
     * Get inbox messages for a profile (legacy).
     */
    public function getInbox(string $profileId): array
    {
        return $this->executeWithRetry(function () use ($profileId) {
            $response = $this->client()->get($this->url("v1/inbox/{$profileId}"));
            $this->throwIfFailed($response, 'getInbox');
            return $response->json();
        }, 'getInbox');
    }

    /**
     * List inbox conversations.
     *
     * GET /v1/inbox/conversations?profileId=...&platform=...&accountId=...
     */
    public function getInboxConversations(array $params = []): array
    {
        return $this->executeWithRetry(function () use ($params) {
            $response = $this->client()->get(
                $this->url('v1/inbox/conversations'),
                array_filter([
                    'profileId' => $params['profileId'] ?? null,
                    'platform'  => $params['platform'] ?? null,
                    'status'    => $params['status'] ?? 'active',
                    'sortOrder' => $params['sortOrder'] ?? 'desc',
                    'limit'     => $params['limit'] ?? 50,
                    'cursor'    => $params['cursor'] ?? null,
                    'accountId' => $params['accountId'] ?? null,
                ])
            );
            $this->throwIfFailed($response, 'getInboxConversations');
            return $response->json();
        }, 'getInboxConversations');
    }

    /**
     * Get messages from a specific conversation.
     *
     * GET /v1/inbox/conversations/{conversationId}/messages?accountId=...
     *
     * @param  string $conversationId  Platform-specific conversation ID
     * @param  string $accountId       Zernio social account ID (required)
     */
    public function getConversationMessages(string $conversationId, string $accountId): array
    {
        return $this->executeWithRetry(function () use ($conversationId, $accountId) {
            $response = $this->client()->get(
                $this->url("v1/inbox/conversations/{$conversationId}/messages"),
                ['accountId' => $accountId]
            );
            $this->throwIfFailed($response, 'getConversationMessages');
            return $response->json();
        }, 'getConversationMessages');
    }

    /**
     * Send a message (reply) to a conversation.
     *
     * POST /v1/inbox/conversations/{conversationId}/messages
     *
     * @param  string $conversationId  Platform-specific conversation ID
     * @param  string $accountId       Zernio social account ID (required)
     * @param  string $message         Message text
     */
    public function sendConversationMessage(string $conversationId, string $accountId, string $message): array
    {
        return $this->executeWithRetry(function () use ($conversationId, $accountId, $message) {
            $response = $this->client()->post(
                $this->url("v1/inbox/conversations/{$conversationId}/messages"),
                [
                    'accountId' => $accountId,
                    'message'   => $message,
                ]
            );
            $this->throwIfFailed($response, 'sendConversationMessage');
            return $response->json();
        }, 'sendConversationMessage');
    }

    /**
     * Mark a conversation as read.
     *
     * POST /v1/inbox/conversations/{conversationId}/read
     *
     * @param  string $conversationId  Platform-specific conversation ID
     * @param  string $accountId       Zernio social account ID (required)
     */
    public function markConversationAsRead(string $conversationId, string $accountId): array
    {
        return $this->executeWithRetry(function () use ($conversationId, $accountId) {
            $response = $this->client()->post(
                $this->url("v1/inbox/conversations/{$conversationId}/read"),
                ['accountId' => $accountId]
            );
            $this->throwIfFailed($response, 'markConversationAsRead');
            return $response->json();
        }, 'markConversationAsRead');
    }

    /**
     * Reply to an inbox message (legacy).
     *
     * @deprecated Use sendConversationMessage() instead.
     */
    public function replyToMessage(string $profileId, string $messageId, string $message): array
    {
        return $this->executeWithRetry(function () use ($profileId, $messageId, $message) {
            $response = $this->client()->post(
                $this->url("v1/inbox/{$profileId}/{$messageId}/reply"),
                ['message' => $message]
            );
            $this->throwIfFailed($response, 'replyToMessage');
            return $response->json();
        }, 'replyToMessage');
    }

    // ─── Webhooks ────────────────────────────────────────────────────────────

    /**
     * Register a webhook for a profile.
     *
     * @param  string[] $events  e.g. ['new_message', 'new_comment', 'post_published', 'post_failed']
     */
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

    /**
     * List all webhooks.
     */
    public function listWebhooks(): array
    {
        $response = $this->client()->get($this->url('v1/webhooks'));
        $this->throwIfFailed($response, 'listWebhooks');
        return $response->json();
    }

    /**
     * Update a webhook.
     */
    public function updateWebhook(string $webhookId, array $data): array
    {
        $response = $this->client()->put($this->url("v1/webhooks/{$webhookId}"), $data);
        $this->throwIfFailed($response, 'updateWebhook');
        return $response->json();
    }

    /**
     * Delete a webhook.
     */
    public function deleteWebhook(string $webhookId): array
    {
        $response = $this->client()->delete($this->url("v1/webhooks/{$webhookId}"));
        $this->throwIfFailed($response, 'deleteWebhook');
        return $response->json();
    }

    // ─── Analytics ───────────────────────────────────────────────────────────

    /**
     * Get analytics for a profile.
     */
    public function getAnalytics(string $profileId): array
    {
        $response = $this->client()->get($this->url("v1/analytics/{$profileId}"));
        $this->throwIfFailed($response, 'getAnalytics');
        return $response->json();
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Throw a RuntimeException with a descriptive message on HTTP failure
     * or when the response body is not valid JSON (e.g. Zernio serves HTML
     * for some paths that aren't recognised by their Next.js router).
     *
     * Also handles connection timeout and network errors gracefully.
     */
    protected function throwIfFailed(\Illuminate\Http\Client\Response $response, string $method): void
    {
        if ($response->failed()) {
            $status = $response->status();
            $body   = $response->json();
            $reason = $body['message'] ?? $body['error'] ?? $response->body();

            // More descriptive error for timeouts
            if ($status >= 500) {
                Log::error("Zernio {$method} server error [{$status}]", [
                    'method' => $method,
                    'status' => $status,
                    'reason' => $reason,
                ]);
            }

            throw new RuntimeException("Zernio {$method} failed [{$status}]: {$reason}");
        }

        // Guard against HTML "200 OK" responses (Next.js catch-all route)
        $contentType = $response->header('Content-Type') ?? '';
        if (!str_contains($contentType, 'application/json') && !str_contains($contentType, 'text/json')) {
            Log::warning("Zernio {$method}: unexpected content type", [
                'method' => $method,
                'content_type' => $contentType,
                'body_preview' => substr($response->body(), 0, 200),
            ]);

            throw new RuntimeException(
                "Zernio {$method}: expected JSON response but got Content-Type '{$contentType}'. " .
                    "The endpoint may not be available for this resource ID."
            );
        }
    }
}
