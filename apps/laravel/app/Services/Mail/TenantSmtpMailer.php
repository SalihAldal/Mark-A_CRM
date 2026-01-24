<?php

namespace App\Services\Mail;

use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

class TenantSmtpMailer
{
    /**
     * @throws TransportExceptionInterface|\RuntimeException
     */
    public function sendText(
        string $host,
        int $port,
        string $encryption, // tls|ssl|none
        ?string $username,
        ?string $password,
        string $fromEmail,
        string $fromName,
        string $toEmail,
        string $subject,
        string $body
    ): void {
        $enc = strtolower(trim($encryption));
        if ($enc === 'none' || $enc === '') {
            $enc = null;
        } elseif (!in_array($enc, ['tls', 'ssl'], true)) {
            $enc = null;
        }

        $user = (string) ($username ?? '');
        $pass = (string) ($password ?? '');

        // DSN format: smtp://user:pass@host:port?encryption=tls
        $auth = '';
        if ($user !== '') {
            $auth = rawurlencode($user) . ':' . rawurlencode($pass) . '@';
        }
        $query = $enc ? ('?encryption=' . rawurlencode($enc)) : '';
        $dsn = 'smtp://' . $auth . $host . ':' . (int) $port . $query;

        $transport = Transport::fromDsn($dsn);
        $mailer = new Mailer($transport);

        $email = (new Email())
            ->from(sprintf('"%s" <%s>', addslashes($fromName), $fromEmail))
            ->to($toEmail)
            ->subject($subject)
            ->text($body);

        $mailer->send($email);
    }
}

