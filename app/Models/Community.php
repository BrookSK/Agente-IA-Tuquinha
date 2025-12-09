<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Community
{
    public static function allActive(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM communities WHERE is_active = 1 ORDER BY name ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function findById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM communities WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $slug = trim($slug);
        if ($slug === '') {
            return null;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM communities WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
