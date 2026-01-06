<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\MenuIcon;
use App\Services\MediaStorageService;

class AdminMenuIconController extends Controller
{
    private function ensureAdmin(): void
    {
        if (empty($_SESSION['is_admin'])) {
            header('Location: /admin/login');
            exit;
        }
    }

    private function getMenuItems(): array
    {
        return [
            'chat_history' => 'Histórico de chats',
            'quick_home' => 'Quem é o Tuquinha',
            'quick_plans' => 'Planos e limites',
            'quick_courses' => 'Cursos',
            'projects_list' => 'Meus projetos',
            'projects_new' => 'Novo projeto',
            'social_profile' => 'Perfil social',
            'social_friends' => 'Amigos',
            'social_communities' => 'Comunidades',
            'account_home' => 'Minha conta',
            'account_certificates' => 'Cursos concluídos',
            'account_persona' => 'Personalidade padrão',
            'account_tokens' => 'Histórico de tokens extras',
            'account_support' => 'Suporte',
            'partner_courses' => 'Meus cursos (parceiro)',
            'partner_commissions' => 'Minhas comissões',
            'logout' => 'Sair da conta',
            'admin_dashboard' => 'Dashboard',
            'admin_config' => 'Configurações do sistema',
            'admin_plans' => 'Gerenciar planos',
            'admin_finance' => 'Finanças (admin)',
            'admin_courses' => 'Cursos (admin)',
            'admin_commissions' => 'Comissões (admin)',
            'admin_personalities' => 'Personalidades do Tuquinha',
            'admin_users' => 'Usuários',
            'admin_subscriptions' => 'Assinaturas',
            'admin_community_categories' => 'Categorias de comunidades',
        ];
    }

    public function index(): void
    {
        $this->ensureAdmin();
        $items = $this->getMenuItems();
        $current = MenuIcon::allAssoc();
        $error = $_SESSION['admin_menu_icon_error'] ?? null;
        $success = $_SESSION['admin_menu_icon_success'] ?? null;
        unset($_SESSION['admin_menu_icon_error'], $_SESSION['admin_menu_icon_success']);

        $this->view('admin/menu_icons/index', [
            'pageTitle' => 'Ícones do menu',
            'items' => $items,
            'current' => $current,
            'error' => $error,
            'success' => $success,
        ]);
    }

    public function save(): void
    {
        $this->ensureAdmin();

        $key = trim((string)($_POST['key'] ?? ''));
        $label = trim((string)($_POST['label'] ?? ''));
        if ($key === '' || $label === '') {
            $_SESSION['admin_menu_icon_error'] = 'Item inválido.';
            header('Location: /admin/menu-icones');
            exit;
        }

        $existing = MenuIcon::allAssoc();
        $darkPath = isset($existing[$key]['dark_path']) ? (string)$existing[$key]['dark_path'] : null;
        $lightPath = isset($existing[$key]['light_path']) ? (string)$existing[$key]['light_path'] : null;

        if (!empty($_POST['clear_dark'])) {
            $darkPath = null;
        }
        if (!empty($_POST['clear_light'])) {
            $lightPath = null;
        }

        $maxSize = 700 * 1024; // 700 KB
        $allowed = [
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
        ];

        $publicDir = __DIR__ . '/../../public/uploads/menu-icons';
        if (!is_dir($publicDir)) {
            @mkdir($publicDir, 0775, true);
        }

        $darkPath = $this->handleUpload('dark_file', $key, 'dark', $publicDir, $allowed, $maxSize, $darkPath);
        if ($darkPath === false) {
            header('Location: /admin/menu-icones');
            exit;
        }

        $lightPath = $this->handleUpload('light_file', $key, 'light', $publicDir, $allowed, $maxSize, $lightPath);
        if ($lightPath === false) {
            header('Location: /admin/menu-icones');
            exit;
        }

        MenuIcon::upsert($key, $label, $darkPath ?: null, $lightPath ?: null);
        $_SESSION['admin_menu_icon_success'] = 'Ícones atualizados.';
        header('Location: /admin/menu-icones');
        exit;
    }

    private function handleUpload(string $field, string $key, string $variant, string $publicDir, array $allowed, int $maxSize, ?string $currentPath)
    {
        if (empty($_FILES[$field]) || !is_array($_FILES[$field])) {
            return $currentPath;
        }

        $uploadError = (int)($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($uploadError === UPLOAD_ERR_NO_FILE) {
            return $currentPath;
        }
        if ($uploadError !== UPLOAD_ERR_OK) {
            $_SESSION['admin_menu_icon_error'] = 'Erro ao enviar o arquivo.';
            return false;
        }

        $tmp = (string)($_FILES[$field]['tmp_name'] ?? '');
        $type = (string)($_FILES[$field]['type'] ?? '');
        $size = (int)($_FILES[$field]['size'] ?? 0);
        $originalName = (string)($_FILES[$field]['name'] ?? 'icon');

        if ($tmp === '' || !is_uploaded_file($tmp)) {
            $_SESSION['admin_menu_icon_error'] = 'Upload inválido.';
            return false;
        }
        if ($size <= 0 || $size > $maxSize) {
            $_SESSION['admin_menu_icon_error'] = 'O ícone deve ter até 700 KB.';
            return false;
        }

        if (!isset($allowed[$type])) {
            $_SESSION['admin_menu_icon_error'] = 'Formato inválido. Use PNG, JPG, WEBP ou SVG.';
            return false;
        }

        // Primeiro tenta subir para o servidor de mídia externo (se estiver configurado)
        try {
            $remoteUrl = MediaStorageService::uploadFile($tmp, $originalName, $type);
            if (is_string($remoteUrl) && $remoteUrl !== '') {
                return $remoteUrl;
            }
        } catch (\Throwable $e) {
        }

        $ext = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));
        $fallbackExt = $allowed[$type];
        if ($ext === '') {
            $ext = $fallbackExt;
        }

        $safeKey = preg_replace('/[^a-z0-9_\-]/i', '_', $key);
        $fileName = 'menu_' . $safeKey . '_' . $variant . '_' . uniqid('', true) . '.' . $ext;
        $targetPath = rtrim($publicDir, '/\\') . DIRECTORY_SEPARATOR . $fileName;

        if (!@move_uploaded_file($tmp, $targetPath)) {
            $_SESSION['admin_menu_icon_error'] = 'Não foi possível salvar o ícone enviado.';
            return false;
        }

        return '/public/uploads/menu-icons/' . $fileName;
    }
}
