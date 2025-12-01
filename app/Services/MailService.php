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
        $portInt = (int)$port ?: 587;
        $useSsl = ($portInt === 465);

        $transportHost = $useSsl ? 'ssl://' . $host : $host;

        $fp = @fsockopen($transportHost, $portInt, $errno, $errstr, 15);
        if (!$fp) {
            error_log('MailService: não conseguiu conectar ao SMTP: ' . $errno . ' - ' . $errstr);
            return false;
        }

        $read = function () use ($fp): string {
            $data = '';
            while (!feof($fp)) {
                $line = fgets($fp, 515);
                if ($line === false) {
                    break;
                }
                $data .= $line;
                // Resposta SMTP termina quando o quarto caractere não é '-'
                if (strlen($line) >= 4 && $line[3] !== '-') {
                    break;
                }
            }
            return $data;
        };

        $write = function (string $cmd) use ($fp): void {
            fwrite($fp, $cmd . "\r\n");
        };

        // Banner inicial
        $banner = $read();
        if (strpos($banner, '220') !== 0) {
            error_log('MailService: banner SMTP inesperado: ' . trim($banner));
            fclose($fp);
            return false;
        }

        $localHost = 'localhost';
        $write('EHLO ' . $localHost);
        $ehloResp = $read();
        if (strpos($ehloResp, '250') !== 0) {
            // tenta HELO simples
            $write('HELO ' . $localHost);
            $heloResp = $read();
            if (strpos($heloResp, '250') !== 0) {
                error_log('MailService: falha no EHLO/HELO: ' . trim($ehloResp . ' ' . $heloResp));
                fclose($fp);
                return false;
            }
        }

        // Autenticação LOGIN (mais amplamente suportada)
        $write('AUTH LOGIN');
        $authResp = $read();
        if (strpos($authResp, '334') !== 0) {
            error_log('MailService: servidor não aceitou AUTH LOGIN: ' . trim($authResp));
            fclose($fp);
            return false;
        }

        $write(base64_encode($user));
        $userResp = $read();
        if (strpos($userResp, '334') !== 0) {
            error_log('MailService: usuário SMTP rejeitado: ' . trim($userResp));
            fclose($fp);
            return false;
        }

        $write(base64_encode($pass));
        $passResp = $read();
        if (strpos($passResp, '235') !== 0) {
            error_log('MailService: senha SMTP rejeitada: ' . trim($passResp));
            fclose($fp);
            return false;
        }

        // MAIL FROM / RCPT TO / DATA
        $write('MAIL FROM:<' . $fromEmail . '>');
        $fromResp = $read();
        if (strpos($fromResp, '250') !== 0) {
            error_log('MailService: MAIL FROM falhou: ' . trim($fromResp));
            fclose($fp);
            return false;
        }

        $write('RCPT TO:<' . $toEmail . '>');
        $rcptResp = $read();
        if (strpos($rcptResp, '250') !== 0 && strpos($rcptResp, '251') !== 0) {
            error_log('MailService: RCPT TO falhou: ' . trim($rcptResp));
            fclose($fp);
            return false;
        }

        $write('DATA');
        $dataResp = $read();
        if (strpos($dataResp, '354') !== 0) {
            error_log('MailService: comando DATA rejeitado: ' . trim($dataResp));
            fclose($fp);
            return false;
        }

        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $headers = [];
        $headers[] = 'From: ' . self::encodeName($fromName) . ' <' . $fromEmail . '>';
        $headers[] = 'Reply-To: ' . $fromEmail;
        $headers[] = 'To: ' . ($toName !== '' ? self::encodeName($toName) . ' <' . $toEmail . '>' : $toEmail);
        $headers[] = 'Subject: ' . $encodedSubject;
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'Date: ' . date('r');

        $message = implode("\r\n", $headers) . "\r\n\r\n" . $body;

        // Termina mensagem com <CRLF>.<CRLF>
        $write($message . "\r\n.");
        $finalResp = $read();
        if (strpos($finalResp, '250') !== 0) {
            error_log('MailService: envio de dados SMTP falhou: ' . trim($finalResp));
            $write('QUIT');
            fclose($fp);
            return false;
        }

        $write('QUIT');
        fclose($fp);

        return true;
    }

    private static function encodeName(string $name): string
    {
        return '=?UTF-8?B?' . base64_encode($name) . '?=';
    }
}
