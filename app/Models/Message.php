<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Message
{
    public static function create(int $conversationId, string $role, string $content, ?int $tokensUsed = null): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO messages (conversation_id, role, content, tokens_used) VALUES (:conversation_id, :role, :content, :tokens_used)');
        $stmt->execute([
            'conversation_id' => $conversationId,
            'role' => $role,
            'content' => $content,
            'tokens_used' => $tokensUsed,
        ]);

        return (int)$pdo->lastInsertId();
    }

    public static function findById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, conversation_id, role, content, tokens_used, created_at FROM messages WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    public static function allByConversation(int $conversationId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, role, content, tokens_used, created_at FROM messages WHERE conversation_id = :conversation_id ORDER BY id ASC');
        $stmt->execute(['conversation_id' => $conversationId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
