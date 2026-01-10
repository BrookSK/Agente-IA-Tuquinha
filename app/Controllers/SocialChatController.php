<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserFriend;
use App\Models\SocialConversation;
use App\Models\SocialMessage;
use App\Models\Subscription;

class SocialChatController extends Controller
{
    private function getActivePlanForUser(array $user): ?array
    {
        if (!empty($_SESSION['is_admin'])) {
            return Plan::findTopActive();
        }

        $email = trim((string)($user['email'] ?? ''));
        if ($email === '') {
            return null;
        }

        $subscription = Subscription::findLastByEmail($email);
        if (!$subscription || empty($subscription['plan_id'])) {
            return null;
        }

        $status = strtolower((string)($subscription['status'] ?? ''));
        if (in_array($status, ['canceled', 'expired'], true)) {
            return null;
        }

        $plan = Plan::findById((int)$subscription['plan_id']);
        return $plan ?: null;
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

    public function stream(): void
    {
        $currentUser = $this->requireLogin();
        $currentId = (int)$currentUser['id'];

        $conversationId = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;
        $lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

        if ($conversationId <= 0) {
            http_response_code(400);
            return;
        }

        $this->ensureConversationAccess($currentId, $conversationId);

        if (function_exists('session_write_close')) {
            @session_write_close();
        }

        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache, no-transform');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');
        header('Content-Encoding: none');

        if (function_exists('ini_set')) {
            @ini_set('output_buffering', 'off');
            @ini_set('zlib.output_compression', '0');
            @ini_set('implicit_flush', '1');
        }

        @ignore_user_abort(true);
        @set_time_limit(0);

        while (ob_get_level() > 0) {
            @ob_end_flush();
        }

        echo "event: ping\n";
        echo "data: {}\n\n";
        @flush();

        $deadline = microtime(true) + 25.0;

        while (microtime(true) < $deadline) {
            if (connection_aborted()) {
                break;
            }

            $rows = SocialMessage::sinceId($conversationId, $lastId, 50);
            if (!empty($rows)) {
                foreach ($rows as $row) {
                    $id = (int)($row['id'] ?? 0);
                    $lastId = max($lastId, $id);
                    echo "event: message\n";
                    echo 'data: ' . json_encode([
                        'id' => $id,
                        'conversation_id' => (int)($row['conversation_id'] ?? 0),
                        'sender_user_id' => (int)($row['sender_user_id'] ?? 0),
                        'sender_name' => (string)($row['sender_name'] ?? ''),
                        'body' => (string)($row['body'] ?? ''),
                        'created_at' => (string)($row['created_at'] ?? ''),
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
                }
                @flush();
            } else {
                echo "event: ping\n";
                echo "data: {}\n\n";
                @flush();
                usleep(400000);
            }
        }

        echo "event: done\n";
        echo 'data: ' . json_encode(['last_id' => $lastId], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
        @flush();
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

    private function ensureConversationAccess(int $currentId, int $conversationId): array
    {
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

        return [$conversation, $otherUserId];
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
            [$conversation, $otherUserId] = $this->ensureConversationAccess($currentId, $conversationId);
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

        $plan = $this->getActivePlanForUser($currentUser);
        $canStartVideoCall = !empty($_SESSION['is_admin']) || (!empty($plan) && !empty($plan['allow_video_chat']));

        $this->view('social/chat_thread', [
            'pageTitle' => 'Chat social',
            'user' => $currentUser,
            'otherUser' => $otherUser,
            'conversation' => $conversation,
            'messages' => $messages,
            'canStartVideoCall' => $canStartVideoCall,
        ]);
    }

    public function send(): void
    {
        $currentUser = $this->requireLogin();
        $currentId = (int)$currentUser['id'];

        $conversationId = isset($_POST['conversation_id']) ? (int)$_POST['conversation_id'] : 0;
        $body = trim((string)($_POST['body'] ?? ''));
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (!empty($_POST['ajax']) && (string)$_POST['ajax'] === '1');

        if ($conversationId <= 0) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['ok' => false, 'error' => 'Conversa inválida.']);
                return;
            }

            header('Location: /amigos');
            exit;
        }

        $conversation = SocialConversation::findById($conversationId);
        if (!$conversation) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['ok' => false, 'error' => 'Conversa não encontrada.']);
                return;
            }

            header('Location: /amigos');
            exit;
        }

        $u1 = (int)($conversation['user1_id'] ?? 0);
        $u2 = (int)($conversation['user2_id'] ?? 0);
        if ($currentId !== $u1 && $currentId !== $u2) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['ok' => false, 'error' => 'Você não participa desta conversa.']);
                return;
            }

            header('Location: /amigos');
            exit;
        }

        $otherUserId = $currentId === $u1 ? $u2 : $u1;
        $this->ensureFriends($currentId, $otherUserId);

        if ($body === '') {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['ok' => false, 'error' => 'Mensagem vazia.']);
                return;
            }

            header('Location: /social/chat?conversation_id=' . $conversationId);
            exit;
        }

        $messageId = SocialMessage::create([
            'conversation_id' => $conversationId,
            'sender_user_id' => $currentId,
            'body' => $body,
        ]);
        SocialConversation::touchWithMessage($conversationId, $body);

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'ok' => true,
                'message' => [
                    'id' => $messageId,
                    'conversation_id' => $conversationId,
                    'sender_user_id' => $currentId,
                    'sender_name' => (string)($currentUser['name'] ?? ''),
                    'body' => $body,
                    'created_at' => date('Y-m-d H:i:s'),
                ],
            ]);
            return;
        }

        header('Location: /social/chat?conversation_id=' . $conversationId);
        exit;
    }
}
