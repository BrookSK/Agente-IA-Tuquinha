<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Setting;
use App\Models\AsaasConfig;
use App\Models\TuquinhaEngine;
use App\Services\MailService;
use App\Services\MediaStorageService;

class AdminConfigController extends Controller
{
    private function ensureAdmin(): void
    {
        if (empty($_SESSION['is_admin'])) {
            header('Location: /admin/login');
            exit;
        }
    }

    public function index(): void
    {
        $this->ensureAdmin();
        $openaiKey = Setting::get('openai_api_key', '');
        $anthropicKey = Setting::get('anthropic_api_key', ANTHROPIC_API_KEY);
        $defaultModel = Setting::get('openai_default_model', AI_MODEL);
        $transcriptionModel = Setting::get('openai_transcription_model', 'whisper-1');
        $systemPrompt = Setting::get('tuquinha_system_prompt', TuquinhaEngine::getDefaultPrompt());
        $systemPromptExtra = Setting::get('tuquinha_system_prompt_extra', '');
        $historyRetentionDays = (int)Setting::get('chat_history_retention_days', '90');
        if ($historyRetentionDays <= 0) {
            $historyRetentionDays = 90;
        }

        $freeGlobalLimit = (int)Setting::get('free_memory_global_chars', '500');
        if ($freeGlobalLimit <= 0) {
            $freeGlobalLimit = 500;
        }
        $freeChatLimit = (int)Setting::get('free_memory_chat_chars', '400');
        if ($freeChatLimit <= 0) {
            $freeChatLimit = 400;
        }

        $smtpHost = Setting::get('smtp_host', '');
        $smtpPort = Setting::get('smtp_port', '587');
        $smtpUser = Setting::get('smtp_user', '');
        $smtpPassword = Setting::get('smtp_password', '');
        $smtpFromEmail = Setting::get('smtp_from_email', '');
        $smtpFromName = Setting::get('smtp_from_name', 'Tuquinha IA');

        $adminErrorEmail = Setting::get('admin_error_notification_email', '');
        $adminErrorWebhook = Setting::get('admin_error_webhook_url', '');

        $extraTokenPricePer1kGlobal = Setting::get('extra_token_price_per_1k_global', '');

        $googleClientId = Setting::get('google_calendar_client_id', '');
        $googleClientSecret = Setting::get('google_calendar_client_secret', '');
        $googleRefreshToken = Setting::get('google_calendar_refresh_token', '');
        $googleCalendarId = Setting::get('google_calendar_calendar_id', 'primary');

        $mediaEndpoint = Setting::get('media_upload_endpoint', defined('MEDIA_UPLOAD_ENDPOINT') ? MEDIA_UPLOAD_ENDPOINT : '');
        $mediaVideoEndpoint = Setting::get('media_video_upload_endpoint', defined('MEDIA_VIDEO_UPLOAD_ENDPOINT') ? MEDIA_VIDEO_UPLOAD_ENDPOINT : '');
        $textExtractionEndpoint = Setting::get('text_extraction_endpoint', '');

        $certificateIssuerName = Setting::get('certificate_issuer_name', 'Thiago Marques');
        $certificateSignatureImagePath = Setting::get('certificate_signature_image_path', '');

        $asaas = AsaasConfig::getActive();

        $this->view('admin/config', [
            'pageTitle' => 'Configuração - OpenAI',
            'openaiKey' => $openaiKey,
            'anthropicKey' => $anthropicKey,
            'defaultModel' => $defaultModel,
            'transcriptionModel' => $transcriptionModel,
            'systemPrompt' => $systemPrompt,
            'systemPromptExtra' => $systemPromptExtra,
            'historyRetentionDays' => $historyRetentionDays,
            'freeGlobalLimit' => $freeGlobalLimit,
            'freeChatLimit' => $freeChatLimit,
            'smtpHost' => $smtpHost,
            'smtpPort' => $smtpPort,
            'smtpUser' => $smtpUser,
            'smtpPassword' => $smtpPassword,
            'smtpFromEmail' => $smtpFromEmail,
            'smtpFromName' => $smtpFromName,
            'adminErrorEmail' => $adminErrorEmail,
            'adminErrorWebhook' => $adminErrorWebhook,
            'extraTokenPricePer1kGlobal' => $extraTokenPricePer1kGlobal,
            'googleClientId' => $googleClientId,
            'googleClientSecret' => $googleClientSecret,
            'googleRefreshToken' => $googleRefreshToken,
            'googleCalendarId' => $googleCalendarId,
            'mediaEndpoint' => $mediaEndpoint,
            'mediaVideoEndpoint' => $mediaVideoEndpoint,
            'textExtractionEndpoint' => $textExtractionEndpoint,
            'certificateIssuerName' => $certificateIssuerName,
            'certificateSignatureImagePath' => $certificateSignatureImagePath,
            'asaasEnvironment' => $asaas['environment'] ?? 'sandbox',
            'asaasSandboxKey' => $asaas['sandbox_api_key'] ?? '',
            'asaasProdKey' => $asaas['production_api_key'] ?? '',
            'saved' => false,
            'testEmailStatus' => null,
            'testEmailError' => null,
        ]);
    }

