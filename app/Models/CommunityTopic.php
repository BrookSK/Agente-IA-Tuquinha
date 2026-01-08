<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class CommunityTopic
{
    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO community_topics (community_id, user_id, title, body, media_url, media_mime, media_kind)
            VALUES (:community_id, :user_id, :title, :body, :media_url, :media_mime, :media_kind)');
        $stmt->execute([
            'community_id' => (int)($data['community_id'] ?? 0),
            'user_id' => (int)($data['user_id'] ?? 0),
            'title' => $data['title'] ?? '',
            'body' => $data['body'] ?? null,
            'media_url' => $data['media_url'] ?? null,
            'media_mime' => $data['media_mime'] ?? null,
            'media_kind' => $data['media_kind'] ?? null,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function findById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT t.*, u.name AS user_name, usp.avatar_path AS user_avatar_path
            FROM community_topics t
            JOIN users u ON u.id = t.user_id
            LEFT JOIN user_social_profiles usp ON usp.user_id = u.id
            WHERE t.id = :id AND t.deleted_at IS NULL
            LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function allByCommunity(int $communityId, int $limit = 50): array
    {
        if ($communityId <= 0) {
            return [];
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT t.*, u.name AS user_name
            FROM community_topics t
            JOIN users u ON u.id = t.user_id
            WHERE t.community_id = :cid AND t.deleted_at IS NULL
            ORDER BY t.is_sticky DESC, t.created_at DESC, t.id DESC
            LIMIT :lim');
        $stmt->bindValue(':cid', $communityId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
