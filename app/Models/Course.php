<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Course
{
    public static function all(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM courses ORDER BY created_at DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function allActive(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM courses WHERE is_active = 1 ORDER BY created_at DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function allByOwner(int $userId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM courses WHERE owner_user_id = :owner_id ORDER BY created_at DESC');
        $stmt->execute(['owner_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM courses WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM courses WHERE slug = :slug AND is_active = 1 LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO courses (owner_user_id, title, slug, short_description, description, image_path, badge_image_path, is_paid, price_cents, allow_plan_access_only, allow_public_purchase, is_active)
            VALUES (:owner_user_id, :title, :slug, :short_description, :description, :image_path, :badge_image_path, :is_paid, :price_cents, :allow_plan_access_only, :allow_public_purchase, :is_active)');
        $stmt->execute([
            'owner_user_id' => $data['owner_user_id'] ?? null,
            'title' => $data['title'] ?? '',
            'slug' => $data['slug'] ?? '',
            'short_description' => $data['short_description'] ?? null,
            'description' => $data['description'] ?? null,
            'image_path' => $data['image_path'] ?? null,
            'badge_image_path' => $data['badge_image_path'] ?? null,
            'is_paid' => (int)($data['is_paid'] ?? 0),
            'price_cents' => $data['price_cents'] ?? null,
            'allow_plan_access_only' => (int)($data['allow_plan_access_only'] ?? 1),
            'allow_public_purchase' => (int)($data['allow_public_purchase'] ?? 0),
            'is_active' => (int)($data['is_active'] ?? 1),
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE courses SET
            owner_user_id = :owner_user_id,
            title = :title,
            slug = :slug,
            short_description = :short_description,
            description = :description,
            image_path = :image_path,
            badge_image_path = :badge_image_path,
            is_paid = :is_paid,
            price_cents = :price_cents,
            allow_plan_access_only = :allow_plan_access_only,
            allow_public_purchase = :allow_public_purchase,
            is_active = :is_active,
            updated_at = NOW()
            WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'owner_user_id' => $data['owner_user_id'] ?? null,
            'title' => $data['title'] ?? '',
            'slug' => $data['slug'] ?? '',
            'short_description' => $data['short_description'] ?? null,
            'description' => $data['description'] ?? null,
            'image_path' => $data['image_path'] ?? null,
            'badge_image_path' => $data['badge_image_path'] ?? null,
            'is_paid' => (int)($data['is_paid'] ?? 0),
            'price_cents' => $data['price_cents'] ?? null,
            'allow_plan_access_only' => (int)($data['allow_plan_access_only'] ?? 1),
            'allow_public_purchase' => (int)($data['allow_public_purchase'] ?? 0),
            'is_active' => (int)($data['is_active'] ?? 1),
        ]);
    }
}
