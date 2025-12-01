<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Plan
{
    public int $id;
    public string $name;
    public string $slug;
    public int $price_cents;
    public ?int $monthly_message_limit;
    public ?string $description;
    public ?string $benefits;

    public static function allActive(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM plans WHERE is_active = 1 ORDER BY sort_order ASC, price_cents ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findBySlug(string $slug): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM plans WHERE slug = :slug AND is_active = 1 LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM plans WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findBySessionSlug(?string $slug): ?array
    {
        if (!$slug) {
            return null;
        }
        return self::findBySlug($slug);
    }

    public static function parseAllowedModels(?string $allowed): array
    {
        if (!$allowed) {
            return [];
        }

        $json = json_decode($allowed, true);
        if (is_array($json)) {
            return array_values(array_filter(array_map('strval', $json)));
        }

        $parts = array_map('trim', explode(',', $allowed));
        return array_values(array_filter($parts));
    }
}
