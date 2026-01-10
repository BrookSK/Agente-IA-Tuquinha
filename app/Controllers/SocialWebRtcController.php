<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Plan;
use App\Models\SocialConversation;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserFriend;
use PDO;

class SocialWebRtcController extends Controller
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

        if (!in_array($kind, ['offer', 'answer', 'ice', 'end', 'typing', 'media'], true)) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Tipo inválido.']);
            return;
        }

        [$conversation, $otherUserId] = $this->ensureParticipantAndFriends($currentId, $conversationId);

        // Regra: apenas quem tem plano com allow_video_chat pode INICIAR (offer).
        // Receber/aceitar (answer) continua permitido.
        if ($kind === 'offer' && empty($_SESSION['is_admin'])) {
            $plan = $this->getActivePlanForUser($currentUser);
            if (!$plan || empty($plan['allow_video_chat'])) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'ok' => false,
                    'error' => 'Seu plano não permite iniciar chat de vídeo. Faça upgrade para um plano que inclua chamadas de vídeo.',
                    'code' => 'video_chat_not_allowed',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
        }

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

        if ($kind === 'answer') {
            $ackOffer = $pdo->prepare('UPDATE social_webrtc_signals
                SET delivered_at = NOW()
                WHERE conversation_id = :cid
                  AND to_user_id = :uid
                  AND kind = \'offer\'
                  AND delivered_at IS NULL');
            $ackOffer->execute([
                'cid' => $conversationId,
                'uid' => $currentId,
            ]);
        }

        if ($kind === 'end') {
            $cleanup = $pdo->prepare('UPDATE social_webrtc_signals
                SET delivered_at = NOW()
                WHERE conversation_id = :cid
                  AND delivered_at IS NULL
                  AND kind IN (\'offer\', \'answer\', \'ice\', \'typing\')
                  AND to_user_id IN (:u1, :u2)');
            $cleanup->execute([
                'cid' => $conversationId,
                'u1' => $currentId,
                'u2' => $otherUserId,
            ]);
        }

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
                  AND (id > :since_id OR kind = \'offer\')
                ORDER BY id ASC
                LIMIT 20');
            $stmt->execute([
                'cid' => $conversationId,
                'uid' => $currentId,
                'since_id' => $sinceId,
            ]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            if (!empty($rows)) {
                $idsToDeliver = [];
                foreach ($rows as $row) {
                    $id = (int)($row['id'] ?? 0);
                    $kind = (string)($row['kind'] ?? '');
                    if ($id > 0 && $kind !== 'offer') {
                        $idsToDeliver[] = $id;
                    }
                    $decoded = null;
                    $raw = (string)($row['payload_json'] ?? '');
                    if ($raw !== '') {
                        $decoded = json_decode($raw, true);
                    }
                    $events[] = [
                        'id' => $id,
                        'kind' => $kind,
                        'from_user_id' => (int)($row['from_user_id'] ?? 0),
                        'payload' => $decoded,
                        'created_at' => (string)($row['created_at'] ?? ''),
                    ];
                    $sinceId = max($sinceId, $id);
                }

                if (!empty($idsToDeliver)) {
                    $in = implode(',', array_fill(0, count($idsToDeliver), '?'));
                    $upd = $pdo->prepare('UPDATE social_webrtc_signals SET delivered_at = NOW() WHERE id IN (' . $in . ')');
                    $upd->execute($idsToDeliver);
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

    public function incoming(): void
    {
        $currentUser = $this->requireLogin();
        $currentId = (int)$currentUser['id'];

        if (function_exists('session_write_close')) {
            @session_write_close();
        }

        $pdo = Database::getConnection();

        // Procura uma oferta não entregue ainda (chamada recebida) em qualquer conversa
        $stmt = $pdo->prepare('SELECT s.conversation_id, s.from_user_id, s.created_at
            FROM social_webrtc_signals s
            WHERE s.to_user_id = :uid
              AND s.kind = "offer"
              AND s.delivered_at IS NULL
            ORDER BY s.id DESC
            LIMIT 1');
        $stmt->execute(['uid' => $currentId]);
        $offer = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        if (!$offer) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => true, 'incoming' => false]);
            return;
        }

        $conversationId = (int)($offer['conversation_id'] ?? 0);
        $fromUserId = (int)($offer['from_user_id'] ?? 0);

        // Se já houve "end" depois desta offer, não mostra popup
        $endStmt = $pdo->prepare('SELECT id
            FROM social_webrtc_signals
            WHERE conversation_id = :cid
              AND kind = "end"
              AND from_user_id = :from_uid
              AND created_at >= :offer_created
            ORDER BY id DESC
            LIMIT 1');
        $endStmt->execute([
            'cid' => $conversationId,
            'from_uid' => $fromUserId,
            'offer_created' => (string)($offer['created_at'] ?? ''),
        ]);
        $ended = (bool)$endStmt->fetch(PDO::FETCH_ASSOC);

        if ($ended) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => true, 'incoming' => false]);
            return;
        }

        // Confere se o usuário participa da conversa e se ainda são amigos (accepted)
        [$conversation, $otherUserId] = $this->ensureParticipantAndFriends($currentId, $conversationId);

        // O "otherUserId" aqui deve bater com "fromUserId" (quem está chamando)
        $callerId = $fromUserId > 0 ? $fromUserId : (int)$otherUserId;

        $caller = User::findById($callerId);
        $callerName = $caller ? (string)($caller['preferred_name'] ?? ($caller['name'] ?? '')) : '';
        if (trim($callerName) === '') {
            $callerName = 'Seu amigo';
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => true,
            'incoming' => true,
            'conversation_id' => $conversationId,
            'from_user_id' => $callerId,
            'from_user_name' => $callerName,
            'offer_created_at' => (string)($offer['created_at'] ?? ''),
        ]);
    }
}
