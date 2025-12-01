<?php

namespace App\Services;

use App\Models\Setting;

class MailService
{
    public static function send(string $toEmail, string $toName, string $subject, string $body): bool
    {
        $host = Setting::get('smtp_host', '');
        $port = Setting::get('smtp_port', '587');
        $user = Setting::get('smtp_user', '');
        $pass = Setting::get('smtp_password', '');
        $fromEmail = Setting::get('smtp_from_email', '');
        $fromName = Setting::get('smtp_from_name', 'Tuquinha IA');

        if ($host === '' || $user === '' || $pass === '' || $fromEmail === '') {
            // SMTP não configurado; falha controlada
            return false;
        }

        $headers = [];
        $headers[] = 'From: ' . self::encodeName($fromName) . ' <' . $fromEmail . '>';
        $headers[] = 'Reply-To: ' . $fromEmail;
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';

        // Observação: aqui usamos mail() simples; em muitos provedores, será suficiente
        // desde que o servidor esteja com SMTP/config de e-mail do servidor corretamente.
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $headerString = implode("\r\n", $headers);

        $success = @mail($toEmail, $encodedSubject, $body, $headerString);

        if (!$success) {
            // Loga um erro simples no log do PHP para ajudar na depuração
            error_log('MailService: falha ao enviar e-mail para ' . $toEmail . ' usando remetente ' . $fromEmail);
        }

        return (bool)$success;
    }

    private static function encodeName(string $name): string
    {
        return '=?UTF-8?B?' . base64_encode($name) . '?=';
    }
}
