<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class ConversationSetting
{
    public static function findForConversation(int $conversationId, int $userId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM conversation_settings WHERE conversation_id = :conversation_id AND user_id = :user_id LIMIT 1');
        $stmt->execute([
            'conversation_id' => $conversationId,
            'user_id' => $userId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function upsert(int $conversationId, int $userId, ?string $customInstructions, ?string $memoryNotes): void
    {
        $pdo = Database::getConnection();
        $existing = self::findForConversation($conversationId, $userId);

        if ($existing) {
            $stmt = $pdo->prepare('UPDATE conversation_settings SET custom_instructions = :custom_instructions, memory_notes = :memory_notes WHERE id = :id');
            $stmt->execute([
                'id' => $existing['id'],
                'custom_instructions' => $customInstructions,
                'memory_notes' => $memoryNotes,
            ]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO conversation_settings (conversation_id, user_id, custom_instructions, memory_notes) VALUES (:conversation_id, :user_id, :custom_instructions, :memory_notes)');
            $stmt->execute([
                'conversation_id' => $conversationId,
                'user_id' => $userId,
                'custom_instructions' => $customInstructions,
                'memory_notes' => $memoryNotes,
            ]);
        }
    }
}
