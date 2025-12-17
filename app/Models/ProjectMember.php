<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class ProjectMember
{
    public static function addOrUpdate(int $projectId, int $userId, string $role): void
    {
        $role = in_array($role, ['read', 'write', 'admin'], true) ? $role : 'read';

        $pdo = Database::getConnection();
        $existing = self::find($projectId, $userId);

        if ($existing) {
            $stmt = $pdo->prepare('UPDATE project_members SET role = :role WHERE id = :id');
            $stmt->execute([
                'role' => $role,
                'id' => (int)$existing['id'],
            ]);
            return;
        }

        $stmt = $pdo->prepare('INSERT INTO project_members (project_id, user_id, role) VALUES (:project_id, :user_id, :role)');
        $stmt->execute([
            'project_id' => $projectId,
            'user_id' => $userId,
            'role' => $role,
        ]);
    }

    public static function find(int $projectId, int $userId): ?array
    {
        if ($projectId <= 0 || $userId <= 0) {
            return null;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM project_members WHERE project_id = :pid AND user_id = :uid LIMIT 1');
        $stmt->execute([
            'pid' => $projectId,
            'uid' => $userId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function userRole(int $projectId, int $userId): ?string
    {
        $project = Project::findById($projectId);
        if (!$project) {
            return null;
        }

        if ((int)($project['owner_user_id'] ?? 0) === $userId) {
            return 'admin';
        }

        $m = self::find($projectId, $userId);
        return $m ? (string)($m['role'] ?? null) : null;
    }

    public static function canRead(int $projectId, int $userId): bool
    {
        $role = self::userRole($projectId, $userId);
        return in_array($role, ['read', 'write', 'admin'], true);
    }

    public static function canWrite(int $projectId, int $userId): bool
    {
        $role = self::userRole($projectId, $userId);
        return in_array($role, ['write', 'admin'], true);
    }

    public static function canAdmin(int $projectId, int $userId): bool
    {
        $role = self::userRole($projectId, $userId);
        return $role === 'admin';
    }

    public static function allWithUsers(int $projectId): array
    {
        if ($projectId <= 0) {
            return [];
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT pm.*, u.name AS user_name, u.preferred_name AS user_preferred_name, u.email AS user_email
            FROM project_members pm
            INNER JOIN users u ON u.id = pm.user_id
            WHERE pm.project_id = :pid
            ORDER BY pm.role DESC, pm.created_at ASC');
        $stmt->execute(['pid' => $projectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function remove(int $projectId, int $userId): void
    {
        if ($projectId <= 0 || $userId <= 0) {
            return;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM project_members WHERE project_id = :pid AND user_id = :uid LIMIT 1');
        $stmt->execute([
            'pid' => $projectId,
            'uid' => $userId,
        ]);
    }

    public static function updateRole(int $projectId, int $userId, string $role): void
    {
        if ($projectId <= 0 || $userId <= 0) {
            return;
        }
        $role = in_array($role, ['read', 'write', 'admin'], true) ? $role : 'read';
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE project_members SET role = :role WHERE project_id = :pid AND user_id = :uid LIMIT 1');
        $stmt->execute([
            'role' => $role,
            'pid' => $projectId,
            'uid' => $userId,
        ]);
    }
}
