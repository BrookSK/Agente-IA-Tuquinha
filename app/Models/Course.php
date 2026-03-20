<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Course
{
    public static function findByExternalToken(string $token): ?array
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM courses WHERE external_token = :t AND is_active = 1 AND is_external = 1 LIMIT 1');
        $stmt->execute(['t' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function ensureExternalToken(int $courseId): ?string
    {
        if ($courseId <= 0) {
            return null;
        }

        $course = self::findById($courseId);
        if (!$course) {
            return null;
        }

        $existing = trim((string)($course['external_token'] ?? ''));
        if ($existing !== '') {
            return $existing;
        }

        $token = bin2hex(random_bytes(24));
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE courses SET external_token = :t WHERE id = :id LIMIT 1');
        $stmt->execute(['t' => $token, 'id' => $courseId]);
        return $token;
    }

    public static function all(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM courses ORDER BY created_at DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function allActive(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM courses WHERE is_active = 1 AND is_external = 0 ORDER BY created_at DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function allByOwner(int $userId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM courses WHERE owner_user_id = :owner_id ORDER BY created_at DESC');
        $stmt->execute(['owner_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function allExternalActiveByOwner(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM courses WHERE owner_user_id = :owner_id AND is_active = 1 AND is_external = 1 ORDER BY created_at DESC');
        $stmt->execute(['owner_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findExternalActiveBySlugAndOwner(string $slug, int $ownerUserId): ?array
    {
        $slug = trim($slug);
        if ($slug === '' || $ownerUserId <= 0) {
            return null;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM courses WHERE slug = :slug AND owner_user_id = :owner_id AND is_active = 1 AND is_external = 1 LIMIT 1');
        $stmt->execute([
            'slug' => $slug,
            'owner_id' => $ownerUserId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
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
        $stmt = $pdo->prepare('INSERT INTO courses (owner_user_id, title, slug, short_description, description, tagline, image_path, badge_image_path, certificate_syllabus, certificate_workload_hours, certificate_location, is_paid, price_cents, allow_plan_access_only, allow_public_purchase, is_active, is_external, allow_community_access)
            VALUES (:owner_user_id, :title, :slug, :short_description, :description, :tagline, :image_path, :badge_image_path, :certificate_syllabus, :certificate_workload_hours, :certificate_location, :is_paid, :price_cents, :allow_plan_access_only, :allow_public_purchase, :is_active, :is_external, :allow_community_access)');
        $stmt->execute([
            'owner_user_id' => $data['owner_user_id'] ?? null,
            'title' => $data['title'] ?? '',
            'slug' => $data['slug'] ?? '',
            'short_description' => $data['short_description'] ?? null,
            'description' => $data['description'] ?? null,
            'tagline' => $data['tagline'] ?? 'Aprenda Agora.',
            'image_path' => $data['image_path'] ?? null,
            'badge_image_path' => $data['badge_image_path'] ?? null,
            'certificate_syllabus' => $data['certificate_syllabus'] ?? null,
            'certificate_workload_hours' => $data['certificate_workload_hours'] ?? null,
            'certificate_location' => $data['certificate_location'] ?? null,
            'is_paid' => (int)($data['is_paid'] ?? 0),
            'price_cents' => $data['price_cents'] ?? null,
            'allow_plan_access_only' => (int)($data['allow_plan_access_only'] ?? 1),
            'allow_public_purchase' => (int)($data['allow_public_purchase'] ?? 0),
            'is_active' => (int)($data['is_active'] ?? 1),
            'is_external' => (int)($data['is_external'] ?? 0),
            'allow_community_access' => (int)($data['allow_community_access'] ?? 0),
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
            tagline = :tagline,
            image_path = :image_path,
            badge_image_path = :badge_image_path,
            certificate_syllabus = :certificate_syllabus,
            certificate_workload_hours = :certificate_workload_hours,
            certificate_location = :certificate_location,
            is_paid = :is_paid,
            price_cents = :price_cents,
            allow_plan_access_only = :allow_plan_access_only,
            allow_public_purchase = :allow_public_purchase,
            is_active = :is_active,
            is_external = :is_external,
            allow_community_access = :allow_community_access,
            updated_at = NOW()
            WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'owner_user_id' => $data['owner_user_id'] ?? null,
            'title' => $data['title'] ?? '',
            'slug' => $data['slug'] ?? '',
            'short_description' => $data['short_description'] ?? null,
            'description' => $data['description'] ?? null,
            'tagline' => $data['tagline'] ?? 'Aprenda Agora.',
            'image_path' => $data['image_path'] ?? null,
            'badge_image_path' => $data['badge_image_path'] ?? null,
            'certificate_syllabus' => $data['certificate_syllabus'] ?? null,
            'certificate_workload_hours' => $data['certificate_workload_hours'] ?? null,
            'certificate_location' => $data['certificate_location'] ?? null,
            'is_paid' => (int)($data['is_paid'] ?? 0),
            'price_cents' => $data['price_cents'] ?? null,
            'allow_plan_access_only' => (int)($data['allow_plan_access_only'] ?? 1),
            'allow_public_purchase' => (int)($data['allow_public_purchase'] ?? 0),
            'is_active' => (int)($data['is_active'] ?? 1),
            'is_external' => (int)($data['is_external'] ?? 0),
            'allow_community_access' => (int)($data['allow_community_access'] ?? 0),
        ]);
    }
}
