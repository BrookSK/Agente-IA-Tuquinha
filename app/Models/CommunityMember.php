<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class CommunityMember
{
    public static function join(int $communityId, int $userId, string $role = 'member'): void
    {
        if ($communityId <= 0 || $userId <= 0) {
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO community_members (community_id, user_id, role, joined_at)
            VALUES (:cid, :uid, :role, NOW())
            ON DUPLICATE KEY UPDATE role = VALUES(role), left_at = NULL');
        $stmt->execute([
            'cid' => $communityId,
            'uid' => $userId,
            'role' => $role,
        ]);
    }

    public static function leave(int $communityId, int $userId): void
    {
        if ($communityId <= 0 || $userId <= 0) {
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE community_members SET left_at = NOW()
            WHERE community_id = :cid AND user_id = :uid AND left_at IS NULL');
        $stmt->execute([
            'cid' => $communityId,
            'uid' => $userId,
        ]);
    }

    public static function allMembersWithUser(int $communityId): array
    {
        if ($communityId <= 0) {
            return [];
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT m.*, u.name AS user_name
            FROM community_members m
            JOIN users u ON u.id = m.user_id
            WHERE m.community_id = :cid AND m.left_at IS NULL
            ORDER BY u.name ASC');
        $stmt->execute(['cid' => $communityId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function isMember(int $communityId, int $userId): bool
    {
        if ($communityId <= 0 || $userId <= 0) {
            return false;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT 1 FROM community_members
            WHERE community_id = :cid AND user_id = :uid AND left_at IS NULL
            LIMIT 1');
        $stmt->execute([
            'cid' => $communityId,
            'uid' => $userId,
        ]);
        return (bool)$stmt->fetchColumn();
    }

    public static function findMember(int $communityId, int $userId): ?array
    {
        if ($communityId <= 0 || $userId <= 0) {
            return null;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM community_members
            WHERE community_id = :cid AND user_id = :uid AND left_at IS NULL
            LIMIT 1');
        $stmt->execute([
            'cid' => $communityId,
            'uid' => $userId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function isBlocked(int $communityId, int $userId): bool
    {
        if ($communityId <= 0 || $userId <= 0) {
            return false;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT 1 FROM community_members
            WHERE community_id = :cid AND user_id = :uid AND left_at IS NULL AND is_blocked = 1
            LIMIT 1');
        $stmt->execute([
            'cid' => $communityId,
            'uid' => $userId,
        ]);
        return (bool)$stmt->fetchColumn();
    }

    public static function block(int $communityId, int $userId, ?string $reason = null): void
    {
        if ($communityId <= 0 || $userId <= 0) {
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE community_members
            SET is_blocked = 1, blocked_reason = :reason
            WHERE community_id = :cid AND user_id = :uid AND left_at IS NULL');
        $stmt->execute([
            'cid' => $communityId,
            'uid' => $userId,
            'reason' => $reason !== '' ? $reason : null,
        ]);
    }

    public static function unblock(int $communityId, int $userId): void
    {
        if ($communityId <= 0 || $userId <= 0) {
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE community_members
            SET is_blocked = 0, blocked_reason = NULL
            WHERE community_id = :cid AND user_id = :uid AND left_at IS NULL');
        $stmt->execute([
            'cid' => $communityId,
            'uid' => $userId,
        ]);
    }

    public static function communitiesForUser(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT m.*, c.name, c.slug, c.image_path, c.cover_image_path
            FROM community_members m
            JOIN communities c ON c.id = m.community_id
            WHERE m.user_id = :uid AND m.left_at IS NULL AND c.is_active = 1
            ORDER BY c.name ASC');
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
