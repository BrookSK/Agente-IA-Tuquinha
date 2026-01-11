<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class PageShare
{
    public static function upsert(int $pageId, int $userId, string $role): void
    {
        $role = strtolower(trim($role));
        if (!in_array($role, ['view', 'edit'], true)) {
            $role = 'view';
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO page_shares (page_id, user_id, role)
            VALUES (:pid, :uid, :r)
            ON DUPLICATE KEY UPDATE role = VALUES(role)');
        $stmt->execute([
            'pid' => $pageId,
            'uid' => $userId,
            'r' => $role,
        ]);
    }

    public static function remove(int $pageId, int $userId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM page_shares WHERE page_id = :pid AND user_id = :uid');
        $stmt->execute(['pid' => $pageId, 'uid' => $userId]);
    }

    public static function listForPage(int $pageId): array
    {
        $pdo = Database::getConnection();
        $sql = 'SELECT s.*, u.email, u.name
                FROM page_shares s
                INNER JOIN users u ON u.id = s.user_id
                WHERE s.page_id = :pid
                ORDER BY s.created_at ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['pid' => $pageId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
