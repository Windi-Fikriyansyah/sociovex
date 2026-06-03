<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ZernioService
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.zernio.base_url', 'https://api.zernio.com'), '/');
        $this->apiKey  = config('services.zernio.api_key', '');
    }

    /**
     * Return a configured HTTP client (no baseUrl — we build full URLs explicitly).
     * Redirects are disabled so we get the actual API response rather than
     * following a redirect to the Zernio marketing site on unknown endpoint paths.
     */
    protected function client(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ])->withoutRedirecting();
    }

    /**
     * Build the absolute URL for a given path segment.
     */
    protected function url(string $path): string
    {
        return $this->baseUrl . '/' . ltrim($path, '/');
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
     * Disconnect a social account from a profile.
     *
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

    // ─── Platforms ────────────────────────────────────────────────────────────

    /**
     * Get all connected platforms/accounts for a profile.
     *
     * Response: { "success": true, "platforms": [ { "_id": "acc_...", "platform": "instagram", "username": "...", ... } ] }
     */
    public function getPlatforms(string $profileId): array
    {
        $response = $this->client()->get($this->url("v1/platforms/{$profileId}"));
        $this->throwIfFailed($response, 'getPlatforms');
        return $response->json();
    }

    /**
     * Get a specific connected account.
     */
    public function getAccount(string $profileId, string $accountId): array
    {
        $response = $this->client()->get($this->url("v1/platforms/{$profileId}/{$accountId}"));
        $this->throwIfFailed($response, 'getAccount');
        return $response->json();
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
     * Get inbox messages for a profile.
     */
    public function getInbox(string $profileId): array
    {
        $response = $this->client()->get($this->url("v1/inbox/{$profileId}"));
        $this->throwIfFailed($response, 'getInbox');
        return $response->json();
    }

    /**
     * Reply to an inbox message.
     */
    public function replyToMessage(string $profileId, string $messageId, string $message): array
    {
        $response = $this->client()->post(
            $this->url("v1/inbox/{$profileId}/{$messageId}/reply"),
            ['message' => $message]
        );
        $this->throwIfFailed($response, 'replyToMessage');
        return $response->json();
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
     */
    protected function throwIfFailed(\Illuminate\Http\Client\Response $response, string $method): void
    {
        if ($response->failed()) {
            $body   = $response->json();
            $reason = $body['message'] ?? $body['error'] ?? $response->body();
            throw new RuntimeException("Zernio {$method} failed [{$response->status()}]: {$reason}");
        }

        // Guard against HTML "200 OK" responses (Next.js catch-all route)
        $contentType = $response->header('Content-Type') ?? '';
        if (!str_contains($contentType, 'application/json') && !str_contains($contentType, 'text/json')) {
            throw new RuntimeException(
                "Zernio {$method}: expected JSON response but got Content-Type '{$contentType}'. " .
                "The endpoint may not be available for this resource ID."
            );
        }
    }
}
