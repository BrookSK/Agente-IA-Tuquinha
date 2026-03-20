<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class ChatJob
{
    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO chat_jobs (session_id, conversation_id, user_message_id, status) VALUES (:session_id, :conversation_id, :user_message_id, :status)'
        );
        $stmt->execute([
            'session_id' => (string)($data['session_id'] ?? ''),
            'conversation_id' => (int)($data['conversation_id'] ?? 0),
            'user_message_id' => (int)($data['user_message_id'] ?? 0),
            'status' => (string)($data['status'] ?? 'pending'),
        ]);

        return (int)$pdo->lastInsertId();
    }

    public static function markRunning(int $id): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE chat_jobs SET status = "running" WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
    }

    public static function markDone(int $id, int $assistantMessageId, ?int $tokensUsed = null): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE chat_jobs SET status = "done", assistant_message_id = :assistant_message_id, tokens_used = :tokens_used WHERE id = :id LIMIT 1');
        $stmt->execute([
            'id' => $id,
            'assistant_message_id' => $assistantMessageId,
            'tokens_used' => $tokensUsed,
        ]);
    }

    public static function markError(int $id, string $errorText): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE chat_jobs SET status = "error", error_text = :error_text WHERE id = :id LIMIT 1');
        $stmt->execute([
            'id' => $id,
            'error_text' => $errorText,
        ]);
    }

    public static function findByIdAndSession(int $id, string $sessionId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM chat_jobs WHERE id = :id AND session_id = :session_id LIMIT 1');
        $stmt->execute([
            'id' => $id,
            'session_id' => $sessionId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    public static function findNextPending(): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT * FROM chat_jobs WHERE status = 'pending' ORDER BY id ASC LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    public static function findLastByConversation(int $conversationId, string $sessionId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM chat_jobs WHERE conversation_id = :conversation_id AND session_id = :session_id ORDER BY id DESC LIMIT 1');
        $stmt->execute([
            'conversation_id' => $conversationId,
            'session_id' => $sessionId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }
}
