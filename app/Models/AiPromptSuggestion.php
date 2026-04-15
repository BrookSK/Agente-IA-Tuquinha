<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class AiPromptSuggestion
{
    public static function create(string $category, string $suggestion, string $rationale = '', ?int $projectId = null, ?int $sourceConversationId = null): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO ai_prompt_suggestions (project_id, category, suggestion, rationale, source_conversation_id) VALUES (:pid, :cat, :sug, :rat, :scid)');
        $stmt->execute([
            'pid' => $projectId,
            'cat' => $category,
            'sug' => $suggestion,
            'rat' => $rationale,
            'scid' => $sourceConversationId,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function allPending(int $limit = 50): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM ai_prompt_suggestions WHERE status = :s ORDER BY created_at DESC LIMIT ' . max(1, (int)$limit));
        $stmt->execute(['s' => 'pending']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM ai_prompt_suggestions WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function updateStatus(int $id, string $status): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE ai_prompt_suggestions SET status = :s, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['s' => $status, 'id' => $id]);
    }

    public static function approveAndApplyToProject(int $id): bool
    {
        $suggestion = self::findById($id);
        if (!$suggestion) {
            return false;
        }

        self::updateStatus($id, 'approved');

        $projectId = isset($suggestion['project_id']) ? (int)$suggestion['project_id'] : 0;
        if ($projectId <= 0) {
            return true;
        }

        $content = trim((string)($suggestion['suggestion'] ?? ''));
        if ($content === '') {
            return true;
        }

        $sourceConvId = isset($suggestion['source_conversation_id']) ? (int)$suggestion['source_conversation_id'] : null;
        ProjectMemoryItem::create($projectId, null, $sourceConvId > 0 ? $sourceConvId : null, null, $content);

        return true;
    }
}
