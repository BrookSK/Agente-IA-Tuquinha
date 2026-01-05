<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Personality
{
    public static function hasAnyUsableForUsers(): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT 1 FROM personalities WHERE active = 1 AND coming_soon = 0 LIMIT 1');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (bool)$row;
    }

    public static function allVisibleForUsers(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM personalities WHERE active = 1 ORDER BY is_default DESC, name ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function allActive(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM personalities WHERE active = 1 ORDER BY is_default DESC, name ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function all(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM personalities ORDER BY is_default DESC, name ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM personalities WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findDefault(): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM personalities WHERE is_default = 1 AND active = 1 ORDER BY id ASC LIMIT 1');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO personalities (name, area, slug, prompt, image_path, is_default, active, coming_soon) VALUES (:name, :area, :slug, :prompt, :image_path, :is_default, :active, :coming_soon)');
        $stmt->execute([
            'name' => $data['name'],
            'area' => $data['area'],
            'slug' => $data['slug'],
            'prompt' => $data['prompt'],
            'image_path' => $data['image_path'] ?? null,
            'is_default' => !empty($data['is_default']) ? 1 : 0,
            'active' => !empty($data['active']) ? 1 : 0,
            'coming_soon' => !empty($data['coming_soon']) ? 1 : 0,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE personalities SET name = :name, area = :area, slug = :slug, prompt = :prompt, image_path = :image_path, is_default = :is_default, active = :active, coming_soon = :coming_soon WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'area' => $data['area'],
            'slug' => $data['slug'],
            'prompt' => $data['prompt'],
            'image_path' => $data['image_path'] ?? null,
            'is_default' => !empty($data['is_default']) ? 1 : 0,
            'active' => !empty($data['active']) ? 1 : 0,
            'coming_soon' => !empty($data['coming_soon']) ? 1 : 0,
        ]);
    }

    public static function deactivate(int $id): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE personalities SET active = 0 WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
