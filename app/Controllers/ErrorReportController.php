<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ErrorReport;
use App\Models\Setting;
use App\Models\User;
use App\Services\MailService;

class ErrorReportController extends Controller
{
    public function store(): void
    {
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo 'unauthorized';
            return;
        }

        $userId = (int)$_SESSION['user_id'];
        $conversationId = isset($_POST['conversation_id']) ? (int)$_POST['conversation_id'] : 0;
        $messageId = isset($_POST['message_id']) ? (int)$_POST['message_id'] : 0;
        $tokensUsed = isset($_POST['tokens_used']) ? (int)$_POST['tokens_used'] : 0;
        $errorMessage = trim((string)($_POST['error_message'] ?? ''));
        $userComment = trim((string)($_POST['user_comment'] ?? ''));

        if ($tokensUsed < 0) {
            $tokensUsed = 0;
        }

        $reportId = ErrorReport::create([
            'user_id' => $userId,
            'conversation_id' => $conversationId ?: null,
            'message_id' => $messageId ?: null,
            'tokens_used' => $tokensUsed,
            'error_message' => $errorMessage !== '' ? $errorMessage : null,
            'user_comment' => $userComment !== '' ? $userComment : null,
            'status' => 'open',
        ]);

        // Notificação para admin (e-mail)
        $adminEmail = Setting::get('admin_error_notification_email', '');
        $adminWebhook = Setting::get('admin_error_webhook_url', '');

        $user = User::findById($userId);

        if ($adminEmail && $user) {
            $subject = 'Novo relato de erro de análise no Tuquinha';

            $safeName = htmlspecialchars($user['name'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeEmail = htmlspecialchars($user['email'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $body = '<p>Um usuário relatou um problema ao analisar mensagem/arquivo.</p>' .
                '<ul>' .
                '<li><strong>Usuário:</strong> ' . $safeName . ' (' . $safeEmail . ')</li>' .
                '<li><strong>ID do relatório:</strong> ' . (int)$reportId . '</li>' .
                '<li><strong>Tokens usados:</strong> ' . (int)$tokensUsed . '</li>' .
                '<li><strong>Conversa:</strong> ' . ($conversationId ?: '-') . '</li>' .
                '<li><strong>Mensagem:</strong> ' . ($messageId ?: '-') . '</li>' .
                '</ul>';

            if ($errorMessage !== '') {
                $body .= '<p><strong>Mensagem de erro técnica:</strong><br><pre style="white-space:pre-wrap;">' .
                    htmlspecialchars($errorMessage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</pre></p>';
            }

            if ($userComment !== '') {
                $body .= '<p><strong>Comentário do usuário:</strong><br>' .
                    nl2br(htmlspecialchars($userComment, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) . '</p>';
            }

            try {
                MailService::send($adminEmail, $adminEmail, $subject, $body);
            } catch (\Throwable $e) {
                // Falha na notificação por e-mail não deve quebrar o fluxo do usuário
            }
        }

        // Notificação por webhook (opcional)
        if ($adminWebhook) {
            $payload = [
                'id' => (int)$reportId,
                'user_id' => $userId,
                'conversation_id' => $conversationId ?: null,
                'message_id' => $messageId ?: null,
                'tokens_used' => $tokensUsed,
                'error_message' => $errorMessage,
                'user_comment' => $userComment,
                'created_at' => date('c'),
            ];

            try {
                $ch = curl_init($adminWebhook);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_HTTPHEADER => [
                        'Content-Type: application/json',
                    ],
                    CURLOPT_POSTFIELDS => json_encode($payload),
                    CURLOPT_TIMEOUT => 10,
                ]);
                curl_exec($ch);
                curl_close($ch);
            } catch (\Throwable $e) {
                // Ignora falha em webhook
            }
        }

        // Resposta simples para o frontend (AJAX)
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'message' => 'Seu relato foi enviado para a equipe analisar. Se os tokens tiverem sido consumidos por erro do sistema, eles poderão ser devolvidos na sua conta.',
            ]);
            return;
        }

        // Fallback para requisições normais
        $_SESSION['report_success'] = 'Seu relato foi enviado para a equipe analisar.';
        header('Location: /chat');
        exit;
    }
}
