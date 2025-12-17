<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class SocialPortfolioItem
{
    public static function create(int $userId, string $title, ?string $description, ?string $externalUrl, ?int $projectId = null): int
    {
        $title = trim($title);
        if ($userId <= 0 || $title === '') {
            return 0;
        }

        $description = $description !== null ? trim($description) : null;
        $externalUrl = $externalUrl !== null ? trim($externalUrl) : null;
        if ($externalUrl !== null && $externalUrl !== '' && !preg_match('/^https?:\/\//i', $externalUrl)) {
            $externalUrl = 'https://' . $externalUrl;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO social_portfolio_items (user_id, project_id, title, description, external_url)
            VALUES (:user_id, :project_id, :title, :description, :external_url)');
        $stmt->execute([
            'user_id' => $userId,
            'project_id' => $projectId && $projectId > 0 ? $projectId : null,
            'title' => $title,
            'description' => $description !== '' ? $description : null,
            'external_url' => $externalUrl !== '' ? $externalUrl : null,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, int $userId, string $title, ?string $description, ?string $externalUrl, ?int $projectId = null): void
    {
        $title = trim($title);
        if ($id <= 0 || $userId <= 0 || $title === '') {
            return;
        }

        $description = $description !== null ? trim($description) : null;
        $externalUrl = $externalUrl !== null ? trim($externalUrl) : null;
        if ($externalUrl !== null && $externalUrl !== '' && !preg_match('/^https?:\/\//i', $externalUrl)) {
            $externalUrl = 'https://' . $externalUrl;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE social_portfolio_items
            SET title = :title, description = :description, external_url = :external_url, project_id = :project_id, updated_at = NOW()
            WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL');
        $stmt->execute([
            'title' => $title,
            'description' => $description !== '' ? $description : null,
            'external_url' => $externalUrl !== '' ? $externalUrl : null,
            'project_id' => $projectId && $projectId > 0 ? $projectId : null,
            'id' => $id,
            'user_id' => $userId,
        ]);
    }

    public static function softDelete(int $id, int $userId): void
    {
        if ($id <= 0 || $userId <= 0) {
            return;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE social_portfolio_items SET deleted_at = NOW() WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL');
        $stmt->execute([
            'id' => $id,
            'user_id' => $userId,
        ]);
    }

    public static function allForUser(int $userId, int $limit = 100): array
    {
        if ($userId <= 0) {
            return [];
        }
        $limit = $limit > 0 ? $limit : 100;
        if ($limit > 300) {
            $limit = 300;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM social_portfolio_items WHERE user_id = :uid AND deleted_at IS NULL ORDER BY created_at DESC LIMIT ' . (int)$limit);
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function findById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM social_portfolio_items WHERE id = :id AND deleted_at IS NULL LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
