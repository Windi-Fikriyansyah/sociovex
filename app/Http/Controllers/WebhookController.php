<?php

namespace App\Http\Controllers;

use App\Models\AiSetting;
use App\Models\Comment;
use App\Models\CommentReply;
use App\Models\InboxEvent;
use App\Models\InboxMessage;
use App\Models\KnowledgeBase;
use App\Models\Post;
use App\Models\ScheduledPost;
use App\Models\SocialAccount;
use App\Models\WebhookLog;
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
                'comment.new'    => $this->handleNewComment($payload),
                'message.new'    => $this->handleNewMessage($payload),
                'post.published' => $this->handlePostPublished($payload),
                'post.failed'    => $this->handlePostFailed($payload),
                default          => null,
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

        if (!$signature) {
            // If no signature header, check global config (dev fallback)
            $globalSecret = config('services.zernio.webhook_secret');
            return empty($globalSecret); // pass only if no secret configured
        }

        // Try each tenant's webhook_secret first, then fall back to global config
        $secrets = \App\Models\ZernioApiKey::whereNotNull('webhook_secret')
            ->where('webhook_secret', '!=', '')
            ->pluck('webhook_secret')
            ->toArray();

        // Also check global config as fallback
        $globalSecret = config('services.zernio.webhook_secret');
        if ($globalSecret) {
            $secrets[] = $globalSecret;
        }

        if (empty($secrets)) {
            return true; // No secrets configured anywhere, skip verification
        }

        $content = $request->getContent();

        foreach ($secrets as $secret) {
            $expected = 'sha256=' . hash_hmac('sha256', $content, $secret);
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

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

        // Fire real-time event for frontend
        InboxEvent::create([
            'tenant_id'  => $socialAccount->tenant_id,
            'event_type' => 'new_comment',
            'payload'    => [
                'account_id' => $zernioAccountId,
                'username'   => $payload['username'] ?? 'Unknown',
                'text'       => $payload['text'] ?? '',
                'platform'   => $socialAccount->platform,
            ],
        ]);
    }

    private function handleNewMessage(array $payload): void
    {
        $zernioAccountId = $payload['account_id'] ?? null;

        if (!$zernioAccountId) return;

        $socialAccount = SocialAccount::where('zernio_account_id', $zernioAccountId)->first();

        if (!$socialAccount) {
            Log::warning("Webhook message.new: unknown account_id {$zernioAccountId}");
            return;
        }

        InboxMessage::create([
            'tenant_id'         => $socialAccount->tenant_id,
            'social_account_id' => $socialAccount->id,
            'sender_name'       => $payload['sender_name'] ?? 'Unknown',
            'sender_id'         => $payload['sender_id'] ?? null,
            'message_text'      => $payload['text'] ?? '',
            'platform'          => $socialAccount->platform,
            'type'              => 'dm',
            'is_read'           => 0,
            'received_at'       => now(),
        ]);

        // Fire real-time event for frontend
        InboxEvent::create([
            'tenant_id'  => $socialAccount->tenant_id,
            'event_type' => 'new_message',
            'payload'    => [
                'account_id'      => $zernioAccountId,
                'conversation_id' => $payload['conversation_id'] ?? null,
                'sender_name'     => $payload['sender_name'] ?? 'Unknown',
                'text'            => $payload['text'] ?? '',
                'platform'        => $socialAccount->platform,
            ],
        ]);
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
