<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\TuquinhaEngine;
use App\Models\Plan;
use App\Models\Attachment;
use App\Models\Setting;
use App\Models\User;
use App\Models\ConversationSetting;
use App\Models\Personality;
use App\Services\MediaStorageService;

class ChatController extends Controller
{
    public function index(): void
    {
        $sessionId = session_id();
        $userId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
        $conversationParam = isset($_GET['c']) ? (int)$_GET['c'] : 0;
        $isNew = isset($_GET['new']);

        // Se acessar /chat sem ?new=1 e sem ?c=, e não houver conversa atual, redireciona para seleção de personalidade
        if (!$isNew && $conversationParam === 0 && empty($_SESSION['current_conversation_id'])) {
            header('Location: /personalidades');
            exit;
        }

        if ($isNew) {
            $personaIdForNew = null;

            $requestedPersonaId = isset($_GET['persona_id']) ? (int)$_GET['persona_id'] : 0;
            if ($requestedPersonaId > 0) {
                $requestedPersona = Personality::findById($requestedPersonaId);
                if ($requestedPersona && !empty($requestedPersona['active'])) {
                    $personaIdForNew = (int)$requestedPersona['id'];
                }
            }

            // Se não veio persona explícita na URL, tenta a personalidade padrão da conta do usuário (se logado)
            if ($personaIdForNew === null && $userId > 0 && !empty($_SESSION['default_persona_id'])) {
                $userDefaultPersonaId = (int)$_SESSION['default_persona_id'];
                if ($userDefaultPersonaId > 0) {
                    $userPersona = Personality::findById($userDefaultPersonaId);
                    if ($userPersona && !empty($userPersona['active'])) {
                        $personaIdForNew = (int)$userPersona['id'];
                    }
                }
            }

            // Fallback: personalidade padrão global do Tuquinha
            if ($personaIdForNew === null) {
                $defaultPersona = Personality::findDefault();
                if ($defaultPersona) {
                    $personaIdForNew = (int)$defaultPersona['id'];
                }
            }

            if ($userId > 0) {
                $conversation = Conversation::createForUser($userId, $sessionId, $personaIdForNew);
            } else {
                $conversation = Conversation::createForSession($sessionId, $personaIdForNew);
            }
        } elseif ($conversationParam > 0) {
            if ($userId > 0) {
                $row = Conversation::findByIdForUser($conversationParam, $userId);
            } else {
                $row = Conversation::findByIdAndSession($conversationParam, $sessionId);
            }

            if ($row) {
                $conversation = new Conversation();
                $conversation->id = (int)$row['id'];
                $conversation->session_id = $row['session_id'];
                $conversation->user_id = isset($row['user_id']) ? (int)$row['user_id'] : null;
                $conversation->persona_id = isset($row['persona_id']) ? (int)$row['persona_id'] : null;
                $conversation->title = $row['title'] ?? null;
            } else {
                if ($userId > 0) {
                    $conversation = Conversation::createForUser($userId, $sessionId);
                } else {
                    $conversation = Conversation::findOrCreateBySession($sessionId);
                }
            }
        } else {
            $conversation = Conversation::findOrCreateBySession($sessionId);
        }

        $_SESSION['current_conversation_id'] = $conversation->id;

        $history = Message::allByConversation($conversation->id);
        $attachments = Attachment::allByConversation($conversation->id);

        $conversationSettings = null;

        $draftMessage = $_SESSION['draft_message'] ?? '';
        $audioError = $_SESSION['audio_error'] ?? null;
        $chatError = $_SESSION['chat_error'] ?? null;
        unset($_SESSION['draft_message'], $_SESSION['audio_error'], $_SESSION['chat_error']);

        $currentPlan = null;
        if (!empty($_SESSION['is_admin'])) {
            $currentPlan = Plan::findTopActive();
            if ($currentPlan && !empty($currentPlan['slug'])) {
                $_SESSION['plan_slug'] = $currentPlan['slug'];
            }
        } else {
            $currentPlan = Plan::findBySessionSlug($_SESSION['plan_slug'] ?? null);
            if (!$currentPlan) {
                $currentPlan = Plan::findBySlug('free');
                if ($currentPlan) {
                    $_SESSION['plan_slug'] = $currentPlan['slug'];
                }
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

        // Usuários logados podem usar regras/memórias por chat (inclusive plano free)
        $canUseConversationSettings = $userId > 0;

        // Personalidades só estão disponíveis para usuários logados em planos que liberam essa funcionalidade
        $planAllowsPersonalities = $userId > 0 && !empty($currentPlan['allow_personalities']);

        if ($conversationSettings === null && $userId > 0) {
            $conversationSettings = ConversationSetting::findForConversation($conversation->id, $userId) ?: null;
        }

        $currentPersona = null;
        $personalities = [];
        if ($planAllowsPersonalities) {
            if (!empty($conversation->persona_id)) {
                $currentPersona = Personality::findById((int)$conversation->persona_id) ?: null;
            }
            $personalities = Personality::allActive();
        }

        $this->view('chat/index', [
            'pageTitle' => 'Chat - Tuquinha',
            'chatHistory' => $history,
            'attachments' => $attachments,
            'allowedModels' => $allowedModels,
            'currentModel' => $_SESSION['chat_model'] ?? $defaultModel,
            'currentPlan' => $currentPlan,
            'draftMessage' => $draftMessage,
            'audioError' => $audioError,
            'chatError' => $chatError,
            'conversationId' => $conversation->id,
            'conversationSettings' => $conversationSettings,
            'canUseConversationSettings' => $canUseConversationSettings,
            'currentPersona' => $currentPersona,
            'personalities' => $personalities,
            'planAllowsPersonalities' => $planAllowsPersonalities,
        ]);
    }

    public function send(): void
    {
        $rawInput = (string)($_POST['message'] ?? '');
        $rawInput = str_replace(["\r\n", "\r"], "\n", $rawInput);
        // remove qualquer espaço/branco no início das linhas
        $rawInput = preg_replace('/^\s+/mu', '', $rawInput);
        $message = trim($rawInput);

        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        if (isset($_POST['model']) && $_POST['model'] !== '') {
            $_SESSION['chat_model'] = $_POST['model'];
        }

        if ($message !== '') {
            $sessionId = session_id();
            $userId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
            $conversation = null;

            if (!empty($_SESSION['current_conversation_id'])) {
                $row = Conversation::findByIdAndSession((int)$_SESSION['current_conversation_id'], $sessionId);
                if ($row) {
                    $conversation = new Conversation();
                    $conversation->id = (int)$row['id'];
                    $conversation->session_id = $row['session_id'];
                    $conversation->user_id = isset($row['user_id']) ? (int)$row['user_id'] : null;
                    $conversation->persona_id = isset($row['persona_id']) ? (int)$row['persona_id'] : null;
                    $conversation->title = $row['title'] ?? null;
                }
            }

            if (!$conversation) {
                if ($userId > 0) {
                    $conversation = Conversation::createForUser($userId, $sessionId);
                } else {
                    $conversation = Conversation::findOrCreateBySession($sessionId);
                }
                $_SESSION['current_conversation_id'] = $conversation->id;
            }

            // Verifica se é a primeira mensagem dessa conversa
            $existingMessages = Message::allByConversation($conversation->id);

            // Salva mensagem de texto do usuário
            Message::create($conversation->id, 'user', $message, null);

            // Se for a primeira mensagem, gera um título automático curto usando a IA
            if (empty($existingMessages)) {
                $raw = trim(preg_replace('/\s+/', ' ', $message));
                if ($raw === '') {
                    $raw = 'Chat com o Tuquinha';
                }

                $title = TuquinhaEngine::generateShortTitle($raw);

                if (!$title) {
                    // Fallback antigo: corta a primeira frase
                    $title = mb_substr($raw, 0, 60, 'UTF-8');
                    if (mb_strlen($raw, 'UTF-8') > 60) {
                        $title .= '...';
                    }
                }

                // Garante que não haja dois títulos idênticos para a mesma sessão
                $uniqueTitle = Conversation::ensureUniqueTitle($sessionId, $title);

                Conversation::updateTitle($conversation->id, $uniqueTitle);
            }

            // Trata anexos (imagens/arquivos) se enviados e se o plano permitir
            if (!empty($_SESSION['is_admin'])) {
                $plan = Plan::findTopActive();
                if ($plan && !empty($plan['slug'])) {
                    $_SESSION['plan_slug'] = $plan['slug'];
                }
            } else {
                $plan = Plan::findBySessionSlug($_SESSION['plan_slug'] ?? null);
                if (!$plan) {
                    $plan = Plan::findBySlug('free');
                    if ($plan) {
                        $_SESSION['plan_slug'] = $plan['slug'];
                    }
                }
            }
            $allowImages = !empty($plan['allow_images']);
            $allowFiles = !empty($plan['allow_files']);
            $maxSize = isset($plan['max_file_size_bytes']) && (int)$plan['max_file_size_bytes'] > 0
                ? (int)$plan['max_file_size_bytes']
                : 5 * 1024 * 1024; // default 5MB

            $attachmentSummaries = [];
            $attachmentMeta = [];
            $attachmentCsvPreviews = [];

            if (!empty($_FILES['attachments']) && is_array($_FILES['attachments']['name'])) {
                $count = count($_FILES['attachments']['name']);

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

                    if (!is_string($tmp) || $tmp === '' || !is_file($tmp)) {
                        continue;
                    }

                    // Envia o arquivo para o servidor de mídia externo
                    $remoteUrl = MediaStorageService::uploadFile($tmp, (string)$name, (string)$type);

                    // Monta um resumo amigável para o Tuquinha usar
                    if ($isCsv) {
                        $previewLines = [];
                        if (is_readable($tmp)) {
                            if (($fh = fopen($tmp, 'r')) !== false) {
                                $lineCount = 0;
                                while (($row = fgetcsv($fh)) !== false && $lineCount < 30) {
                                    $previewLines[] = implode(',', $row);
                                    $lineCount++;
                                }
                                fclose($fh);
                            }
                        }
                        if ($previewLines) {
                            $attachmentSummaries[] = "Arquivo CSV '" . $name . "' (até 30 linhas iniciais)";
                            $attachmentCsvPreviews[] = "ARQUIVO CSV: '" . $name . "' - ATÉ 30 LINHAS INICIAIS (USE ESTES DADOS, NÃO PEÇA PRO USUÁRIO COPIAR):\n" . implode("\n", $previewLines);
                        } else {
                            $attachmentSummaries[] = "Arquivo CSV '" . $name . "' foi enviado.";
                        }
                    } else {
                        $attachmentSummaries[] = "Arquivo '" . $name . "' foi enviado.";
                    }

                    if ($remoteUrl === null) {
                        // Não registra anexo se não tiver URL pública
                        continue;
                    }

                    $attType = $isImage ? 'image' : 'file';

                    Attachment::create([
                        'conversation_id' => $conversation->id,
                        'message_id' => null,
                        'type' => $attType,
                        'path' => $remoteUrl,
                        'original_name' => $name,
                        'mime_type' => $type,
                        'size' => $size,
                    ]);

                    // metadados para o frontend montar os cards
                    $humanSize = null;
                    if ($size > 0) {
                        if ($size >= 1024 * 1024) {
                            $humanSize = number_format($size / (1024 * 1024), 2, ',', '.') . ' MB';
                        } elseif ($size >= 1024) {
                            $humanSize = number_format($size / 1024, 2, ',', '.') . ' KB';
                        } else {
                            $humanSize = $size . ' B';
                        }
                    }

                    $label = 'Arquivo';
                    if ($isCsv) {
                        $label = 'CSV';
                    } elseif ($isPdf) {
                        $label = 'PDF';
                    } elseif ($isImage) {
                        $label = 'Imagem';
                    }

                    $attachmentMeta[] = [
                        'name' => $name,
                        'mime_type' => $type,
                        'size' => $size,
                        'size_human' => $humanSize,
                        'is_csv' => $isCsv,
                        'is_pdf' => $isPdf,
                        'is_image' => $isImage,
                        'label' => $label,
                    ];
                }
            }

            $attachmentsMessage = null;
            if (!empty($attachmentSummaries)) {
                $parts = [];
                $parts[] = "O usuário enviou os seguintes arquivos nesta mensagem. Você, Tuquinha, TEM acesso às prévias abaixo e deve usar esses dados diretamente nas suas respostas, sem pedir para o usuário abrir ou copiar o conteúdo do arquivo.";
                $parts[] = implode("\n", $attachmentSummaries);

                if (!empty($attachmentCsvPreviews)) {
                    $parts[] = implode("\n\n", $attachmentCsvPreviews);
                }

                $attachmentsMessage = implode("\n\n", $parts);

                Message::create($conversation->id, 'user', $attachmentsMessage, null);
            }

            $history = Message::allByConversation($conversation->id);

            // Carrega contexto do usuário, personalidade e da conversa para personalizar o Tuquinha
            $userData = null;
            $conversationSettings = null;
            $personaData = null;

            $planForContext = null;
            if (!empty($_SESSION['is_admin'])) {
                $planForContext = Plan::findTopActive();
            } else {
                $planForContext = Plan::findBySessionSlug($_SESSION['plan_slug'] ?? null);
                if (!$planForContext) {
                    $planForContext = Plan::findBySlug('free');
                }
            }

            $isFreePlan = $planForContext && ($planForContext['slug'] ?? '') === 'free';

            if (!empty($conversation->persona_id)) {
                $personaData = Personality::findById((int)$conversation->persona_id) ?: null;
            }

            if ($userId > 0) {
                $userData = User::findById($userId) ?: null;

                $currentBalance = User::getTokenBalance($userId);

                // Plano free: quando acabar os tokens, sugere assinar um plano pago
                if ($isFreePlan && $currentBalance <= 0) {
                    $assistantReply = 'Você está usando o plano Free e os seus tokens gratuitos chegaram ao fim. '
                        . 'Para continuar usando o Tuquinha com mais limite e recursos, é só assinar um plano pago.\n\n'
                        . 'Clique em "Planos e limites" no menu lateral ou acesse diretamente /planos para escolher o melhor plano para você.';

                    Message::create($conversation->id, 'assistant', $assistantReply, null);

                    if ($isAjax) {
                        header('Content-Type: application/json; charset=utf-8');

                        $nowLabel = date('d/m/Y H:i');
                        $responseMessages = [];
                        $responseMessages[] = [
                            'role' => 'user',
                            'content' => $message,
                            'created_label' => $nowLabel,
                        ];
                        $responseMessages[] = [
                            'role' => 'assistant',
                            'content' => $assistantReply,
                            'tokens_used' => 0,
                            'created_label' => $nowLabel,
                        ];

                        echo json_encode([
                            'success' => true,
                            'messages' => $responseMessages,
                            'total_tokens_used' => 0,
                        ]);
                        exit;
                    }

                    header('Location: /chat');
                    exit;
                }

                // Planos pagos com limite mensal de tokens: sugerem compra de tokens extras
                if ($planForContext && !$isFreePlan && isset($planForContext['monthly_token_limit']) && (int)$planForContext['monthly_token_limit'] > 0) {
                    if ($currentBalance <= 0) {
                        $assistantReply = 'Parece que o seu saldo de tokens deste plano chegou a zero. '
                            . 'Para continuar usando o Tuquinha sem interrupções, você pode comprar tokens extras na página de planos.\n\n'
                            . 'Clique em "Planos e limites" no menu lateral ou acesse diretamente /tokens/comprar para adicionar mais tokens ao seu saldo.';

                        // Grava mensagem do assistente no histórico
                        Message::create($conversation->id, 'assistant', $assistantReply, null);

                        if ($isAjax) {
                            header('Content-Type: application/json; charset=utf-8');

                            $nowLabel = date('d/m/Y H:i');

                            $responseMessages = [];

                            $responseMessages[] = [
                                'role' => 'user',
                                'content' => $message,
                                'created_label' => $nowLabel,
                            ];
                            $responseMessages[] = [
                                'role' => 'assistant',
                                'content' => $assistantReply,
                                'tokens_used' => 0,
                                'created_label' => $nowLabel,
                            ];

                            echo json_encode([
                                'success' => true,
                                'messages' => $responseMessages,
                                'total_tokens_used' => 0,
                            ]);
                            exit;
                        }

                        // Para requisições não-AJAX, apenas volta para o chat; a mensagem do assistente já foi gravada
                        header('Location: /chat');
                        exit;
                    }
                }
                $conversationSettings = ConversationSetting::findForConversation($conversation->id, $userId) ?: null;

                // Limites para plano free: corta textos muito longos de memórias/regras
                if ($isFreePlan) {
                    $maxGlobalChars = (int)Setting::get('free_memory_global_chars', '500');
                    if ($maxGlobalChars <= 0) {
                        $maxGlobalChars = 500;
                    }
                    $maxChatChars = (int)Setting::get('free_memory_chat_chars', '400');
                    if ($maxChatChars <= 0) {
                        $maxChatChars = 400;
                    }

                    if (is_array($userData)) {
                        if (isset($userData['global_memory']) && is_string($userData['global_memory'])) {
                            $userData['global_memory'] = mb_substr($userData['global_memory'], 0, $maxGlobalChars, 'UTF-8');
                        }
                        if (isset($userData['global_instructions']) && is_string($userData['global_instructions'])) {
                            $userData['global_instructions'] = mb_substr($userData['global_instructions'], 0, $maxGlobalChars, 'UTF-8');
                        }
                    }

                    if (is_array($conversationSettings)) {
                        if (isset($conversationSettings['memory_notes']) && is_string($conversationSettings['memory_notes'])) {
                            $conversationSettings['memory_notes'] = mb_substr($conversationSettings['memory_notes'], 0, $maxChatChars, 'UTF-8');
                        }
                        if (isset($conversationSettings['custom_instructions']) && is_string($conversationSettings['custom_instructions'])) {
                            $conversationSettings['custom_instructions'] = mb_substr($conversationSettings['custom_instructions'], 0, $maxChatChars, 'UTF-8');
                        }
                    }
                }
            }

            $engine = new TuquinhaEngine();
            $result = $engine->generateResponseWithContext(
                $history,
                $_SESSION['chat_model'] ?? null,
                $userData,
                $conversationSettings,
                $personaData
            );

            $assistantReply = is_array($result) ? (string)($result['content'] ?? '') : (string)$result;
            $totalTokensUsed = is_array($result) ? (int)($result['total_tokens'] ?? 0) : 0;

            // Normaliza quebras de linha e remove espaços/brancos no início de cada linha
            $assistantReply = str_replace(["\r\n", "\r"], "\n", (string)$assistantReply);
            $assistantReply = preg_replace('/^\s+/mu', '', $assistantReply);
            $assistantReply = trim($assistantReply);

            Message::create($conversation->id, 'assistant', $assistantReply, $totalTokensUsed > 0 ? $totalTokensUsed : null);

            // Debita tokens do usuário logado, se houver contador de uso disponível
            if ($userId > 0 && $totalTokensUsed > 0) {
                User::debitTokens($userId, $totalTokensUsed, 'chat_completion', [
                    'conversation_id' => $conversation->id,
                    'plan_slug' => $planForContext['slug'] ?? null,
                ]);
            }

            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');

                $nowLabel = date('d/m/Y H:i');

                $responseMessages = [];
                $responseMessages[] = [
                    'role' => 'user',
                    'content' => $message,
                    'created_label' => $nowLabel,
                ];

                if (!empty($attachmentMeta)) {
                    $responseMessages[] = [
                        'role' => 'attachment_summary',
                        'content' => $attachmentsMessage,
                        'attachments' => $attachmentMeta,
                    ];
                }

                $responseMessages[] = [
                    'role' => 'assistant',
                    'content' => $assistantReply,
                    'tokens_used' => $totalTokensUsed,
                    'created_label' => $nowLabel,
                ];

                echo json_encode([
                    'success' => true,
                    'messages' => $responseMessages,
                    'total_tokens_used' => $totalTokensUsed,
                ]);
                exit;
            }
        }

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => 'Mensagem vazia.',
            ]);
            exit;
        }

        header('Location: /chat');
        exit;
    }

    public function sendAudio(): void
    {
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        if (empty($_FILES['audio']['tmp_name'])) {
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'error' => 'Nenhum áudio recebido.',
                ]);
                exit;
            }

            header('Location: /chat');
            exit;
        }

        $sessionId = session_id();
        $userId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
        $conversation = null;

        if (!empty($_SESSION['current_conversation_id'])) {
            $row = Conversation::findByIdAndSession((int)$_SESSION['current_conversation_id'], $sessionId);
            if ($row) {
                $conversation = new Conversation();
                $conversation->id = (int)$row['id'];
                $conversation->session_id = $row['session_id'];
                $conversation->user_id = isset($row['user_id']) ? (int)$row['user_id'] : null;
                $conversation->persona_id = isset($row['persona_id']) ? (int)$row['persona_id'] : null;
                $conversation->title = $row['title'] ?? null;
            }
        }

        if (!$conversation) {
            if ($userId > 0) {
                $conversation = Conversation::createForUser($userId, $sessionId);
            } else {
                $conversation = Conversation::findOrCreateBySession($sessionId);
            }
            $_SESSION['current_conversation_id'] = $conversation->id;
        }

        $tmpPath = $_FILES['audio']['tmp_name'];
        $originalName = $_FILES['audio']['name'] ?? 'audio.webm';
        $mime = $_FILES['audio']['type'] ?? 'audio/webm';
        $size = (int)($_FILES['audio']['size'] ?? 0);

        // Envia o áudio para o servidor de mídia externo (como anexo da conversa)
        $remoteAudioUrl = null;
        if (is_string($tmpPath) && $tmpPath !== '' && is_file($tmpPath)) {
            $remoteAudioUrl = MediaStorageService::uploadFile($tmpPath, (string)$originalName, (string)$mime);
        }

        if ($remoteAudioUrl !== null) {
            Attachment::create([
                'conversation_id' => $conversation->id,
                'message_id' => null,
                'type' => 'audio',
                'path' => $remoteAudioUrl,
                'original_name' => $originalName,
                'mime_type' => $mime,
                'size' => $size,
            ]);
        }

        // Transcrição via OpenAI (se chave configurada)
        $configuredApiKey = Setting::get('openai_api_key', AI_API_KEY);
        $transcriptionModel = Setting::get('openai_transcription_model', 'whisper-1');

        if (empty($configuredApiKey)) {
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'error' => 'A transcrição de áudio ainda não está configurada pelo administrador.',
                ]);
                exit;
            }

            $_SESSION['audio_error'] = 'A transcrição de áudio ainda não está configurada pelo administrador.';
            header('Location: /chat');
            exit;
        }

        $transcriptText = '';

        if (is_string($tmpPath) && $tmpPath !== '' && file_exists($tmpPath)) {
            $ch = curl_init('https://api.openai.com/v1/audio/transcriptions');
            $cfile = new \CURLFile($tmpPath, $mime, $originalName);
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
                } else {
                    if ($isAjax) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode([
                            'success' => false,
                            'error' => 'Não consegui transcrever o áudio (código ' . $http . '). Tente novamente.',
                        ]);
                        curl_close($ch);
                        exit;
                    }

                    $_SESSION['audio_error'] = 'Não consegui transcrever o áudio (código ' . $http . '). Tente novamente.';
                }
            } else {
                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'success' => false,
                        'error' => 'Ocorreu um erro ao enviar o áudio para transcrição.',
                    ]);
                    curl_close($ch);
                    exit;
                }

                $_SESSION['audio_error'] = 'Ocorreu um erro ao enviar o áudio para transcrição.';
            }
            curl_close($ch);
        }

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            if ($transcriptText !== '') {
                echo json_encode([
                    'success' => true,
                    'text' => $transcriptText,
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Não consegui obter texto a partir do áudio enviado.',
                ]);
            }
            exit;
        }

        if ($transcriptText !== '') {
            $_SESSION['draft_message'] = $transcriptText;
        } elseif (empty($_SESSION['audio_error'])) {
            $_SESSION['audio_error'] = 'Não consegui obter texto a partir do áudio enviado.';
        }

        header('Location: /chat');
        exit;
    }

    public function saveSettings(): void
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $userId = (int)$_SESSION['user_id'];
        $conversationId = isset($_POST['conversation_id']) ? (int)$_POST['conversation_id'] : 0;
        $memoryNotes = trim((string)($_POST['memory_notes'] ?? ''));
        $customInstructions = trim((string)($_POST['custom_instructions'] ?? ''));

        if ($conversationId > 0) {
            $conv = Conversation::findByIdForUser($conversationId, $userId);
            if ($conv) {
                ConversationSetting::upsert($conversationId, $userId, $customInstructions, $memoryNotes);
            }
        }

        $redirect = '/chat';
        if ($conversationId > 0) {
            $redirect .= '?c=' . $conversationId;
        }
        header('Location: ' . $redirect);
        exit;
    }

    public function changePersona(): void
    {
        $sessionId = session_id();
        $userId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

        $conversationId = isset($_POST['conversation_id']) ? (int)$_POST['conversation_id'] : 0;
        $personaIdRaw = isset($_POST['persona_id']) ? (int)$_POST['persona_id'] : 0;

        // Apenas usuários logados podem trocar a personalidade da conversa
        if ($userId <= 0) {
            header('Location: /chat');
            exit;
        }

        // Verifica se o plano atual permite uso de personalidades
        $currentPlan = null;
        if (!empty($_SESSION['is_admin'])) {
            $currentPlan = Plan::findTopActive();
        } else {
            $currentPlan = Plan::findBySessionSlug($_SESSION['plan_slug'] ?? null);
            if (!$currentPlan) {
                $currentPlan = Plan::findBySlug('free');
                if ($currentPlan && !empty($currentPlan['slug'])) {
                    $_SESSION['plan_slug'] = $currentPlan['slug'];
                }
            }
        }

        if (empty($currentPlan['allow_personalities'])) {
            header('Location: /chat');
            exit;
        }

        if ($conversationId <= 0) {
            header('Location: /chat');
            exit;
        }

        $convRow = null;
        if ($userId > 0) {
            $convRow = Conversation::findByIdForUser($conversationId, $userId);
        } else {
            $convRow = Conversation::findByIdAndSession($conversationId, $sessionId);
        }

        if (!$convRow) {
            header('Location: /chat');
            exit;
        }

        $currentPersonaId = isset($convRow['persona_id']) ? (int)$convRow['persona_id'] : 0;
        if ($currentPersonaId > 0) {
            $_SESSION['chat_error'] = 'A personalidade deste chat já foi escolhida e não pode mais ser alterada. Crie um novo chat para usar outra personalidade.';
            header('Location: /chat?c=' . $conversationId);
            exit;
        }

        $personaId = null;
        if ($personaIdRaw > 0) {
            $persona = Personality::findById($personaIdRaw);
            if ($persona && !empty($persona['active'])) {
                $personaId = (int)$persona['id'];
            }
        }

        Conversation::updatePersona($conversationId, $personaId);

        $_SESSION['current_conversation_id'] = $conversationId;

        header('Location: /chat?c=' . $conversationId);
        exit;
    }
}
