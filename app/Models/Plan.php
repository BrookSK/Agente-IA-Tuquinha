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

    public static function all(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM plans ORDER BY sort_order ASC, price_cents ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function countAll(): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT COUNT(*) AS c FROM plans');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['c'] ?? 0);
    }

    public static function findBySlug(string $slug): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM plans WHERE slug = :slug AND is_active = 1 LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO plans (name, slug, price_cents, description, benefits, allowed_models, default_model, allow_audio, allow_images, allow_files, is_active, history_retention_days)
            VALUES (:name, :slug, :price_cents, :description, :benefits, :allowed_models, :default_model, :allow_audio, :allow_images, :allow_files, :is_active, :history_retention_days)');
        $stmt->execute([
            'name' => $data['name'] ?? '',
            'slug' => $data['slug'] ?? '',
            'price_cents' => (int)($data['price_cents'] ?? 0),
            'description' => $data['description'] ?? null,
            'benefits' => $data['benefits'] ?? null,
            'allowed_models' => $data['allowed_models'] ?? null,
            'default_model' => $data['default_model'] ?? null,
            'allow_audio' => (int)($data['allow_audio'] ?? 0),
            'allow_images' => (int)($data['allow_images'] ?? 0),
            'allow_files' => (int)($data['allow_files'] ?? 0),
            'is_active' => (int)($data['is_active'] ?? 1),
            'history_retention_days' => isset($data['history_retention_days']) ? (int)$data['history_retention_days'] : null,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function updateById(int $id, array $data): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE plans SET
            name = :name,
            slug = :slug,
            price_cents = :price_cents,
            description = :description,
            benefits = :benefits,
            allowed_models = :allowed_models,
            default_model = :default_model,
            allow_audio = :allow_audio,
            allow_images = :allow_images,
            allow_files = :allow_files,
            is_active = :is_active,
            history_retention_days = :history_retention_days
            WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'name' => $data['name'] ?? '',
            'slug' => $data['slug'] ?? '',
            'price_cents' => (int)($data['price_cents'] ?? 0),
            'description' => $data['description'] ?? null,
            'benefits' => $data['benefits'] ?? null,
            'allow_audio' => (int)($data['allow_audio'] ?? 0),
            'allow_images' => (int)($data['allow_images'] ?? 0),
            'allow_files' => (int)($data['allow_files'] ?? 0),
            'is_active' => (int)($data['is_active'] ?? 1),
            'history_retention_days' => isset($data['history_retention_days']) ? (int)$data['history_retention_days'] : null,
        ]);
    }

    public static function setActive(int $id, bool $active): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE plans SET is_active = :active WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'active' => $active ? 1 : 0,
        ]);
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

    public static function findTopActive(): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM plans WHERE is_active = 1 ORDER BY price_cents DESC, sort_order DESC LIMIT 1');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Plano padrão para usuários comuns (não-admin).
     * Ordem de prioridade:
     * 1) Plano ativo com is_default_for_users = 1 (primeiro por sort_order, price_cents ASC)
     * 2) Plano com slug 'free'
     * 3) Primeiro plano ativo mais barato
     */
    public static function findDefaultForUsers(): ?array
    {
        $pdo = Database::getConnection();

        // 1) Plano marcado explicitamente como padrão
        $stmt = $pdo->query('SELECT * FROM plans WHERE is_active = 1 AND is_default_for_users = 1 ORDER BY sort_order ASC, price_cents ASC LIMIT 1');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row;
        }

        // 2) Fallback para slug "free"
        $free = self::findBySlug('free');
        if ($free) {
            return $free;
        }

        // 3) Fallback geral: primeiro plano ativo mais barato
        $stmt = $pdo->query('SELECT * FROM plans WHERE is_active = 1 ORDER BY price_cents ASC, sort_order ASC LIMIT 1');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
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
