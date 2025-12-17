<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class ProjectFolder
{
    public static function create(int $projectId, ?int $parentId, string $name, string $path): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO project_folders (project_id, parent_id, name, path) VALUES (:project_id, :parent_id, :name, :path)');
        $stmt->execute([
            'project_id' => $projectId,
            'parent_id' => $parentId,
            'name' => $name,
            'path' => $path,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function findByPath(int $projectId, string $path): ?array
    {
        if ($projectId <= 0 || $path === '') {
            return null;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM project_folders WHERE project_id = :pid AND path = :path LIMIT 1');
        $stmt->execute([
            'pid' => $projectId,
            'path' => $path,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function ensureDefaultTree(int $projectId): void
    {
        $defaults = ['/base', '/documentacao', '/codigo', '/regras', '/outros'];
        foreach ($defaults as $path) {
            $existing = self::findByPath($projectId, $path);
            if ($existing) {
                continue;
            }
            $name = ltrim($path, '/');
            self::create($projectId, null, $name, $path);
        }
    }

    public static function allForProject(int $projectId): array
    {
        if ($projectId <= 0) {
            return [];
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM project_folders WHERE project_id = :pid ORDER BY path ASC');
        $stmt->execute(['pid' => $projectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
