<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class SocialMessage
{
    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO social_messages (conversation_id, sender_user_id, body, created_at)
            VALUES (:conversation_id, :sender_user_id, :body, NOW())');
        $stmt->execute([
            'conversation_id' => (int)($data['conversation_id'] ?? 0),
            'sender_user_id' => (int)($data['sender_user_id'] ?? 0),
            'body' => $data['body'] ?? '',
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function allForConversation(int $conversationId, int $limit = 100): array
    {
        if ($conversationId <= 0) {
            return [];
        }

        $pdo = Database::getConnection();
        $sql = 'SELECT m.*, u.name AS sender_name
                FROM social_messages m
                JOIN users u ON u.id = m.sender_user_id
                WHERE m.conversation_id = :cid
                ORDER BY m.created_at ASC, m.id ASC
                LIMIT :lim';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':cid', $conversationId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function sinceId(int $conversationId, int $afterId, int $limit = 100): array
    {
        if ($conversationId <= 0) {
            return [];
        }

        if ($afterId < 0) {
            $afterId = 0;
        }

        $pdo = Database::getConnection();
        $sql = 'SELECT m.*, u.name AS sender_name
                FROM social_messages m
                JOIN users u ON u.id = m.sender_user_id
                WHERE m.conversation_id = :cid AND m.id > :after_id
                ORDER BY m.id ASC
                LIMIT :lim';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':cid', $conversationId, PDO::PARAM_INT);
        $stmt->bindValue(':after_id', $afterId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function markAsRead(int $conversationId, int $currentUserId): void
    {
        if ($conversationId <= 0 || $currentUserId <= 0) {
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE social_messages
            SET is_read = 1, read_at = NOW()
            WHERE conversation_id = :cid AND sender_user_id != :uid AND is_read = 0');
        $stmt->execute([
            'cid' => $conversationId,
            'uid' => $currentUserId,
        ]);
    }
}
