<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\AiPromptTemplate;
use App\Models\AiRule;
use App\Models\AiSuggestion;
use App\Models\Message;
use App\Models\Thread;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AiController extends Controller
{
    public function suggest(Request $request, Thread $thread)
    {
        $data = $request->validate([
            'template_key' => ['required', 'string', 'max:64'],
            'username' => ['nullable', 'string', 'max:80'],
            'scan_all' => ['nullable', 'boolean'],
        ]);

        $allowed = [
            'last_message_to_sale',
            'objection_handle',
            'offer_generate',
            'continue_chat',
            'warm_sales',
            'professional_sales',
        ];
        if (!in_array($data['template_key'], $allowed, true)) {
            return response()->json(['ok' => false, 'error' => 'Template not allowed'], 422);
        }

        /** @var TenantContext $ctx */
        $ctx = app(TenantContext::class);
        $tenantId = $ctx->requireTenantId();

        $rule = AiRule::query()->orderByDesc('id')->first();
        if (!$rule) {
            return response()->json(['ok' => false, 'error' => 'AI rules missing'], 422);
        }

        $template = AiPromptTemplate::query()
            ->where('template_key', $data['template_key'])
            ->where('is_active', 1)
            ->where(function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
            })
            ->orderByRaw('tenant_id IS NULL') // tenant-specific önce
            ->first();

        if (!$template) {
            return response()->json(['ok' => false, 'error' => 'Template not found'], 422);
        }

        $scanAll = (bool) ($data['scan_all'] ?? false);
        $limit = $scanAll ? 500 : 60;

        $msgs = Message::query()
            ->where('thread_id', $thread->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();

        $history = $msgs->map(function ($m) {
            $sender = $m->sender_type === 'user' ? 'Temsilci' : ($m->sender_type === 'contact' ? 'Müşteri' : 'Sistem');
            if ($m->message_type === 'text') {
                return "{$sender}: " . trim((string) $m->body_text);
            }
            if ($m->message_type === 'voice') {
                return "{$sender}: [SES] " . (string) ($m->file_path ?? '');
            }
            if ($m->message_type === 'image') {
                return "{$sender}: [GÖRSEL] " . (string) ($m->file_path ?? '');
            }
            return "{$sender}: [DOSYA] " . (string) ($m->file_path ?? '');
        })->implode("\n");

        $rulesText = $this->formatRules($rule);
        $sector = (string) $rule->sector;

        $system = str_replace('{{sector}}', $sector, (string) $template->system_prompt);
        $user = str_replace(
            ['{{rules}}', '{{chat_history}}'],
            [$rulesText, $history],
            (string) $template->user_prompt
        );

        if (!empty($data['username'])) {
            $user .= "\n\nKullanıcı adı: " . $data['username'];
        }

        $apiKey = (string) env('OPENAI_API_KEY', '');
        if ($apiKey === '') {
            return response()->json(['ok' => false, 'error' => 'OPENAI_API_KEY missing'], 500);
        }

        $model = (string) env('OPENAI_MODEL', 'gpt-4o-mini');

        $resp = Http::timeout(30)
            ->withToken($apiKey)
            ->acceptJson()
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'temperature' => 0.6,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $user],
                ],
            ]);

        if (!$resp->ok()) {
            return response()->json([
                'ok' => false,
                'error' => 'OpenAI request failed',
                'details' => $resp->json(),
            ], 502);
        }

        $json = $resp->json();
        $text = (string) data_get($json, 'choices.0.message.content', '');
        $tokens = (int) (data_get($json, 'usage.total_tokens') ?? 0);

        $suggestion = AiSuggestion::query()->create([
            'thread_id' => $thread->id,
            'user_id' => $request->user()->id,
            'template_key' => $data['template_key'],
            'input_snapshot_json' => json_encode([
                'rules' => [
                    'sector' => $rule->sector,
                    'tone' => $rule->tone,
                    'forbidden_phrases' => $rule->forbidden_phrases,
                    'sales_focus' => (bool) $rule->sales_focus,
                    'language' => $rule->language,
                ],
                'template' => [
                    'template_key' => $template->template_key,
                    'title' => $template->title,
                ],
                'scan_all' => $scanAll,
                'message_count' => $msgs->count(),
            ], JSON_UNESCAPED_UNICODE),
            'output_text' => $text,
            'model' => $model,
            'tokens' => $tokens ?: null,
            'created_at' => now(),
        ]);

        return response()->json([
            'ok' => true,
            'suggestion_id' => (int) $suggestion->id,
            'output_text' => $text,
        ]);
    }

    private function formatRules(AiRule $rule): string
    {
        $lines = [];
        $lines[] = "Firma sektörü: {$rule->sector}";
        $lines[] = "Ton: {$rule->tone}";
        $lines[] = "Satış hedefi: " . ($rule->sales_focus ? 'Satış odaklı' : 'Bilgilendirici');
        $lines[] = "Dil: {$rule->language}";
        if (!empty($rule->forbidden_phrases)) {
            $lines[] = "Yasaklar: {$rule->forbidden_phrases}";
        }
        return implode("\n", $lines);
    }
}

