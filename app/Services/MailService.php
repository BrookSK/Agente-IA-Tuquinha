<?php

namespace App\Services;

use App\Models\Setting;

class MailService
{
    public static function buildDefaultTemplate(string $greetingName, string $contentHtml, ?string $ctaText = null, ?string $ctaUrl = null, ?string $logoUrl = null): string
    {
        $safeGreeting = htmlspecialchars($greetingName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $resolvedLogoUrl = $logoUrl;
        if ($resolvedLogoUrl === null || trim($resolvedLogoUrl) === '') {
            $publicUrl = trim((string)Setting::get('app_public_url', ''));
            if ($publicUrl !== '') {
                $resolvedLogoUrl = rtrim($publicUrl, '/') . '/public/favicon.png';
            }
        }

        $safeLogoUrl = $resolvedLogoUrl !== null && trim($resolvedLogoUrl) !== ''
            ? htmlspecialchars($resolvedLogoUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            : '';

        $brandAvatar = '<div style="width:32px; height:32px; line-height:32px; border-radius:50%; background:radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%); text-align:center; font-weight:700; font-size:16px; color:#050509;">T</div>';
        if ($safeLogoUrl !== '') {
            $brandAvatar = '<div style="width:32px; height:32px; border-radius:50%; overflow:hidden; background:#050509; box-shadow:0 0 18px rgba(229,57,53,0.8);">'
                . '<img src="' . $safeLogoUrl . '" alt="Tuquinha" style="width:100%; height:100%; display:block; object-fit:cover;">'
                . '</div>';
        }

        $ctaBlock = '';
        if ($ctaText !== null && $ctaUrl !== null && trim($ctaText) !== '' && trim($ctaUrl) !== '') {
            $safeCtaText = htmlspecialchars($ctaText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeCtaUrl = htmlspecialchars($ctaUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $ctaBlock = '<div style="text-align:center; margin:14px 0 8px 0;">'
                . '<a href="' . $safeCtaUrl . '" style="display:inline-block; padding:9px 18px; border-radius:999px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:600; font-size:13px; text-decoration:none;">'
                . $safeCtaText
                . '</a>'
                . '</div>'
                . '<p style="font-size:12px; color:#777; margin:8px 0 0 0;">Se o botão não funcionar, copie e cole este link no navegador:<br>'
                . '<a href="' . $safeCtaUrl . '" style="color:#ff6f60; text-decoration:none; word-break:break-all;">' . $safeCtaUrl . '</a>'
                . '</p>';
        }

        return '<html>'
            . '<body style="margin:0; padding:0; background:#050509; font-family:system-ui, -apple-system, BlinkMacSystemFont, \'Segoe UI\', sans-serif; color:#f5f5f5;">'
            . '<div style="width:100%; padding:24px 0;">'
            . '<div style="max-width:520px; margin:0 auto; background:#111118; border-radius:16px; border:1px solid #272727; padding:18px 20px;">'
            . '<div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">'
            . $brandAvatar
            . '<div>'
            . '<div style="font-weight:700; font-size:15px;">Resenha 2.0</div>'
            . '<div style="font-size:11px; color:#b0b0b0;">Branding vivo na veia</div>'
            . '</div>'
            . '</div>'
            . '<p style="font-size:14px; margin:0 0 10px 0;">Oi, ' . $safeGreeting . ' 👋</p>'
            . $contentHtml
            . $ctaBlock
            . '</div>'
            . '</div>'
            . '</body>'
            . '</html>';
    }

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
