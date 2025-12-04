<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class CommunityUserBlock
{
    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO community_user_blocks (user_id, reason, blocked_by)
            VALUES (:user_id, :reason, :blocked_by)');
        $stmt->execute([
            'user_id' => (int)($data['user_id'] ?? 0),
            'reason' => $data['reason'] ?? '',
            'blocked_by' => (int)($data['blocked_by'] ?? 0),
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function unblock(int $userId, int $unblockedBy): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE community_user_blocks
            SET unblocked_at = NOW(), unblocked_by = :unblocked_by
            WHERE user_id = :user_id AND unblocked_at IS NULL');
        $stmt->execute([
            'user_id' => $userId,
            'unblocked_by' => $unblockedBy,
        ]);
    }

    public static function findActiveByUserId(int $userId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM community_user_blocks
            WHERE user_id = :user_id AND unblocked_at IS NULL
            ORDER BY created_at DESC, id DESC
            LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function allActiveWithUsers(): array
    {
        $pdo = Database::getConnection();
        $sql = 'SELECT b.*, u.name AS user_name, u.email AS user_email, admin.name AS blocked_by_name
            FROM community_user_blocks b
            JOIN users u ON u.id = b.user_id
            LEFT JOIN users admin ON admin.id = b.blocked_by
            WHERE b.unblocked_at IS NULL
            ORDER BY b.created_at DESC, b.id DESC';
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
