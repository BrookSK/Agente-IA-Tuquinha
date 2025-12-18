<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Community
{
    public static function allActive(?string $category = null, ?string $q = null): array
    {
        $pdo = Database::getConnection();

        $category = $category !== null ? trim($category) : '';
        $q = $q !== null ? trim($q) : '';

        $where = 'is_active = 1';
        $params = [];

        if ($category !== '') {
            $where .= ' AND category = :category';
            $params['category'] = $category;
        }

        if ($q !== '') {
            $where .= ' AND (name LIKE :q OR description LIKE :q)';
            $params['q'] = '%' . $q . '%';
        }

        $stmt = $pdo->prepare('SELECT * FROM communities WHERE ' . $where . ' ORDER BY name ASC');
        $stmt->execute($params);

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

    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO communities (
                owner_user_id,
                name,
                slug,
                description,
                language,
                category,
                community_type,
                posting_policy,
                forum_type,
                image_path,
                cover_image_path,
                members_count,
                topics_count,
                is_active
            ) VALUES (
                :owner_user_id,
                :name,
                :slug,
                :description,
                :language,
                :category,
                :community_type,
                :posting_policy,
                :forum_type,
                :image_path,
                :cover_image_path,
                :members_count,
                :topics_count,
                :is_active
            )');
        $stmt->execute([
            'owner_user_id' => $data['owner_user_id'] ?? null,
            'name' => $data['name'] ?? '',
            'slug' => $data['slug'] ?? '',
            'description' => $data['description'] ?? null,
            'language' => $data['language'] ?? null,
            'category' => $data['category'] ?? null,
            'community_type' => $data['community_type'] ?? 'public',
            'posting_policy' => $data['posting_policy'] ?? 'any_member',
            'forum_type' => $data['forum_type'] ?? 'non_anonymous',
            'image_path' => $data['image_path'] ?? null,
            'cover_image_path' => $data['cover_image_path'] ?? null,
            'members_count' => (int)($data['members_count'] ?? 0),
            'topics_count' => (int)($data['topics_count'] ?? 0),
            'is_active' => (int)($data['is_active'] ?? 1),
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function allCategories(): array
    {
        return CommunityCategory::allActiveNames();
    }

    public static function update(int $id, array $data): void
    {
        if ($id <= 0) {
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE communities SET
                name = :name,
                description = :description,
                language = :language,
                category = :category,
                community_type = :community_type,
                posting_policy = :posting_policy,
                forum_type = :forum_type,
                cover_image_path = :cover_image_path
            WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'name' => $data['name'] ?? '',
            'description' => $data['description'] ?? null,
            'language' => $data['language'] ?? null,
            'category' => $data['category'] ?? null,
            'community_type' => $data['community_type'] ?? 'public',
            'posting_policy' => $data['posting_policy'] ?? 'any_member',
            'forum_type' => $data['forum_type'] ?? 'non_anonymous',
            'cover_image_path' => $data['cover_image_path'] ?? null,
        ]);
    }

    private static function buildCourseCommunitySlug(array $course): string
    {
        $id = (int)($course['id'] ?? 0);
        $slugBase = trim((string)($course['slug'] ?? ''));

        if ($slugBase !== '') {
            return 'curso-' . $slugBase;
        }

        if ($id > 0) {
            return 'curso-id-' . $id;
        }

        return 'curso-sem-id-' . bin2hex(random_bytes(4));
    }

    public static function findForCourse(array $course): ?array
    {
        $slug = self::buildCourseCommunitySlug($course);
        return self::findBySlug($slug);
    }

    public static function findOrCreateForCourse(array $course): ?array
    {
        $existing = self::findForCourse($course);
        if ($existing) {
            return $existing;
        }

        $id = (int)($course['id'] ?? 0);
        if ($id <= 0) {
            return null;
        }

        $slug = self::buildCourseCommunitySlug($course);
        $name = 'Comunidade: ' . trim((string)($course['title'] ?? 'Curso do Tuquinha'));
        $description = (string)($course['short_description'] ?? $course['description'] ?? '');
        $ownerId = !empty($course['owner_user_id']) ? (int)$course['owner_user_id'] : null;

        $communityId = self::create([
            'owner_user_id' => $ownerId,
            'name' => $name,
            'slug' => $slug,
            'description' => $description !== '' ? $description : null,
            'language' => null,
            'category' => 'Cursos',
            'community_type' => 'public',
            'posting_policy' => 'any_member',
            'forum_type' => 'non_anonymous',
            'image_path' => null,
            'cover_image_path' => null,
            'members_count' => 0,
            'topics_count' => 0,
            'is_active' => 1,
        ]);

        return self::findById($communityId);
    }
}
