<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class MarketingEventShare
{
    public static function upsert(int $ownerUserId, int $sharedWithUserId, string $role): void
    {
        $role = strtolower(trim($role));
        if (!in_array($role, ['view', 'edit'], true)) {
            $role = 'view';
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO marketing_event_shares (owner_user_id, shared_with_user_id, role)
            VALUES (:oid, :uid, :r)
            ON DUPLICATE KEY UPDATE role = VALUES(role)');
        $stmt->execute([
            'oid' => $ownerUserId,
            'uid' => $sharedWithUserId,
            'r' => $role,
        ]);
    }

    public static function remove(int $ownerUserId, int $sharedWithUserId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM marketing_event_shares WHERE owner_user_id = :oid AND shared_with_user_id = :uid');
        $stmt->execute(['oid' => $ownerUserId, 'uid' => $sharedWithUserId]);
    }

    public static function listForOwner(int $ownerUserId): array
    {
        $pdo = Database::getConnection();
        $sql = 'SELECT s.*, u.email, u.name
                FROM marketing_event_shares s
                INNER JOIN users u ON u.id = s.shared_with_user_id
                WHERE s.owner_user_id = :oid
                ORDER BY s.created_at ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['oid' => $ownerUserId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
