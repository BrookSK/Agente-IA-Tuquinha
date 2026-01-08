<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\UserSocialProfile;
use App\Models\SocialPortfolioItem;
use App\Models\SocialPortfolioMedia;
use App\Models\SocialPortfolioBlock;
use App\Models\SocialPortfolioLike;
use App\Models\SocialPortfolioCollaborator;
use App\Models\SocialPortfolioInvitation;
use App\Models\Setting;
use App\Services\MediaStorageService;
use App\Services\MailService;

class SocialPortfolioController extends Controller
{
    private function sanitizeEmbedHtml(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        if (!class_exists('DOMDocument')) {
            return '';
        }

        $prev = libxml_use_internal_errors(true);
        try {
            $doc = new \DOMDocument();
            $doc->loadHTML('<!doctype html><html><body>' . $html . '</body></html>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

            $iframes = $doc->getElementsByTagName('iframe');

            if ($iframes->length > 0) {
                $iframe = $iframes->item(0);
                if (!$iframe) {
                    return '';
                }

                $src = trim((string)$iframe->getAttribute('src'));
                if ($src === '') {
                    return '';
                }

                if (strpos($src, '//') === 0) {
                    $src = 'https:' . $src;
                }

                if (!preg_match('/^https?:\/\//i', $src)) {
                    return '';
                }

                $clean = new \DOMDocument();
                $cleanIframe = $clean->createElement('iframe');
                $cleanIframe->setAttribute('src', $src);

                $w = trim((string)$iframe->getAttribute('width'));
                $h = trim((string)$iframe->getAttribute('height'));
                if ($w !== '' && preg_match('/^[0-9]{1,4}$/', $w)) {
                    $cleanIframe->setAttribute('width', $w);
                }
                if ($h !== '' && preg_match('/^[0-9]{1,4}$/', $h)) {
                    $cleanIframe->setAttribute('height', $h);
                }

                $allow = trim((string)$iframe->getAttribute('allow'));
                if ($allow !== '') {
                    $cleanIframe->setAttribute('allow', $allow);
                }
                $allowFs = trim((string)$iframe->getAttribute('allowfullscreen'));
                if ($allowFs !== '') {
                    $cleanIframe->setAttribute('allowfullscreen', '');
                }

                $ref = trim((string)$iframe->getAttribute('referrerpolicy'));
                if ($ref !== '') {
                    $cleanIframe->setAttribute('referrerpolicy', $ref);
                }

                $loading = trim((string)$iframe->getAttribute('loading'));
                if ($loading !== '') {
                    $cleanIframe->setAttribute('loading', $loading);
                }

                $clean->appendChild($cleanIframe);
                $out = $clean->saveHTML($cleanIframe);
                $out = is_string($out) ? trim($out) : '';
                return $out;
            }

            $body = $doc->getElementsByTagName('body')->item(0);
            if (!$body) {
                return '';
            }

            $allowedTags = [
                'p', 'br', 'div', 'span',
                'strong', 'b', 'em', 'i', 'u',
                'ul', 'ol', 'li',
                'blockquote', 'code', 'pre',
                'h1', 'h2', 'h3', 'h4',
                'a',
            ];

            $cleanDoc = new \DOMDocument();
            $container = $cleanDoc->createElement('div');
            $cleanDoc->appendChild($container);

            foreach (iterator_to_array($body->childNodes) as $child) {
                $san = $this->sanitizeEmbedNode($cleanDoc, $child, $allowedTags);
                if ($san) {
                    $container->appendChild($san);
                }
            }

            $out = $cleanDoc->saveHTML($container);
            $out = is_string($out) ? trim($out) : '';
            if ($out === '') {
                return '';
            }

            if (preg_match('/^<div>(.*)<\/div>$/s', $out, $m)) {
                $out = trim((string)($m[1] ?? ''));
            }
            return $out;
        } catch (\Throwable $e) {
            return '';
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($prev);
        }
    }

    private function sanitizeEmbedNode(\DOMDocument $doc, $node, array $allowedTags): ?\DOMNode
    {
        if (!$node) {
            return null;
        }

        if ($node instanceof \DOMText) {
            $txt = $node->wholeText;
            return $doc->createTextNode($txt);
        }

        if (!($node instanceof \DOMElement)) {
            return null;
        }

        $tag = strtolower($node->tagName);
        if (!in_array($tag, $allowedTags, true)) {
            return null;
        }

        if ($tag === 'br') {
            return $doc->createElement('br');
        }

        $el = $doc->createElement($tag);

        if ($tag === 'a') {
            $href = trim((string)$node->getAttribute('href'));
            if ($href !== '' && $this->isSafeEmbedHref($href)) {
                $el->setAttribute('href', $href);
                $el->setAttribute('rel', 'noopener noreferrer');
                $el->setAttribute('target', '_blank');
            }
        }

        foreach (iterator_to_array($node->childNodes) as $child) {
            $san = $this->sanitizeEmbedNode($doc, $child, $allowedTags);
            if ($san) {
                $el->appendChild($san);
            }
        }

        return $el;
    }

    private function isSafeEmbedHref(string $href): bool
    {
        $href = trim($href);
        if ($href === '') {
            return false;
        }

        if (preg_match('/^(javascript|data):/i', $href)) {
            return false;
        }

        if ($href[0] === '#' || $href[0] === '/') {
            return true;
        }

        return (bool)preg_match('/^(https?:\/\/|mailto:|tel:)/i', $href);
    }

    private function sanitizeBlocksForSave(array $blocks): array
    {
        $out = [];
        foreach ($blocks as $b) {
            if (!is_array($b)) {
                continue;
            }
            $type = isset($b['type']) ? (string)$b['type'] : 'text';
            $type = strtolower(trim($type));

            if ($type === 'embed') {
                $b['text'] = $this->sanitizeEmbedHtml((string)($b['text'] ?? ''));
            }

            $out[] = $b;
        }
        return $out;
    }

    private function getEmbedSanitizationErrors(array $before, array $after): array
    {
        $errors = [];
        $max = max(count($before), count($after));
        for ($i = 0; $i < $max; $i++) {
            $b1 = isset($before[$i]) && is_array($before[$i]) ? $before[$i] : null;
            $b2 = isset($after[$i]) && is_array($after[$i]) ? $after[$i] : null;

            $t1 = $b1 && isset($b1['type']) ? strtolower(trim((string)$b1['type'])) : '';
            if ($t1 !== 'embed') {
                continue;
            }

            $raw = $b1 && isset($b1['text']) ? trim((string)$b1['text']) : '';
            $clean = $b2 && isset($b2['text']) ? trim((string)$b2['text']) : '';
            if ($raw !== '' && $clean === '') {
                $errors[] = 'Um bloco de embed foi recusado por conter conteúdo não permitido.';
                break;
            }
        }
        return $errors;
    }
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

    private function requirePortfolioOwner(int $ownerUserId, int $currentUserId): void
    {
        if ($ownerUserId === $currentUserId) {
            return;
        }
        if ($this->wantsJson()) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Somente o dono pode gerenciar este portfólio.']);
            exit;
        }
        $_SESSION['portfolio_error'] = 'Somente o dono pode gerenciar este portfólio.';
        header('Location: /perfil/portfolio');
        exit;
    }

