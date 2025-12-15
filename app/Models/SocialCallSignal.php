<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class SocialCallSignal
{
    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO social_call_signals (conversation_id, sender_user_id, type, payload, created_at)
            VALUES (:conversation_id, :sender_user_id, :type, :payload, NOW())');
        $stmt->execute([
            'conversation_id' => (int)($data['conversation_id'] ?? 0),
            'sender_user_id' => (int)($data['sender_user_id'] ?? 0),
            'type' => (string)($data['type'] ?? ''),
            'payload' => (string)($data['payload'] ?? ''),
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function allSince(int $conversationId, int $afterId): array
    {
        if ($conversationId <= 0) {
            return [];
        }

        $pdo = Database::getConnection();
        $sql = 'SELECT * FROM social_call_signals
                WHERE conversation_id = :cid AND id > :after
                ORDER BY id ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':cid', $conversationId, PDO::PARAM_INT);
        $stmt->bindValue(':after', $afterId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
