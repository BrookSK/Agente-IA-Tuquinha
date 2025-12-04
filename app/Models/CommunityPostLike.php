<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class CommunityPostLike
{
    public static function toggle(int $postId, int $userId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id FROM community_post_likes WHERE post_id = :post_id AND user_id = :user_id LIMIT 1');
        $stmt->execute(['post_id' => $postId, 'user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $del = $pdo->prepare('DELETE FROM community_post_likes WHERE id = :id');
            $del->execute(['id' => (int)$row['id']]);
        } else {
            $ins = $pdo->prepare('INSERT INTO community_post_likes (post_id, user_id) VALUES (:post_id, :user_id)');
            $ins->execute(['post_id' => $postId, 'user_id' => $userId]);
        }
    }

    public static function likesCountByPostIds(array $postIds): array
    {
        if (!$postIds) {
            return [];
        }
        $pdo = Database::getConnection();
        $placeholders = implode(',', array_fill(0, count($postIds), '?'));
        $stmt = $pdo->prepare("SELECT post_id, COUNT(*) AS c FROM community_post_likes WHERE post_id IN ($placeholders) GROUP BY post_id");
        $stmt->execute(array_map('intval', $postIds));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r['post_id']] = (int)$r['c'];
        }
        return $map;
    }

    public static function likedPostIdsByUser(int $userId, array $postIds): array
    {
        if (!$postIds) {
            return [];
        }
        $pdo = Database::getConnection();
        $placeholders = implode(',', array_fill(0, count($postIds), '?'));
        $params = array_merge([(int)$userId], array_map('intval', $postIds));
        $stmt = $pdo->prepare("SELECT post_id FROM community_post_likes WHERE user_id = ? AND post_id IN ($placeholders)");
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $ids = [];
        foreach ($rows as $r) {
            $ids[(int)$r['post_id']] = true;
        }
        return $ids;
    }
}
