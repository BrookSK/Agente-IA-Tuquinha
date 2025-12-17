<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectFolder;
use App\Models\ProjectFile;
use App\Models\ProjectFileVersion;
use App\Models\Subscription;
use App\Models\Plan;
use App\Models\Setting;
use App\Services\MediaStorageService;
use App\Services\TextExtractionService;

class ProjectController extends Controller
{
    private function requireLogin(): array
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $user = User::findById((int)$_SESSION['user_id']);
        if (!$user) {
            unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email']);
            header('Location: /login');
            exit;
        }

        return $user;
    }

    private function requirePaidPlan(array $user): void
    {
        if (!empty($_SESSION['is_admin'])) {
            return;
        }

        $email = (string)($user['email'] ?? '');
        if ($email === '') {
            header('Location: /planos');
            exit;
        }

        $subscription = Subscription::findLastByEmail($email);
        if (!$subscription || empty($subscription['plan_id'])) {
            header('Location: /planos');
            exit;
        }

        $plan = Plan::findById((int)$subscription['plan_id']);
        $slug = $plan ? (string)($plan['slug'] ?? '') : '';
        $status = strtolower((string)($subscription['status'] ?? ''));

        $isPaid = ($slug !== '' && $slug !== 'free');
        $isActive = !in_array($status, ['canceled', 'expired'], true);

        if (!$isPaid || !$isActive) {
            header('Location: /planos');
            exit;
        }
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
        $this->requirePaidPlan($user);
        $projects = Project::allForUser((int)$user['id']);

        $this->view('projects/index', [
            'pageTitle' => 'Projetos - Tuquinha',
            'user' => $user,
            'projects' => $projects,
        ]);
    }

    public function createForm(): void
    {
        $user = $this->requireLogin();
        $this->requirePaidPlan($user);

        $this->view('projects/new', [
            'pageTitle' => 'Novo projeto - Tuquinha',
            'user' => $user,
            'error' => null,
        ]);
    }

    public function create(): void
    {
        $user = $this->requireLogin();
        $this->requirePaidPlan($user);

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
        $this->requirePaidPlan($user);
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

        $folders = ProjectFolder::allForProject($projectId);

        $baseFiles = ProjectFile::allBaseFilesWithFolder($projectId);
        $baseFileIds = array_map(static function ($f) {
            return (int)($f['id'] ?? 0);
        }, $baseFiles);
        $latestByFileId = ProjectFileVersion::latestForFiles($baseFileIds);

        $this->view('projects/show', [
            'pageTitle' => ($project['name'] ?? 'Projeto') . ' - Tuquinha',
            'user' => $user,
            'project' => $project,
            'folders' => $folders,
            'baseFiles' => $baseFiles,
            'latestByFileId' => $latestByFileId,
            'uploadError' => $_SESSION['project_upload_error'] ?? null,
            'uploadOk' => $_SESSION['project_upload_ok'] ?? null,
        ]);

        unset($_SESSION['project_upload_error'], $_SESSION['project_upload_ok']);
    }

    public function uploadBaseFile(): void
    {
        $user = $this->requireLogin();
        $this->requirePaidPlan($user);
        $userId = (int)($user['id'] ?? 0);

        $projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
        $folderPath = trim((string)($_POST['folder_path'] ?? '/base'));

        if ($projectId <= 0 || !ProjectMember::canWrite($projectId, $userId)) {
            header('Location: /projetos');
            exit;
        }

        $project = Project::findById($projectId);
        if (!$project) {
            header('Location: /projetos');
            exit;
        }

        if ($folderPath === '' || $folderPath[0] !== '/') {
            $folderPath = '/' . ltrim($folderPath, '/');
        }

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
        $needsExtractor = in_array($ext, ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx'], true);
        if ($needsExtractor) {
            $endpoint = trim((string)Setting::get('text_extraction_endpoint', ''));
            if ($endpoint === '') {
                $_SESSION['project_upload_error'] = 'Para usar PDF/Word/Office como conteúdo base, copie o texto do arquivo e cole no campo de texto ("Salvar texto como arquivo base").';
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
        $this->requirePaidPlan($user);
        $userId = (int)($user['id'] ?? 0);

        $projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
        $folderPath = trim((string)($_POST['folder_path'] ?? '/base'));
        $fileName = trim((string)($_POST['file_name'] ?? ''));
        $content = (string)($_POST['content'] ?? '');
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        if ($projectId <= 0 || !ProjectMember::canWrite($projectId, $userId)) {
            header('Location: /projetos');
            exit;
        }

        if ($folderPath === '' || $folderPath[0] !== '/') {
            $folderPath = '/' . ltrim($folderPath, '/');
        }
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
