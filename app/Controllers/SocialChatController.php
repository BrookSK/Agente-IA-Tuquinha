<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\UserFriend;
use App\Models\SocialConversation;
use App\Models\SocialMessage;

class SocialChatController extends Controller
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

    private function ensureFriends(int $currentUserId, int $otherUserId): void
    {
        if ($currentUserId <= 0 || $otherUserId <= 0 || $currentUserId === $otherUserId) {
            header('Location: /amigos');
            exit;
        }

        $friendship = UserFriend::findFriendship($currentUserId, $otherUserId);
        if (!$friendship || ($friendship['status'] ?? '') !== 'accepted') {
            $_SESSION['social_error'] = 'Você só pode conversar no chat privado com amigos aceitos.';
            header('Location: /perfil?user_id=' . $otherUserId);
            exit;
        }
    }

    public function open(): void
    {
        $currentUser = $this->requireLogin();
        $currentId = (int)$currentUser['id'];

        $conversationId = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;
        $otherUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

        $conversation = null;
        $otherUser = null;

        if ($conversationId > 0) {
            $conversation = SocialConversation::findById($conversationId);
            if (!$conversation) {
                header('Location: /amigos');
                exit;
            }

            $u1 = (int)($conversation['user1_id'] ?? 0);
            $u2 = (int)($conversation['user2_id'] ?? 0);
            if ($currentId !== $u1 && $currentId !== $u2) {
                header('Location: /amigos');
                exit;
            }

            $otherUserId = $currentId === $u1 ? $u2 : $u1;
            $otherUser = User::findById($otherUserId);
            if (!$otherUser) {
                header('Location: /amigos');
                exit;
            }
        } elseif ($otherUserId > 0) {
            $otherUser = User::findById($otherUserId);
            if (!$otherUser) {
                header('Location: /amigos');
                exit;
            }

            $this->ensureFriends($currentId, $otherUserId);
            $conversation = SocialConversation::findOrCreateForUsers($currentId, $otherUserId);
            $conversationId = (int)$conversation['id'];
        } else {
            header('Location: /amigos');
            exit;
        }

        $messages = SocialMessage::allForConversation($conversationId, 200);
        SocialMessage::markAsRead($conversationId, $currentId);

        $this->view('social/chat_thread', [
            'pageTitle' => 'Chat social',
            'user' => $currentUser,
            'otherUser' => $otherUser,
            'conversation' => $conversation,
            'messages' => $messages,
        ]);
    }

    public function send(): void
    {
        $currentUser = $this->requireLogin();
        $currentId = (int)$currentUser['id'];

        $conversationId = isset($_POST['conversation_id']) ? (int)$_POST['conversation_id'] : 0;
        $body = trim((string)($_POST['body'] ?? ''));

        if ($conversationId <= 0) {
            header('Location: /amigos');
            exit;
        }

        $conversation = SocialConversation::findById($conversationId);
        if (!$conversation) {
            header('Location: /amigos');
            exit;
        }

        $u1 = (int)($conversation['user1_id'] ?? 0);
        $u2 = (int)($conversation['user2_id'] ?? 0);
        if ($currentId !== $u1 && $currentId !== $u2) {
            header('Location: /amigos');
            exit;
        }

        $otherUserId = $currentId === $u1 ? $u2 : $u1;
        $this->ensureFriends($currentId, $otherUserId);

        if ($body !== '') {
            SocialMessage::create([
                'conversation_id' => $conversationId,
                'sender_user_id' => $currentId,
                'body' => $body,
            ]);
            SocialConversation::touchWithMessage($conversationId, $body);
        }

        header('Location: /social/chat?conversation_id=' . $conversationId);
        exit;
    }
}
