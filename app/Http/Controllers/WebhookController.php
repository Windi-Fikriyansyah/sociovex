<?php

namespace App\Http\Controllers;

use App\Events\InboxMessageReceived;
use App\Models\AiSetting;
use App\Models\Comment;
use App\Models\CommentReply;
use App\Models\Conversation;
use App\Models\InboxMessage;
use App\Models\KnowledgeBase;
use App\Models\Post;
use App\Models\ScheduledPost;
use App\Models\SocialAccount;
use App\Models\WebhookLog;
use App\Models\ZernioApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    // -------------------------------------------------------------------------
    //  Entry point
    // -------------------------------------------------------------------------

    public function handle(Request $request)
    {
        // --- Signature verification ---
        if (!$this->verifySignature($request)) {
            Log::warning('Zernio webhook: invalid signature', ['ip' => $request->ip()]);
            return response()->json(['status' => 'forbidden'], 403);
        }

        $payload   = $request->all();
        $eventType = $payload['event'] ?? 'unknown';

        // Log every webhook regardless of outcome
        $log = WebhookLog::create([
            'tenant_id'  => null,
            'event_type' => $eventType,
            'payload'    => json_encode($payload),
            'status'     => 'received',
        ]);

        try {
            match ($eventType) {
                'comment.new'       => $this->handleNewComment($payload),
                'message.new'       => $this->handleNewMessage($payload),
                'message.received'  => $this->handleNewMessage($payload),
                'post.published'    => $this->handlePostPublished($payload),
                'post.failed'       => $this->handlePostFailed($payload),
                default             => Log::info("Webhook: unhandled event type '{$eventType}'"),
            };

            $log->update(['status' => 'processed']);

            return response()->json(['status' => 'ok']);

        } catch (\Exception $e) {
            Log::error('Webhook processing error: ' . $e->getMessage(), [
                'event'   => $eventType,
                'payload' => $payload,
            ]);

            $log->update(['status' => 'error']);

            // Return 200 to prevent Zernio from retrying on our application errors
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // -------------------------------------------------------------------------
    //  Signature verification
    // -------------------------------------------------------------------------

    private function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Zernio-Signature')
            ?? $request->header('X-Webhook-Signature');

        $content = $request->getContent();
        $payload = $request->all();

        // ── Step 1: Identify the tenant from the payload ─────────
        // Zernio sends account_id which maps to a social_account,
        // which belongs to a tenant.
        $zernioAccountId = $payload['account_id'] ?? null;
        $tenant = null;

        if ($zernioAccountId) {
            $socialAccount = SocialAccount::where('zernio_account_id', $zernioAccountId)->first();
            if ($socialAccount) {
                $tenant = $socialAccount->tenant;
            }
        }

        // ── Step 2: Collect candidate secrets for this tenant ────
        $secrets = [];

        if ($tenant) {
            // Tenant-specific webhook secrets (highest priority)
            $tenantSecrets = ZernioApiKey::where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->whereNotNull('webhook_secret')
                ->where('webhook_secret', '!=', '')
                ->pluck('webhook_secret')
                ->toArray();
            $secrets = array_merge($secrets, $tenantSecrets);
        }

        // Global fallback secret from .env
        $globalSecret = config('services.zernio.webhook_secret');
        if ($globalSecret) {
            $secrets[] = $globalSecret;
        }

        // ── Step 3: If no signature header ──────────────────────
        if (!$signature) {
            // Allow if no secrets are configured at all (dev mode)
            if (empty($secrets)) {
                Log::info('Zernio webhook: no signature, no secrets configured — allowing');
                return true;
            }
            // Secrets exist but no signature provided — reject
            Log::warning('Zernio webhook: no signature header but secrets are configured');
            return false;
        }

        // ── Step 4: If no secrets configured anywhere, allow ─────
        if (empty($secrets)) {
            Log::info('Zernio webhook: signature present but no secrets configured — allowing');
            return true;
        }

        // ── Step 5: Verify signature against collected secrets ──
        foreach ($secrets as $secret) {
            // Try "sha256=..." format
            $expected = 'sha256=' . hash_hmac('sha256', $content, $secret);
            if (hash_equals($expected, $signature)) {
                return true;
            }
            // Try raw hex format (some providers don't prepend "sha256=")
            $rawExpected = hash_hmac('sha256', $content, $secret);
            if (hash_equals($rawExpected, $signature)) {
                return true;
            }
        }

        // ── Step 6: Last resort — try ALL webhook secrets from ALL tenants ──
        // This handles edge cases where account_id doesn't map yet
        $allSecrets = ZernioApiKey::where('is_active', true)
            ->whereNotNull('webhook_secret')
            ->where('webhook_secret', '!=', '')
            ->pluck('webhook_secret')
            ->toArray();

        foreach ($allSecrets as $secret) {
            if (in_array($secret, $secrets)) continue; // Already tried
            $expected = 'sha256=' . hash_hmac('sha256', $content, $secret);
            if (hash_equals($expected, $signature)) {
                return true;
            }
            $rawExpected = hash_hmac('sha256', $content, $secret);
            if (hash_equals($rawExpected, $signature)) {
                return true;
            }
        }

        Log::warning('Zernio webhook: signature verification failed', [
            'ip'           => $request->ip(),
            'account_id'   => $zernioAccountId,
            'tenant_found' => $tenant ? $tenant->id : null,
            'tried_secrets' => count($secrets) . '+' . (count($allSecrets) - count($secrets)),
        ]);

        return false;
    }

    // -------------------------------------------------------------------------
    //  Event handlers
    // -------------------------------------------------------------------------

    private function handleNewComment(array $payload): void
    {
        $zernioAccountId = $payload['account_id'] ?? null;

        if (!$zernioAccountId) return;

        $socialAccount = SocialAccount::where('zernio_account_id', $zernioAccountId)->first();

        if (!$socialAccount) {
            Log::warning("Webhook comment.new: unknown account_id {$zernioAccountId}");
            return;
        }

        $comment = Comment::create([
            'tenant_id'         => $socialAccount->tenant_id,
            'social_account_id' => $socialAccount->id,
            'zernio_comment_id' => $payload['comment_id'] ?? null,
            'post_id'           => $payload['post_id'] ?? null,
            'username'          => $payload['username'] ?? 'Unknown',
            'comment_text'      => $payload['text'] ?? '',
            'platform'          => $socialAccount->platform,
            'commented_at'      => now(),
            'is_replied'        => 0,
        ]);

        // Check if AI auto-reply is enabled
        $aiSetting = AiSetting::where('tenant_id', $socialAccount->tenant_id)
            ->where('auto_reply_enabled', 1)
            ->first();

        if ($aiSetting) {
            $this->autoReplyWithAi($comment, $aiSetting);
        }

        // Fire real-time event for frontend is no longer needed —
        // comments use their own view and are not part of the inbox
        // WebSocket channel. The inbox page polls separately for comments.
    }

    private function handleNewMessage(array $payload): void
    {
        // ── Normalize payload: Zernio v2 sends nested structure ───
        // v1 (flat): { account_id, sender_name, sender_id, text, message_id, ... }
        // v2 (nested): { message: { text, sender: { id, name } }, account: { id }, conversation: { id, participantName } }
        $msg    = $payload['message'] ?? [];
        $conv   = $payload['conversation'] ?? [];
        $acct   = $payload['account'] ?? [];

        $zernioAccountId = $acct['id'] ?? $payload['account_id'] ?? null;
        if (!$zernioAccountId) {
            Log::warning('Webhook message: no account_id found', ['payload' => $payload]);
            return;
        }

        $socialAccount = SocialAccount::where('zernio_account_id', $zernioAccountId)->first();
        if (!$socialAccount) {
            Log::warning("Webhook message: unknown account_id {$zernioAccountId}");
            return;
        }

        // ── Normalize fields from either v1 or v2 format ────────
        $zernioMessageId    = $msg['id'] ?? $payload['message_id'] ?? null;
        $senderName         = $msg['sender']['name'] ?? $payload['sender_name'] ?? 'Unknown';
        $senderId           = $msg['sender']['id'] ?? $payload['sender_id'] ?? null;
        $messageText        = $msg['text'] ?? $payload['text'] ?? '';
        $direction          = $msg['direction'] ?? $payload['direction'] ?? 'incoming';
        $zernioConvId       = $conv['id'] ?? $payload['conversation_id'] ?? null;
        $participantName    = $conv['participantName'] ?? $payload['sender_name'] ?? $senderName;
        $participantUsername = $conv['participantUsername'] ?? null;
        $platform           = $conv['platform'] ?? $msg['platform'] ?? $payload['platform'] ?? $socialAccount->platform;

        // ── Check for duplicate message ────────────────────────
        if ($zernioMessageId) {
            $exists = InboxMessage::where('zernio_message_id', $zernioMessageId)->exists();
            if ($exists) {
                Log::info("Skipping duplicate message {$zernioMessageId}");
                return;
            }
        }

        // ── Upsert conversation ────────────────────────────────
        $conversation = Conversation::upsertFromNormalized([
            'conversation_id'  => $zernioConvId,
            'sender_name'      => $participantName,
            'sender_id'        => $senderId,
            'account_id'       => $zernioAccountId,
            'text'             => $messageText,
            'platform'         => $platform,
        ], $socialAccount->tenant_id, $socialAccount->id);

        // ── Create local message ───────────────────────────────
        $inboxMessage = InboxMessage::create([
            'tenant_id'         => $socialAccount->tenant_id,
            'conversation_id'   => $conversation->id,
            'social_account_id' => $socialAccount->id,
            'zernio_message_id' => $zernioMessageId,
            'sender_name'       => $senderName,
            'sender_id'         => $senderId,
            'message_text'      => $messageText,
            'platform'          => $platform,
            'type'              => 'dm',
            'direction'         => $direction,
            'is_read'           => 0,
            'received_at'       => now(),
        ]);

        // ── Increment unread on conversation ───────────────────
        $conversation->incrementUnread();
        $conversation->refresh();

        Log::info("Webhook message: stored & broadcasting", [
            'message_id'   => $zernioMessageId,
            'conversation' => $conversation->zernio_conversation_id,
            'tenant'       => $socialAccount->tenant_id,
        ]);

        // ── Broadcast via Reverb (WebSocket) ───────────────────
        broadcast(new InboxMessageReceived(
            $socialAccount->tenant_id,
            $inboxMessage,
            $conversation
        ));
    }

    /**
     * Zernio notifies us when a scheduled post has been successfully published.
     */
    private function handlePostPublished(array $payload): void
    {
        $zernioPostId = $payload['post_id'] ?? null;

        if (!$zernioPostId) return;

        // Update ScheduledPost status
        ScheduledPost::where('zernio_post_id', $zernioPostId)
            ->update(['status' => 'published']);

        // Update Post with post_url if provided
        if ($postUrl = $payload['post_url'] ?? null) {
            Post::where('zernio_post_id', $zernioPostId)
                ->update(['post_url' => $postUrl]);
        }
    }

    /**
     * Zernio notifies us when a scheduled post has failed.
     */
    private function handlePostFailed(array $payload): void
    {
        $zernioPostId = $payload['post_id'] ?? null;

        if (!$zernioPostId) return;

        ScheduledPost::where('zernio_post_id', $zernioPostId)
            ->update(['status' => 'failed']);

        Log::error('Zernio post.failed', [
            'zernio_post_id' => $zernioPostId,
            'reason'         => $payload['reason'] ?? 'unknown',
        ]);
    }

    // -------------------------------------------------------------------------
    //  AI auto-reply
    // -------------------------------------------------------------------------

    private function autoReplyWithAi(Comment $comment, AiSetting $aiSetting): void
    {
        $knowledgeBases = KnowledgeBase::where('tenant_id', $comment->tenant_id)->get();
        $knowledgeText  = $knowledgeBases->map(fn($kb) => "{$kb->title}:\n{$kb->content}")->implode("\n\n");

        $systemPrompt = $aiSetting->system_prompt ?? 'Balas dengan ramah dan profesional.';
        if ($knowledgeText) {
            $systemPrompt .= "\n\nInformasi bisnis:\n{$knowledgeText}";
        }

        $openaiKey = config('services.openai.api_key');
        if (!$openaiKey) return;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $openaiKey,
                'Content-Type'  => 'application/json',
            ])->timeout(30)
              ->post('https://api.openai.com/v1/chat/completions', [
                'model'       => $aiSetting->model ?? 'gpt-4o-mini',
                'temperature' => (float) ($aiSetting->temperature ?? 0.7),
                'messages'    => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => "Balas komentar ini: \"{$comment->comment_text}\""],
                ],
                'max_tokens' => 300,
            ]);

            if ($response->ok()) {
                $replyText = $response->json('choices.0.message.content');

                CommentReply::create([
                    'comment_id' => $comment->id,
                    'tenant_id'  => $comment->tenant_id,
                    'reply_text' => $replyText,
                    'source'     => 'ai',
                    'replied_at' => now(),
                ]);

                $comment->update(['is_replied' => 1]);

                Log::info("AI auto-reply sent for comment {$comment->id}");
            } else {
                Log::error('OpenAI auto-reply failed', ['status' => $response->status(), 'body' => $response->body()]);
            }
        } catch (\Exception $e) {
            Log::error('Auto AI reply exception: ' . $e->getMessage());
        }
    }
}
