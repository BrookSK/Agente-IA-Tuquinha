<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class SocialPortfolioInvitation
{
    public static function create(int $ownerUserId, int $portfolioItemId, int $inviterUserId, string $invitedEmail, ?string $invitedName, string $role, string $token): int
    {
        $role = in_array($role, ['read', 'edit'], true) ? $role : 'read';

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO social_portfolio_invitations
            (owner_user_id, portfolio_item_id, inviter_user_id, invited_email, invited_name, role, token)
            VALUES (:owner, :item_id, :inviter, :email, :name, :role, :token)');
        $stmt->execute([
            'owner' => $ownerUserId,
            'item_id' => $portfolioItemId > 0 ? $portfolioItemId : null,
            'inviter' => $inviterUserId,
            'email' => $invitedEmail,
            'name' => $invitedName,
            'role' => $role,
            'token' => $token,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function findByToken(string $token): ?array
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM social_portfolio_invitations WHERE token = :t LIMIT 1');
        $stmt->execute(['t' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function hasValidInviteForEmail(int $ownerUserId, int $portfolioItemId, string $email): bool
    {
        $email = trim($email);
        if ($ownerUserId <= 0 || $portfolioItemId <= 0 || $email === '') {
            return false;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT 1 FROM social_portfolio_invitations
            WHERE owner_user_id = :owner AND portfolio_item_id = :item_id AND invited_email = :email AND status = "pending"
            LIMIT 1');
        $stmt->execute([
            'owner' => $ownerUserId,
            'item_id' => $portfolioItemId,
            'email' => $email,
        ]);
        return (bool)$stmt->fetchColumn();
    }

    public static function allPendingForOwner(int $ownerUserId): array
    {
        if ($ownerUserId <= 0) {
            return [];
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT inv.*, i.id AS portfolio_item_id, i.title AS portfolio_item_title
            FROM social_portfolio_invitations inv
            LEFT JOIN social_portfolio_items i ON i.id = inv.portfolio_item_id
            WHERE inv.owner_user_id = :owner AND inv.status = "pending"
            ORDER BY inv.created_at DESC');
        $stmt->execute(['owner' => $ownerUserId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function cancelById(int $id): void
    {
        if ($id <= 0) {
            return;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE social_portfolio_invitations SET status = "cancelled" WHERE id = :id AND status = "pending"');
        $stmt->execute(['id' => $id]);
    }

    public static function markAccepted(int $id): void
    {
        if ($id <= 0) {
            return;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE social_portfolio_invitations
            SET status = "accepted", accepted_at = NOW()
            WHERE id = :id AND status = "pending"');
        $stmt->execute(['id' => $id]);
    }
}
