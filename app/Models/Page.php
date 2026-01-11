<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Page
{
    public static function listForUser(int $userId): array
    {
        $pdo = Database::getConnection();
        $sql = 'SELECT t.* FROM (
                    SELECT p.*
                    FROM pages p
                    WHERE p.owner_user_id = :uid
                    UNION
                    SELECT p.*
                    FROM pages p
                    INNER JOIN page_shares s ON s.page_id = p.id
                    WHERE s.user_id = :uid
                ) AS t
                ORDER BY t.updated_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM pages WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findAccessibleById(int $pageId, int $userId): ?array
    {
        $pdo = Database::getConnection();
        $sql = 'SELECT p.*,
                    CASE
                        WHEN p.owner_user_id = :uid THEN "owner"
                        ELSE COALESCE(s.role, "")
                    END AS access_role
                FROM pages p
                LEFT JOIN page_shares s ON s.page_id = p.id AND s.user_id = :uid
                WHERE p.id = :pid
                  AND (p.owner_user_id = :uid OR s.user_id = :uid)
                LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['uid' => $userId, 'pid' => $pageId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(int $ownerUserId, string $title = 'Sem título'): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO pages (owner_user_id, title, content_json, is_published)
            VALUES (:uid, :title, NULL, 0)');
        $stmt->execute([
            'uid' => $ownerUserId,
            'title' => $title !== '' ? $title : 'Sem título',
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function updateContent(int $pageId, string $contentJson): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE pages SET content_json = :c WHERE id = :id');
        $stmt->execute(['c' => $contentJson, 'id' => $pageId]);
    }

    public static function rename(int $pageId, string $title, ?string $icon): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE pages SET title = :t, icon = :i WHERE id = :id');
        $stmt->execute([
            't' => $title !== '' ? $title : 'Sem título',
            'i' => ($icon !== null && $icon !== '') ? $icon : null,
            'id' => $pageId,
        ]);
    }

    public static function delete(int $pageId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM pages WHERE id = :id');
        $stmt->execute(['id' => $pageId]);
    }

    public static function setPublished(int $pageId, bool $published, ?string $token): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE pages SET is_published = :p, public_token = :t WHERE id = :id');
        $stmt->execute([
            'p' => $published ? 1 : 0,
            't' => $published ? $token : null,
            'id' => $pageId,
        ]);
    }

    public static function findPublicByToken(string $token): ?array
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM pages WHERE public_token = :t AND is_published = 1 LIMIT 1');
        $stmt->execute(['t' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
