<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Page;
use App\Models\PageShare;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\MediaStorageService;

class CadernoController extends Controller
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

    private function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
        exit;
    }

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

    private function requireCadernoAccess(array $user): array
    {
        if (!empty($_SESSION['is_admin'])) {
            return ['plan' => null, 'subscription_active' => true];
        }

        $plan = $this->getActivePlanForEmail((string)($user['email'] ?? ''));
        if (!$plan) {
            if ($this->wantsJson()) {
                $this->json(['ok' => false, 'error' => 'Sem assinatura ativa.'], 403);
            }
            header('Location: /planos');
            exit;
        }

        if (empty($plan['allow_pages'])) {
            if ($this->wantsJson()) {
                $this->json(['ok' => false, 'error' => 'Seu plano não permite o Caderno.'], 403);
            }
            header('Location: /planos');
            exit;
        }

        return ['plan' => $plan, 'subscription_active' => true];
    }

    private function canEditPage(array $page, int $userId): bool
    {
        if ((int)($page['owner_user_id'] ?? 0) === $userId) {
            return true;
        }
        $role = strtolower((string)($page['access_role'] ?? ''));
        return $role === 'edit' || $role === 'owner';
    }

    public function index(): void
    {
        $user = $this->requireLogin();
        $this->requireCadernoAccess($user);

        $uid = (int)$user['id'];
        $pages = Page::listForUserTree($uid);

        if (empty($pages)) {
            $id = Page::create($uid, 'Sem título', null);
            header('Location: /caderno?id=' . urlencode((string)$id));
            exit;
        }

        $pageId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $current = null;
        if ($pageId > 0) {
            $current = Page::findAccessibleById($pageId, $uid);
        }
        if (!$current && !empty($pages)) {
            $first = $pages[0] ?? null;
            if (is_array($first) && !empty($first['id'])) {
                $current = Page::findAccessibleById((int)$first['id'], $uid);
            }
        }

        $shares = [];
        if ($current && (int)($current['owner_user_id'] ?? 0) === $uid) {
            $shares = PageShare::listForPage((int)$current['id']);
        }

        $breadcrumb = [];
        if ($current && !empty($current['id'])) {
            $breadcrumb = Page::getBreadcrumb((int)$current['id']);
        }

        $this->view('caderno/index', [
            'pageTitle' => 'Caderno - Tuquinha',
            'user' => $user,
            'pages' => $pages,
            'current' => $current,
            'shares' => $shares,
            'breadcrumb' => $breadcrumb,
        ]);
    }

    public function create(): void
    {
        $user = $this->requireLogin();
        $this->requireCadernoAccess($user);

        $uid = (int)$user['id'];
        $title = trim((string)($_POST['title'] ?? 'Sem título'));
        $parentId = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0;
        if ($parentId > 0) {
            $parent = Page::findAccessibleById($parentId, $uid);
            if (!$parent) {
                $this->json(['ok' => false, 'error' => 'Sem acesso à página pai.'], 403);
            }
            if (!$this->canEditPage($parent, $uid)) {
                $this->json(['ok' => false, 'error' => 'Sem permissão para criar subpágina aqui.'], 403);
            }
        }

        $id = Page::create($uid, $title, $parentId > 0 ? $parentId : null);
        $this->json(['ok' => true, 'id' => $id]);
    }

    public function save(): void
    {
        $user = $this->requireLogin();
        $this->requireCadernoAccess($user);

        $uid = (int)$user['id'];
        $pageId = (int)($_POST['page_id'] ?? 0);
        $content = (string)($_POST['content_json'] ?? '');

        if ($pageId <= 0) {
            $this->json(['ok' => false, 'error' => 'Página inválida.'], 400);
        }

        $page = Page::findAccessibleById($pageId, $uid);
        if (!$page) {
            $this->json(['ok' => false, 'error' => 'Sem acesso à página.'], 403);
        }
        if (!$this->canEditPage($page, $uid)) {
            $this->json(['ok' => false, 'error' => 'Sem permissão para editar.'], 403);
        }

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            $this->json(['ok' => false, 'error' => 'Conteúdo inválido.'], 400);
        }

        Page::updateContent($pageId, $content);
        $this->json(['ok' => true]);
    }

    public function rename(): void
    {
        $user = $this->requireLogin();
        $this->requireCadernoAccess($user);

        $uid = (int)$user['id'];
        $pageId = (int)($_POST['page_id'] ?? 0);
        $title = trim((string)($_POST['title'] ?? ''));
        $icon = trim((string)($_POST['icon'] ?? ''));

        $page = Page::findAccessibleById($pageId, $uid);
        if (!$page) {
            $this->json(['ok' => false, 'error' => 'Sem acesso à página.'], 403);
        }

        if (!$this->canEditPage($page, $uid)) {
            $this->json(['ok' => false, 'error' => 'Sem permissão para renomear.'], 403);
        }

        Page::rename($pageId, $title, $icon !== '' ? $icon : null);
        $this->json(['ok' => true]);
    }

    public function delete(): void
    {
        $user = $this->requireLogin();
        $this->requireCadernoAccess($user);

        $uid = (int)$user['id'];
        $pageId = (int)($_POST['page_id'] ?? 0);

        $page = Page::findAccessibleById($pageId, $uid);
        if (!$page) {
            $this->json(['ok' => false, 'error' => 'Sem acesso à página.'], 403);
        }

        if ((int)($page['owner_user_id'] ?? 0) !== $uid) {
            $this->json(['ok' => false, 'error' => 'Apenas o dono pode excluir.'], 403);
        }

        Page::delete($pageId);
        $this->json(['ok' => true]);
    }

    public function publish(): void
    {
        $user = $this->requireLogin();
        $this->requireCadernoAccess($user);

        $uid = (int)$user['id'];
        $pageId = (int)($_POST['page_id'] ?? 0);
        $publish = !empty($_POST['publish']);

        $page = Page::findAccessibleById($pageId, $uid);
        if (!$page) {
            $this->json(['ok' => false, 'error' => 'Sem acesso à página.'], 403);
        }

        if ((int)($page['owner_user_id'] ?? 0) !== $uid) {
            $this->json(['ok' => false, 'error' => 'Apenas o dono pode publicar.'], 403);
        }

        $token = null;
        if ($publish) {
            $token = bin2hex(random_bytes(24));
        }
        Page::setPublished($pageId, $publish, $token);

        $publicUrl = null;
        if ($publish && $token) {
            $publicUrl = '/caderno/publico?token=' . urlencode($token);
        }

        $this->json(['ok' => true, 'public_url' => $publicUrl]);
    }

    public function shareAdd(): void
    {
        $user = $this->requireLogin();
        $this->requireCadernoAccess($user);

        $uid = (int)$user['id'];
        $pageId = (int)($_POST['page_id'] ?? 0);
        $email = trim((string)($_POST['email'] ?? ''));
        $role = trim((string)($_POST['role'] ?? 'view'));

        if ($email === '') {
            $this->json(['ok' => false, 'error' => 'Informe o e-mail.'], 400);
        }

        $page = Page::findAccessibleById($pageId, $uid);
        if (!$page) {
            $this->json(['ok' => false, 'error' => 'Sem acesso à página.'], 403);
        }

        if ((int)($page['owner_user_id'] ?? 0) !== $uid) {
            $this->json(['ok' => false, 'error' => 'Apenas o dono pode compartilhar.'], 403);
        }

        $target = User::findByEmail($email);
        if (!$target || empty($target['id'])) {
            $this->json(['ok' => false, 'error' => 'Usuário não encontrado.'], 404);
        }

        $targetId = (int)$target['id'];
        if ($targetId === $uid) {
            $this->json(['ok' => false, 'error' => 'Você já é o dono.'], 400);
        }

        PageShare::upsert($pageId, $targetId, $role);
        $shares = PageShare::listForPage($pageId);
        $this->json(['ok' => true, 'shares' => $shares]);
    }

    public function uploadMedia(): void
    {
        $user = $this->requireLogin();
        $this->requireCadernoAccess($user);

        $uid = (int)($user['id'] ?? 0);
        $pageId = (int)($_POST['page_id'] ?? 0);
        if ($pageId <= 0) {
            $this->json(['success' => 0, 'message' => 'Página inválida.'], 400);
        }

        $page = Page::findAccessibleById($pageId, $uid);
        if (!$page) {
            $this->json(['success' => 0, 'message' => 'Sem acesso à página.'], 403);
        }
        if (!$this->canEditPage($page, $uid)) {
            $this->json(['success' => 0, 'message' => 'Sem permissão para editar.'], 403);
        }

        if (empty($_FILES['file']) || !is_array($_FILES['file'])) {
            $this->json(['success' => 0, 'message' => 'Envie um arquivo.'], 422);
        }

        $err = (int)($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err !== UPLOAD_ERR_OK) {
            $this->json(['success' => 0, 'message' => 'Erro no upload.'], 422);
        }

        $tmp = (string)($_FILES['file']['tmp_name'] ?? '');
        $originalName = (string)($_FILES['file']['name'] ?? 'arquivo');
        $type = (string)($_FILES['file']['type'] ?? '');
        $size = (int)($_FILES['file']['size'] ?? 0);
        if (!is_file($tmp) || $size <= 0) {
            $this->json(['success' => 0, 'message' => 'Arquivo inválido.'], 422);
        }

        $remoteUrl = MediaStorageService::uploadFile($tmp, $originalName, $type);
        if (!is_string($remoteUrl) || trim($remoteUrl) === '') {
            $this->json(['success' => 0, 'message' => 'Não foi possível enviar a mídia para o servidor.'], 500);
        }

        $mimeLower = strtolower($type);
        $isImage = $mimeLower !== '' && str_starts_with($mimeLower, 'image/');

        if ($isImage) {
            // Formato esperado pelo ImageTool
            $this->json([
                'success' => 1,
                'file' => [
                    'url' => $remoteUrl,
                ],
            ]);
        }

        // Formato esperado pelo AttachesTool
        $this->json([
            'success' => 1,
            'file' => [
                'url' => $remoteUrl,
                'name' => $originalName,
                'title' => $originalName,
                'size' => $size,
                'extension' => (string)pathinfo($originalName, PATHINFO_EXTENSION),
            ],
        ]);
    }

    public function shareRemove(): void
    {
        $user = $this->requireLogin();
        $this->requireCadernoAccess($user);

        $uid = (int)$user['id'];
        $pageId = (int)($_POST['page_id'] ?? 0);
        $userId = (int)($_POST['user_id'] ?? 0);

        $page = Page::findAccessibleById($pageId, $uid);
        if (!$page) {
            $this->json(['ok' => false, 'error' => 'Sem acesso à página.'], 403);
        }

        if ((int)($page['owner_user_id'] ?? 0) !== $uid) {
            $this->json(['ok' => false, 'error' => 'Apenas o dono pode remover compartilhamento.'], 403);
        }

        if ($userId <= 0) {
            $this->json(['ok' => false, 'error' => 'Usuário inválido.'], 400);
        }

        PageShare::remove($pageId, $userId);
        $shares = PageShare::listForPage($pageId);
        $this->json(['ok' => true, 'shares' => $shares]);
    }

    public function publico(): void
    {
        $token = (string)($_GET['token'] ?? '');
        $page = Page::findPublicByToken($token);
        if (!$page) {
            http_response_code(404);
            echo 'Página não encontrada';
            return;
        }

        $this->view('caderno/publico', [
            'pageTitle' => (string)($page['title'] ?? 'Caderno'),
            'page' => $page,
        ]);
    }
}
