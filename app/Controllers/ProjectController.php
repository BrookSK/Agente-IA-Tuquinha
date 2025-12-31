<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectFile;
use App\Models\ProjectFileVersion;
use App\Models\ProjectFolder;
use App\Models\Conversation;
use App\Models\Subscription;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\Personality;
use App\Models\ProjectFavorite;
use App\Models\ProjectInvitation;
use App\Models\ProjectMemoryItem;
use App\Services\MailService;
use App\Services\MediaStorageService;
use App\Services\TextExtractionService;

class ProjectController extends Controller
{
    private function wantsJson(): bool
    {
        $accept = (string)($_SERVER['HTTP_ACCEPT'] ?? '');
        if ($accept !== '' && stripos($accept, 'application/json') !== false) {
            return true;
        }

        $xrw = (string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
        if ($xrw !== '' && strtolower($xrw) === 'xmlhttprequest') {
            return true;
        }

        return false;
    }

    private function requireLogin(): array
    {
        if (empty($_SESSION['user_id'])) {
            if ($this->wantsJson()) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'error' => 'Não autenticado.']);
                exit;
            }
            header('Location: /login');
            exit;
        }

        $user = User::findById((int)$_SESSION['user_id']);
        if (!$user) {
            unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email']);
            if ($this->wantsJson()) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'error' => 'Sessão inválida.']);
                exit;
            }
            header('Location: /login');
            exit;
        }

        return $user;
    }

    private function getActivePlanForEmail(string $email): ?array
    {
        $email = trim($email);
        if ($email === '') {
            return null;
        }

        $subscription = Subscription::findLastByEmail($email);
        if (!$subscription || empty($subscription['plan_id'])) {
            return null;
        }

        $status = strtolower((string)($subscription['status'] ?? ''));
        $isActive = !in_array($status, ['canceled', 'expired'], true);
        if (!$isActive) {
            return null;
        }

        $plan = Plan::findById((int)$subscription['plan_id']);
        return $plan ?: null;
    }

    private function requireProjectPermission(array $user, string $permission): void
    {
        if (!empty($_SESSION['is_admin'])) {
            return;
        }

        // Acesso (listar/ver) não deve exigir plano. Mantemos a exigência de plano
        // apenas para ações que geram custo/uso intensivo (criar/editar/compartilhar).
        if ($permission === 'access') {
            return;
        }

        $email = (string)($user['email'] ?? '');
        $plan = $this->getActivePlanForEmail($email);
        if (!$plan) {
            if ($this->wantsJson()) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'error' => 'Sem plano ativo.']);
                exit;
            }
            header('Location: /planos');
            exit;
        }

        if (empty($plan['allow_projects_access'])) {
            if ($this->wantsJson()) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'error' => 'Seu plano não permite acesso a projetos.']);
                exit;
            }
            header('Location: /planos');
            exit;
        }

        // Se o plano libera acesso a projetos, ele libera tudo (criar/editar/compartilhar).
        // Mantemos as flags allow_projects_* no banco por compatibilidade, mas a regra é unificada.
    }

    private function extractTextFromFile(string $tmpPath, string $mime, string $fileName): ?string
    {
        if ($tmpPath === '' || !is_file($tmpPath)) {
            return null;
        }

        $mime = trim($mime);
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $isTextLike = false;
        if ($mime !== '' && (str_starts_with($mime, 'text/') || $mime === 'application/json')) {
            $isTextLike = true;
        }
        if (in_array($ext, ['txt','md','json','php','js','ts','tsx','jsx','html','css','scss','py','java','go','rb','sh','yml','yaml','xml','sql'], true)) {
            $isTextLike = true;
        }

        if ($isTextLike) {
            $content = @file_get_contents($tmpPath);
            if (is_string($content)) {
                if (mb_strlen($content, 'UTF-8') > 200000) {
                    $content = mb_substr($content, 0, 200000, 'UTF-8');
                }
                return $content;
            }
            return null;
        }

        if ($ext === 'docx') {
            if (!class_exists('ZipArchive')) {
                return null;
            }
            $zip = new \ZipArchive();
            if ($zip->open($tmpPath) !== true) {
                return null;
            }
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();
            if (!is_string($xml) || $xml === '') {
                return null;
            }
            $xml = preg_replace('/<w:tab\b[^>]*\/>/i', "\t", $xml);
            $xml = preg_replace('/<w:br\b[^>]*\/>/i', "\n", $xml);
            $xml = preg_replace('/<w:p\b[^>]*>/i', "\n", $xml);
            $text = strip_tags($xml);
            $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
            $text = preg_replace('/\n{3,}/', "\n\n", $text);
            $text = trim($text);
            if ($text === '') {
                return null;
            }
            if (mb_strlen($text, 'UTF-8') > 200000) {
                $text = mb_substr($text, 0, 200000, 'UTF-8');
            }
            return $text;
        }

        if ($ext === 'pdf' || $mime === 'application/pdf') {
            // Tentativa best-effort: usa pdftotext se estiver disponível no ambiente
            $outTxt = tempnam(sys_get_temp_dir(), 'pdf_txt_');
            if (!is_string($outTxt) || $outTxt === '') {
                return null;
            }
            $cmd = 'pdftotext -layout ' . escapeshellarg($tmpPath) . ' ' . escapeshellarg($outTxt);
            $ok = false;
            try {
                @shell_exec($cmd . ' 2>&1');
                $ok = is_file($outTxt) && filesize($outTxt) > 0;
            } catch (\Throwable $e) {
                $ok = false;
            }

            if (!$ok) {
                @unlink($outTxt);
                return null;
            }

            $text = @file_get_contents($outTxt);
            @unlink($outTxt);
            if (!is_string($text)) {
                return null;
            }
            $text = trim($text);
            if ($text === '') {
                return null;
            }
            if (mb_strlen($text, 'UTF-8') > 200000) {
                $text = mb_substr($text, 0, 200000, 'UTF-8');
            }
            return $text;
        }

        return null;
    }

    public function index(): void
    {
        $user = $this->requireLogin();
        $this->requireProjectPermission($user, 'access');
        $onlyFavorites = isset($_GET['favoritos']) && (string)$_GET['favoritos'] === '1';
        $projects = Project::allForUserWithFavorites((int)$user['id'], $onlyFavorites);

        $this->view('projects/index', [
            'pageTitle' => 'Projetos - Tuquinha',
            'user' => $user,
            'projects' => $projects,
            'onlyFavorites' => $onlyFavorites,
        ]);
    }

    public function createForm(): void
    {
        $user = $this->requireLogin();
        $this->requireProjectPermission($user, 'create');

        $this->view('projects/new', [
            'pageTitle' => 'Novo projeto - Tuquinha',
            'user' => $user,
            'error' => null,
        ]);
    }

    public function create(): void
    {
        $user = $this->requireLogin();
        $this->requireProjectPermission($user, 'create');

        $name = trim((string)($_POST['name'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));

        if ($name === '') {
            $this->view('projects/new', [
                'pageTitle' => 'Novo projeto - Tuquinha',
                'user' => $user,
                'error' => 'Informe o nome do projeto.',
            ]);
            return;
        }

        $projectId = Project::create((int)$user['id'], $name, $description !== '' ? $description : null);

        ProjectMember::addOrUpdate($projectId, (int)$user['id'], 'admin');
        ProjectFolder::ensureDefaultTree($projectId);

        header('Location: /projetos/ver?id=' . $projectId);
        exit;
    }

    public function show(): void
    {
        $user = $this->requireLogin();
        $this->requireProjectPermission($user, 'access');
        $projectId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if ($projectId <= 0 || !ProjectMember::canRead($projectId, (int)$user['id'])) {
            header('Location: /projetos');
            exit;
        }

        $project = Project::findById($projectId);
        if (!$project) {
            header('Location: /projetos');
            exit;
        }

        $baseFiles = ProjectFile::allBaseFiles($projectId);
        $baseFileIds = array_map(static function ($f) {
            return (int)($f['id'] ?? 0);
        }, $baseFiles);
        $latestByFileId = ProjectFileVersion::latestForFiles($baseFileIds);

        $conversations = Conversation::allByProjectForUser($projectId, (int)$user['id']);
        $isFavorite = ProjectFavorite::isFavorite($projectId, (int)$user['id']);

        $canAdmin = ProjectMember::canAdmin($projectId, (int)$user['id']);

        $members = [];
        $pendingInvites = [];
        $projectMemoryItems = [];
        if ($canAdmin) {
            $members = ProjectMember::allWithUsers($projectId);
            $pendingInvites = ProjectInvitation::allPendingForProject($projectId);
            $projectMemoryItems = ProjectMemoryItem::allActiveForProject($projectId, 200);
        }

        $planAllowsPersonalities = false;
        $personalities = [];
        if (!empty($_SESSION['is_admin'])) {
            $planAllowsPersonalities = true;
        } else {
            $email = (string)($user['email'] ?? '');
            $plan = $this->getActivePlanForEmail($email);
            $planAllowsPersonalities = !empty($plan['allow_personalities']);
        }
        if ($planAllowsPersonalities) {
            $personalities = Personality::allActive();
        }

        $this->view('projects/show', [
            'pageTitle' => ($project['name'] ?? 'Projeto') . ' - Tuquinha',
            'user' => $user,
            'project' => $project,
            'baseFiles' => $baseFiles,
            'latestByFileId' => $latestByFileId,
            'conversations' => $conversations,
            'isFavorite' => $isFavorite,
            'canAdmin' => $canAdmin,
            'members' => $members,
            'pendingInvites' => $pendingInvites,
            'projectMemoryItems' => $projectMemoryItems,
            'planAllowsPersonalities' => $planAllowsPersonalities,
            'personalities' => $personalities,
            'projectInstructions' => (string)($project['instructions'] ?? ''),
            'uploadError' => $_SESSION['project_upload_error'] ?? null,
            'uploadOk' => $_SESSION['project_upload_ok'] ?? null,
        ]);

        unset($_SESSION['project_upload_error'], $_SESSION['project_upload_ok']);
    }

    public function saveInstructions(): void
    {
        $user = $this->requireLogin();
        $this->requireProjectPermission($user, 'edit');
        $userId = (int)($user['id'] ?? 0);

        $projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
        $instructions = (string)($_POST['instructions'] ?? '');

        if ($projectId <= 0 || !ProjectMember::canAdmin($projectId, $userId)) {
            header('Location: /projetos');
            exit;
        }

        $instructions = trim(str_replace(["\r\n", "\r"], "\n", $instructions));
        if ($instructions === '') {
            $instructions = null;
        }

        Project::updateInstructions($projectId, $instructions);
        header('Location: /projetos/ver?id=' . $projectId);
        exit;
    }

    public function removeBaseFile(): void
    {
        $user = $this->requireLogin();
        $this->requireProjectPermission($user, 'edit');
        $userId = (int)($user['id'] ?? 0);

        $projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
        $fileId = isset($_POST['file_id']) ? (int)$_POST['file_id'] : 0;

        if ($projectId <= 0 || $fileId <= 0 || !ProjectMember::canAdmin($projectId, $userId)) {
            header('Location: /projetos');
            exit;
        }

        $project = Project::findById($projectId);
        if (!$project) {
            header('Location: /projetos');
            exit;
        }

        $file = ProjectFile::findById($fileId);
        if (!$file || (int)($file['project_id'] ?? 0) !== $projectId || empty($file['is_base'])) {
            $_SESSION['project_upload_error'] = 'Arquivo inválido.';
            header('Location: /projetos/ver?id=' . $projectId);
            exit;
        }

        ProjectFileVersion::deleteAllForFile($fileId);
        ProjectFile::softDelete($fileId);

        $_SESSION['project_upload_ok'] = 'Arquivo removido com sucesso.';
        header('Location: /projetos/ver?id=' . $projectId);
        exit;
    }

    public function openBaseFile(): void
    {
        $user = $this->requireLogin();
        $this->requireProjectPermission($user, 'access');
        $userId = (int)($user['id'] ?? 0);

        $projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
        $fileId = isset($_GET['file_id']) ? (int)$_GET['file_id'] : 0;

        if ($projectId <= 0 || $fileId <= 0 || !ProjectMember::canRead($projectId, $userId)) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo "Arquivo não encontrado.";
            return;
        }

        $file = ProjectFile::findById($fileId);
        if (!$file || (int)($file['project_id'] ?? 0) !== $projectId || empty($file['is_base'])) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo "Arquivo não encontrado.";
            return;
        }

        $ver = ProjectFileVersion::latestForFile($fileId);
        $raw = is_array($ver) ? (string)($ver['extracted_text'] ?? '') : '';
        $raw = str_replace(["\r\n", "\r"], "\n", $raw);

        $name = trim((string)($file['name'] ?? ''));
        if ($name === '') {
            $name = trim((string)($file['path'] ?? ''));
        }
        if ($name === '') {
            $name = 'arquivo.txt';
        }

        // Garante UTF-8 (muitos .txt sobem como Windows-1252/ISO-8859-1)
        $content = $raw;
        if ($content !== '') {
            $looksUtf8 = function_exists('mb_check_encoding') ? mb_check_encoding($content, 'UTF-8') : true;
            if (!$looksUtf8) {
                $converted = null;
                if (function_exists('mb_convert_encoding')) {
                    $converted = @mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
                    if (!is_string($converted) || $converted === '' || (function_exists('mb_check_encoding') && !mb_check_encoding($converted, 'UTF-8'))) {
                        $converted = @mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1');
                    }
                }
                if (is_string($converted) && $converted !== '') {
                    $content = $converted;
                }
            }
        }

        header('Content-Type: text/plain; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: inline; filename="' . str_replace('"', '', basename($name)) . '"');
        echo $content !== '' ? $content : "(Sem texto extraído para este arquivo.)";
    }

    public function updateMemoryItem(): void
    {
        $user = $this->requireLogin();
        $this->requireProjectPermission($user, 'edit');
        $userId = (int)($user['id'] ?? 0);

        $projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
        $itemId = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
        $content = (string)($_POST['content'] ?? '');

        if ($projectId <= 0 || $itemId <= 0 || !ProjectMember::canAdmin($projectId, $userId)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false]);
            return;
        }

        $content = trim(str_replace(["\r\n", "\r"], "\n", $content));
        if ($content === '') {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Conteúdo vazio.']);
            return;
        }

        ProjectMemoryItem::updateContent($itemId, $projectId, $content);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true]);
    }

    public function deleteMemoryItem(): void
    {
        $user = $this->requireLogin();
        $this->requireProjectPermission($user, 'edit');
        $userId = (int)($user['id'] ?? 0);

        $projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
        $itemId = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;

        if ($projectId <= 0 || $itemId <= 0 || !ProjectMember::canAdmin($projectId, $userId)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false]);
            return;
        }

        ProjectMemoryItem::softDelete($itemId, $projectId);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true]);
    }

    public function inviteCollaborator(): void
    {
        $user = $this->requireLogin();
        $this->requireProjectPermission($user, 'share');
        $userId = (int)($user['id'] ?? 0);

        $projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
        $email = trim((string)($_POST['email'] ?? ''));
        $role = trim((string)($_POST['role'] ?? 'read'));

        if ($projectId <= 0 || !ProjectMember::canAdmin($projectId, $userId)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false]);
            return;
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Informe um e-mail válido.']);
            return;
        }

        if (strcasecmp($email, (string)($user['email'] ?? '')) === 0) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Você já é membro deste projeto.']);
            return;
        }

        $invitedUser = User::findByEmail($email);
        if (!$invitedUser) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Este e-mail não tem conta no Tuquinha.']);
            return;
        }

        $invitedEmail = (string)($invitedUser['email'] ?? $email);

        $invitedUserId = (int)($invitedUser['id'] ?? 0);
        if ($invitedUserId > 0 && ProjectMember::canRead($projectId, $invitedUserId)) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Este usuário já tem acesso ao projeto.']);
            return;
        }

        if (ProjectInvitation::hasValidInviteForEmail($projectId, $invitedEmail)) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Já existe um convite pendente para este e-mail.']);
            return;
        }

        $role = in_array($role, ['read', 'write', 'admin'], true) ? $role : 'read';
        $token = bin2hex(random_bytes(16));
        ProjectInvitation::create($projectId, $userId, $invitedEmail, null, $role, $token);

        $project = Project::findById($projectId);
        $projectName = $project ? (string)($project['name'] ?? 'Projeto') : 'Projeto';

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $link = $scheme . $host . '/projetos/aceitar-convite?token=' . urlencode($token);

        $subject = 'Convite para colaborar no projeto "' . $projectName . '"';
        $toName = trim((string)($invitedUser['preferred_name'] ?? ''));
        if ($toName === '') {
            $toName = trim((string)($invitedUser['name'] ?? ''));
        }
        if ($toName === '') {
            $toName = $invitedEmail;
        }

        $safeProjectName = htmlspecialchars($projectName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $roleLabel = 'Leitura';
        if ($role === 'write') { $roleLabel = 'Escrita'; }
        if ($role === 'admin') { $roleLabel = 'Administrador'; }
        $safeRole = htmlspecialchars($roleLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $contentHtml = '<p style="font-size:14px; margin:0 0 10px 0;">Você foi convidado para colaborar no projeto <strong>' . $safeProjectName . '</strong> no Tuquinha.</p>'
            . '<p style="font-size:14px; margin:0 0 10px 0;">Permissão: <strong>' . $safeRole . '</strong></p>'
            . '<p style="font-size:12px; color:#777; margin:10px 0 0 0;">Se você não reconhece este convite, pode ignorar este e-mail.</p>';

        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = $scheme . $host;
        $logoUrl = $baseUrl . '/public/favicon.png';

        $body = MailService::buildDefaultTemplate(
            $toName,
            $contentHtml,
            'Aceitar convite',
            $link,
            $logoUrl
        );

        $sentToInvitee = MailService::send($invitedEmail, $toName, $subject, $body);

        $ownerEmail = (string)($user['email'] ?? '');
        if ($ownerEmail !== '') {
            $ownerName = trim((string)($user['preferred_name'] ?? ''));
            if ($ownerName === '') {
                $ownerName = trim((string)($user['name'] ?? ''));
            }
            if ($ownerName === '') {
                $ownerName = $ownerEmail;
            }
            $ownerSubject = 'Convite enviado: ' . $invitedEmail;
            $safeInvitedEmail = htmlspecialchars($invitedEmail, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $ownerContentHtml = '<p style="font-size:14px; margin:0 0 10px 0;">Você convidou <strong>' . $safeInvitedEmail . '</strong> para o projeto <strong>'
                . $safeProjectName . '</strong> com permissão <strong>' . $safeRole . '</strong>.</p>'
                . '<p style="font-size:13px; margin:0;">O aceite vai aparecer assim que a pessoa clicar no link do convite.</p>';

            $ownerBody = MailService::buildDefaultTemplate(
                $ownerName,
                $ownerContentHtml,
                'Ver link do convite',
                $link,
                $logoUrl
            );
            MailService::send($ownerEmail, $ownerName, $ownerSubject, $ownerBody);
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true, 'email_sent' => $sentToInvitee]);
    }

    public function acceptInvite(): void
    {
        $user = $this->requireLogin();
        $this->requireProjectPermission($user, 'access');
        $userId = (int)($user['id'] ?? 0);

        $token = trim((string)($_GET['token'] ?? ''));
        if ($token === '') {
            header('Location: /projetos');
            exit;
        }

        $invite = ProjectInvitation::findByToken($token);
        if (!$invite || ($invite['status'] ?? '') !== 'pending') {
            $_SESSION['project_upload_error'] = 'Convite não encontrado ou já utilizado.';
            header('Location: /projetos');
            exit;
        }

        $invitedEmail = trim((string)($invite['invited_email'] ?? ''));
        $userEmail = trim((string)($user['email'] ?? ''));
        if ($invitedEmail !== '' && $userEmail !== '' && strcasecmp($invitedEmail, $userEmail) !== 0) {
            $_SESSION['project_upload_error'] = 'Este convite foi enviado para outro e-mail.';
            header('Location: /projetos');
            exit;
        }

        $projectId = (int)($invite['project_id'] ?? 0);
        $role = (string)($invite['role'] ?? 'read');

        if ($projectId <= 0) {
            $_SESSION['project_upload_error'] = 'Projeto do convite não encontrado.';
            header('Location: /projetos');
            exit;
        }

        ProjectMember::addOrUpdate($projectId, $userId, $role);
        ProjectInvitation::markAccepted((int)$invite['id']);

        $_SESSION['project_upload_ok'] = 'Convite aceito. Você agora tem acesso a este projeto.';
        header('Location: /projetos/ver?id=' . $projectId);
        exit;
    }

    public function revokeInvite(): void
    {
        $user = $this->requireLogin();
        $this->requireProjectPermission($user, 'share');
        $userId = (int)($user['id'] ?? 0);

        $projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
        $inviteId = isset($_POST['invite_id']) ? (int)$_POST['invite_id'] : 0;
        if ($projectId <= 0 || $inviteId <= 0 || !ProjectMember::canAdmin($projectId, $userId)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false]);
            return;
        }

        ProjectInvitation::cancelById($inviteId);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true]);
    }

    public function updateMemberRole(): void
    {
        $user = $this->requireLogin();
        $this->requireProjectPermission($user, 'share');
        $userId = (int)($user['id'] ?? 0);

        $projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
        $memberUserId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        $role = trim((string)($_POST['role'] ?? 'read'));

        if ($projectId <= 0 || $memberUserId <= 0 || !ProjectMember::canAdmin($projectId, $userId)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false]);
            return;
        }

        $project = Project::findById($projectId);
        if ($project && (int)($project['owner_user_id'] ?? 0) === $memberUserId) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Não é possível alterar o dono do projeto.']);
            return;
        }

        ProjectMember::updateRole($projectId, $memberUserId, $role);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true]);
    }

    public function removeMember(): void
    {
        $user = $this->requireLogin();
        $this->requireProjectPermission($user, 'share');
        $userId = (int)($user['id'] ?? 0);

        $projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
        $memberUserId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

        if ($projectId <= 0 || $memberUserId <= 0 || !ProjectMember::canAdmin($projectId, $userId)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false]);
            return;
        }

        $project = Project::findById($projectId);
        if ($project && (int)($project['owner_user_id'] ?? 0) === $memberUserId) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Não é possível remover o dono do projeto.']);
            return;
        }

        ProjectMember::remove($projectId, $memberUserId);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true]);
    }

    public function saveMemory(): void
    {
        $user = $this->requireLogin();
        $this->requireProjectPermission($user, 'edit');
        $userId = (int)($user['id'] ?? 0);

        $projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
        $memory = (string)($_POST['memory'] ?? '');
        $memory = str_replace(["\r\n", "\r"], "\n", $memory);
        $memory = trim($memory);

        if ($projectId <= 0 || !ProjectMember::canWrite($projectId, $userId)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false]);
            return;
        }

        if ($memory === '') {
            $memory = '';
        }
        if (mb_strlen($memory, 'UTF-8') > 200000) {
            $memory = mb_substr($memory, 0, 200000, 'UTF-8');
        }

        Project::updateDescription($projectId, $memory !== '' ? $memory : null);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => true,
            'memory' => $memory,
        ]);
    }

    public function createChat(): void
    {
        $user = $this->requireLogin();
        $this->requireProjectPermission($user, 'access');
        $userId = (int)($user['id'] ?? 0);

        $projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
        $message = (string)($_POST['message'] ?? '');
        $message = str_replace(["\r\n", "\r"], "\n", $message);
        $message = preg_replace('/^\s+/mu', '', $message);
        $message = trim($message);

        if ($projectId <= 0 || !ProjectMember::canRead($projectId, $userId)) {
            header('Location: /projetos');
            exit;
        }

        if ($message === '') {
            $_SESSION['project_upload_error'] = 'Digite uma mensagem para iniciar o chat.';
            header('Location: /projetos/ver?id=' . $projectId);
            exit;
        }

        $personaId = null;
        $personaIdRaw = isset($_POST['persona_id']) ? (int)$_POST['persona_id'] : 0;
        if ($personaIdRaw > 0) {
            $planAllowsPersonalities = false;
            if (!empty($_SESSION['is_admin'])) {
                $planAllowsPersonalities = true;
            } else {
                $email = (string)($user['email'] ?? '');
                $plan = $this->getActivePlanForEmail($email);
                $planAllowsPersonalities = !empty($plan['allow_personalities']);
            }

            if ($planAllowsPersonalities) {
                $p = Personality::findById($personaIdRaw);
                if ($p && !empty($p['active'])) {
                    $personaId = (int)$p['id'];
                }
            }
        }

        $sessionId = session_id();
        $conversation = Conversation::createForUser($userId, $sessionId, $personaId, $projectId);
        $_SESSION['current_conversation_id'] = $conversation->id;
        $_SESSION['draft_message'] = $message;

        header('Location: /chat?c=' . $conversation->id . '&autosend=1');
        exit;
    }

    public function toggleFavorite(): void
    {
        $user = $this->requireLogin();
        $this->requireProjectPermission($user, 'access');
        $userId = (int)($user['id'] ?? 0);

        $projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
        if ($projectId <= 0 || !ProjectMember::canRead($projectId, $userId)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false]);
            return;
        }

        $fav = ProjectFavorite::toggle($projectId, $userId);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true, 'favorite' => $fav]);
    }

    public function rename(): void
    {
        $user = $this->requireLogin();
        $this->requireProjectPermission($user, 'edit');
        $userId = (int)($user['id'] ?? 0);

        $projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
        $name = trim((string)($_POST['name'] ?? ''));

        if ($projectId <= 0 || !ProjectMember::canAdmin($projectId, $userId) || $name === '') {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false]);
            return;
        }

        Project::updateName($projectId, $name);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true, 'name' => $name]);
    }

    public function delete(): void
    {
        $user = $this->requireLogin();
        $this->requireProjectPermission($user, 'edit');
        $userId = (int)($user['id'] ?? 0);

        $projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
        if ($projectId <= 0 || !ProjectMember::canAdmin($projectId, $userId)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false]);
            return;
        }

        Project::deleteProject($projectId);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true]);
    }

    public function uploadBaseFile(): void
    {
        $user = $this->requireLogin();
        $this->requireProjectPermission($user, 'edit');
        $userId = (int)($user['id'] ?? 0);

        $projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
        $folderPath = '/base';

        if ($projectId <= 0 || !ProjectMember::canWrite($projectId, $userId)) {
            header('Location: /projetos');
            exit;
        }

        $project = Project::findById($projectId);
        if (!$project) {
            header('Location: /projetos');
            exit;
        }

        ProjectFolder::ensureDefaultTree($projectId);
        $folder = ProjectFolder::findByPath($projectId, $folderPath);
        if (!$folder) {
            $_SESSION['project_upload_error'] = 'Pasta inválida.';
            header('Location: /projetos/ver?id=' . $projectId);
            exit;
        }

        if (empty($_FILES['file']) || !is_array($_FILES['file'])) {
            $_SESSION['project_upload_error'] = 'Selecione um arquivo.';
            header('Location: /projetos/ver?id=' . $projectId);
            exit;
        }

        $err = (int)($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err !== UPLOAD_ERR_OK) {
            $_SESSION['project_upload_error'] = 'Erro ao enviar arquivo.';
            header('Location: /projetos/ver?id=' . $projectId);
            exit;
        }

        $tmp = (string)($_FILES['file']['tmp_name'] ?? '');
        $originalName = trim((string)($_FILES['file']['name'] ?? ''));
        $mime = trim((string)($_FILES['file']['type'] ?? ''));
        $size = isset($_FILES['file']['size']) ? (int)$_FILES['file']['size'] : null;

        if ($tmp === '' || !is_file($tmp)) {
            $_SESSION['project_upload_error'] = 'Arquivo inválido.';
            header('Location: /projetos/ver?id=' . $projectId);
            exit;
        }
        if ($originalName === '') {
            $originalName = basename($tmp);
        }

        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $needsExtractor = in_array($ext, ['doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx'], true);
        if ($needsExtractor) {
            $endpoint = trim((string)Setting::get('text_extraction_endpoint', ''));
            if ($endpoint === '') {
                $_SESSION['project_upload_error'] = 'Para usar Word/Excel/PowerPoint como conteúdo base, converta para PDF/TXT/CSV ou copie o texto e cole no campo de texto ("Salvar texto como arquivo base").';
                header('Location: /projetos/ver?id=' . $projectId);
                exit;
            }
        }

        $remoteUrl = MediaStorageService::uploadFile($tmp, $originalName, $mime);
        if ($remoteUrl === null) {
            $_SESSION['project_upload_error'] = 'Não foi possível salvar o arquivo no storage.';
            header('Location: /projetos/ver?id=' . $projectId);
            exit;
        }

        $safeFileName = str_replace('\\', '/', $originalName);
        $safeFileName = basename($safeFileName);

        $fullPath = rtrim($folderPath, '/') . '/' . $safeFileName;
        if ($fullPath === '') {
            $fullPath = '/' . $safeFileName;
        }

        $sha256 = null;
        try {
            $sha256 = is_readable($tmp) ? hash_file('sha256', $tmp) : null;
        } catch (\Throwable $e) {
            $sha256 = null;
        }

        $extractedText = $this->extractTextFromFile($tmp, $mime, $safeFileName);
        if ($extractedText === null) {
            $extractedText = TextExtractionService::extractFromFile($tmp, $safeFileName, $mime);
        }

        $existing = ProjectFile::findByPath($projectId, $fullPath);
        if ($existing) {
            $projectFileId = (int)$existing['id'];
        } else {
            $projectFileId = ProjectFile::create(
                $projectId,
                isset($folder['id']) ? (int)$folder['id'] : null,
                $safeFileName,
                $fullPath,
                $mime !== '' ? $mime : null,
                true,
                $userId > 0 ? $userId : null
            );
        }

        ProjectFileVersion::createNewVersion(
            $projectFileId,
            $remoteUrl,
            $size,
            $sha256,
            $extractedText,
            $userId > 0 ? $userId : null
        );

        $_SESSION['project_upload_ok'] = 'Arquivo base enviado com sucesso.';
        header('Location: /projetos/ver?id=' . $projectId);
        exit;
    }

    public function createBaseText(): void
    {
        $user = $this->requireLogin();
        $this->requireProjectPermission($user, 'edit');
        $userId = (int)($user['id'] ?? 0);

        $projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
        $folderPath = '/base';
        $fileName = trim((string)($_POST['file_name'] ?? ''));
        $content = (string)($_POST['content'] ?? '');
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        if ($projectId <= 0 || !ProjectMember::canWrite($projectId, $userId)) {
            header('Location: /projetos');
            exit;
        }

        ProjectFolder::ensureDefaultTree($projectId);
        $folder = ProjectFolder::findByPath($projectId, $folderPath);
        if (!$folder) {
            $_SESSION['project_upload_error'] = 'Pasta inválida.';
            header('Location: /projetos/ver?id=' . $projectId);
            exit;
        }

        if ($fileName === '') {
            $_SESSION['project_upload_error'] = 'Informe o nome do arquivo.';
            header('Location: /projetos/ver?id=' . $projectId);
            exit;
        }

        $fileName = str_replace('\\', '/', $fileName);
        $fileName = basename($fileName);
        if (!preg_match('/\.[A-Za-z0-9]{1,8}$/', $fileName)) {
            $fileName .= '.txt';
        }

        if (trim($content) === '') {
            $_SESSION['project_upload_error'] = 'O texto não pode ficar vazio.';
            header('Location: /projetos/ver?id=' . $projectId);
            exit;
        }

        if (mb_strlen($content, 'UTF-8') > 200000) {
            $content = mb_substr($content, 0, 200000, 'UTF-8');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'proj_txt_');
        if (!is_string($tmp) || $tmp === '') {
            $_SESSION['project_upload_error'] = 'Falha ao preparar arquivo temporário.';
            header('Location: /projetos/ver?id=' . $projectId);
            exit;
        }
        @file_put_contents($tmp, $content);

        $mime = 'text/plain';
        $size = is_file($tmp) ? (int)filesize($tmp) : null;
        $sha256 = null;
        try {
            $sha256 = is_readable($tmp) ? hash_file('sha256', $tmp) : null;
        } catch (\Throwable $e) {
            $sha256 = null;
        }

        $remoteUrl = MediaStorageService::uploadFile($tmp, $fileName, $mime);
        @unlink($tmp);

        if ($remoteUrl === null) {
            $_SESSION['project_upload_error'] = 'Não foi possível salvar o texto no storage.';
            header('Location: /projetos/ver?id=' . $projectId);
            exit;
        }

        $fullPath = rtrim($folderPath, '/') . '/' . $fileName;
        if ($fullPath === '') {
            $fullPath = '/' . $fileName;
        }

        $existing = ProjectFile::findByPath($projectId, $fullPath);
        if ($existing) {
            $projectFileId = (int)$existing['id'];
        } else {
            $projectFileId = ProjectFile::create(
                $projectId,
                isset($folder['id']) ? (int)$folder['id'] : null,
                $fileName,
                $fullPath,
                $mime,
                true,
                $userId > 0 ? $userId : null
            );
        }

        ProjectFileVersion::createNewVersion(
            $projectFileId,
            $remoteUrl,
            $size,
            $sha256,
            $content,
            $userId > 0 ? $userId : null
        );

        $_SESSION['project_upload_ok'] = 'Texto salvo como arquivo base com sucesso.';
        header('Location: /projetos/ver?id=' . $projectId);
        exit;
    }
}
