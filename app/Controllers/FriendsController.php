<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\UserFriend;

class FriendsController extends Controller
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

    public function index(): void
    {
        $user = $this->requireLogin();
        $userId = (int)$user['id'];

        $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        $q = preg_replace('/\s+/', ' ', (string)$q);

        $fav = isset($_GET['fav']) ? trim((string)$_GET['fav']) : '';
        $onlyFavorites = ($fav === '1' || strtolower($fav) === 'true');

        $friends = UserFriend::friendsWithUsers($userId, $q, $onlyFavorites);
        $pending = UserFriend::pendingForUser($userId);

        $success = $_SESSION['friends_success'] ?? null;
        $error = $_SESSION['friends_error'] ?? null;
        unset($_SESSION['friends_success'], $_SESSION['friends_error']);

        $this->view('social/friends', [
            'pageTitle' => 'Amigos do Tuquinha',
            'user' => $user,
            'friends' => $friends,
            'pending' => $pending,
            'success' => $success,
            'error' => $error,
            'q' => $q,
            'onlyFavorites' => $onlyFavorites,
        ]);
    }

    public function add(): void
    {
        $user = $this->requireLogin();
        $this->view('social/friends_add', [
            'pageTitle' => 'Adicionar amigo',
            'user' => $user,
        ]);
    }

    public function search(): void
    {
        $user = $this->requireLogin();
        $userId = (int)($user['id'] ?? 0);

        $q = trim((string)($_GET['q'] ?? ''));
        $q = ltrim($q, '@');
        $q = trim($q);

        if ($q === '') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => true, 'items' => []]);
            return;
        }

        $items = User::searchForFriend($q, $userId, 10);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true, 'items' => $items]);
    }

    public function request(): void
    {
        $user = $this->requireLogin();
        $fromUserId = (int)$user['id'];

        $otherUserId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        if ($otherUserId <= 0 || $otherUserId === $fromUserId) {
            if ($this->wantsJson()) {
                http_response_code(422);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'error' => 'Usuário inválido para pedido de amizade.']);
                return;
            }
            $_SESSION['friends_error'] = 'Usuário inválido para pedido de amizade.';
            header('Location: /amigos');
            exit;
        }

        $other = User::findById($otherUserId);
        if (!$other) {
            if ($this->wantsJson()) {
                http_response_code(404);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'error' => 'Usuário não encontrado.']);
                return;
            }
            $_SESSION['friends_error'] = 'Usuário não encontrado.';
            header('Location: /amigos');
            exit;
        }

        UserFriend::request($fromUserId, $otherUserId);

        if ($this->wantsJson()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => true]);
            return;
        }

        $_SESSION['friends_success'] = 'Pedido de amizade enviado.';
        header('Location: /perfil?user_id=' . $otherUserId);
        exit;
    }

    public function cancelRequest(): void
    {
        $user = $this->requireLogin();
        $fromUserId = (int)$user['id'];

        $otherUserId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        if ($otherUserId <= 0 || $otherUserId === $fromUserId) {
            if ($this->wantsJson()) {
                http_response_code(422);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'error' => 'Usuário inválido para cancelar pedido.']);
                return;
            }
            $_SESSION['friends_error'] = 'Usuário inválido para cancelar pedido.';
            header('Location: /amigos');
            exit;
        }

        $ok = UserFriend::cancelRequest($fromUserId, $otherUserId);
        if (!$ok) {
            if ($this->wantsJson()) {
                http_response_code(404);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'error' => 'Pedido de amizade não encontrado para cancelamento.']);
                return;
            }
            $_SESSION['friends_error'] = 'Pedido de amizade não encontrado para cancelamento.';
            header('Location: /amigos');
            exit;
        }

        if ($this->wantsJson()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => true]);
            return;
        }

        $_SESSION['friends_success'] = 'Pedido de amizade cancelado.';
        header('Location: /perfil?user_id=' . $otherUserId);
        exit;
    }

    public function decide(): void
    {
        $user = $this->requireLogin();
        $currentUserId = (int)$user['id'];

        $otherUserId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        $decision = (string)($_POST['decision'] ?? '');

        if ($otherUserId <= 0 || $otherUserId === $currentUserId) {
            $_SESSION['friends_error'] = 'Pedido de amizade inválido.';
            header('Location: /amigos');
            exit;
        }

        UserFriend::decide($currentUserId, $otherUserId, $decision);

        $_SESSION['friends_success'] = 'Decisão de amizade registrada.';
        header('Location: /amigos');
        exit;
    }

    public function remove(): void
    {
        $user = $this->requireLogin();
        $currentUserId = (int)$user['id'];

        $otherUserId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        if ($otherUserId <= 0 || $otherUserId === $currentUserId) {
            if ($this->wantsJson()) {
                http_response_code(422);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'error' => 'Amigo inválido.']);
                return;
            }
            $_SESSION['friends_error'] = 'Amigo inválido.';
            header('Location: /amigos');
            exit;
        }

        $friendship = UserFriend::findFriendship($currentUserId, $otherUserId);
        if (!$friendship || ($friendship['status'] ?? '') !== 'accepted') {
            if ($this->wantsJson()) {
                http_response_code(404);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'error' => 'Amizade não encontrada.']);
                return;
            }
            $_SESSION['friends_error'] = 'Amizade não encontrada.';
            header('Location: /amigos');
            exit;
        }

        UserFriend::removeFriendship($currentUserId, $otherUserId);

        if ($this->wantsJson()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => true]);
            return;
        }

        $_SESSION['friends_success'] = 'Amizade removida.';
        header('Location: /amigos');
        exit;
    }

    public function favorite(): void
    {
        $user = $this->requireLogin();
        $currentUserId = (int)$user['id'];

        $otherUserId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        $isFavorite = !empty($_POST['is_favorite']);

        if ($otherUserId <= 0 || $otherUserId === $currentUserId) {
            if ($this->wantsJson()) {
                http_response_code(422);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'error' => 'Amigo inválido.']);
                return;
            }
            $_SESSION['friends_error'] = 'Amigo inválido.';
            header('Location: /amigos');
            exit;
        }

        UserFriend::setFavorite($currentUserId, $otherUserId, $isFavorite);

        if ($this->wantsJson()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => true]);
            return;
        }

        header('Location: /perfil?user_id=' . $otherUserId);
        exit;
    }
}
