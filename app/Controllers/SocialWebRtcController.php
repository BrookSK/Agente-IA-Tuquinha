<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\SocialConversation;
use App\Models\User;
use App\Models\UserFriend;
use PDO;

class SocialWebRtcController extends Controller
{
    private function requireLogin(): array
    {
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false]);
            exit;
        }

        $user = User::findById((int)$_SESSION['user_id']);
        if (!$user) {
            unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email']);
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false]);
            exit;
        }

        return $user;
    }

    private function ensureParticipantAndFriends(int $currentUserId, int $conversationId): array
    {
        $conversation = SocialConversation::findById($conversationId);
        if (!$conversation) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Conversa não encontrada.']);
            exit;
        }

        $u1 = (int)($conversation['user1_id'] ?? 0);
        $u2 = (int)($conversation['user2_id'] ?? 0);
        if ($currentUserId !== $u1 && $currentUserId !== $u2) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Você não participa desta conversa.']);
            exit;
        }

        $otherUserId = $currentUserId === $u1 ? $u2 : $u1;
        $friendship = UserFriend::findFriendship($currentUserId, $otherUserId);
        if (!$friendship || ($friendship['status'] ?? '') !== 'accepted') {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Você só pode usar chamada com amigos aceitos.']);
            exit;
        }

        return [$conversation, $otherUserId];
    }

    public function send(): void
    {
        $currentUser = $this->requireLogin();
        $currentId = (int)$currentUser['id'];

        $conversationId = isset($_POST['conversation_id']) ? (int)$_POST['conversation_id'] : 0;
        $kind = trim((string)($_POST['kind'] ?? ''));
        $payload = $_POST['payload'] ?? null;
        if (is_string($payload)) {
            $decoded = json_decode($payload, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $payload = $decoded;
            }
        }

        if ($conversationId <= 0) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Conversa inválida.']);
            return;
        }

        if (!in_array($kind, ['offer', 'answer', 'ice', 'end', 'typing'], true)) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Tipo inválido.']);
            return;
        }

        [$conversation, $otherUserId] = $this->ensureParticipantAndFriends($currentId, $conversationId);

        if (function_exists('session_write_close')) {
            @session_write_close();
        }

        $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($payloadJson === false) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Payload inválido.']);
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO social_webrtc_signals (conversation_id, from_user_id, to_user_id, kind, payload_json, created_at)
            VALUES (:cid, :from_uid, :to_uid, :kind, :payload_json, NOW())');
        $stmt->execute([
            'cid' => $conversationId,
            'from_uid' => $currentId,
            'to_uid' => $otherUserId,
            'kind' => $kind,
            'payload_json' => $payloadJson,
        ]);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true]);
    }

    public function poll(): void
    {
        $currentUser = $this->requireLogin();
        $currentId = (int)$currentUser['id'];

        $conversationId = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;
        $sinceId = isset($_GET['since_id']) ? (int)$_GET['since_id'] : 0;

        if ($conversationId <= 0) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Conversa inválida.']);
            return;
        }

        $this->ensureParticipantAndFriends($currentId, $conversationId);

        if (function_exists('session_write_close')) {
            @session_write_close();
        }

        $pdo = Database::getConnection();

        $deadline = microtime(true) + 25.0;
        $events = [];

        while (microtime(true) < $deadline) {
            $stmt = $pdo->prepare('SELECT id, kind, payload_json, from_user_id, created_at
                FROM social_webrtc_signals
                WHERE conversation_id = :cid
                  AND to_user_id = :uid
                  AND delivered_at IS NULL
                  AND id > :since_id
                ORDER BY id ASC
                LIMIT 20');
            $stmt->execute([
                'cid' => $conversationId,
                'uid' => $currentId,
                'since_id' => $sinceId,
            ]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            if (!empty($rows)) {
                $ids = [];
                foreach ($rows as $row) {
                    $id = (int)($row['id'] ?? 0);
                    if ($id > 0) {
                        $ids[] = $id;
                    }
                    $decoded = null;
                    $raw = (string)($row['payload_json'] ?? '');
                    if ($raw !== '') {
                        $decoded = json_decode($raw, true);
                    }
                    $events[] = [
                        'id' => $id,
                        'kind' => (string)($row['kind'] ?? ''),
                        'from_user_id' => (int)($row['from_user_id'] ?? 0),
                        'payload' => $decoded,
                        'created_at' => (string)($row['created_at'] ?? ''),
                    ];
                    $sinceId = max($sinceId, $id);
                }

                if (!empty($ids)) {
                    $in = implode(',', array_fill(0, count($ids), '?'));
                    $upd = $pdo->prepare('UPDATE social_webrtc_signals SET delivered_at = NOW() WHERE id IN (' . $in . ')');
                    $upd->execute($ids);
                }

                break;
            }

            usleep(400000);
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => true,
            'events' => $events,
            'since_id' => $sinceId,
        ]);
    }
}
