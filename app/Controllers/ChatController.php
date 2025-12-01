<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\TuquinhaEngine;
use App\Models\Plan;
use App\Models\Attachment;
use App\Models\Setting;

class ChatController extends Controller
{
    public function index(): void
    {
        $sessionId = session_id();
        $conversation = Conversation::findOrCreateBySession($sessionId);
        $history = Message::allByConversation($conversation->id);

        $currentPlan = Plan::findBySessionSlug($_SESSION['plan_slug'] ?? null);
        if (!$currentPlan) {
            $currentPlan = Plan::findBySlug('free');
            if ($currentPlan) {
                $_SESSION['plan_slug'] = $currentPlan['slug'];
            }
        }

        $allowedModels = [];
        $defaultModel = null;

        if ($currentPlan) {
            $allowedModels = Plan::parseAllowedModels($currentPlan['allowed_models'] ?? null);
            $defaultModel = $currentPlan['default_model'] ?? null;
        }

        if (!$allowedModels) {
            $fallbackModel = Setting::get('openai_default_model', AI_MODEL);
            if ($fallbackModel) {
                $allowedModels = [$fallbackModel];
                if (!$defaultModel) {
                    $defaultModel = $fallbackModel;
                }
            }
        }

        if (empty($_SESSION['chat_model']) && $defaultModel) {
            $_SESSION['chat_model'] = $defaultModel;
        }

        $this->view('chat/index', [
            'pageTitle' => 'Chat - Tuquinha',
            'chatHistory' => $history,
            'allowedModels' => $allowedModels,
            'currentModel' => $_SESSION['chat_model'] ?? $defaultModel,
            'currentPlan' => $currentPlan,
        ]);
    }

    public function send(): void
    {
        $message = trim($_POST['message'] ?? '');

        if (isset($_POST['model']) && $_POST['model'] !== '') {
            $_SESSION['chat_model'] = $_POST['model'];
        }

        if ($message !== '') {
            $sessionId = session_id();
            $conversation = Conversation::findOrCreateBySession($sessionId);

            // Verifica se é a primeira mensagem dessa conversa
            $existingMessages = Message::allByConversation($conversation->id);

            // Salva mensagem de texto do usuário
            Message::create($conversation->id, 'user', $message);

            // Se for a primeira mensagem, gera um título automático para o chat
            if (empty($existingMessages)) {
                $raw = trim(preg_replace('/\s+/', ' ', $message));
                if ($raw === '') {
                    $raw = 'Chat com o Tuquinha';
                }
                $title = mb_substr($raw, 0, 60, 'UTF-8');
                if (mb_strlen($raw, 'UTF-8') > 60) {
                    $title .= '...';
                }
                Conversation::updateTitle($conversation->id, $title);
            }

            // Trata anexos (imagens/arquivos) se enviados e se o plano permitir
            $plan = Plan::findBySessionSlug($_SESSION['plan_slug'] ?? null);
            if (!$plan) {
                $plan = Plan::findBySlug('free');
                if ($plan) {
                    $_SESSION['plan_slug'] = $plan['slug'];
                }
            }
            $allowImages = !empty($plan['allow_images']);
            $allowFiles = !empty($plan['allow_files']);
            $maxSize = isset($plan['max_file_size_bytes']) && (int)$plan['max_file_size_bytes'] > 0
                ? (int)$plan['max_file_size_bytes']
                : 5 * 1024 * 1024; // default 5MB

            if (!empty($_FILES['attachments']) && is_array($_FILES['attachments']['name'])) {
                $count = count($_FILES['attachments']['name']);
                $uploadDir = __DIR__ . '/../../storage/uploads/files';
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0775, true);
                }

                for ($i = 0; $i < $count; $i++) {
                    $error = $_FILES['attachments']['error'][$i] ?? UPLOAD_ERR_NO_FILE;
                    if ($error !== UPLOAD_ERR_OK) {
                        continue;
                    }

                    $tmp = $_FILES['attachments']['tmp_name'][$i];
                    $name = $_FILES['attachments']['name'][$i];
                    $type = $_FILES['attachments']['type'][$i] ?? '';
                    $size = (int)($_FILES['attachments']['size'][$i] ?? 0);

                    if ($size <= 0 || $size > $maxSize) {
                        continue;
                    }

                    $isImage = str_starts_with($type, 'image/');
                    $isPdf = $type === 'application/pdf';
                    $isCsv = $type === 'text/csv' || $type === 'application/vnd.ms-excel';

                    if ($isImage && !$allowImages) {
                        continue;
                    }
                    if (!$isImage && !$allowFiles) {
                        continue;
                    }

                    $ext = pathinfo($name, PATHINFO_EXTENSION);
                    $targetPath = $uploadDir . '/' . uniqid('file_', true) . ($ext ? ('.' . $ext) : '');
                    if (!@move_uploaded_file($tmp, $targetPath)) {
                        continue;
                    }

                    $attType = $isImage ? 'image' : 'file';

                    Attachment::create([
                        'conversation_id' => $conversation->id,
                        'message_id' => null,
                        'type' => $attType,
                        'path' => $targetPath,
                        'original_name' => $name,
                        'mime_type' => $type,
                        'size' => $size,
                    ]);
                }
            }

