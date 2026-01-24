<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\AiRule;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SupportBotController extends Controller
{
    public function ask(Request $request)
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:2000'],
            'context' => ['nullable', 'array'],
            'context.path' => ['nullable', 'string', 'max:190'],
            'context.query' => ['nullable', 'string', 'max:600'],
            'context.page_title' => ['nullable', 'string', 'max:120'],
            'context.role' => ['nullable', 'string', 'max:32'],
        ]);

        /** @var TenantContext $ctx */
        $ctx = app(TenantContext::class);
        $ctx->requireTenantId();

        $rule = AiRule::query()->orderByDesc('id')->first();

        $apiKey = (string) env('OPENAI_API_KEY', '');
        if ($apiKey === '') {
            return response()->json(['ok' => false, 'error' => 'OPENAI_API_KEY missing'], 500);
        }
        $model = (string) env('OPENAI_MODEL', 'gpt-4o-mini');

        $rulesText = '';
        if ($rule) {
            $rulesText = "Firma sektörü: {$rule->sector}\nTon: {$rule->tone}\nDil: {$rule->language}\n";
            if (!empty($rule->forbidden_phrases)) {
                $rulesText .= "Yasaklar: {$rule->forbidden_phrases}\n";
            }
        }

        $ctxData = is_array($data['context'] ?? null) ? $data['context'] : [];
        $path = trim((string) ($ctxData['path'] ?? ''));
        $query = trim((string) ($ctxData['query'] ?? ''));
        $pageTitle = trim((string) ($ctxData['page_title'] ?? ''));
        $role = trim((string) ($ctxData['role'] ?? ''));

        $pageHint = $this->pageHint($path);
        $contextText = '';
        if ($path !== '' || $pageTitle !== '' || $role !== '') {
            $contextText = "Kullanıcı şu an şu sayfada:\n"
                . "- Rol: " . ($role !== '' ? $role : '-') . "\n"
                . "- Sayfa: " . ($pageTitle !== '' ? $pageTitle : '-') . "\n"
                . "- Path: " . ($path !== '' ? $path : '-') . "\n"
                . ($query !== '' ? ("- Query: " . $query . "\n") : '')
                . ($pageHint !== '' ? ("\nSayfa bağlamı ipucu:\n" . $pageHint . "\n") : '');
        }

        $system = "Sen Mark-A CRM sistem yardım asistanısın. Kullanıcı CRM panelini kullanırken takıldığı konularda adım adım çözüm üret.\n"
            . "Kurallar:\n"
            . "- Cevaplar Türkçe, kısa ve net olsun.\n"
            . "- Gerekirse maddeler halinde anlat.\n"
            . "- Bilmediğin entegrasyon anahtarlarını uydurma; hangi alana ne girileceğini söyle.\n"
            . "- Güvenlik: şifre/token gibi değerleri asla isteme.\n"
            . ($contextText !== '' ? ("\n" . $contextText) : '')
            . ($rulesText !== '' ? ("\nTenant AI ayarları:\n" . $rulesText) : '');

        $question = trim((string) $data['question']);

        $resp = Http::timeout(30)
            ->withToken($apiKey)
            ->acceptJson()
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'temperature' => 0.2,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $question],
                ],
            ]);

        if (!$resp->ok()) {
            return response()->json(['ok' => false, 'error' => 'OpenAI request failed'], 502);
        }

        $json = $resp->json();
        $text = (string) data_get($json, 'choices.0.message.content', '');

        return response()->json([
            'ok' => true,
            'answer' => $text,
        ]);
    }

    private function pageHint(string $path): string
    {
        $p = trim($path);
        if ($p === '' || $p === '/') {
            return '';
        }
        if (str_starts_with($p, '/leads')) {
            return "- Lead listesinden filtreleme yapabilir, 'Yeni Lead' ile manuel lead ekleyebilir, lead detayında not ve ilgili chat'e gidebilirsin.\n"
                . "- Customer rolündeysen sadece kendi lead'ini görürsün.";
        }
        if (str_starts_with($p, '/chats')) {
            return "- Sohbet listeden seçilir, mesajlar altta akar.\n"
                . "- AI butonu ile satış odaklı öneri alabilirsin.\n"
                . "- Entegrasyon bağlıysa mesajlar kanal üzerinden gidebilir.";
        }
        if (str_starts_with($p, '/calendar')) {
            return "- Aylık görünüm açık.\n"
                . "- Etkinliğe tıklayıp detay görebilir, yetkin varsa silebilirsin.\n"
                . "- Çok günlü etkinlikler otomatik gün gün kayıt olur.";
        }
        if (str_starts_with($p, '/stats')) {
            return "- Üstte periyot seç (gün/hafta/ay).\n"
                . "- Grafik ve tablolar lead akışı, durum dağılımı ve performansı gösterir.";
        }
        if (str_starts_with($p, '/settings')) {
            return "- SMTP alanına host + mail + şifre gir.\n"
                . "- Lead stage ve entegrasyon ayarlarını buradan yönetirsin.";
        }
        if (str_starts_with($p, '/mail')) {
            return "- Mail ekranından SMTP ile mail gönderebilirsin.";
        }
        return '';
    }
}

