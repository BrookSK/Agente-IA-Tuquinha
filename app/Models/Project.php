<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Project
{
    public static function create(int $ownerUserId, string $name, ?string $description = null): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO projects (owner_user_id, name, description) VALUES (:owner_user_id, :name, :description)');
        $stmt->execute([
            'owner_user_id' => $ownerUserId,
            'name' => $name,
            'description' => $description,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function findById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM projects WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function allForUser(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT DISTINCT p.*
             FROM projects p
             LEFT JOIN project_members pm ON pm.project_id = p.id
             WHERE p.owner_user_id = :uid OR pm.user_id = :uid
             ORDER BY p.created_at DESC, p.id DESC'
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function updateDescription(int $projectId, ?string $description): void
    {
        if ($projectId <= 0) {
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE projects SET description = :description, updated_at = NOW() WHERE id = :id LIMIT 1');
        $stmt->execute([
            'description' => $description,
            'id' => $projectId,
        ]);
    }

    public static function updateName(int $projectId, string $name): void
    {
        if ($projectId <= 0) {
            return;
        }

        $name = trim($name);
        if ($name === '') {
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE projects SET name = :name, updated_at = NOW() WHERE id = :id LIMIT 1');
        $stmt->execute([
            'name' => $name,
            'id' => $projectId,
        ]);
    }

    public static function deleteProject(int $projectId): void
    {
        if ($projectId <= 0) {
            return;
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT id FROM project_files WHERE project_id = :pid');
            $stmt->execute(['pid' => $projectId]);
            $fileIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $fileIds = array_values(array_filter(array_map('intval', $fileIds), static function ($v) {
                return $v > 0;
            }));

            if ($fileIds) {
                $placeholders = implode(',', array_fill(0, count($fileIds), '?'));
                $delVersions = $pdo->prepare('DELETE FROM project_file_versions WHERE project_file_id IN (' . $placeholders . ')');
                $delVersions->execute($fileIds);
            }

            $pdo->prepare('DELETE FROM project_files WHERE project_id = :pid')->execute(['pid' => $projectId]);
            $pdo->prepare('DELETE FROM project_folders WHERE project_id = :pid')->execute(['pid' => $projectId]);
            $pdo->prepare('DELETE FROM project_members WHERE project_id = :pid')->execute(['pid' => $projectId]);
            $pdo->prepare('DELETE FROM project_favorites WHERE project_id = :pid')->execute(['pid' => $projectId]);

            $pdo->prepare('UPDATE conversations SET project_id = NULL WHERE project_id = :pid')->execute(['pid' => $projectId]);

            $pdo->prepare('DELETE FROM projects WHERE id = :pid LIMIT 1')->execute(['pid' => $projectId]);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
