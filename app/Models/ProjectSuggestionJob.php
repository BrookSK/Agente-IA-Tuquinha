<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class ProjectSuggestionJob
{
    public static function enqueue(int $projectId, int $conversationId, string $userMessage, string $assistantReply): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO project_suggestion_jobs (project_id, conversation_id, user_message, assistant_reply) VALUES (:pid, :cid, :um, :ar)');
        $stmt->execute([
            'pid' => $projectId,
            'cid' => $conversationId,
            'um' => $userMessage,
            'ar' => $assistantReply,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function fetchPendingBatch(int $limit = 10): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM project_suggestion_jobs WHERE status = :s ORDER BY created_at ASC LIMIT ' . max(1, (int)$limit));
        $stmt->execute(['s' => 'pending']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function markRunning(int $id): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE project_suggestion_jobs SET status = :s, started_at = NOW() WHERE id = :id');
        $stmt->execute(['s' => 'running', 'id' => $id]);
    }

    public static function markDone(int $id): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE project_suggestion_jobs SET status = :s, done_at = NOW() WHERE id = :id');
        $stmt->execute(['s' => 'done', 'id' => $id]);
    }

    public static function markError(int $id, string $errorText): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE project_suggestion_jobs SET status = :s, error_text = :e, done_at = NOW() WHERE id = :id');
        $stmt->execute(['s' => 'error', 'e' => $errorText, 'id' => $id]);
    }
}
