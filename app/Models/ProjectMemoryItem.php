<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class ProjectMemoryItem
{
    public static function allActiveForProject(int $projectId, int $limit = 200): array
    {
        if ($projectId <= 0) {
            return [];
        }

        $limit = $limit > 0 ? $limit : 200;
        if ($limit > 500) {
            $limit = 500;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM project_memory_items
            WHERE project_id = :pid AND deleted_at IS NULL
            ORDER BY created_at DESC
            LIMIT ' . (int)$limit);
        $stmt->execute(['pid' => $projectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function create(int $projectId, ?int $createdByUserId, ?int $sourceConversationId, ?string $sourceText, string $content): int
    {
        $projectId = (int)$projectId;
        $content = trim(str_replace(["\r\n", "\r"], "\n", $content));
        if ($projectId <= 0 || $content === '') {
            return 0;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO project_memory_items (project_id, created_by_user_id, source_conversation_id, source_text, content)
            VALUES (:project_id, :created_by_user_id, :source_conversation_id, :source_text, :content)');
        $stmt->execute([
            'project_id' => $projectId,
            'created_by_user_id' => $createdByUserId && $createdByUserId > 0 ? $createdByUserId : null,
            'source_conversation_id' => $sourceConversationId && $sourceConversationId > 0 ? $sourceConversationId : null,
            'source_text' => $sourceText !== '' ? $sourceText : null,
            'content' => $content,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function updateContent(int $id, int $projectId, string $content): void
    {
        $content = trim(str_replace(["\r\n", "\r"], "\n", $content));
        if ($id <= 0 || $projectId <= 0 || $content === '') {
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE project_memory_items
            SET content = :content, updated_at = NOW()
            WHERE id = :id AND project_id = :pid AND deleted_at IS NULL');
        $stmt->execute([
            'content' => $content,
            'id' => $id,
            'pid' => $projectId,
        ]);
    }

    public static function softDelete(int $id, int $projectId): void
    {
        if ($id <= 0 || $projectId <= 0) {
            return;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE project_memory_items SET deleted_at = NOW() WHERE id = :id AND project_id = :pid AND deleted_at IS NULL');
        $stmt->execute([
            'id' => $id,
            'pid' => $projectId,
        ]);
    }

    public static function existsSimilar(int $projectId, string $content): bool
    {
        $content = trim(str_replace(["\r\n", "\r"], "\n", $content));
        if ($projectId <= 0 || $content === '') {
            return false;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT 1 FROM project_memory_items
            WHERE project_id = :pid AND deleted_at IS NULL AND content = :content
            LIMIT 1');
        $stmt->execute([
            'pid' => $projectId,
            'content' => $content,
        ]);
        return (bool)$stmt->fetchColumn();
    }
}
