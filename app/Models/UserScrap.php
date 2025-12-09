<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class UserScrap
{
    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO user_scraps (from_user_id, to_user_id, body)
            VALUES (:from_user_id, :to_user_id, :body)');
        $stmt->execute([
            'from_user_id' => (int)($data['from_user_id'] ?? 0),
            'to_user_id' => (int)($data['to_user_id'] ?? 0),
            'body' => $data['body'] ?? '',
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function allForUser(int $toUserId, int $limit = 50): array
    {
        if ($toUserId <= 0) {
            return [];
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT s.*, u.name AS from_user_name
            FROM user_scraps s
            JOIN users u ON u.id = s.from_user_id
            WHERE s.to_user_id = :uid AND s.is_deleted = 0
            ORDER BY s.created_at DESC, s.id DESC
            LIMIT :lim');
        $stmt->bindValue(':uid', $toUserId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
