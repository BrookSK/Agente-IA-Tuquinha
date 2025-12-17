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
}
