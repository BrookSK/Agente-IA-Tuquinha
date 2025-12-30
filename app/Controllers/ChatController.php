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
use App\Models\ProjectMember;
use App\Models\ProjectFile;
use App\Models\ProjectFileVersion;
use App\Models\Project;
use App\Models\ProjectMemoryItem;

class ChatController extends Controller
{
    public function index(): void
    {
        $sessionId = session_id();
        $userId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
        $conversationParam = isset($_GET['c']) ? (int)$_GET['c'] : 0;
        $isNew = isset($_GET['new']);
        $projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

        if ($projectId > 0) {
            if ($userId <= 0 || !ProjectMember::canRead($projectId, $userId)) {
                header('Location: /projetos');
                exit;
            }
        }

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
                $conversation = Conversation::createForUser($userId, $sessionId, $personaIdForNew, $projectId > 0 ? $projectId : null);
            } else {
                $conversation = Conversation::createForSession($sessionId, $personaIdForNew, $projectId > 0 ? $projectId : null);
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
                $conversation->project_id = isset($row['project_id']) ? (int)$row['project_id'] : null;

                if (!empty($conversation->project_id) && $userId > 0) {
                    if (!ProjectMember::canRead((int)$conversation->project_id, $userId)) {
                        header('Location: /projetos');
                        exit;
                    }
                }
            } else {
                if ($userId > 0) {
                    $conversation = Conversation::createForUser($userId, $sessionId, null, $projectId > 0 ? $projectId : null);
                } else {
                    $conversation = Conversation::findOrCreateBySession($sessionId, null, $projectId > 0 ? $projectId : null);
                }
            }
        } else {
            $conversation = Conversation::findOrCreateBySession($sessionId, null, $projectId > 0 ? $projectId : null);
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

        $projectContext = null;
        if (!empty($conversation->project_id)) {
            $pid = (int)$conversation->project_id;
            $project = Project::findById($pid);
            if ($project) {
                $baseFiles = ProjectFile::allBaseFiles($pid);
                $baseFileIds = array_map(static function ($f) {
                    return (int)($f['id'] ?? 0);
                }, $baseFiles);
                $latestByFileId = ProjectFileVersion::latestForFiles($baseFileIds);

                $withText = 0;
                foreach ($baseFiles as $bf) {
                    $fid = (int)($bf['id'] ?? 0);
                    $ver = $latestByFileId[$fid] ?? null;
                    $txt = is_array($ver) ? (string)($ver['extracted_text'] ?? '') : '';
                    if (trim($txt) !== '') {
                        $withText++;
                    }
                }

                $projectContext = [
                    'project' => $project,
                    'base_files_total' => count($baseFiles),
                    'base_files_with_text' => $withText,
                ];
            }
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
            'projectContext' => $projectContext,
        ]);
    }

    public function deleteConversation(): void
    {
        $sessionId = session_id();
        $userId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

        $conversationId = isset($_POST['conversation_id']) ? (int)$_POST['conversation_id'] : 0;
        $projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
        $redirect = trim((string)($_POST['redirect'] ?? ''));

        $deleted = false;
        if ($userId > 0) {
            $deleted = Conversation::deleteByIdForUser($conversationId, $userId);
        } else {
            $deleted = Conversation::deleteByIdForSession($conversationId, $sessionId);
        }

        if (!empty($_SESSION['current_conversation_id']) && (int)$_SESSION['current_conversation_id'] === $conversationId) {
            unset($_SESSION['current_conversation_id']);
        }

        if ($deleted) {
            $_SESSION['chat_error'] = null;
        }

        if ($redirect !== '') {
            header('Location: ' . $redirect);
            exit;
        }

        if ($projectId > 0) {
            header('Location: /projetos/ver?id=' . $projectId);
            exit;
        }

        header('Location: /historico');
        exit;
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
                    $conversation->project_id = isset($row['project_id']) ? (int)$row['project_id'] : null;
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

            if (!empty($conversation->project_id)) {
                if ($userId <= 0 || !ProjectMember::canRead((int)$conversation->project_id, $userId)) {
                    if ($isAjax) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['success' => false, 'error' => 'Sem acesso a este projeto.']);
                        exit;
                    }
                    header('Location: /projetos');
                    exit;
                }
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
            $fileInputsForModel = [];

            if (!empty($_FILES['attachments']) && is_array($_FILES['attachments']['name'])) {
                $count = count($_FILES['attachments']['name']);

                $modelToUse = isset($_SESSION['chat_model']) && is_string($_SESSION['chat_model']) && $_SESSION['chat_model'] !== ''
                    ? (string)$_SESSION['chat_model']
                    : '';
                $isOpenAIModel = !str_starts_with($modelToUse, 'claude-');

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
                    $isAudio = str_starts_with($type, 'audio/');

                    $ext = '';
                    if (is_string($name) && $name !== '' && strpos($name, '.') !== false) {
                        $ext = strtolower((string)pathinfo($name, PATHINFO_EXTENSION));
                    }

                    if ($isOpenAIModel) {
                        $isOfficeUnsupported = in_array($ext, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'], true)
                            || in_array($type, [
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-powerpoint',
                                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                            ], true);

                        if ($isOfficeUnsupported) {
                            $friendly = "Este tipo de arquivo (Word/Excel/PowerPoint) não é suportado pelo modelo OpenAI neste chat. "
                                . "Sugestões: (1) converta para PDF, TXT ou CSV e envie novamente; (2) selecione um modelo Claude (que aceita documentos via base64).";

                            if ($isAjax) {
                                header('Content-Type: application/json; charset=utf-8');
                                echo json_encode([
                                    'success' => false,
                                    'error' => $friendly,
                                ]);
                                exit;
                            }

                            $_SESSION['chat_error'] = $friendly;
                            header('Location: /chat');
                            exit;
                        }
                    }

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

                    // Monta um resumo amigável para exibir no histórico
                    $attachmentSummaries[] = "Arquivo '" . $name . "' foi enviado.";

                    if ($remoteUrl === null) {
                        // Não registra anexo se não tiver URL pública
                        continue;
                    }

                    $attType = $isImage ? 'image' : 'file';

                    $attachmentId = Attachment::create([
                        'conversation_id' => $conversation->id,
                        'message_id' => null,
                        'type' => $attType,
                        'path' => $remoteUrl,
                        'original_name' => $name,
                        'mime_type' => $type,
                        'size' => $size,
                    ]);

                    if (!$isAudio) {
                        $fileInputsForModel[] = [
                            'attachment_id' => (int)$attachmentId,
                            'tmp_path' => (string)$tmp,
                            'name' => (string)$name,
                            'mime_type' => (string)$type,
                            'url' => (string)$remoteUrl,
                        ];
                    }

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
                    if ($isPdf) {
                        $label = 'PDF';
                    } elseif ($isImage) {
                        $label = 'Imagem';
                    }

                    $attachmentMeta[] = [
                        'name' => $name,
                        'mime_type' => $type,
                        'size' => $size,
                        'size_human' => $humanSize,
                        'is_pdf' => $isPdf,
                        'is_image' => $isImage,
                        'label' => $label,
                    ];
                }
            }

            $attachmentsMessage = null;
            if (!empty($attachmentSummaries)) {
                $parts = [];
                $parts[] = "O usuário enviou os seguintes arquivos nesta mensagem.";
                $parts[] = implode("\n", $attachmentSummaries);

                $attachmentsMessage = implode("\n\n", $parts);
            }

            $history = Message::allByConversation($conversation->id);

            $projectContextFilesUsed = [];
            $projectContextMessage = null;
            $projectFileInputsForModel = [];

            if (!empty($conversation->project_id) && $userId > 0) {
                $projectId = (int)$conversation->project_id;
                if (ProjectMember::canRead($projectId, $userId)) {
                    $enabled = (string)Setting::get('project_auto_memory_enabled', '1');
                    if ($enabled !== '0') {
                        $normalizedMsg = trim((string)$message);
                        if (mb_strlen($normalizedMsg, 'UTF-8') >= 25) {
                            $engineForMemory = new TuquinhaEngine();
                            $instruction = "Extraia APENAS fatos estáveis e úteis sobre o PROJETO (não sobre emoções momentâneas). "
                                . "Retorne APENAS JSON válido, sem texto extra, no formato: {\"items\":[{\"content\":\"...\"}]} . "
                                . "Regras: (1) cada item deve ser curto (até 180 chars), (2) sem perguntas, (3) sem dados sensíveis (senha, cartão, cpf), "
                                . "(4) só inclua se for algo que ajudaria o assistente em conversas futuras (ex: tipo do projeto, público, stack, regras). "
                                . "Se não houver nada relevante, retorne {\"items\":[]}.";

                            $memoryResult = $engineForMemory->generateResponseWithContext(
                                [
                                    ['role' => 'user', 'content' => $instruction . "\n\nMENSAGEM DO USUÁRIO:\n" . $normalizedMsg],
                                ],
                                $_SESSION['chat_model'] ?? null,
                                null,
                                null,
                                null
                            );

                            $memoryText = is_array($memoryResult) ? (string)($memoryResult['content'] ?? '') : (string)$memoryResult;
                            $memoryText = trim((string)$memoryText);

                            if ($memoryText !== '' && $memoryText[0] === '{') {
                                $json = json_decode($memoryText, true);
                                if (is_array($json) && isset($json['items']) && is_array($json['items'])) {
                                    foreach ($json['items'] as $it) {
                                        $content = is_array($it) ? (string)($it['content'] ?? '') : '';
                                        $content = trim(str_replace(["\r\n", "\r"], "\n", $content));
                                        if ($content === '') {
                                            continue;
                                        }
                                        if (mb_strlen($content, 'UTF-8') > 180) {
                                            $content = mb_substr($content, 0, 180, 'UTF-8');
                                            $content = rtrim($content);
                                        }
                                        if (strpos($content, '?') !== false) {
                                            continue;
                                        }
                                        $lc = mb_strtolower($content, 'UTF-8');
                                        if (strpos($lc, 'senha') !== false || strpos($lc, 'cartão') !== false || strpos($lc, 'cpf') !== false) {
                                            continue;
                                        }
                                        if (!ProjectMemoryItem::existsSimilar($projectId, $content)) {
                                            ProjectMemoryItem::create($projectId, $userId, (int)$conversation->id, $normalizedMsg, $content);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if (!empty($conversation->project_id)) {
                $projectId = (int)$conversation->project_id;
                $projectRow = Project::findById($projectId);
                $baseFiles = ProjectFile::allBaseFiles($projectId);
                $baseFileIds = array_map(static function ($f) {
                    return (int)($f['id'] ?? 0);
                }, $baseFiles);
                $latestByFileId = ProjectFileVersion::latestForFiles($baseFileIds);

                $parts = [];

                if (is_array($projectRow)) {
                    $pName = trim((string)($projectRow['name'] ?? ''));
                    $pDesc = (string)($projectRow['description'] ?? '');

                    if ($pName !== '') {
                        $parts[] = 'DADOS DO PROJETO: nome=' . $pName;
                    }

                    $pDescNorm = trim(str_replace(["\r\n", "\r"], "\n", $pDesc));
                    if ($pDescNorm !== '') {
                        $parts[] = "MEMÓRIA / DESCRIÇÃO DO PROJETO (persistente):\n" . $pDescNorm;
                    }
                }

                $autoMem = ProjectMemoryItem::allActiveForProject($projectId, 60);
                if (!empty($autoMem)) {
                    $lines = [];
                    foreach ($autoMem as $mi) {
                        $c = trim((string)($mi['content'] ?? ''));
                        if ($c !== '') {
                            $lines[] = '- ' . $c;
                        }
                    }
                    if (!empty($lines)) {
                        $parts[] = "MEMÓRIAS AUTOMÁTICAS DO PROJETO (extraídas do chat; podem estar incompletas, trate como pistas e confirme se necessário):\n" . implode("\n", $lines);
                    }
                }

                if (!empty($baseFiles)) {
                    $names = [];
                    foreach ($baseFiles as $bfMeta) {
                        $names[] = (string)($bfMeta['name'] ?? '');
                    }
                    $names = array_values(array_filter(array_map('trim', $names)));
                    if ($names) {
                        $parts[] = "ARQUIVOS BASE DISPONÍVEIS (você tem acesso ao conteúdo extraído quando houver):\n- " . implode("\n- ", $names);
                    }
                }

                foreach ($baseFiles as $bf) {
                    $fid = (int)($bf['id'] ?? 0);
                    $ver = $latestByFileId[$fid] ?? null;
                    $path = (string)($bf['path'] ?? '');
                    $displayName = (string)($bf['name'] ?? '');
                    if ($path === '') {
                        continue;
                    }
                    $projectContextFilesUsed[] = $path;
                    $url = is_array($ver) ? (string)($ver['storage_url'] ?? '') : '';
                    if ($url !== '') {
                        $label = trim($displayName) !== '' ? $displayName : $path;
                        $parts[] = "ARQUIVO BASE DO PROJETO: {$label}\nURL: {$url}";
                    }
                }

                // Menções explícitas a arquivos no texto do usuário
                $baseFilesByPath = [];
                $baseFilesByNameLower = [];
                $baseNameToPaths = [];

                $normalizeForFileMatch = static function (string $s): string {
                    $s = mb_strtolower($s, 'UTF-8');
                    // remove tudo que não for letra/número e normaliza espaços (isso ignora emojis e pontuação)
                    $s = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $s);
                    $s = trim(preg_replace('/\s+/u', ' ', (string)$s));
                    return (string)$s;
                };

                $msgLowerForFiles = mb_strtolower((string)$message, 'UTF-8');
                $msgNormForFiles = $normalizeForFileMatch((string)$message);

                foreach ($baseFiles as $bfMeta) {
                    $p = trim((string)($bfMeta['path'] ?? ''));
                    $n = trim((string)($bfMeta['name'] ?? ''));
                    if ($p !== '') {
                        $baseFilesByPath[$p] = $bfMeta;
                    }
                    if ($n !== '') {
                        $baseFilesByNameLower[mb_strtolower($n, 'UTF-8')] = $bfMeta;
                        $base = $n;
                        $dotPos = strrpos($base, '.');
                        if ($dotPos !== false) {
                            $base = substr($base, 0, $dotPos);
                        }
                        $base = trim($base);
                        if ($base !== '') {
                            $key = mb_strtolower($base, 'UTF-8');
                            if (!isset($baseNameToPaths[$key])) {
                                $baseNameToPaths[$key] = [];
                            }
                            $baseNameToPaths[$key][] = $p;
                        }
                    }
                }

                $mentionedPaths = [];

                // 0) Match simples por substring do nome completo (funciona com espaços)
                // Ex: "Briefing Lumiclinic.pdf" ou "briefing lumiclinic"
                foreach ($baseFilesByNameLower as $nameLower => $bfMeta) {
                    $nameLower = trim((string)$nameLower);
                    if ($nameLower === '') {
                        continue;
                    }
                    if (mb_strlen($nameLower, 'UTF-8') < 3) {
                        continue;
                    }
                    if (strpos($msgLowerForFiles, $nameLower) !== false) {
                        $p = (string)($bfMeta['path'] ?? '');
                        if ($p !== '') {
                            $mentionedPaths[] = $p;
                        }
                        continue;
                    }

                    // fallback por nome normalizado (ignora emoji/pontuação)
                    $nameNorm = $normalizeForFileMatch((string)$nameLower);
                    if ($nameNorm !== '' && mb_strlen($nameNorm, 'UTF-8') >= 3 && $msgNormForFiles !== '' && strpos($msgNormForFiles, $nameNorm) !== false) {
                        $p = (string)($bfMeta['path'] ?? '');
                        if ($p !== '') {
                            $mentionedPaths[] = $p;
                        }
                    }
                }

                // 1) Detecta menções tipo "arquivo.ext" no texto
                if (preg_match_all('/\b([A-Za-z0-9_\-]{1,120}\.[A-Za-z0-9]{1,8})\b/u', $message, $mmNames)) {
                    $fileNames = array_values(array_unique(array_map('trim', $mmNames[1] ?? [])));
                    foreach ($fileNames as $fn) {
                        if ($fn === '') continue;
                        $lower = mb_strtolower($fn, 'UTF-8');
                        if (isset($baseFilesByNameLower[$lower])) {
                            $p = (string)($baseFilesByNameLower[$lower]['path'] ?? '');
                            if ($p !== '') {
                                $mentionedPaths[] = $p;
                                continue;
                            }
                        }

                        // fallback: tenta achar por sufixo do path (funciona mesmo se estiver em /base/...)
                        $suffix = '/' . ltrim($fn, '/');
                        $candidates = ProjectFile::searchByPathSuffix($projectId, $suffix, 10);
                        if (count($candidates) > 1) {
                            $lines = [];
                            $lines[] = 'Encontrei mais de um arquivo com esse nome. Qual deles você quer usar?';
                            foreach ($candidates as $c) {
                                $lines[] = '- ' . (string)($c['name'] ?? ($c['path'] ?? ''));
                            }
                            $assistantReply = implode("\n", $lines);
                            Message::create($conversation->id, 'assistant', $assistantReply, null);
                            if ($isAjax) {
                                header('Content-Type: application/json; charset=utf-8');
                                $nowLabel = date('d/m/Y H:i');
                                echo json_encode([
                                    'success' => true,
                                    'messages' => [
                                        ['role' => 'user', 'content' => $message, 'created_label' => $nowLabel],
                                        ['role' => 'assistant', 'content' => $assistantReply, 'tokens_used' => 0, 'created_label' => $nowLabel],
                                    ],
                                    'total_tokens_used' => 0,
                                ]);
                                exit;
                            }
                            header('Location: /chat');
                            exit;
                        }
                        if (count($candidates) === 1) {
                            $p = (string)($candidates[0]['path'] ?? '');
                            if ($p !== '') {
                                $mentionedPaths[] = $p;
                            }
                        }
                    }
                }

                // 2) Detecta menções só pelo nome (sem extensão), quando for único
                foreach ($baseNameToPaths as $baseLower => $pathsForBase) {
                    if (count($pathsForBase) !== 1) {
                        continue;
                    }
                    $base = (string)$baseLower;
                    if ($base === '') continue;
                    // Usa substring no lower para suportar nomes com espaços e evitar regex frágil
                    if (mb_strlen($base, 'UTF-8') >= 3 && strpos($msgLowerForFiles, $base) !== false) {
                        $mentionedPaths[] = (string)$pathsForBase[0];
                        continue;
                    }

                    // fallback por nome normalizado (ignora emoji/pontuação)
                    $baseNorm = $normalizeForFileMatch((string)$base);
                    if ($baseNorm !== '' && mb_strlen($baseNorm, 'UTF-8') >= 3 && $msgNormForFiles !== '' && strpos($msgNormForFiles, $baseNorm) !== false) {
                        $mentionedPaths[] = (string)$pathsForBase[0];
                    }
                }

                $mentionedPaths = array_values(array_unique(array_filter(array_map('trim', $mentionedPaths))));

                // Se a mensagem parece citar um arquivo, mas não foi encontrado nenhum match, retorna feedback amigável
                // (isso reduz casos onde o modelo diz "não tenho acesso ao arquivo" por falta de anexo)
                if (empty($mentionedPaths)) {
                    $maybeMentioned = false;
                    if (preg_match('/\b(pdf|docx?|xlsx?|pptx?)\b/i', (string)$message)) {
                        $maybeMentioned = true;
                    } elseif (strpos($msgLowerForFiles, 'arquivo') !== false || strpos($msgLowerForFiles, 'anexo') !== false) {
                        $maybeMentioned = true;
                    }

                    if ($maybeMentioned && !empty($baseFiles)) {
                        $names = [];
                        foreach ($baseFiles as $bfMeta) {
                            $nm = trim((string)($bfMeta['name'] ?? ''));
                            if ($nm !== '') {
                                $names[] = $nm;
                            }
                        }
                        $names = array_values(array_unique(array_filter($names)));
                        if (!empty($names)) {
                            $assistantReply = "Não consegui localizar qual arquivo do projeto você está mencionando.\n\n"
                                . "Tente mencionar o nome exatamente como aparece em *Arquivos* (incluindo a extensão). Exemplos disponíveis:\n- "
                                . implode("\n- ", array_slice($names, 0, 12));

                            Message::create($conversation->id, 'assistant', $assistantReply, null);
                            if ($isAjax) {
                                header('Content-Type: application/json; charset=utf-8');
                                $nowLabel = date('d/m/Y H:i');
                                echo json_encode([
                                    'success' => true,
                                    'messages' => [
                                        ['role' => 'user', 'content' => $message, 'created_label' => $nowLabel],
                                        ['role' => 'assistant', 'content' => $assistantReply, 'tokens_used' => 0, 'created_label' => $nowLabel],
                                    ],
                                    'total_tokens_used' => 0,
                                ]);
                                exit;
                            }
                            header('Location: /chat');
                            exit;
                        }
                    }
                }

                foreach ($mentionedPaths as $path) {
                    $file = $baseFilesByPath[$path] ?? ProjectFile::findByPath($projectId, $path);
                    if (!$file) {
                        continue;
                    }

                    $fid = (int)($file['id'] ?? 0);
                    $ver = $fid > 0 ? ProjectFileVersion::latestForFile($fid) : null;
                    $display = trim((string)($file['name'] ?? ''));
                    if ($display === '') {
                        $display = (string)($file['path'] ?? '');
                    }
                    if ($path !== '') {
                        $projectContextFilesUsed[] = $path;

                        $url = is_array($ver) ? (string)($ver['storage_url'] ?? '') : '';
                        if ($url !== '') {
                            $parts[] = "ARQUIVO CITADO PELO USUÁRIO: {$display}\nURL: {$url}";

                            $openAIFileId = is_array($ver) ? (string)($ver['openai_file_id'] ?? '') : '';
                            $versionId = is_array($ver) ? (int)($ver['id'] ?? 0) : 0;
                            $mime = trim((string)($file['mime_type'] ?? ''));
                            $name = trim((string)($file['name'] ?? ''));
                            if ($name === '') {
                                $name = trim((string)($file['path'] ?? ''));
                            }
                            if ($name === '') {
                                $name = 'arquivo';
                            }

                            // Bloqueio amigável para Office no OpenAI (também vale para arquivos mencionados do projeto)
                            $modelToUseHere = isset($_SESSION['chat_model']) && is_string($_SESSION['chat_model']) && $_SESSION['chat_model'] !== ''
                                ? (string)$_SESSION['chat_model']
                                : '';
                            $isOpenAIModelHere = !str_starts_with($modelToUseHere, 'claude-');

                            $extHere = '';
                            if (strpos($name, '.') !== false) {
                                $extHere = strtolower((string)pathinfo($name, PATHINFO_EXTENSION));
                            }

                            if ($isOpenAIModelHere) {
                                $isOfficeUnsupportedHere = in_array($extHere, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'], true)
                                    || in_array($mime, [
                                        'application/msword',
                                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                        'application/vnd.ms-excel',
                                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                        'application/vnd.ms-powerpoint',
                                        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                                    ], true);
                                if ($isOfficeUnsupportedHere) {
                                    $friendly = "O arquivo citado ({$name}) é do tipo Word/Excel/PowerPoint e não é suportado pelo modelo OpenAI neste chat. "
                                        . "Sugestões: (1) converta para PDF, TXT ou CSV; (2) selecione um modelo Claude.";
                                    if ($isAjax) {
                                        header('Content-Type: application/json; charset=utf-8');
                                        echo json_encode([
                                            'success' => false,
                                            'error' => $friendly,
                                        ]);
                                        exit;
                                    }
                                    $_SESSION['chat_error'] = $friendly;
                                    header('Location: /chat');
                                    exit;
                                }
                            }

                            $projectFileInputsForModel[] = [
                                'project_file_version_id' => $versionId,
                                'openai_file_id' => $openAIFileId,
                                'name' => $name,
                                'mime_type' => $mime,
                                'url' => $url,
                            ];
                        }
                    }
                }

                $projectContextFilesUsed = array_values(array_unique(array_filter($projectContextFilesUsed)));

                if (!empty($parts)) {
                    $projectContextMessage = "Contexto do projeto (use como fonte de verdade; não invente se estiver aqui):\n\n" . implode("\n\n---\n\n", $parts);
                }
            }

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

                // Plano free: quando acabar os tokens, sugere assinar um plano pago (com link clicável)
                if ($isFreePlan && $currentBalance <= 0) {
                    $assistantReply = 'Você está usando o plano Free e os seus tokens gratuitos chegaram ao fim. '
                        . 'Para continuar usando o Tuquinha com mais limite e recursos, é só assinar um plano pago.\n\n'
                        . 'Você pode clicar em **Planos e limites** no menu lateral ou acessar diretamente [a página de planos](/planos) para escolher o melhor plano para você.';

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

                // Planos pagos com limite mensal de tokens: sugerem compra de tokens extras (com link clicável)
                if ($planForContext && !$isFreePlan && isset($planForContext['monthly_token_limit']) && (int)$planForContext['monthly_token_limit'] > 0) {
                    if ($currentBalance <= 0) {
                        $assistantReply = 'Parece que o seu saldo de tokens deste plano chegou a zero. '
                            . 'Para continuar usando o Tuquinha sem interrupções, você pode comprar tokens extras.\n\n'
                            . 'Clique em **Planos e limites** no menu lateral ou vá direto para [comprar tokens extras](/tokens/comprar) e adicionar mais tokens ao seu saldo.';

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
            $historyForEngine = $history;
            if (is_string($projectContextMessage) && $projectContextMessage !== '') {
                array_unshift($historyForEngine, [
                    'role' => 'user',
                    'content' => $projectContextMessage,
                ]);
            }

            $existingAttachments = Attachment::allByConversation((int)$conversation->id);
            $persistentInputsById = [];
            foreach ($existingAttachments as $a) {
                $type = (string)($a['type'] ?? '');
                if ($type === 'audio') {
                    continue;
                }
                $aid = (int)($a['id'] ?? 0);
                if ($aid <= 0) {
                    continue;
                }
                $persistentInputsById[$aid] = [
                    'attachment_id' => $aid,
                    'openai_file_id' => (string)($a['openai_file_id'] ?? ''),
                    'name' => (string)($a['original_name'] ?? ''),
                    'mime_type' => (string)($a['mime_type'] ?? ''),
                    'url' => (string)($a['path'] ?? ''),
                ];
            }
            foreach ($fileInputsForModel as $fi) {
                $aid = isset($fi['attachment_id']) ? (int)$fi['attachment_id'] : 0;
                if ($aid > 0) {
                    $persistentInputsById[$aid] = array_merge($persistentInputsById[$aid] ?? [], $fi);
                }
            }

            $persistentProjectInputsByVersionId = [];
            foreach ($projectFileInputsForModel as $fi) {
                $vid = isset($fi['project_file_version_id']) ? (int)$fi['project_file_version_id'] : 0;
                if ($vid > 0) {
                    $persistentProjectInputsByVersionId[$vid] = $fi;
                }
            }

            $persistentFileInputs = array_values($persistentInputsById);
            if (!empty($persistentProjectInputsByVersionId)) {
                $persistentFileInputs = array_merge($persistentFileInputs, array_values($persistentProjectInputsByVersionId));
            }

            $hasPdf = false;
            foreach ($persistentFileInputs as $fi) {
                $mt = isset($fi['mime_type']) ? (string)$fi['mime_type'] : '';
                if ($mt === 'application/pdf') {
                    $hasPdf = true;
                    break;
                }
            }

            $needsRewrite = false;
            if ($hasPdf) {
                $msgLower = mb_strtolower((string)$message, 'UTF-8');
                if (
                    (strpos($msgLower, 'transcrev') !== false || strpos($msgLower, 'transcrib') !== false || strpos($msgLower, 'transcri') !== false)
                    && (strpos($msgLower, 'todo') !== false || strpos($msgLower, 'inteiro') !== false || strpos($msgLower, 'completo') !== false)
                ) {
                    $needsRewrite = true;
                }
            }

            if ($needsRewrite) {
                for ($i = count($historyForEngine) - 1; $i >= 0; $i--) {
                    if (($historyForEngine[$i]['role'] ?? '') === 'user') {
                        $historyForEngine[$i]['content'] = "Analise o PDF anexado e transforme o conteúdo em PERGUNTAS E RESPOSTAS (Q&A) com linguagem clara. "
                            . "Regras: (1) NÃO transcreva o documento inteiro nem reproduza trechos longos literalmente; "
                            . "(2) faça um resumo fiel e estruture em perguntas curtas; "
                            . "(3) em cada resposta, use apenas informações presentes no PDF; "
                            . "(4) se faltar contexto, marque como 'não informado no documento'. "
                            . "Formatação: coloque cada pergunta em itálico e a resposta logo abaixo. "
                            . "Separe cada bloco de pergunta/resposta com uma linha contendo apenas: ---\n\n"
                            . "PEDIDO ORIGINAL DO USUÁRIO (para intenção):\n" . (string)$message;
                        break;
                    }
                }
            }

            $result = $engine->generateResponseWithContext(
                $historyForEngine,
                $_SESSION['chat_model'] ?? null,
                $userData,
                $conversationSettings,
                $personaData,
                !empty($persistentFileInputs) ? $persistentFileInputs : null
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

    public function projectFiles(): void
    {
        $sessionId = session_id();
        $userId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
        $conversationId = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;

        if ($conversationId <= 0) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false]);
            return;
        }

        if ($userId > 0) {
            $row = Conversation::findByIdForUser($conversationId, $userId);
        } else {
            $row = Conversation::findByIdAndSession($conversationId, $sessionId);
        }

        if (!$row) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false]);
            return;
        }

        $projectId = isset($row['project_id']) ? (int)$row['project_id'] : 0;
        if ($projectId <= 0) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'files' => []]);
            return;
        }

        if ($userId <= 0 || !ProjectMember::canRead($projectId, $userId)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false]);
            return;
        }

        $files = ProjectFile::allBaseFiles($projectId);
        $out = [];
        foreach ($files as $f) {
            $path = trim((string)($f['path'] ?? ''));
            if ($path === '') {
                continue;
            }
            $out[] = [
                'path' => $path,
                'name' => (string)($f['name'] ?? ''),
            ];
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'files' => $out]);
    }
}
