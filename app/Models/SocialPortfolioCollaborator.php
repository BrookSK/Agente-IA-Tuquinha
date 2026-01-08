<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class SocialPortfolioCollaborator
{
    public static function addOrUpdate(int $ownerUserId, int $collaboratorUserId, int $portfolioItemId, string $role): void
    {
        if ($ownerUserId <= 0 || $collaboratorUserId <= 0 || $portfolioItemId <= 0 || $ownerUserId === $collaboratorUserId) {
            return;
        }

        $role = in_array($role, ['read', 'edit'], true) ? $role : 'read';

        $pdo = Database::getConnection();
        $existing = self::find($ownerUserId, $collaboratorUserId, $portfolioItemId);
        if ($existing) {
            $stmt = $pdo->prepare('UPDATE social_portfolio_collaborators SET role = :role, updated_at = NOW() WHERE id = :id');
            $stmt->execute([
                'role' => $role,
                'id' => (int)$existing['id'],
            ]);
            return;
        }

        $stmt = $pdo->prepare('INSERT INTO social_portfolio_collaborators (owner_user_id, collaborator_user_id, portfolio_item_id, role)
            VALUES (:owner, :collab, :item_id, :role)');
        $stmt->execute([
            'owner' => $ownerUserId,
            'collab' => $collaboratorUserId,
            'item_id' => $portfolioItemId,
            'role' => $role,
        ]);
    }

    public static function find(int $ownerUserId, int $collaboratorUserId, int $portfolioItemId): ?array
    {
        if ($ownerUserId <= 0 || $collaboratorUserId <= 0 || $portfolioItemId <= 0) {
            return null;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM social_portfolio_collaborators WHERE owner_user_id = :owner AND collaborator_user_id = :collab AND portfolio_item_id = :item_id LIMIT 1');
        $stmt->execute([
            'owner' => $ownerUserId,
            'collab' => $collaboratorUserId,
            'item_id' => $portfolioItemId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function userRoleForItem(int $ownerUserId, int $userId, int $portfolioItemId): ?string
    {
        if ($ownerUserId <= 0 || $userId <= 0 || $portfolioItemId <= 0) {
            return null;
        }
        if ($ownerUserId === $userId) {
            return 'edit';
        }
        $m = self::find($ownerUserId, $userId, $portfolioItemId);
        return $m ? (string)($m['role'] ?? null) : null;
    }

    public static function canReadItem(int $ownerUserId, int $userId, int $portfolioItemId): bool
    {
        $role = self::userRoleForItem($ownerUserId, $userId, $portfolioItemId);
        return in_array($role, ['read', 'edit'], true);
    }

    public static function canEditItem(int $ownerUserId, int $userId, int $portfolioItemId): bool
    {
        $role = self::userRoleForItem($ownerUserId, $userId, $portfolioItemId);
        return $role === 'edit';
    }

    public static function canEditAny(int $ownerUserId, int $userId): bool
    {
        if ($ownerUserId <= 0 || $userId <= 0) {
            return false;
        }
        if ($ownerUserId === $userId) {
            return true;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT 1 FROM social_portfolio_collaborators WHERE owner_user_id = :owner AND collaborator_user_id = :collab AND role = "edit" AND portfolio_item_id IS NOT NULL LIMIT 1');
        $stmt->execute([
            'owner' => $ownerUserId,
            'collab' => $userId,
        ]);
        return (bool)$stmt->fetchColumn();
    }

    public static function remove(int $ownerUserId, int $collaboratorUserId, int $portfolioItemId): void
    {
        if ($ownerUserId <= 0 || $collaboratorUserId <= 0 || $portfolioItemId <= 0) {
            return;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM social_portfolio_collaborators WHERE owner_user_id = :owner AND collaborator_user_id = :collab AND portfolio_item_id = :item_id LIMIT 1');
        $stmt->execute([
            'owner' => $ownerUserId,
            'collab' => $collaboratorUserId,
            'item_id' => $portfolioItemId,
        ]);
    }

    public static function updateRole(int $ownerUserId, int $collaboratorUserId, int $portfolioItemId, string $role): void
    {
        if ($ownerUserId <= 0 || $collaboratorUserId <= 0 || $portfolioItemId <= 0) {
            return;
        }
        $role = in_array($role, ['read', 'edit'], true) ? $role : 'read';
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE social_portfolio_collaborators SET role = :role, updated_at = NOW() WHERE owner_user_id = :owner AND collaborator_user_id = :collab AND portfolio_item_id = :item_id LIMIT 1');
        $stmt->execute([
            'role' => $role,
            'owner' => $ownerUserId,
            'collab' => $collaboratorUserId,
            'item_id' => $portfolioItemId,
        ]);
    }

    public static function allWithUsers(int $ownerUserId): array
    {
        if ($ownerUserId <= 0) {
            return [];
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT c.*, u.name AS user_name, u.preferred_name AS user_preferred_name, u.email AS user_email, u.nickname AS user_nickname,
                i.id AS portfolio_item_id, i.title AS portfolio_item_title
            FROM social_portfolio_collaborators c
            INNER JOIN users u ON u.id = c.collaborator_user_id
            LEFT JOIN social_portfolio_items i ON i.id = c.portfolio_item_id
            WHERE c.owner_user_id = :owner
            ORDER BY i.title ASC, c.role DESC, c.created_at ASC');
        $stmt->execute(['owner' => $ownerUserId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function allForItemWithUsers(int $ownerUserId, int $portfolioItemId): array
    {
        if ($ownerUserId <= 0 || $portfolioItemId <= 0) {
            return [];
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT c.*, u.name AS user_name, u.preferred_name AS user_preferred_name, u.email AS user_email, u.nickname AS user_nickname
            FROM social_portfolio_collaborators c
            INNER JOIN users u ON u.id = c.collaborator_user_id
            WHERE c.owner_user_id = :owner AND c.portfolio_item_id = :item_id
            ORDER BY c.role DESC, c.created_at ASC');
        $stmt->execute([
            'owner' => $ownerUserId,
            'item_id' => $portfolioItemId,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
