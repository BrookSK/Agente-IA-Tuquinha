<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class CourseModule
{
    public static function allByCourse(int $courseId): array
    {
        if ($courseId <= 0) {
            return [];
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM course_modules WHERE course_id = :course_id ORDER BY sort_order ASC, id ASC');
        $stmt->execute(['course_id' => $courseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function findById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM course_modules WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO course_modules (course_id, title, description, sort_order) VALUES (:course_id, :title, :description, :sort_order)');
        $stmt->execute([
            'course_id' => (int)($data['course_id'] ?? 0),
            'title' => $data['title'] ?? '',
            'description' => $data['description'] ?? null,
            'sort_order' => (int)($data['sort_order'] ?? 0),
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE course_modules SET title = :title, description = :description, sort_order = :sort_order, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'title' => $data['title'] ?? '',
            'description' => $data['description'] ?? null,
            'sort_order' => (int)($data['sort_order'] ?? 0),
        ]);
    }

    public static function delete(int $id): void
    {
        if ($id <= 0) {
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM course_modules WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
