<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\UserSocialProfile;
use App\Models\SocialPortfolioItem;
use App\Models\SocialPortfolioMedia;
use App\Models\SocialPortfolioLike;
use App\Models\SocialPortfolioCollaborator;
use App\Models\SocialPortfolioInvitation;
use App\Services\MediaStorageService;
use App\Services\MailService;

class SocialPortfolioController extends Controller
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

    private function getOwnerIdFromRequest(int $defaultOwnerId): int
    {
        $ownerId = $defaultOwnerId;
        if (isset($_GET['owner_user_id'])) {
            $ownerId = (int)$_GET['owner_user_id'];
        }
        if (isset($_POST['owner_user_id'])) {
            $ownerId = (int)$_POST['owner_user_id'];
        }
        if ($ownerId <= 0) {
            $ownerId = $defaultOwnerId;
        }
        return $ownerId;
    }

    private function requirePortfolioEdit(int $ownerUserId, int $currentUserId): void
    {
        if ($ownerUserId === $currentUserId) {
            return;
        }
        if (!SocialPortfolioCollaborator::canEdit($ownerUserId, $currentUserId)) {
            if ($this->wantsJson()) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'error' => 'Sem permissão para editar este portfólio.']);
                exit;
            }
            $_SESSION['portfolio_error'] = 'Sem permissão para editar este portfólio.';
            header('Location: /perfil/portfolio');
            exit;
        }
    }

    private function requirePortfolioShare(int $ownerUserId, int $currentUserId): void
    {
        if ($ownerUserId === $currentUserId) {
            return;
        }
        if ($this->wantsJson()) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Somente o dono pode compartilhar este portfólio.']);
            exit;
        }
        $_SESSION['portfolio_error'] = 'Somente o dono pode compartilhar este portfólio.';
        header('Location: /perfil/portfolio');
        exit;
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
            'canManage' => ($targetId === $currentId) ? true : SocialPortfolioCollaborator::canEdit($targetId, $currentId),
        ]);
    }

    public function manage(): void
    {
        $currentUser = $this->requireLogin();
        $currentId = (int)($currentUser['id'] ?? 0);
        $ownerId = $this->getOwnerIdFromRequest($currentId);
        $this->requirePortfolioEdit($ownerId, $currentId);

        $ownerUser = $ownerId === $currentId ? $currentUser : User::findById($ownerId);
        if (!$ownerUser) {
            $_SESSION['portfolio_error'] = 'Dono do portfólio não encontrado.';
            header('Location: /perfil/portfolio');
            exit;
        }

        $profile = UserSocialProfile::findByUserId($ownerId);
        if (!$profile) {
            UserSocialProfile::upsertForUser($ownerId, []);
            $profile = UserSocialProfile::findByUserId($ownerId);
        }

        $items = SocialPortfolioItem::allForUser($ownerId, 200);

        $canShare = $ownerId === $currentId;
        $collaborators = $canShare ? SocialPortfolioCollaborator::allWithUsers($ownerId) : [];
        $pendingInvites = $canShare ? SocialPortfolioInvitation::allPendingForOwner($ownerId) : [];

        $success = $_SESSION['portfolio_success'] ?? null;
        $error = $_SESSION['portfolio_error'] ?? null;
        unset($_SESSION['portfolio_success'], $_SESSION['portfolio_error']);

        $this->view('social/portfolio_manage', [
            'pageTitle' => 'Meu portfólio - Tuquinha',
            'user' => $currentUser,
            'profileUser' => $ownerUser,
            'profile' => $profile,
            'items' => $items,
            'success' => $success,
            'error' => $error,
            'ownerId' => $ownerId,
            'canShare' => $canShare,
            'collaborators' => $collaborators,
            'pendingInvites' => $pendingInvites,
        ]);
    }

    public function upsert(): void
    {
        $currentUser = $this->requireLogin();
        $currentId = (int)($currentUser['id'] ?? 0);
        $ownerId = $this->getOwnerIdFromRequest($currentId);
        $this->requirePortfolioEdit($ownerId, $currentId);

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
            SocialPortfolioItem::update($id, $ownerId, $title, $description !== '' ? $description : null, $externalUrl !== '' ? $externalUrl : null, $projectId > 0 ? $projectId : null);
            $_SESSION['portfolio_success'] = 'Portfólio atualizado.';
        } else {
            $newId = SocialPortfolioItem::create($ownerId, $title, $description !== '' ? $description : null, $externalUrl !== '' ? $externalUrl : null, $projectId > 0 ? $projectId : null);
            if ($newId <= 0) {
                $_SESSION['portfolio_error'] = 'Não foi possível criar o item do portfólio.';
                header('Location: /perfil/portfolio/gerenciar?owner_user_id=' . $ownerId);
                exit;
            }
            $_SESSION['portfolio_success'] = 'Portfólio criado.';
        }

        header('Location: /perfil/portfolio/gerenciar?owner_user_id=' . $ownerId);
        exit;
    }

    public function delete(): void
    {
        $currentUser = $this->requireLogin();
        $currentId = (int)($currentUser['id'] ?? 0);
        $ownerId = $this->getOwnerIdFromRequest($currentId);
        $this->requirePortfolioEdit($ownerId, $currentId);

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            $_SESSION['portfolio_error'] = 'Item inválido.';
            header('Location: /perfil/portfolio/gerenciar');
            exit;
        }

        SocialPortfolioItem::softDelete($id, $ownerId);
        $_SESSION['portfolio_success'] = 'Item removido.';
        header('Location: /perfil/portfolio/gerenciar?owner_user_id=' . $ownerId);
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

        $canEdit = $ownerId === $currentId ? true : SocialPortfolioCollaborator::canEdit($ownerId, $currentId);

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
            'canEdit' => $canEdit,
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
        $currentId = (int)($currentUser['id'] ?? 0);
        $ownerId = $this->getOwnerIdFromRequest($currentId);
        $this->requirePortfolioEdit($ownerId, $currentId);

        $itemId = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
        $item = $itemId > 0 ? SocialPortfolioItem::findById($itemId) : null;
        if (!$item || (int)($item['user_id'] ?? 0) !== $ownerId) {
            $_SESSION['portfolio_error'] = 'Sem permissão para enviar arquivos para este portfólio.';
            header('Location: /perfil/portfolio/gerenciar?owner_user_id=' . $ownerId);
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
        $currentId = (int)($currentUser['id'] ?? 0);
        $ownerId = $this->getOwnerIdFromRequest($currentId);
        $this->requirePortfolioEdit($ownerId, $currentId);

        $itemId = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
        $mediaId = isset($_POST['media_id']) ? (int)$_POST['media_id'] : 0;

        $item = $itemId > 0 ? SocialPortfolioItem::findById($itemId) : null;
        if (!$item || (int)($item['user_id'] ?? 0) !== $ownerId) {
            $_SESSION['portfolio_error'] = 'Sem permissão.';
            header('Location: /perfil/portfolio/gerenciar?owner_user_id=' . $ownerId);
            exit;
        }

        SocialPortfolioMedia::softDelete($mediaId, $itemId);
        $_SESSION['portfolio_success'] = 'Arquivo removido.';
        header('Location: /perfil/portfolio/ver?id=' . $itemId);
        exit;
    }

    public function inviteCollaborator(): void
    {
        $user = $this->requireLogin();
        $currentId = (int)($user['id'] ?? 0);

        $ownerId = isset($_POST['owner_user_id']) ? (int)$_POST['owner_user_id'] : $currentId;
        if ($ownerId <= 0) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Portfólio inválido.']);
            return;
        }
        $this->requirePortfolioShare($ownerId, $currentId);

        $email = trim((string)($_POST['email'] ?? ''));
        $role = trim((string)($_POST['role'] ?? 'read'));
        $role = in_array($role, ['read', 'edit'], true) ? $role : 'read';

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Informe um e-mail válido.']);
            return;
        }

        if (strcasecmp($email, (string)($user['email'] ?? '')) === 0) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Você já é dono deste portfólio.']);
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
        if ($invitedUserId > 0 && SocialPortfolioCollaborator::canRead($ownerId, $invitedUserId)) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Este usuário já tem acesso ao portfólio.']);
            return;
        }

        if (SocialPortfolioInvitation::hasValidInviteForEmail($ownerId, $invitedEmail)) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Já existe um convite pendente para este e-mail.']);
            return;
        }

        $token = bin2hex(random_bytes(16));
        SocialPortfolioInvitation::create($ownerId, $currentId, $invitedEmail, null, $role, $token);

        $ownerUser = User::findById($ownerId);
        $ownerName = $ownerUser ? trim((string)($ownerUser['preferred_name'] ?? $ownerUser['name'] ?? '')) : 'Portfólio';
        if ($ownerName === '') {
            $ownerName = 'Portfólio';
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $link = $scheme . $host . '/perfil/portfolio/aceitar-convite?token=' . urlencode($token);

        $subject = 'Convite para colaborar no portfólio de ' . $ownerName;

        $toName = trim((string)($invitedUser['preferred_name'] ?? ''));
        if ($toName === '') {
            $toName = trim((string)($invitedUser['name'] ?? ''));
        }
        if ($toName === '') {
            $toName = $invitedEmail;
        }

        $safeOwnerName = htmlspecialchars($ownerName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $roleLabel = $role === 'edit' ? 'Edição' : 'Leitura';
        $safeRole = htmlspecialchars($roleLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $contentHtml = '<p style="font-size:14px; margin:0 0 10px 0;">Você foi convidado para colaborar no portfólio de <strong>' . $safeOwnerName . '</strong> no Tuquinha.</p>'
            . '<p style="font-size:14px; margin:0 0 10px 0;">Permissão: <strong>' . $safeRole . '</strong></p>'
            . '<p style="font-size:12px; color:#777; margin:10px 0 0 0;">Se você não reconhece este convite, pode ignorar este e-mail.</p>';

        $scheme2 = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
        $host2 = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = $scheme2 . $host2;
        $logoUrl = $baseUrl . '/public/favicon.png';

        $body = MailService::buildDefaultTemplate(
            $toName,
            $contentHtml,
            'Aceitar convite',
            $link,
            $logoUrl
        );

        $sent = MailService::send($invitedEmail, $toName, $subject, $body);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true, 'email_sent' => $sent]);
    }

    public function acceptInvite(): void
    {
        $user = $this->requireLogin();
        $currentId = (int)($user['id'] ?? 0);

        $token = trim((string)($_GET['token'] ?? ''));
        if ($token === '') {
            header('Location: /perfil/portfolio');
            exit;
        }

        $invite = SocialPortfolioInvitation::findByToken($token);
        if (!$invite || ($invite['status'] ?? '') !== 'pending') {
            $_SESSION['portfolio_error'] = 'Convite não encontrado ou já utilizado.';
            header('Location: /perfil/portfolio');
            exit;
        }

        $invitedEmail = trim((string)($invite['invited_email'] ?? ''));
        $userEmail = trim((string)($user['email'] ?? ''));
        if ($invitedEmail !== '' && $userEmail !== '' && strcasecmp($invitedEmail, $userEmail) !== 0) {
            $_SESSION['portfolio_error'] = 'Este convite foi enviado para outro e-mail.';
            header('Location: /perfil/portfolio');
            exit;
        }

        $ownerId = (int)($invite['owner_user_id'] ?? 0);
        $role = (string)($invite['role'] ?? 'read');
        if ($ownerId <= 0) {
            $_SESSION['portfolio_error'] = 'Portfólio do convite não encontrado.';
            header('Location: /perfil/portfolio');
            exit;
        }

        SocialPortfolioCollaborator::addOrUpdate($ownerId, $currentId, $role);
        SocialPortfolioInvitation::markAccepted((int)($invite['id'] ?? 0));

        $_SESSION['portfolio_success'] = 'Convite aceito. Você agora tem acesso a este portfólio.';
        header('Location: /perfil/portfolio/gerenciar?owner_user_id=' . $ownerId);
        exit;
    }

    public function revokeInvite(): void
    {
        $user = $this->requireLogin();
        $currentId = (int)($user['id'] ?? 0);

        $ownerId = isset($_POST['owner_user_id']) ? (int)$_POST['owner_user_id'] : $currentId;
        $inviteId = isset($_POST['invite_id']) ? (int)$_POST['invite_id'] : 0;
        $this->requirePortfolioShare($ownerId, $currentId);

        if ($ownerId <= 0 || $inviteId <= 0) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Convite inválido.']);
            return;
        }

        SocialPortfolioInvitation::cancelById($inviteId);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true]);
    }

    public function updateCollaboratorRole(): void
    {
        $user = $this->requireLogin();
        $currentId = (int)($user['id'] ?? 0);

        $ownerId = isset($_POST['owner_user_id']) ? (int)$_POST['owner_user_id'] : $currentId;
        $collabUserId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        $role = trim((string)($_POST['role'] ?? 'read'));

        $this->requirePortfolioShare($ownerId, $currentId);
        if ($ownerId <= 0 || $collabUserId <= 0 || $collabUserId === $ownerId) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Colaborador inválido.']);
            return;
        }

        SocialPortfolioCollaborator::updateRole($ownerId, $collabUserId, $role);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true]);
    }

    public function removeCollaborator(): void
    {
        $user = $this->requireLogin();
        $currentId = (int)($user['id'] ?? 0);

        $ownerId = isset($_POST['owner_user_id']) ? (int)$_POST['owner_user_id'] : $currentId;
        $collabUserId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

        $this->requirePortfolioShare($ownerId, $currentId);
        if ($ownerId <= 0 || $collabUserId <= 0 || $collabUserId === $ownerId) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Colaborador inválido.']);
            return;
        }

        SocialPortfolioCollaborator::remove($ownerId, $collabUserId);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true]);
    }
}
