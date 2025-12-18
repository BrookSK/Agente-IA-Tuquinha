<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class SocialPortfolioCollaborator
{
    public static function addOrUpdate(int $ownerUserId, int $collaboratorUserId, string $role): void
    {
        if ($ownerUserId <= 0 || $collaboratorUserId <= 0 || $ownerUserId === $collaboratorUserId) {
            return;
        }

        $role = in_array($role, ['read', 'edit'], true) ? $role : 'read';

        $pdo = Database::getConnection();
        $existing = self::find($ownerUserId, $collaboratorUserId);
        if ($existing) {
            $stmt = $pdo->prepare('UPDATE social_portfolio_collaborators SET role = :role, updated_at = NOW() WHERE id = :id');
            $stmt->execute([
                'role' => $role,
                'id' => (int)$existing['id'],
            ]);
            return;
        }

        $stmt = $pdo->prepare('INSERT INTO social_portfolio_collaborators (owner_user_id, collaborator_user_id, role)
            VALUES (:owner, :collab, :role)');
        $stmt->execute([
            'owner' => $ownerUserId,
            'collab' => $collaboratorUserId,
            'role' => $role,
        ]);
    }

    public static function find(int $ownerUserId, int $collaboratorUserId): ?array
    {
        if ($ownerUserId <= 0 || $collaboratorUserId <= 0) {
            return null;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM social_portfolio_collaborators WHERE owner_user_id = :owner AND collaborator_user_id = :collab LIMIT 1');
        $stmt->execute([
            'owner' => $ownerUserId,
            'collab' => $collaboratorUserId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function userRole(int $ownerUserId, int $userId): ?string
    {
        if ($ownerUserId <= 0 || $userId <= 0) {
            return null;
        }
        if ($ownerUserId === $userId) {
            return 'edit';
        }
        $m = self::find($ownerUserId, $userId);
        return $m ? (string)($m['role'] ?? null) : null;
    }

    public static function canRead(int $ownerUserId, int $userId): bool
    {
        $role = self::userRole($ownerUserId, $userId);
        return in_array($role, ['read', 'edit'], true);
    }

    public static function canEdit(int $ownerUserId, int $userId): bool
    {
        $role = self::userRole($ownerUserId, $userId);
        return $role === 'edit';
    }

    public static function remove(int $ownerUserId, int $collaboratorUserId): void
    {
        if ($ownerUserId <= 0 || $collaboratorUserId <= 0) {
            return;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM social_portfolio_collaborators WHERE owner_user_id = :owner AND collaborator_user_id = :collab LIMIT 1');
        $stmt->execute([
            'owner' => $ownerUserId,
            'collab' => $collaboratorUserId,
        ]);
    }

    public static function updateRole(int $ownerUserId, int $collaboratorUserId, string $role): void
    {
        if ($ownerUserId <= 0 || $collaboratorUserId <= 0) {
            return;
        }
        $role = in_array($role, ['read', 'edit'], true) ? $role : 'read';
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE social_portfolio_collaborators SET role = :role, updated_at = NOW() WHERE owner_user_id = :owner AND collaborator_user_id = :collab LIMIT 1');
        $stmt->execute([
            'role' => $role,
            'owner' => $ownerUserId,
            'collab' => $collaboratorUserId,
        ]);
    }

    public static function allWithUsers(int $ownerUserId): array
    {
        if ($ownerUserId <= 0) {
            return [];
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT c.*, u.name AS user_name, u.preferred_name AS user_preferred_name, u.email AS user_email, u.nickname AS user_nickname
            FROM social_portfolio_collaborators c
            INNER JOIN users u ON u.id = c.collaborator_user_id
            WHERE c.owner_user_id = :owner
            ORDER BY c.role DESC, c.created_at ASC');
        $stmt->execute(['owner' => $ownerUserId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