    public function save(): void
    {
        $this->ensureAdmin();
        $openaiKey = trim($_POST['openai_key'] ?? '');
        $anthropicKey = trim($_POST['anthropic_key'] ?? '');
        $defaultModel = trim($_POST['default_model'] ?? '');
        $transcriptionModel = trim($_POST['transcription_model'] ?? '');
        $systemPrompt = trim($_POST['system_prompt'] ?? '');
        $systemPromptExtra = trim($_POST['system_prompt_extra'] ?? '');
        $historyRetentionDays = (int)($_POST['history_retention_days'] ?? 90);
        if ($historyRetentionDays <= 0) {
            $historyRetentionDays = 90;
        }
        $freeGlobalLimit = (int)($_POST['free_global_limit'] ?? 500);
        if ($freeGlobalLimit <= 0) {
            $freeGlobalLimit = 500;
        }
        $freeChatLimit = (int)($_POST['free_chat_limit'] ?? 400);
        if ($freeChatLimit <= 0) {
            $freeChatLimit = 400;
        }
        $smtpHost = trim($_POST['smtp_host'] ?? '');
        $smtpPort = trim($_POST['smtp_port'] ?? '587');
        $smtpUser = trim($_POST['smtp_user'] ?? '');
        $smtpPassword = trim($_POST['smtp_password'] ?? '');
        $smtpFromEmail = trim($_POST['smtp_from_email'] ?? '');
        $smtpFromName = trim($_POST['smtp_from_name'] ?? 'Tuquinha IA');
        $adminErrorEmail = trim($_POST['admin_error_email'] ?? '');
        $adminErrorWebhook = trim($_POST['admin_error_webhook'] ?? '');
        $extraTokenPricePer1kGlobalRaw = trim($_POST['extra_token_price_per_1k_global'] ?? '');
        $asaasEnv = $_POST['asaas_environment'] ?? 'sandbox';
        $asaasSandboxKey = trim($_POST['asaas_sandbox_key'] ?? '');
        $asaasProdKey = trim($_POST['asaas_prod_key'] ?? '');

        $googleClientId = trim($_POST['google_client_id'] ?? '');
        $googleClientSecret = trim($_POST['google_client_secret'] ?? '');
        $googleRefreshToken = trim($_POST['google_refresh_token'] ?? '');
        $googleCalendarId = trim($_POST['google_calendar_id'] ?? 'primary');
        $mediaEndpoint = trim($_POST['media_endpoint'] ?? '');
        $mediaVideoEndpoint = trim($_POST['media_video_endpoint'] ?? '');
        $textExtractionEndpoint = trim($_POST['text_extraction_endpoint'] ?? '');

        $certificateIssuerName = trim($_POST['certificate_issuer_name'] ?? 'Thiago Marques');
        if ($certificateIssuerName === '') {
            $certificateIssuerName = 'Thiago Marques';
        }
        $certificateSignatureImagePath = trim($_POST['certificate_signature_image_path'] ?? '');

        // Upload opcional da assinatura do emissor (imagem) para servidor de mídia externo
        if (!empty($_FILES['certificate_signature_upload']['tmp_name'])) {
            $imgError = $_FILES['certificate_signature_upload']['error'] ?? UPLOAD_ERR_NO_FILE;
            if ($imgError === UPLOAD_ERR_OK) {
                $imgTmp = (string)($_FILES['certificate_signature_upload']['tmp_name'] ?? '');
                $imgName = (string)($_FILES['certificate_signature_upload']['name'] ?? '');
                $imgMime = (string)($_FILES['certificate_signature_upload']['type'] ?? '');
                if ($imgTmp !== '' && is_file($imgTmp)) {
                    $remote = MediaStorageService::uploadFile($imgTmp, $imgName, $imgMime);
                    if ($remote !== null) {
                        $certificateSignatureImagePath = $remote;
                    }
                }
            }
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO settings (`key`, `value`) VALUES (:key, :value)
            ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)');

        $extraTokenPricePer1kGlobal = '';
        if ($extraTokenPricePer1kGlobalRaw !== '') {
            $normalized = str_replace([' ', ','], ['', '.'], $extraTokenPricePer1kGlobalRaw);
            if (is_numeric($normalized)) {
                $extraTokenPricePer1kGlobal = number_format((float)$normalized, 4, '.', '');
            }
        }

        $settingsToSave = [
            'openai_api_key' => $openaiKey,
            'anthropic_api_key' => $anthropicKey,
            'openai_default_model' => $defaultModel !== '' ? $defaultModel : AI_MODEL,
            'openai_transcription_model' => $transcriptionModel !== '' ? $transcriptionModel : 'whisper-1',
            'tuquinha_system_prompt' => $systemPrompt !== '' ? $systemPrompt : TuquinhaEngine::getDefaultPrompt(),
            'tuquinha_system_prompt_extra' => $systemPromptExtra,
            'chat_history_retention_days' => (string)$historyRetentionDays,
            'free_memory_global_chars' => (string)$freeGlobalLimit,
            'free_memory_chat_chars' => (string)$freeChatLimit,
            'smtp_host' => $smtpHost,
            'smtp_port' => $smtpPort,
            'smtp_user' => $smtpUser,
            'smtp_password' => $smtpPassword,
            'smtp_from_email' => $smtpFromEmail,
            'smtp_from_name' => $smtpFromName,
            'admin_error_notification_email' => $adminErrorEmail,
            'admin_error_webhook_url' => $adminErrorWebhook,
            'extra_token_price_per_1k_global' => $extraTokenPricePer1kGlobal,
            'google_calendar_client_id' => $googleClientId,
            'google_calendar_client_secret' => $googleClientSecret,
            'google_calendar_refresh_token' => $googleRefreshToken,
            'google_calendar_calendar_id' => $googleCalendarId !== '' ? $googleCalendarId : 'primary',
            'media_upload_endpoint' => $mediaEndpoint !== '' ? $mediaEndpoint : (defined('MEDIA_UPLOAD_ENDPOINT') ? MEDIA_UPLOAD_ENDPOINT : ''),
            'media_video_upload_endpoint' => $mediaVideoEndpoint !== '' ? $mediaVideoEndpoint : (defined('MEDIA_VIDEO_UPLOAD_ENDPOINT') ? MEDIA_VIDEO_UPLOAD_ENDPOINT : ''),
            'text_extraction_endpoint' => $textExtractionEndpoint,
            'certificate_issuer_name' => $certificateIssuerName,
            'certificate_signature_image_path' => $certificateSignatureImagePath,
        ];

        foreach ($settingsToSave as $sKey => $sValue) {
            $stmt->execute([
                'key' => $sKey,
                'value' => $sValue,
            ]);
        }

        // Salva configuração Asaas (linha única)
        $pdo->exec("INSERT INTO asaas_configs (id, environment, sandbox_api_key, production_api_key)
            VALUES (1, 'sandbox', '', '')
            ON DUPLICATE KEY UPDATE environment = environment");

        $stmtAsaas = $pdo->prepare('UPDATE asaas_configs SET environment = :env, sandbox_api_key = :sandbox, production_api_key = :prod WHERE id = 1');
        $stmtAsaas->execute([
            'env' => $asaasEnv === 'production' ? 'production' : 'sandbox',
            'sandbox' => $asaasSandboxKey,
            'prod' => $asaasProdKey,
        ]);

        $this->view('admin/config', [
            'pageTitle' => 'Configuração - OpenAI',
            'openaiKey' => $openaiKey,
            'anthropicKey' => $anthropicKey,
            'defaultModel' => $settingsToSave['openai_default_model'],
            'transcriptionModel' => $settingsToSave['openai_transcription_model'],
            'systemPrompt' => $settingsToSave['tuquinha_system_prompt'],
            'systemPromptExtra' => $settingsToSave['tuquinha_system_prompt_extra'],
            'historyRetentionDays' => $historyRetentionDays,
            'freeGlobalLimit' => $freeGlobalLimit,
            'freeChatLimit' => $freeChatLimit,
            'smtpHost' => $smtpHost,
            'smtpPort' => $smtpPort,
            'smtpUser' => $smtpUser,
            'smtpPassword' => $smtpPassword,
            'smtpFromEmail' => $smtpFromEmail,
            'smtpFromName' => $smtpFromName,
            'adminErrorEmail' => $adminErrorEmail,
            'adminErrorWebhook' => $adminErrorWebhook,
            'extraTokenPricePer1kGlobal' => $extraTokenPricePer1kGlobal,
            'googleClientId' => $googleClientId,
            'googleClientSecret' => $googleClientSecret,
            'googleRefreshToken' => $googleRefreshToken,
            'googleCalendarId' => $googleCalendarId !== '' ? $googleCalendarId : 'primary',
            'mediaEndpoint' => $mediaEndpoint !== '' ? $mediaEndpoint : (defined('MEDIA_UPLOAD_ENDPOINT') ? MEDIA_UPLOAD_ENDPOINT : ''),
            'mediaVideoEndpoint' => $mediaVideoEndpoint !== '' ? $mediaVideoEndpoint : (defined('MEDIA_VIDEO_UPLOAD_ENDPOINT') ? MEDIA_VIDEO_UPLOAD_ENDPOINT : ''),
            'textExtractionEndpoint' => $textExtractionEndpoint,
            'certificateIssuerName' => $certificateIssuerName,
            'certificateSignatureImagePath' => $certificateSignatureImagePath,
            'asaasEnvironment' => $asaasEnv === 'production' ? 'production' : 'sandbox',
            'asaasSandboxKey' => $asaasSandboxKey,
            'asaasProdKey' => $asaasProdKey,
            'saved' => true,
            'testEmailStatus' => null,
            'testEmailError' => null,
        ]);
    }

    public function sendTestEmail(): void
    {
        $this->ensureAdmin();

        $toEmail = trim($_POST['test_email'] ?? '');

        $openaiKey = Setting::get('openai_api_key', '');
        $anthropicKey = Setting::get('anthropic_api_key', ANTHROPIC_API_KEY);
        $defaultModel = Setting::get('openai_default_model', AI_MODEL);
        $transcriptionModel = Setting::get('openai_transcription_model', 'whisper-1');
        $systemPrompt = Setting::get('tuquinha_system_prompt', TuquinhaEngine::getDefaultPrompt());
        $systemPromptExtra = Setting::get('tuquinha_system_prompt_extra', '');
        $historyRetentionDays = (int)Setting::get('chat_history_retention_days', '90');
        if ($historyRetentionDays <= 0) {
            $historyRetentionDays = 90;
        }

        $smtpHost = Setting::get('smtp_host', '');
        $smtpPort = Setting::get('smtp_port', '587');
        $smtpUser = Setting::get('smtp_user', '');
        $smtpPassword = Setting::get('smtp_password', '');
        $smtpFromEmail = Setting::get('smtp_from_email', '');
        $smtpFromName = Setting::get('smtp_from_name', 'Tuquinha IA');

        $freeGlobalLimit = (int)Setting::get('free_memory_global_chars', '500');
        if ($freeGlobalLimit <= 0) {
            $freeGlobalLimit = 500;
        }
        $freeChatLimit = (int)Setting::get('free_memory_chat_chars', '400');
        if ($freeChatLimit <= 0) {
            $freeChatLimit = 400;
        }

        $adminErrorEmail = Setting::get('admin_error_notification_email', '');
        $adminErrorWebhook = Setting::get('admin_error_webhook_url', '');

        $extraTokenPricePer1kGlobal = Setting::get('extra_token_price_per_1k_global', '');

        $googleClientId = Setting::get('google_calendar_client_id', '');
        $googleClientSecret = Setting::get('google_calendar_client_secret', '');
        $googleRefreshToken = Setting::get('google_calendar_refresh_token', '');
        $googleCalendarId = Setting::get('google_calendar_calendar_id', 'primary');

        $mediaEndpoint = Setting::get('media_upload_endpoint', defined('MEDIA_UPLOAD_ENDPOINT') ? MEDIA_UPLOAD_ENDPOINT : '');
        $mediaVideoEndpoint = Setting::get('media_video_upload_endpoint', defined('MEDIA_VIDEO_UPLOAD_ENDPOINT') ? MEDIA_VIDEO_UPLOAD_ENDPOINT : '');
        $textExtractionEndpoint = Setting::get('text_extraction_endpoint', '');

        $certificateIssuerName = Setting::get('certificate_issuer_name', 'Thiago Marques');
        $certificateSignatureImagePath = Setting::get('certificate_signature_image_path', '');

        $asaas = AsaasConfig::getActive();

        $status = null;
        $error = null;

        if ($toEmail === '') {
            $status = false;
            $error = 'Informe um e-mail para teste.';
        } else {
            $subject = 'Teste de e-mail - Tuquinha';
            $body = '<p>Se você recebeu este e-mail, o envio SMTP do Tuquinha está funcionando.</p>';
            $sent = MailService::send($toEmail, $toEmail, $subject, $body);
            $status = $sent;
            if (!$sent) {
                $error = 'Não consegui enviar o e-mail de teste. Verifique as credenciais SMTP ou o servidor.';
            }
        }

        $this->view('admin/config', [
            'pageTitle' => 'Configuração - OpenAI',
            'openaiKey' => $openaiKey,
            'anthropicKey' => $anthropicKey,
            'defaultModel' => $defaultModel,
            'transcriptionModel' => $transcriptionModel,
            'systemPrompt' => $systemPrompt,
            'systemPromptExtra' => $systemPromptExtra,
            'historyRetentionDays' => $historyRetentionDays,
            'freeGlobalLimit' => $freeGlobalLimit,
            'freeChatLimit' => $freeChatLimit,
            'smtpHost' => $smtpHost,
            'smtpPort' => $smtpPort,
            'smtpUser' => $smtpUser,
            'smtpPassword' => $smtpPassword,
            'smtpFromEmail' => $smtpFromEmail,
            'smtpFromName' => $smtpFromName,
            'adminErrorEmail' => $adminErrorEmail,
            'adminErrorWebhook' => $adminErrorWebhook,
            'extraTokenPricePer1kGlobal' => $extraTokenPricePer1kGlobal,
            'googleClientId' => $googleClientId,
            'googleClientSecret' => $googleClientSecret,
            'googleRefreshToken' => $googleRefreshToken,
            'googleCalendarId' => $googleCalendarId,
            'mediaEndpoint' => $mediaEndpoint,
            'mediaVideoEndpoint' => $mediaVideoEndpoint,
            'textExtractionEndpoint' => $textExtractionEndpoint,
            'certificateIssuerName' => $certificateIssuerName,
            'certificateSignatureImagePath' => $certificateSignatureImagePath,
            'asaasEnvironment' => $asaas['environment'] ?? 'sandbox',
            'asaasSandboxKey' => $asaas['sandbox_api_key'] ?? '',
            'asaasProdKey' => $asaas['production_api_key'] ?? '',
            'saved' => false,
            'testEmailStatus' => $status,
            'testEmailError' => $error,
        ]);
    }

    public function certificatePreview(): void
    {
        $this->ensureAdmin();

        $issuerName = Setting::get('certificate_issuer_name', 'Thiago Marques') ?: 'Thiago Marques';
        $issuerSignatureImage = Setting::get('certificate_signature_image_path', '') ?: '';

        $theme = isset($_GET['theme']) ? (string)$_GET['theme'] : '';
        if ($theme !== 'light' && $theme !== 'dark') {
            $theme = 'dark';
        }

        $course = [
            'title' => 'Curso Exemplo - Tuquinha',
            'certificate_workload_hours' => 12,
            'certificate_location' => 'Online',
            'certificate_syllabus' => "- Introdução\n- Conteúdo\n- Prática",
        ];

        $user = [
            'name' => 'Aluno Exemplo',
        ];

        $badge = [
            'certificate_code' => 'PREVIEW-0001',
            'started_at' => date('Y-m-d', strtotime('-14 days')),
            'finished_at' => date('Y-m-d'),
        ];

        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $verifyUrl = $scheme . $host . '/certificados/verificar?code=' . urlencode((string)($badge['certificate_code'] ?? ''));

        $viewFile = __DIR__ . '/../Views/admin/config_certificate_preview.php';
        if (!file_exists($viewFile)) {
            http_response_code(500);
            echo 'View não encontrada';
            return;
        }

        header('Content-Type: text/html; charset=utf-8');
        include $viewFile;
    }
}
