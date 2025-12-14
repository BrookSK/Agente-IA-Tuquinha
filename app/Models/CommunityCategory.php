<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class CommunityCategory
{
    public static function all(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM community_categories ORDER BY is_active DESC, sort_order ASC, name ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function allActiveNames(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT name FROM community_categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC');
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        return array_map(static fn($c) => (string)$c, $rows);
    }

    public static function findById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM community_categories WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(string $name): void
    {
        $name = trim($name);
        if ($name === '') {
            return;
        }

        $pdo = Database::getConnection();
        $maxOrder = (int)$pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM community_categories')->fetchColumn();
        $sortOrder = $maxOrder + 10;

        $stmt = $pdo->prepare('INSERT INTO community_categories (name, sort_order, is_active) VALUES (:name, :sort_order, 1)');
        $stmt->execute([
            'name' => $name,
            'sort_order' => $sortOrder,
        ]);
    }

    public static function toggleActive(int $id): void
    {
        if ($id <= 0) {
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE community_categories SET is_active = 1 - is_active WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
