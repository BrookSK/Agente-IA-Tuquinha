<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class CommunityTopicPost
{
    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO community_topic_posts (topic_id, parent_post_id, user_id, body, media_url, media_mime, media_kind)
            VALUES (:topic_id, :parent_post_id, :user_id, :body, :media_url, :media_mime, :media_kind)');
        $stmt->execute([
            'topic_id' => (int)($data['topic_id'] ?? 0),
            'parent_post_id' => isset($data['parent_post_id']) ? (int)$data['parent_post_id'] : null,
            'user_id' => (int)($data['user_id'] ?? 0),
            'body' => $data['body'] ?? '',
            'media_url' => $data['media_url'] ?? null,
            'media_mime' => $data['media_mime'] ?? null,
            'media_kind' => $data['media_kind'] ?? null,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function allByTopicWithUser(int $topicId): array
    {
        if ($topicId <= 0) {
            return [];
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT p.*, u.name AS user_name, usp.avatar_path AS user_avatar_path
            FROM community_topic_posts p
            JOIN users u ON u.id = p.user_id
            LEFT JOIN user_social_profiles usp ON usp.user_id = u.id
            WHERE p.topic_id = :tid AND p.deleted_at IS NULL
            ORDER BY p.created_at ASC, p.id ASC');
        $stmt->execute(['tid' => $topicId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function findById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM community_topic_posts WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
