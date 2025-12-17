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
}
