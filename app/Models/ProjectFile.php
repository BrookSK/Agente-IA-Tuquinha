<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class ProjectFile
{
    public static function create(int $projectId, ?int $folderId, string $name, string $path, ?string $mimeType, bool $isBase, ?int $createdByUserId): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO project_files (project_id, folder_id, name, path, mime_type, is_base, created_by_user_id) VALUES (:project_id, :folder_id, :name, :path, :mime_type, :is_base, :created_by_user_id)');
        $stmt->execute([
            'project_id' => $projectId,
            'folder_id' => $folderId,
            'name' => $name,
            'path' => $path,
            'mime_type' => $mimeType,
            'is_base' => $isBase ? 1 : 0,
            'created_by_user_id' => $createdByUserId,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function findById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM project_files WHERE id = :id AND deleted_at IS NULL LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findByPath(int $projectId, string $path): ?array
    {
        if ($projectId <= 0 || $path === '') {
            return null;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM project_files WHERE project_id = :pid AND path = :path AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([
            'pid' => $projectId,
            'path' => $path,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function allBaseFiles(int $projectId): array
    {
        if ($projectId <= 0) {
            return [];
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM project_files WHERE project_id = :pid AND is_base = 1 AND deleted_at IS NULL ORDER BY path ASC');
        $stmt->execute(['pid' => $projectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function allBaseFilesWithFolder(int $projectId): array
    {
        if ($projectId <= 0) {
            return [];
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT pf.*, f.path AS folder_path
             FROM project_files pf
             LEFT JOIN project_folders f ON f.id = pf.folder_id
             WHERE pf.project_id = :pid AND pf.is_base = 1 AND pf.deleted_at IS NULL
             ORDER BY pf.path ASC'
        );
        $stmt->execute(['pid' => $projectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function searchByPathSuffix(int $projectId, string $suffix, int $limit = 10): array
    {
        if ($projectId <= 0) {
            return [];
        }
        $suffix = trim($suffix);
        if ($suffix === '') {
            return [];
        }
        if ($suffix[0] !== '/') {
            $suffix = '/' . $suffix;
        }

        $pdo = Database::getConnection();
        $like = '%' . $suffix;
        $stmt = $pdo->prepare('SELECT * FROM project_files WHERE project_id = :pid AND deleted_at IS NULL AND path LIKE :term ORDER BY path ASC LIMIT :lim');
        $stmt->bindValue(':pid', $projectId, PDO::PARAM_INT);
        $stmt->bindValue(':term', $like, PDO::PARAM_STR);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
