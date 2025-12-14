<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class CommunityInvite
{
    public static function create(int $communityId, int $inviterUserId, string $invitedEmail, ?string $invitedName, string $token): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO community_invites
            (community_id, inviter_user_id, invited_email, invited_name, token)
            VALUES (:community_id, :inviter_user_id, :invited_email, :invited_name, :token)');
        $stmt->execute([
            'community_id' => $communityId,
            'inviter_user_id' => $inviterUserId,
            'invited_email' => $invitedEmail,
            'invited_name' => $invitedName,
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
        $stmt = $pdo->prepare('SELECT * FROM community_invites WHERE token = :token LIMIT 1');
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function markAccepted(int $id, int $userId): void
    {
        if ($id <= 0 || $userId <= 0) {
            return;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE community_invites
            SET status = "accepted", accepted_at = NOW()
            WHERE id = :id AND status = "pending"');
        $stmt->execute(['id' => $id]);
    }

    public static function hasValidInviteForEmail(int $communityId, string $email): bool
    {
        $email = trim($email);
        if ($communityId <= 0 || $email === '') {
            return false;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT 1 FROM community_invites
            WHERE community_id = :cid AND invited_email = :email AND status = "pending"
            LIMIT 1');
        $stmt->execute([
            'cid' => $communityId,
            'email' => $email,
        ]);
        return (bool)$stmt->fetchColumn();
    }
}
