<?php

namespace App\Http\Controllers;

use App\Models\AiSetting;
use App\Models\Comment;
use App\Models\CommentReply;
use App\Models\KnowledgeBase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiController extends Controller
{
    public function settings()
    {
        $tenant = Auth::user()->tenant;

        $aiSetting = AiSetting::where('tenant_id', $tenant->id)->first()
            ?? new AiSetting(['model' => 'gpt-4o-mini', 'temperature' => 0.7, 'auto_reply_enabled' => 0]);

        $knowledgeBases = KnowledgeBase::where('tenant_id', $tenant->id)->get();

        return view('ai.settings', compact('aiSetting', 'knowledgeBases', 'tenant'));
    }

    public function saveSettings(Request $request)
    {
        $request->validate([
            'model'               => ['required', 'string'],
            'temperature'         => ['required', 'numeric', 'min:0', 'max:2'],
            'system_prompt'       => ['nullable', 'string'],
            'auto_reply_enabled'  => ['nullable'],
        ]);

        $tenant = Auth::user()->tenant;

        AiSetting::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'model'              => $request->model,
                'temperature'        => $request->temperature,
                'system_prompt'      => $request->system_prompt,
                'auto_reply_enabled' => $request->has('auto_reply_enabled') ? 1 : 0,
            ]
        );

        return back()->with('success', 'Pengaturan AI berhasil disimpan.');
    }

    public function storeKnowledge(Request $request)
    {
        $request->validate([
            'title'   => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
        ]);

        $tenant = Auth::user()->tenant;

        KnowledgeBase::create([
            'tenant_id' => $tenant->id,
            'title'     => $request->title,
            'content'   => $request->content,
        ]);

        return back()->with('success', 'Knowledge base berhasil ditambahkan.');
    }

    public function destroyKnowledge(KnowledgeBase $knowledgeBase)
    {
        $tenant = Auth::user()->tenant;

        if ($knowledgeBase->tenant_id !== $tenant->id) {
            abort(403);
        }

        $knowledgeBase->delete();

        return back()->with('success', 'Knowledge base berhasil dihapus.');
    }

    public function generateReply(Request $request, Comment $comment)
    {
        $tenant = Auth::user()->tenant;

        if ($comment->tenant_id !== $tenant->id) {
            abort(403);
        }

        $aiSetting = AiSetting::where('tenant_id', $tenant->id)->first();

        if (!$aiSetting) {
            return response()->json(['error' => 'Pengaturan AI belum dikonfigurasi.'], 422);
        }

        $knowledgeBases = KnowledgeBase::where('tenant_id', $tenant->id)->get();
        $knowledgeText = $knowledgeBases->map(fn($kb) => "{$kb->title}:\n{$kb->content}")->implode("\n\n");

        $systemPrompt = $aiSetting->system_prompt ?? 'Balas dengan ramah dan profesional.';

        if ($knowledgeText) {
            $systemPrompt .= "\n\nInformasi bisnis:\n{$knowledgeText}";
        }

        try {
            $openaiKey = config('services.openai.api_key');

            if (!$openaiKey) {
                return response()->json(['error' => 'OpenAI API key belum dikonfigurasi.'], 422);
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $openaiKey,
                'Content-Type'  => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model'       => $aiSetting->model ?? 'gpt-4o-mini',
                'temperature' => (float) ($aiSetting->temperature ?? 0.7),
                'messages'    => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => "Balas komentar ini: \"{$comment->comment_text}\""],
                ],
                'max_tokens' => 300,
            ]);

            if ($response->failed()) {
                Log::error('OpenAI API error', ['response' => $response->body()]);
                return response()->json(['error' => 'Gagal menghasilkan balasan dari AI.'], 500);
            }

            $reply = $response->json('choices.0.message.content');

            return response()->json(['reply' => $reply]);
        } catch (\Exception $e) {
            Log::error('AI reply error: ' . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan saat menghubungi AI.'], 500);
        }
    }

    public function saveAiReply(Request $request, Comment $comment)
    {
        $request->validate(['reply_text' => ['required', 'string']]);

        $tenant = Auth::user()->tenant;

        if ($comment->tenant_id !== $tenant->id) {
            abort(403);
        }

        CommentReply::create([
            'comment_id' => $comment->id,
            'tenant_id'  => $tenant->id,
            'reply_text' => $request->reply_text,
            'source'     => 'ai',
            'replied_at' => now(),
        ]);

        $comment->update(['is_replied' => 1]);

        return response()->json(['success' => true]);
    }
}