            $history = Message::allByConversation($conversation->id);

            $engine = new TuquinhaEngine();
            $assistantReply = $engine->generateResponse($history, $_SESSION['chat_model'] ?? null);

            Message::create($conversation->id, 'assistant', $assistantReply);
        }

        header('Location: /chat');
        exit;
    }

    public function sendAudio(): void
    {
        if (empty($_FILES['audio']['tmp_name'])) {
            header('Location: /chat');
            exit;
        }

        $sessionId = session_id();
        $conversation = Conversation::findOrCreateBySession($sessionId);

        $tmpPath = $_FILES['audio']['tmp_name'];
        $originalName = $_FILES['audio']['name'] ?? 'audio.webm';
        $mime = $_FILES['audio']['type'] ?? 'audio/webm';
        $size = (int)($_FILES['audio']['size'] ?? 0);

        // Salva o áudio como anexo (opcional)
        $uploadDir = __DIR__ . '/../../storage/uploads/audio';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0775, true);
        }
        $targetPath = $uploadDir . '/' . uniqid('audio_', true) . '.webm';
        @move_uploaded_file($tmpPath, $targetPath);

        Attachment::create([
            'conversation_id' => $conversation->id,
            'message_id' => null,
            'type' => 'audio',
            'path' => $targetPath,
            'original_name' => $originalName,
            'mime_type' => $mime,
            'size' => $size,
        ]);

        // Transcrição via OpenAI (se chave configurada)
        $transcriptText = '';
        $configuredApiKey = Setting::get('openai_api_key', AI_API_KEY);
        $transcriptionModel = Setting::get('openai_transcription_model', 'gpt-4o-mini-transcribe');

        if (!empty($configuredApiKey) && file_exists($targetPath)) {
            $ch = curl_init('https://api.openai.com/v1/audio/transcriptions');
            $cfile = new \CURLFile($targetPath, $mime, $originalName);
            $postFields = [
                'file' => $cfile,
                'model' => $transcriptionModel,
            ];

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $configuredApiKey,
                ],
                CURLOPT_POSTFIELDS => $postFields,
                CURLOPT_TIMEOUT => 60,
            ]);

            $result = curl_exec($ch);
            if ($result !== false) {
                $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ($http >= 200 && $http < 300) {
                    $data = json_decode($result, true);
                    $transcriptText = (string)($data['text'] ?? '');
                }
            }
            curl_close($ch);
        }

        if ($transcriptText !== '') {
            Message::create($conversation->id, 'user', $transcriptText);

            $history = Message::allByConversation($conversation->id);
            $engine = new TuquinhaEngine();
            $assistantReply = $engine->generateResponse($history, $_SESSION['chat_model'] ?? null);
            Message::create($conversation->id, 'assistant', $assistantReply);
        }

        header('Location: /chat');
        exit;
    }
}
