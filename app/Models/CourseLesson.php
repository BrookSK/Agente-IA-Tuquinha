<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class CourseLesson
{
    public static function allByCourseId(int $courseId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM course_lessons WHERE course_id = :course_id ORDER BY sort_order ASC, id ASC');
        $stmt->execute(['course_id' => $courseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM course_lessons WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO course_lessons (course_id, module_id, title, description, video_url, sort_order, is_published)
            VALUES (:course_id, :module_id, :title, :description, :video_url, :sort_order, :is_published)');
        $stmt->execute([
            'course_id' => (int)($data['course_id'] ?? 0),
            'module_id' => !empty($data['module_id']) ? (int)$data['module_id'] : null,
            'title' => $data['title'] ?? '',
            'description' => $data['description'] ?? null,
            'video_url' => $data['video_url'] ?? '',
            'sort_order' => (int)($data['sort_order'] ?? 0),
            'is_published' => (int)($data['is_published'] ?? 1),
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE course_lessons SET
            module_id = :module_id,
            title = :title,
            description = :description,
            video_url = :video_url,
            sort_order = :sort_order,
            is_published = :is_published,
            updated_at = NOW()
            WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'module_id' => !empty($data['module_id']) ? (int)$data['module_id'] : null,
            'title' => $data['title'] ?? '',
            'description' => $data['description'] ?? null,
            'video_url' => $data['video_url'] ?? '',
            'sort_order' => (int)($data['sort_order'] ?? 0),
            'is_published' => (int)($data['is_published'] ?? 1),
        ]);
    }

    public static function delete(int $id): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM course_lessons WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
