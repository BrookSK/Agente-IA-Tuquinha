<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\UserSocialProfile;
use App\Models\SocialPortfolioItem;
use App\Models\SocialPortfolioMedia;
use App\Models\SocialPortfolioLike;
use App\Services\MediaStorageService;

class SocialPortfolioController extends Controller
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

    public function listForUser(): void
    {
        $currentUser = $this->requireLogin();
        $currentId = (int)($currentUser['id'] ?? 0);

        $targetId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $currentId;
        if ($targetId <= 0) {
            $targetId = $currentId;
        }

        $profileUser = User::findById($targetId);
        if (!$profileUser) {
            header('Location: /perfil');
            exit;
        }

        $profile = UserSocialProfile::findByUserId($targetId);
        if (!$profile) {
            UserSocialProfile::upsertForUser($targetId, []);
            $profile = UserSocialProfile::findByUserId($targetId);
        }

        $items = SocialPortfolioItem::allForUser($targetId, 200);

        $likesCountById = [];
        foreach ($items as $it) {
            $id = (int)($it['id'] ?? 0);
            $likesCountById[$id] = $id > 0 ? SocialPortfolioLike::countForItem($id) : 0;
        }

        $displayName = trim((string)($profileUser['preferred_name'] ?? $profileUser['name'] ?? ''));
        if ($displayName === '') {
            $displayName = 'Perfil';
        }

        $this->view('social/portfolio_list', [
            'pageTitle' => 'Portfólio - ' . $displayName,
            'user' => $currentUser,
            'profileUser' => $profileUser,
            'profile' => $profile,
            'items' => $items,
            'likesCountById' => $likesCountById,
            'isOwn' => $targetId === $currentId,
        ]);
    }

    public function manage(): void
    {
        $currentUser = $this->requireLogin();
        $userId = (int)($currentUser['id'] ?? 0);

        $profile = UserSocialProfile::findByUserId($userId);
        if (!$profile) {
            UserSocialProfile::upsertForUser($userId, []);
            $profile = UserSocialProfile::findByUserId($userId);
        }

        $items = SocialPortfolioItem::allForUser($userId, 200);

        $success = $_SESSION['portfolio_success'] ?? null;
        $error = $_SESSION['portfolio_error'] ?? null;
        unset($_SESSION['portfolio_success'], $_SESSION['portfolio_error']);

        $this->view('social/portfolio_manage', [
            'pageTitle' => 'Meu portfólio - Tuquinha',
            'user' => $currentUser,
            'profileUser' => $currentUser,
            'profile' => $profile,
            'items' => $items,
            'success' => $success,
            'error' => $error,
        ]);
    }

    public function upsert(): void
    {
        $currentUser = $this->requireLogin();
        $userId = (int)($currentUser['id'] ?? 0);

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $title = trim((string)($_POST['title'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $externalUrl = trim((string)($_POST['external_url'] ?? ''));
        $projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;

        if ($title === '') {
            $_SESSION['portfolio_error'] = 'Informe um título.';
            header('Location: /perfil/portfolio/gerenciar');
            exit;
        }

        if ($externalUrl !== '' && !preg_match('/^https?:\/\//i', $externalUrl)) {
            $externalUrl = 'https://' . $externalUrl;
        }

        if ($id > 0) {
            SocialPortfolioItem::update($id, $userId, $title, $description !== '' ? $description : null, $externalUrl !== '' ? $externalUrl : null, $projectId > 0 ? $projectId : null);
            $_SESSION['portfolio_success'] = 'Portfólio atualizado.';
        } else {
            $newId = SocialPortfolioItem::create($userId, $title, $description !== '' ? $description : null, $externalUrl !== '' ? $externalUrl : null, $projectId > 0 ? $projectId : null);
            if ($newId <= 0) {
                $_SESSION['portfolio_error'] = 'Não foi possível criar o item do portfólio.';
                header('Location: /perfil/portfolio/gerenciar');
                exit;
            }
            $_SESSION['portfolio_success'] = 'Portfólio criado.';
        }

        header('Location: /perfil/portfolio/gerenciar');
        exit;
    }

    public function delete(): void
    {
        $currentUser = $this->requireLogin();
        $userId = (int)($currentUser['id'] ?? 0);

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            $_SESSION['portfolio_error'] = 'Item inválido.';
            header('Location: /perfil/portfolio/gerenciar');
            exit;
        }

        SocialPortfolioItem::softDelete($id, $userId);
        $_SESSION['portfolio_success'] = 'Item removido.';
        header('Location: /perfil/portfolio/gerenciar');
        exit;
    }

    public function viewItem(): void
    {
        $currentUser = $this->requireLogin();
        $currentId = (int)($currentUser['id'] ?? 0);

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $item = $id > 0 ? SocialPortfolioItem::findById($id) : null;
        if (!$item) {
            header('Location: /perfil/portfolio');
            exit;
        }

        $ownerId = (int)($item['user_id'] ?? 0);
        $profileUser = User::findById($ownerId);
        if (!$profileUser) {
            header('Location: /perfil/portfolio');
            exit;
        }

        $profile = UserSocialProfile::findByUserId($ownerId);
        if (!$profile) {
            UserSocialProfile::upsertForUser($ownerId, []);
            $profile = UserSocialProfile::findByUserId($ownerId);
        }

        $media = SocialPortfolioMedia::allForItem($id);
        $likesCount = SocialPortfolioLike::countForItem($id);
        $isLiked = $currentId > 0 ? SocialPortfolioLike::isLikedByUser($id, $currentId) : false;

        $this->view('social/portfolio_view', [
            'pageTitle' => 'Portfólio - ' . (string)($item['title'] ?? 'Item'),
            'user' => $currentUser,
            'profileUser' => $profileUser,
            'profile' => $profile,
            'item' => $item,
            'media' => $media,
            'likesCount' => $likesCount,
            'isLiked' => $isLiked,
            'isOwner' => $ownerId === $currentId,
        ]);
    }

    public function toggleLike(): void
    {
        $currentUser = $this->requireLogin();
        $userId = (int)($currentUser['id'] ?? 0);

        $itemId = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
        if ($itemId <= 0) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false]);
            return;
        }

        $liked = SocialPortfolioLike::toggle($itemId, $userId);
        $count = SocialPortfolioLike::countForItem($itemId);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true, 'liked' => $liked, 'count' => $count]);
    }

    public function uploadMedia(): void
    {
        $currentUser = $this->requireLogin();
        $userId = (int)($currentUser['id'] ?? 0);

        $itemId = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
        $item = $itemId > 0 ? SocialPortfolioItem::findById($itemId) : null;
        if (!$item || (int)($item['user_id'] ?? 0) !== $userId) {
            $_SESSION['portfolio_error'] = 'Sem permissão para enviar arquivos para este portfólio.';
            header('Location: /perfil/portfolio/gerenciar');
            exit;
        }

        if (empty($_FILES['file']) || !is_array($_FILES['file'])) {
            $_SESSION['portfolio_error'] = 'Envie um arquivo.';
            header('Location: /perfil/portfolio/ver?id=' . $itemId);
            exit;
        }

        $err = (int)($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err !== UPLOAD_ERR_OK) {
            $_SESSION['portfolio_error'] = 'Erro no upload.';
            header('Location: /perfil/portfolio/ver?id=' . $itemId);
            exit;
        }

        $tmp = (string)($_FILES['file']['tmp_name'] ?? '');
        $originalName = (string)($_FILES['file']['name'] ?? 'arquivo');
        $type = (string)($_FILES['file']['type'] ?? '');
        $size = (int)($_FILES['file']['size'] ?? 0);

        if (!is_file($tmp) || $size <= 0) {
            $_SESSION['portfolio_error'] = 'Arquivo inválido.';
            header('Location: /perfil/portfolio/ver?id=' . $itemId);
            exit;
        }

        $isImage = $type !== '' && str_starts_with($type, 'image/');
        $kind = $isImage ? 'image' : 'file';

        $remoteUrl = MediaStorageService::uploadFile($tmp, $originalName, $type);
        if (!is_string($remoteUrl) || $remoteUrl === '') {
            $_SESSION['portfolio_error'] = 'Não foi possível enviar a mídia para o servidor. Verifique a configuração do endpoint de mídia.';
            header('Location: /perfil/portfolio/ver?id=' . $itemId);
            exit;
        }

        SocialPortfolioMedia::create($itemId, $kind, $remoteUrl, $originalName, $type !== '' ? $type : null, $size);

        $_SESSION['portfolio_success'] = 'Arquivo enviado.';
        header('Location: /perfil/portfolio/ver?id=' . $itemId);
        exit;
    }

    public function deleteMedia(): void
    {
        $currentUser = $this->requireLogin();
        $userId = (int)($currentUser['id'] ?? 0);

        $itemId = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
        $mediaId = isset($_POST['media_id']) ? (int)$_POST['media_id'] : 0;

        $item = $itemId > 0 ? SocialPortfolioItem::findById($itemId) : null;
        if (!$item || (int)($item['user_id'] ?? 0) !== $userId) {
            $_SESSION['portfolio_error'] = 'Sem permissão.';
            header('Location: /perfil/portfolio/gerenciar');
            exit;
        }

        SocialPortfolioMedia::softDelete($mediaId, $itemId);
        $_SESSION['portfolio_success'] = 'Arquivo removido.';
        header('Location: /perfil/portfolio/ver?id=' . $itemId);
        exit;
    }
}
