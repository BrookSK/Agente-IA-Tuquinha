<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class CommunityMemberReport
{
    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO community_member_reports
            (community_id, reporter_user_id, reported_user_id, reason)
            VALUES (:community_id, :reporter_user_id, :reported_user_id, :reason)');
        $stmt->execute([
            'community_id' => (int)($data['community_id'] ?? 0),
            'reporter_user_id' => (int)($data['reporter_user_id'] ?? 0),
            'reported_user_id' => (int)($data['reported_user_id'] ?? 0),
            'reason' => $data['reason'] ?? null,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function findById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM community_member_reports WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function allOpenForCommunity(int $communityId): array
    {
        if ($communityId <= 0) {
            return [];
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT r.*, ru.name AS reporter_name, tu.name AS reported_name
            FROM community_member_reports r
            JOIN users ru ON ru.id = r.reporter_user_id
            JOIN users tu ON tu.id = r.reported_user_id
            WHERE r.community_id = :cid AND r.status = "open"
            ORDER BY r.created_at DESC');
        $stmt->execute(['cid' => $communityId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function markResolved(int $id, int $resolverUserId): void
    {
        if ($id <= 0 || $resolverUserId <= 0) {
            return;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE community_member_reports
            SET status = "resolved", resolved_at = NOW(), resolved_by = :resolver
            WHERE id = :id AND status = "open"');
        $stmt->execute([
            'id' => $id,
            'resolver' => $resolverUserId,
        ]);
    }
}
