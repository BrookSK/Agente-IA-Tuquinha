<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class ProjectFavorite
{
    public static function isFavorite(int $projectId, int $userId): bool
    {
        if ($projectId <= 0 || $userId <= 0) {
            return false;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id FROM project_favorites WHERE project_id = :pid AND user_id = :uid LIMIT 1');
        $stmt->execute([
            'pid' => $projectId,
            'uid' => $userId,
        ]);
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function toggle(int $projectId, int $userId): bool
    {
        if ($projectId <= 0 || $userId <= 0) {
            return false;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id FROM project_favorites WHERE project_id = :pid AND user_id = :uid LIMIT 1');
        $stmt->execute([
            'pid' => $projectId,
            'uid' => $userId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $del = $pdo->prepare('DELETE FROM project_favorites WHERE id = :id LIMIT 1');
            $del->execute(['id' => (int)$row['id']]);
            return false;
        }

        $ins = $pdo->prepare('INSERT INTO project_favorites (project_id, user_id) VALUES (:pid, :uid)');
        $ins->execute([
            'pid' => $projectId,
            'uid' => $userId,
        ]);
        return true;
    }
}
