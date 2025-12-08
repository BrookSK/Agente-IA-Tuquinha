<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class CourseLessonComment
{
    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO course_lesson_comments (course_id, lesson_id, live_id, user_id, parent_id, body)
            VALUES (:course_id, :lesson_id, :live_id, :user_id, :parent_id, :body)');
        $stmt->execute([
            'course_id' => (int)($data['course_id'] ?? 0),
            'lesson_id' => isset($data['lesson_id']) ? (int)$data['lesson_id'] : null,
            'live_id' => isset($data['live_id']) ? (int)$data['live_id'] : null,
            'user_id' => (int)($data['user_id'] ?? 0),
            'parent_id' => !empty($data['parent_id']) ? (int)$data['parent_id'] : null,
            'body' => $data['body'] ?? '',
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM course_lesson_comments WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function allByCourseWithUser(int $courseId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT c.*, u.name AS user_name
            FROM course_lesson_comments c
            JOIN users u ON u.id = c.user_id
            WHERE c.course_id = :course_id AND c.lesson_id IS NOT NULL
            ORDER BY c.created_at ASC, c.id ASC');
        $stmt->execute(['course_id' => $courseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function allByLessonWithUser(int $lessonId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT c.*, u.name AS user_name
            FROM course_lesson_comments c
            JOIN users u ON u.id = c.user_id
            WHERE c.lesson_id = :lesson_id
            ORDER BY c.created_at ASC, c.id ASC');
        $stmt->execute(['lesson_id' => $lessonId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function allByLiveWithUser(int $liveId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT c.*, u.name AS user_name
            FROM course_lesson_comments c
            JOIN users u ON u.id = c.user_id
            WHERE c.live_id = :live_id
            ORDER BY c.created_at ASC, c.id ASC');
        $stmt->execute(['live_id' => $liveId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
