<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class CommunityPost
{
    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO community_posts (user_id, body, image_path, file_path, repost_post_id)
            VALUES (:user_id, :body, :image_path, :file_path, :repost_post_id)');
        $stmt->execute([
            'user_id' => (int)($data['user_id'] ?? 0),
            'body' => $data['body'] !== '' ? $data['body'] : null,
            'image_path' => $data['image_path'] ?? null,
            'file_path' => $data['file_path'] ?? null,
            'repost_post_id' => !empty($data['repost_post_id']) ? (int)$data['repost_post_id'] : null,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function updateBody(int $id, string $body): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE community_posts SET body = :body, updated_at = NOW() WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute([
            'id' => $id,
            'body' => $body !== '' ? $body : null,
        ]);
    }

    public static function softDelete(int $id): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE community_posts SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id]);
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM community_posts WHERE id = :id AND deleted_at IS NULL LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function latestWithAuthors(int $limit = 50): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT p.*, u.name AS user_name
            FROM community_posts p
            JOIN users u ON u.id = p.user_id
            WHERE p.deleted_at IS NULL
            ORDER BY p.created_at DESC, p.id DESC
            LIMIT :lim');
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function findByIdsWithAuthors(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = 'SELECT p.*, u.name AS user_name
            FROM community_posts p
            JOIN users u ON u.id = p.user_id
            WHERE p.deleted_at IS NULL AND p.id IN (' . $placeholders . ')
            ORDER BY p.created_at DESC, p.id DESC';

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare($sql);
        foreach ($ids as $i => $id) {
            $stmt->bindValue($i + 1, $id, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
