<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class SocialConversation
{
    private static function normalizePair(int $a, int $b): array
    {
        if ($a <= $b) {
            return [$a, $b];
        }
        return [$b, $a];
    }

    public static function findByUsers(int $userId1, int $userId2): ?array
    {
        if ($userId1 <= 0 || $userId2 <= 0 || $userId1 === $userId2) {
            return null;
        }

        [$u1, $u2] = self::normalizePair($userId1, $userId2);

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM social_conversations WHERE user1_id = :u1 AND user2_id = :u2 LIMIT 1');
        $stmt->execute([
            'u1' => $u1,
            'u2' => $u2,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findOrCreateForUsers(int $userId1, int $userId2): array
    {
        $existing = self::findByUsers($userId1, $userId2);
        if ($existing) {
            return $existing;
        }

        [$u1, $u2] = self::normalizePair($userId1, $userId2);

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO social_conversations (user1_id, user2_id, created_at) VALUES (:u1, :u2, NOW())');
        $stmt->execute([
            'u1' => $u1,
            'u2' => $u2,
        ]);

        $id = (int)$pdo->lastInsertId();
        return [
            'id' => $id,
            'user1_id' => $u1,
            'user2_id' => $u2,
            'created_at' => date('Y-m-d H:i:s'),
            'last_message_at' => null,
            'last_message_preview' => null,
        ];
    }

    public static function allForUser(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $pdo = Database::getConnection();
        $sql = 'SELECT sc.*, 
                       CASE WHEN sc.user1_id = :uid THEN sc.user2_id ELSE sc.user1_id END AS other_user_id,
                       u.name AS other_user_name
                FROM social_conversations sc
                JOIN users u ON u.id = CASE WHEN sc.user1_id = :uid THEN sc.user2_id ELSE sc.user1_id END
                WHERE sc.user1_id = :uid OR sc.user2_id = :uid
                ORDER BY sc.last_message_at DESC, sc.id DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function findById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM social_conversations WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function touchWithMessage(int $conversationId, string $preview): void
    {
        if ($conversationId <= 0) {
            return;
        }

        $preview = trim($preview);
        if ($preview !== '' && mb_strlen($preview) > 255) {
            $preview = mb_substr($preview, 0, 252) . '...';
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE social_conversations SET last_message_at = NOW(), last_message_preview = :preview WHERE id = :id');
        $stmt->execute([
            'id' => $conversationId,
            'preview' => $preview !== '' ? $preview : null,
        ]);
    }
}
