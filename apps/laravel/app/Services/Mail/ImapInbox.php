<?php

namespace App\Services\Mail;

class ImapInbox
{
    public function supported(): bool
    {
        return function_exists('imap_open');
    }

    /**
     * Fetch latest messages (basic) from IMAP folder.
     *
     * @return array<int, array{uid:string, message_id:string|null, from:string, to:string, subject:string, date:\DateTimeInterface|null, body:string}>
     */
    public function fetchLatest(
        string $host,
        int $port,
        string $encryption, // tls|ssl|none
        string $username,
        string $password,
        string $folder = 'INBOX',
        int $limit = 25
    ): array {
        if (!$this->supported()) {
            throw new \RuntimeException('PHP IMAP eklentisi yüklü değil (imap_open yok).');
        }

        $enc = strtolower(trim($encryption));
        $flags = '/imap';
        if ($enc === 'ssl') {
            $flags .= '/ssl';
        } elseif ($enc === 'tls') {
            $flags .= '/tls';
        }

        $mailbox = sprintf('{%s:%d%s}%s', $host, $port, $flags, $folder);
        $stream = @imap_open($mailbox, $username, $password);
        if (!$stream) {
            $err = imap_last_error() ?: 'IMAP bağlantısı kurulamadı.';
            throw new \RuntimeException($err);
        }

        $num = imap_num_msg($stream);
        if ($num <= 0) {
            imap_close($stream);
            return [];
        }

        $start = max(1, $num - $limit + 1);
        $out = [];
        for ($msgno = $num; $msgno >= $start; $msgno--) {
            $uid = (string) imap_uid($stream, $msgno);
            $overviewArr = imap_fetch_overview($stream, (string) $msgno, 0);
            $ov = $overviewArr && isset($overviewArr[0]) ? $overviewArr[0] : null;

            $subject = $ov && isset($ov->subject) ? (string) imap_utf8((string) $ov->subject) : '';
            $from = $ov && isset($ov->from) ? (string) imap_utf8((string) $ov->from) : '';
            $to = $ov && isset($ov->to) ? (string) imap_utf8((string) $ov->to) : '';
            $messageId = $ov && isset($ov->message_id) ? (string) $ov->message_id : null;

            $dt = null;
            if ($ov && isset($ov->date)) {
                try {
                    $dt = new \DateTimeImmutable((string) $ov->date);
                } catch (\Throwable $e) {
                    $dt = null;
                }
            }

            $body = $this->fetchBodyText($stream, $msgno);

            $out[] = [
                'uid' => $uid,
                'message_id' => $messageId,
                'from' => $from,
                'to' => $to,
                'subject' => $subject,
                'date' => $dt,
                'body' => $body,
            ];
        }

        imap_close($stream);
        return $out;
    }

    private function fetchBodyText($stream, int $msgno): string
    {
        // Try part 1 (text/plain), then 1.1, then full body.
        $body = (string) @imap_fetchbody($stream, $msgno, '1');
        if ($body === '') {
            $body = (string) @imap_fetchbody($stream, $msgno, '1.1');
        }
        if ($body === '') {
            $body = (string) @imap_body($stream, $msgno);
        }

        $structure = @imap_fetchstructure($stream, $msgno);
        if ($structure && isset($structure->encoding)) {
            $body = $this->decodeByEncoding((int) $structure->encoding, $body);
        }

        $body = trim($body);
        if ($body === '') {
            return '';
        }

        // If HTML, strip tags quickly
        if (str_contains($body, '<html') || str_contains($body, '<body') || str_contains($body, '<div')) {
            $body = strip_tags($body);
        }

        return trim($body);
    }

    private function decodeByEncoding(int $enc, string $body): string
    {
        // 3 = BASE64, 4 = QUOTED-PRINTABLE
        if ($enc === 3) {
            $d = base64_decode($body, true);
            return $d !== false ? $d : $body;
        }
        if ($enc === 4) {
            return quoted_printable_decode($body);
        }
        return $body;
    }
}

