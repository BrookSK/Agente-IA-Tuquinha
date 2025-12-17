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
use App\Services\MediaStorageService;

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

        $ext = strtolower(pathinfo($safeFileName, PATHINFO_EXTENSION));
        $isTextLike = false;
        if ($mime !== '' && (str_starts_with($mime, 'text/') || $mime === 'application/json')) {
            $isTextLike = true;
        }
        if (in_array($ext, ['txt','md','json','php','js','ts','tsx','jsx','html','css','scss','py','java','go','rb','sh','yml','yaml','xml','sql'], true)) {
            $isTextLike = true;
        }

        $extractedText = null;
        if ($isTextLike) {
            $content = @file_get_contents($tmp);
            if (is_string($content)) {
                if (mb_strlen($content, 'UTF-8') > 200000) {
                    $content = mb_substr($content, 0, 200000, 'UTF-8');
                }
                $extractedText = $content;
            }
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
}
