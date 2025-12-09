<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class CourseLessonProgress
{
    public static function markCompleted(int $courseId, int $lessonId, int $userId): void
    {
        if ($courseId <= 0 || $lessonId <= 0 || $userId <= 0) {
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO course_lesson_progress (course_id, lesson_id, user_id, completed_at)
            VALUES (:course_id, :lesson_id, :user_id, NOW())
            ON DUPLICATE KEY UPDATE completed_at = VALUES(completed_at)');
        $stmt->execute([
            'course_id' => $courseId,
            'lesson_id' => $lessonId,
            'user_id' => $userId,
        ]);
    }

    public static function completedLessonIdsByUserAndCourse(int $courseId, int $userId): array
    {
        if ($courseId <= 0 || $userId <= 0) {
            return [];
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT lesson_id FROM course_lesson_progress WHERE course_id = :course_id AND user_id = :user_id');
        $stmt->execute([
            'course_id' => $courseId,
            'user_id' => $userId,
        ]);
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $ids = [];
        foreach ($rows as $lid) {
            $ids[(int)$lid] = true;
        }
        return $ids;
    }
}
