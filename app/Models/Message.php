<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Message
{
    public static function create(int $conversationId, string $role, string $content, ?int $tokensUsed = null): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO messages (conversation_id, role, content, tokens_used) VALUES (:conversation_id, :role, :content, :tokens_used)');
        $stmt->execute([
            'conversation_id' => $conversationId,
            'role' => $role,
            'content' => $content,
            'tokens_used' => $tokensUsed,
        ]);
    }

    public static function allByConversation(int $conversationId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT role, content, tokens_used, created_at FROM messages WHERE conversation_id = :conversation_id ORDER BY id ASC');
        $stmt->execute(['conversation_id' => $conversationId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
