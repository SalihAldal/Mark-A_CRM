<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\MailMessage;
use App\Services\Mail\ImapInbox;
use App\Services\Mail\TenantSmtpMailer;
use App\Services\TenantSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MailController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->string('tab')->toString();
        if (!in_array($tab, ['inbox', 'outbox'], true)) {
            $tab = 'inbox';
        }

        $ts = app(TenantSettings::class);
        $imapCfg = [
            'host' => (string) $ts->get('mail.imap.host', ''),
            'port' => (int) ((string) $ts->get('mail.imap.port', '993')),
            'enc' => (string) $ts->get('mail.imap.encryption', 'ssl'),
            'user' => (string) $ts->get('mail.imap.username', ''),
            'pass' => (string) ($ts->getSecret('mail.imap.password', '') ?? ''),
            'folder' => (string) $ts->get('mail.imap.folder', 'INBOX'),
        ];

        $smtpCfg = [
            'from_email' => (string) $ts->get('mail.from_email', ''),
            'from_name' => (string) $ts->get('mail.from_name', 'Mark-A CRM'),
            'host' => (string) $ts->get('mail.smtp.host', ''),
            'port' => (int) ((string) $ts->get('mail.smtp.port', '587')),
            'enc' => (string) $ts->get('mail.smtp.encryption', 'tls'),
            'user' => (string) $ts->get('mail.smtp.username', ''),
            'pass' => (string) ($ts->getSecret('mail.smtp.password', '') ?? ''),
        ];

        $imapStatus = [
            'configured' => $imapCfg['host'] !== '' && $imapCfg['user'] !== '' && $imapCfg['pass'] !== '',
            'supported' => app(ImapInbox::class)->supported(),
            'synced' => false,
            'error' => null,
        ];

        if ($request->boolean('sync') && $imapStatus['configured'] && $imapStatus['supported']) {
            try {
                $msgs = app(ImapInbox::class)->fetchLatest(
                    $imapCfg['host'],
                    $imapCfg['port'],
                    $imapCfg['enc'],
                    $imapCfg['user'],
                    $imapCfg['pass'],
                    $imapCfg['folder'],
                    25
                );
                foreach ($msgs as $m) {
                    $uid = (string) ($m['uid'] ?? '');
                    if ($uid === '') {
                        continue;
                    }
                    $exists = MailMessage::query()
                        ->where('direction', 'in')
                        ->where('provider', 'imap')
                        ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(meta_json, '$.uid')) = ?", [$uid])
                        ->exists();
                    if ($exists) {
                        continue;
                    }
                    MailMessage::query()->create([
                        'direction' => 'in',
                        'status' => 'received',
                        'provider' => 'imap',
                        'subject' => (string) ($m['subject'] ?? ''),
                        'body' => (string) ($m['body'] ?? ''),
                        'meta_json' => json_encode([
                            'uid' => $uid,
                            'message_id' => $m['message_id'] ?? null,
                            'from' => (string) ($m['from'] ?? ''),
                            'to' => (string) ($m['to'] ?? ''),
                            'folder' => $imapCfg['folder'],
                        ], JSON_UNESCAPED_UNICODE),
                        'created_at' => ($m['date'] ?? null) ? ($m['date']->format('Y-m-d H:i:s')) : now(),
                    ]);
                }
                $ts->set('mail.imap.last_sync_at', (string) now());
                $imapStatus['synced'] = true;
            } catch (\Throwable $e) {
                $imapStatus['error'] = $e->getMessage();
            }
        }

        $q = MailMessage::query()->orderByDesc('id');
        if ($tab === 'inbox') {
            $q->where('direction', 'in');
        } else {
            $q->where('direction', 'out');
        }
        if ($request->filled('status') && $tab === 'outbox') {
            $q->where('status', $request->string('status')->toString());
        }
        if ($request->filled('q')) {
            $term = '%' . trim($request->string('q')->toString()) . '%';
            $q->where(function ($qq) use ($term) {
                $qq->where('subject', 'like', $term)->orWhere('body', 'like', $term);
            });
        }
        $mails = $q->paginate(20)->appends($request->query());

        $selectedId = $request->integer('msg');
        $selected = null;
        if ($selectedId) {
            $selected = MailMessage::query()->where('id', $selectedId)->first();
        }

        return view('panel.mail.index', [
            'mails' => $mails,
            'tab' => $tab,
            'selected' => $selected,
            'imapStatus' => $imapStatus,
            'smtpCfg' => $smtpCfg,
        ]);
    }

    public function store(Request $request, TenantSettings $ts, TenantSmtpMailer $mailer)
    {
        $data = $request->validate([
            'to' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],
        ]);

        $smtp = [
            'from_email' => (string) $ts->get('mail.from_email', ''),
            'from_name' => (string) $ts->get('mail.from_name', 'Mark-A CRM'),
            'host' => (string) $ts->get('mail.smtp.host', ''),
            'port' => (int) ((string) $ts->get('mail.smtp.port', '587')),
            'enc' => (string) $ts->get('mail.smtp.encryption', 'tls'),
            'user' => (string) $ts->get('mail.smtp.username', ''),
            'pass' => (string) ($ts->getSecret('mail.smtp.password', '') ?? ''),
        ];

        if ($smtp['from_email'] === '' || $smtp['host'] === '') {
            return redirect()->to('/mail')->with('status', 'SMTP ayarı yok. Settings > Mail bölümünden SMTP bilgilerini gir.');
        }

        $to = trim((string) $data['to']);
        $subject = trim((string) $data['subject']);
        $body = (string) $data['body'];

        $status = 'sent';
        $error = null;
        try {
            $mailer->sendText(
                $smtp['host'],
                $smtp['port'],
                $smtp['enc'],
                $smtp['user'] ?: null,
                $smtp['pass'] ?: null,
                $smtp['from_email'],
                $smtp['from_name'],
                $to,
                $subject,
                $body
            );
        } catch (\Throwable $e) {
            $status = 'failed';
            $error = $e->getMessage();
        }

        MailMessage::query()->create([
            'direction' => 'out',
            'status' => $status,
            'provider' => 'smtp',
            'subject' => $subject,
            'body' => $body,
            'meta_json' => json_encode([
                'to' => $to,
                'from' => $smtp['from_email'],
                'from_name' => $smtp['from_name'],
                'error' => $error,
            ], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'sent_at' => $status === 'sent' ? now() : null,
        ]);

        if ($status === 'sent') {
            return redirect()->to('/mail?tab=outbox')->with('status', 'Mail gönderildi.');
        }
        return redirect()->to('/mail?tab=outbox')->with('status', 'Mail gönderilemedi: ' . $error);
    }
}