    private function requirePortfolioItemEdit(array $item, int $currentUserId): void
    {
        $itemId = (int)($item['id'] ?? 0);
        $ownerId = (int)($item['user_id'] ?? 0);

        if ($itemId <= 0 || $ownerId <= 0) {
            if ($this->wantsJson()) {
                http_response_code(404);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'error' => 'Item do portfólio não encontrado.']);
                exit;
            }
            $_SESSION['portfolio_error'] = 'Item do portfólio não encontrado.';
            header('Location: /perfil/portfolio');
            exit;
        }

        if ($ownerId === $currentUserId) {
            return;
        }

        if (!SocialPortfolioCollaborator::canEditItem($ownerId, $currentUserId, $itemId)) {
            if ($this->wantsJson()) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'error' => 'Sem permissão para editar este item do portfólio.']);
                exit;
            }
            $_SESSION['portfolio_error'] = 'Sem permissão para editar este item do portfólio.';
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

        $isOwn = $targetId === $currentId;

        // Para o próprio usuário: mostra tudo dele (draft/published) + itens compartilhados com permissão de edição.
        // Para visitantes: mostra somente publicados do usuário alvo.
        $items = $isOwn ? SocialPortfolioItem::allForUser($targetId, 200) : SocialPortfolioItem::publishedForUser($targetId, 200);
        if ($isOwn) {
            $shared = SocialPortfolioItem::sharedForCollaborator($currentId, true, 200);
            if (!empty($shared)) {
                $byId = [];
                foreach ($items as $it) {
                    $byId[(int)($it['id'] ?? 0)] = $it;
                }
                foreach ($shared as $it) {
                    $iid = (int)($it['id'] ?? 0);
                    if ($iid > 0 && !isset($byId[$iid])) {
                        $it['is_shared'] = 1;
                        $items[] = $it;
                    }
                }
            }
        }
        foreach ($items as &$it) {
            $iid = (int)($it['id'] ?? 0);
            if ($iid <= 0) {
                continue;
            }
            if (!empty($it['cover_url'])) {
                continue;
            }
            $autoCover = SocialPortfolioBlock::firstCoverUrlForItem($iid);
            if ($autoCover) {
                $it['cover_url'] = $autoCover;
            }
        }
        unset($it);

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
            'isOwn' => $isOwn,
            'canManage' => $isOwn,
        ]);
    }

    public function manage(): void
    {
        $currentUser = $this->requireLogin();
        $currentId = (int)($currentUser['id'] ?? 0);
        $ownerId = $this->getOwnerIdFromRequest($currentId);
        $this->requirePortfolioOwner($ownerId, $currentId);

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

        $editId = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
        $editItem = null;
        if ($editId > 0) {
            foreach ($items as $it) {
                if ((int)($it['id'] ?? 0) === $editId) {
                    $editItem = $it;
                    break;
                }
            }
        }

        $editBlocks = [];
        if (!empty($editItem) && $editId > 0) {
            $editBlocks = SocialPortfolioBlock::allForItem($editId);
        }

        $canShare = true;
        $collaborators = SocialPortfolioCollaborator::allWithUsers($ownerId);
        $pendingInvites = SocialPortfolioInvitation::allPendingForOwner($ownerId);

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
            'editItem' => $editItem,
            'editBlocks' => $editBlocks,
        ]);
    }

    public function upsert(): void
    {
        $currentUser = $this->requireLogin();
        $currentId = (int)($currentUser['id'] ?? 0);
        $ownerId = $this->getOwnerIdFromRequest($currentId);
        $this->requirePortfolioOwner($ownerId, $currentId);

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $title = trim((string)($_POST['title'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $externalUrl = trim((string)($_POST['external_url'] ?? ''));
        $projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;

        if ($title === '') {
            $_SESSION['portfolio_error'] = 'Informe um título.';
            header('Location: /perfil/portfolio/gerenciar?owner_user_id=' . $ownerId);
            exit;
        }

        if ($externalUrl !== '' && !preg_match('/^https?:\/\//i', $externalUrl)) {
            $externalUrl = 'https://' . $externalUrl;
        }

        if ($id > 0) {
            SocialPortfolioItem::update($id, $ownerId, $title, $description !== '' ? $description : null, $externalUrl !== '' ? $externalUrl : null, $projectId > 0 ? $projectId : null);
            $_SESSION['portfolio_success'] = 'Portfólio atualizado.';
            header('Location: /perfil/portfolio/gerenciar?owner_user_id=' . $ownerId . '&edit_id=' . $id);
            exit;
        } else {
            $newId = SocialPortfolioItem::create($ownerId, $title, $description !== '' ? $description : null, $externalUrl !== '' ? $externalUrl : null, $projectId > 0 ? $projectId : null);
            if ($newId <= 0) {
                $_SESSION['portfolio_error'] = 'Não foi possível criar o item do portfólio.';
                header('Location: /perfil/portfolio/gerenciar?owner_user_id=' . $ownerId);
                exit;
            }
            $_SESSION['portfolio_success'] = 'Portfólio criado.';
            header('Location: /perfil/portfolio/gerenciar?owner_user_id=' . $ownerId . '&edit_id=' . $newId);
            exit;
        }
    }

    public function delete(): void
    {
        $currentUser = $this->requireLogin();
        $currentId = (int)($currentUser['id'] ?? 0);
        $ownerId = $this->getOwnerIdFromRequest($currentId);
        $this->requirePortfolioOwner($ownerId, $currentId);

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
        $blocks = SocialPortfolioBlock::allForItem($id);
        $likesCount = SocialPortfolioLike::countForItem($id);
        $isLiked = $currentId > 0 ? SocialPortfolioLike::isLikedByUser($id, $currentId) : false;

        $status = isset($item['status']) ? (string)$item['status'] : 'published';
        if ($status === 'draft' && $ownerId !== $currentId) {
            $_SESSION['portfolio_error'] = 'Este projeto ainda está em rascunho.';
            header('Location: /perfil/portfolio?user_id=' . $ownerId);
            exit;
        }

        if ($ownerId !== $currentId && !SocialPortfolioCollaborator::canReadItem($ownerId, $currentId, $id)) {
            $_SESSION['portfolio_error'] = 'Sem permissão para ver este item do portfólio.';
            header('Location: /perfil/portfolio?user_id=' . $ownerId);
            exit;
        }

        $canEdit = $ownerId === $currentId ? true : SocialPortfolioCollaborator::canEditItem($ownerId, $currentId, $id);

        $collabUsers = SocialPortfolioCollaborator::allForItemWithUsers($ownerId, $id);

        $this->view('social/portfolio_view', [
            'pageTitle' => 'Portfólio - ' . (string)($item['title'] ?? 'Item'),
            'user' => $currentUser,
            'profileUser' => $profileUser,
            'profile' => $profile,
            'item' => $item,
            'media' => $media,
            'blocks' => $blocks,
            'likesCount' => $likesCount,
            'isLiked' => $isLiked,
            'isOwner' => $ownerId === $currentId,
            'canEdit' => $canEdit,
            'collaboratorsForItem' => $collabUsers,
        ]);
    }

    public function editor(): void
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

        $status = isset($item['status']) ? (string)$item['status'] : 'published';
        if ($status === 'draft' && $ownerId !== $currentId) {
            if ($this->wantsJson()) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'error' => 'Este projeto ainda está em rascunho.']);
                exit;
            }
            $_SESSION['portfolio_error'] = 'Este projeto ainda está em rascunho.';
            header('Location: /perfil/portfolio?user_id=' . $ownerId);
            exit;
        }

        $this->requirePortfolioItemEdit($item, $currentId);

        $blocks = SocialPortfolioBlock::allForItem($id);

        $this->view('social/portfolio_editor', [
            'pageTitle' => 'Editor - ' . (string)($item['title'] ?? 'Projeto'),
            'user' => $currentUser,
            'profileUser' => $profileUser,
            'item' => $item,
            'blocks' => $blocks,
            'isOwner' => $ownerId === $currentId,
            'canEdit' => true,
        ]);
    }

    public function saveBlocks(): void
    {
        $currentUser = $this->requireLogin();
        $currentId = (int)($currentUser['id'] ?? 0);

        $itemId = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
        $item = $itemId > 0 ? SocialPortfolioItem::findById($itemId) : null;
        if (!$item) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Item não encontrado.']);
            return;
        }
        $this->requirePortfolioItemEdit($item, $currentId);

        $raw = (string)($_POST['blocks_json'] ?? '');
        if (trim($raw) === '') {
            $raw = '[]';
        }
        $parsed = json_decode($raw, true);
        if (!is_array($parsed)) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'JSON inválido.']);
            return;
        }

        $before = $parsed;
        $parsed = $this->sanitizeBlocksForSave($parsed);
        $embedErrors = $this->getEmbedSanitizationErrors($before, $parsed);

        SocialPortfolioBlock::replaceForItem($itemId, $parsed);

        $cover = SocialPortfolioBlock::firstCoverUrlForItem($itemId);
        SocialPortfolioItem::updateCover($itemId, (int)($item['user_id'] ?? 0), $cover, null);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true, 'cover_url' => $cover, 'warnings' => $embedErrors]);
    }

    public function publishItem(): void
    {
        $currentUser = $this->requireLogin();
        $currentId = (int)($currentUser['id'] ?? 0);

        $itemId = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
        $item = $itemId > 0 ? SocialPortfolioItem::findById($itemId) : null;
        if (!$item) {
            if ($this->wantsJson()) {
                http_response_code(404);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'error' => 'Item não encontrado.']);
                return;
            }
            $_SESSION['portfolio_error'] = 'Item não encontrado.';
            header('Location: /perfil/portfolio/gerenciar');
            exit;
        }
        $this->requirePortfolioItemEdit($item, $currentId);

        SocialPortfolioItem::publish($itemId, (int)($item['user_id'] ?? 0));

        if ($this->wantsJson()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => true]);
            return;
        }
        $_SESSION['portfolio_success'] = 'Projeto publicado.';
        header('Location: /perfil/portfolio/ver?id=' . $itemId);
        exit;
    }

    public function unpublishItem(): void
    {
        $currentUser = $this->requireLogin();
        $currentId = (int)($currentUser['id'] ?? 0);

        $itemId = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
        $item = $itemId > 0 ? SocialPortfolioItem::findById($itemId) : null;
        if (!$item) {
            if ($this->wantsJson()) {
                http_response_code(404);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'error' => 'Item não encontrado.']);
                return;
            }
            $_SESSION['portfolio_error'] = 'Item não encontrado.';
            header('Location: /perfil/portfolio/gerenciar');
            exit;
        }
        $this->requirePortfolioItemEdit($item, $currentId);

        SocialPortfolioItem::unpublish($itemId, (int)($item['user_id'] ?? 0));

        if ($this->wantsJson()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => true]);
            return;
        }
        $_SESSION['portfolio_success'] = 'Projeto despublicado.';
        header('Location: /perfil/portfolio/ver?id=' . $itemId);
        exit;
    }

    public function uploadBlockMedia(): void
    {
        $currentUser = $this->requireLogin();
        $currentId = (int)($currentUser['id'] ?? 0);

        $itemId = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
        $item = $itemId > 0 ? SocialPortfolioItem::findById($itemId) : null;
        if (!$item) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Item não encontrado.']);
            return;
        }
        $this->requirePortfolioItemEdit($item, $currentId);

        if (empty($_FILES['file']) || !is_array($_FILES['file'])) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Envie um arquivo.']);
            return;
        }

        $err = (int)($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err !== UPLOAD_ERR_OK) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Erro no upload.']);
            return;
        }

        $tmp = (string)($_FILES['file']['tmp_name'] ?? '');
        $originalName = (string)($_FILES['file']['name'] ?? 'arquivo');
        $type = (string)($_FILES['file']['type'] ?? '');
        $size = (int)($_FILES['file']['size'] ?? 0);
        if (!is_file($tmp) || $size <= 0) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Arquivo inválido.']);
            return;
        }

        $mimeLower = strtolower($type);
        $isVideoOrAudio = $mimeLower !== '' && (str_starts_with($mimeLower, 'video/') || str_starts_with($mimeLower, 'audio/'));
        $remoteUrl = null;
        if ($isVideoOrAudio) {
            $defaultVideoEndpoint = defined('MEDIA_VIDEO_UPLOAD_ENDPOINT') ? MEDIA_VIDEO_UPLOAD_ENDPOINT : '';
            $endpoint = trim(Setting::get('media_video_upload_endpoint', $defaultVideoEndpoint));
            $remoteUrl = $endpoint !== ''
                ? MediaStorageService::uploadFileToEndpoint($tmp, $originalName, $type, $endpoint)
                : MediaStorageService::uploadFile($tmp, $originalName, $type);
        } else {
            $remoteUrl = MediaStorageService::uploadFile($tmp, $originalName, $type);
        }
        if (!is_string($remoteUrl) || trim($remoteUrl) === '') {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Não foi possível enviar a mídia para o servidor.']);
            return;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => true,
            'url' => $remoteUrl,
            'mime_type' => $type !== '' ? $type : null,
            'size_bytes' => $size,
            'title' => $originalName,
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
        $itemId = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
        $item = $itemId > 0 ? SocialPortfolioItem::findById($itemId) : null;
        if (!$item) {
            $_SESSION['portfolio_error'] = 'Item não encontrado.';
            header('Location: /perfil/portfolio');
            exit;
        }
        $this->requirePortfolioItemEdit($item, $currentId);

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

        $mimeLower = strtolower($type);
        $isImage = $mimeLower !== '' && str_starts_with($mimeLower, 'image/');
        $isVideoOrAudio = $mimeLower !== '' && (str_starts_with($mimeLower, 'video/') || str_starts_with($mimeLower, 'audio/'));
        $kind = $isImage ? 'image' : 'file';

        $remoteUrl = null;
        if ($isVideoOrAudio) {
            $defaultVideoEndpoint = defined('MEDIA_VIDEO_UPLOAD_ENDPOINT') ? MEDIA_VIDEO_UPLOAD_ENDPOINT : '';
            $endpoint = trim(Setting::get('media_video_upload_endpoint', $defaultVideoEndpoint));
            $remoteUrl = $endpoint !== ''
                ? MediaStorageService::uploadFileToEndpoint($tmp, $originalName, $type, $endpoint)
                : MediaStorageService::uploadFile($tmp, $originalName, $type);
        } else {
            $remoteUrl = MediaStorageService::uploadFile($tmp, $originalName, $type);
        }
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
        $itemId = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
        $mediaId = isset($_POST['media_id']) ? (int)$_POST['media_id'] : 0;

        $item = $itemId > 0 ? SocialPortfolioItem::findById($itemId) : null;
        if (!$item) {
            $_SESSION['portfolio_error'] = 'Item não encontrado.';
            header('Location: /perfil/portfolio');
            exit;
        }
        $this->requirePortfolioItemEdit($item, $currentId);

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

        $portfolioItemId = isset($_POST['portfolio_item_id']) ? (int)$_POST['portfolio_item_id'] : 0;
        $portfolioItem = $portfolioItemId > 0 ? SocialPortfolioItem::findById($portfolioItemId) : null;
        if (!$portfolioItem || (int)($portfolioItem['user_id'] ?? 0) !== $ownerId) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Selecione um item válido do portfólio.']);
            return;
        }

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
        if ($invitedUserId > 0 && SocialPortfolioCollaborator::canReadItem($ownerId, $invitedUserId, $portfolioItemId)) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Este usuário já tem acesso a este item do portfólio.']);
            return;
        }

        if (SocialPortfolioInvitation::hasValidInviteForEmail($ownerId, $portfolioItemId, $invitedEmail)) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Já existe um convite pendente para este e-mail.']);
            return;
        }

        $token = bin2hex(random_bytes(16));
        SocialPortfolioInvitation::create($ownerId, $portfolioItemId, $currentId, $invitedEmail, null, $role, $token);

        $ownerUser = User::findById($ownerId);
        $ownerName = $ownerUser ? trim((string)($ownerUser['preferred_name'] ?? $ownerUser['name'] ?? '')) : 'Portfólio';
        if ($ownerName === '') {
            $ownerName = 'Portfólio';
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $link = $scheme . $host . '/perfil/portfolio/aceitar-convite?token=' . urlencode($token);

        $itemTitle = trim((string)($portfolioItem['title'] ?? ''));
        if ($itemTitle === '') {
            $itemTitle = 'item do portfólio';
        }

        $subject = 'Convite para colaborar no portfólio de ' . $ownerName;

        $toName = trim((string)($invitedUser['preferred_name'] ?? ''));
        if ($toName === '') {
            $toName = trim((string)($invitedUser['name'] ?? ''));
        }
        if ($toName === '') {
            $toName = $invitedEmail;
        }

        $safeOwnerName = htmlspecialchars($ownerName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeItemTitle = htmlspecialchars($itemTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $roleLabel = $role === 'edit' ? 'Edição' : 'Leitura';
        $safeRole = htmlspecialchars($roleLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $contentHtml = '<p style="font-size:14px; margin:0 0 10px 0;">Você foi convidado para colaborar no portfólio de <strong>' . $safeOwnerName . '</strong> no Tuquinha.</p>'
            . '<p style="font-size:14px; margin:0 0 10px 0;">Item: <strong>' . $safeItemTitle . '</strong></p>'
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
        $portfolioItemId = (int)($invite['portfolio_item_id'] ?? 0);
        $role = (string)($invite['role'] ?? 'read');
        if ($ownerId <= 0 || $portfolioItemId <= 0) {
            $_SESSION['portfolio_error'] = 'Portfólio do convite não encontrado.';
            header('Location: /perfil/portfolio');
            exit;
        }

        $portfolioItem = SocialPortfolioItem::findById($portfolioItemId);
        if (!$portfolioItem || (int)($portfolioItem['user_id'] ?? 0) !== $ownerId) {
            $_SESSION['portfolio_error'] = 'Item do portfólio do convite não encontrado.';
            header('Location: /perfil/portfolio');
            exit;
        }

        SocialPortfolioCollaborator::addOrUpdate($ownerId, $currentId, $portfolioItemId, $role);
        SocialPortfolioInvitation::markAccepted((int)($invite['id'] ?? 0));

        $_SESSION['portfolio_success'] = 'Convite aceito. Você agora tem acesso a este item do portfólio.';
        header('Location: /perfil/portfolio/ver?id=' . $portfolioItemId);
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
        $portfolioItemId = isset($_POST['portfolio_item_id']) ? (int)$_POST['portfolio_item_id'] : 0;
        $collabUserId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        $role = trim((string)($_POST['role'] ?? 'read'));

        $this->requirePortfolioShare($ownerId, $currentId);
        $portfolioItem = $portfolioItemId > 0 ? SocialPortfolioItem::findById($portfolioItemId) : null;
        if ($ownerId <= 0 || $portfolioItemId <= 0 || !$portfolioItem || (int)($portfolioItem['user_id'] ?? 0) !== $ownerId || $collabUserId <= 0 || $collabUserId === $ownerId) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Colaborador inválido.']);
            return;
        }

        SocialPortfolioCollaborator::updateRole($ownerId, $collabUserId, $portfolioItemId, $role);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true]);
    }

    public function removeCollaborator(): void
    {
        $user = $this->requireLogin();
        $currentId = (int)($user['id'] ?? 0);

        $ownerId = isset($_POST['owner_user_id']) ? (int)$_POST['owner_user_id'] : $currentId;
        $portfolioItemId = isset($_POST['portfolio_item_id']) ? (int)$_POST['portfolio_item_id'] : 0;
        $collabUserId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

        $this->requirePortfolioShare($ownerId, $currentId);
        $portfolioItem = $portfolioItemId > 0 ? SocialPortfolioItem::findById($portfolioItemId) : null;
        if ($ownerId <= 0 || $portfolioItemId <= 0 || !$portfolioItem || (int)($portfolioItem['user_id'] ?? 0) !== $ownerId || $collabUserId <= 0 || $collabUserId === $ownerId) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Colaborador inválido.']);
            return;
        }

        SocialPortfolioCollaborator::remove($ownerId, $collabUserId, $portfolioItemId);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true]);
    }
}
