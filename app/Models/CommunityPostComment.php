<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class CommunityPostComment
{
    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO community_post_comments (post_id, user_id, parent_id, body)
            VALUES (:post_id, :user_id, :parent_id, :body)');
        $stmt->execute([
            'post_id' => (int)($data['post_id'] ?? 0),
            'user_id' => (int)($data['user_id'] ?? 0),
            'parent_id' => !empty($data['parent_id']) ? (int)$data['parent_id'] : null,
            'body' => $data['body'] ?? '',
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function allByPostIdsWithUser(array $postIds): array
    {
        if (!$postIds) {
            return [];
        }
        $pdo = Database::getConnection();
        $placeholders = implode(',', array_fill(0, count($postIds), '?'));
        $stmt = $pdo->prepare("SELECT c.*, u.name AS user_name
            FROM community_post_comments c
            JOIN users u ON u.id = c.user_id
            WHERE c.post_id IN ($placeholders)
            ORDER BY c.created_at ASC, c.id ASC");
        $stmt->execute(array_map('intval', $postIds));
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function countsByPostIds(array $postIds): array
    {
        if (!$postIds) {
            return [];
        }
        $pdo = Database::getConnection();
        $placeholders = implode(',', array_fill(0, count($postIds), '?'));
        $stmt = $pdo->prepare("SELECT post_id, COUNT(*) AS c FROM community_post_comments WHERE post_id IN ($placeholders) GROUP BY post_id");
        $stmt->execute(array_map('intval', $postIds));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r['post_id']] = (int)$r['c'];
        }
        return $map;
    }
}
