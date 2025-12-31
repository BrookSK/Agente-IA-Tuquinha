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
        $stmt = $pdo->prepare('SELECT s.*, u.name AS from_user_name, usp.avatar_path AS from_user_avatar_path
            FROM user_scraps s
            JOIN users u ON u.id = s.from_user_id
            LEFT JOIN user_social_profiles usp ON usp.user_id = u.id
            WHERE s.to_user_id = :uid AND s.is_deleted = 0
            ORDER BY s.created_at DESC, s.id DESC
            LIMIT :lim');
        $stmt->bindValue(':uid', $toUserId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function allVisibleForUser(int $toUserId, int $limit = 50): array
    {
        if ($toUserId <= 0) {
            return [];
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT s.*, u.name AS from_user_name, usp.avatar_path AS from_user_avatar_path
            FROM user_scraps s
            JOIN users u ON u.id = s.from_user_id
            LEFT JOIN user_social_profiles usp ON usp.user_id = u.id
            WHERE s.to_user_id = :uid AND s.is_deleted = 0 AND COALESCE(s.is_hidden, 0) = 0
            ORDER BY s.created_at DESC, s.id DESC
            LIMIT :lim');
        $stmt->bindValue(':uid', $toUserId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function findById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM user_scraps WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function updateBodyByAuthor(int $scrapId, int $authorUserId, string $body): bool
    {
        if ($scrapId <= 0 || $authorUserId <= 0) {
            return false;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE user_scraps
            SET body = :body
            WHERE id = :id AND from_user_id = :from_uid AND is_deleted = 0');
        $stmt->execute([
            'body' => $body,
            'id' => $scrapId,
            'from_uid' => $authorUserId,
        ]);
        return $stmt->rowCount() > 0;
    }

    public static function softDeleteByAuthor(int $scrapId, int $authorUserId): bool
    {
        if ($scrapId <= 0 || $authorUserId <= 0) {
            return false;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE user_scraps
            SET is_deleted = 1, deleted_at = NOW()
            WHERE id = :id AND from_user_id = :from_uid AND is_deleted = 0');
        $stmt->execute([
            'id' => $scrapId,
            'from_uid' => $authorUserId,
        ]);
        return $stmt->rowCount() > 0;
    }

    public static function setHiddenByProfileOwner(int $scrapId, int $profileOwnerId, bool $hidden): bool
    {
        if ($scrapId <= 0 || $profileOwnerId <= 0) {
            return false;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE user_scraps
            SET is_hidden = :hidden,
                hidden_at = CASE WHEN :hidden = 1 THEN NOW() ELSE NULL END
            WHERE id = :id AND to_user_id = :to_uid AND is_deleted = 0');
        $stmt->execute([
            'hidden' => $hidden ? 1 : 0,
            'id' => $scrapId,
            'to_uid' => $profileOwnerId,
        ]);
        return $stmt->rowCount() > 0;
    }
}
