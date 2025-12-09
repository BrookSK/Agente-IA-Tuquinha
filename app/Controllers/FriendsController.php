<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\UserFriend;

class FriendsController extends Controller
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

    public function index(): void
    {
        $user = $this->requireLogin();
        $userId = (int)$user['id'];

        $friends = UserFriend::friendsWithUsers($userId);
        $pending = UserFriend::pendingForUser($userId);

        $success = $_SESSION['friends_success'] ?? null;
        $error = $_SESSION['friends_error'] ?? null;
        unset($_SESSION['friends_success'], $_SESSION['friends_error']);

        $this->view('social/friends', [
            'pageTitle' => 'Amigos - Orkut do Tuquinha',
            'user' => $user,
            'friends' => $friends,
            'pending' => $pending,
            'success' => $success,
            'error' => $error,
        ]);
    }

    public function request(): void
    {
        $user = $this->requireLogin();
        $fromUserId = (int)$user['id'];

        $otherUserId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        if ($otherUserId <= 0 || $otherUserId === $fromUserId) {
            $_SESSION['friends_error'] = 'Usuário inválido para pedido de amizade.';
            header('Location: /amigos');
            exit;
        }

        $other = User::findById($otherUserId);
        if (!$other) {
            $_SESSION['friends_error'] = 'Usuário não encontrado.';
            header('Location: /amigos');
            exit;
        }

        UserFriend::request($fromUserId, $otherUserId);

        $_SESSION['friends_success'] = 'Pedido de amizade enviado.';
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
}
