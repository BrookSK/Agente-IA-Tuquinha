<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class CourseEnrollment
{
    public static function isEnrolled(int $courseId, int $userId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id FROM course_enrollments WHERE course_id = :course_id AND user_id = :user_id LIMIT 1');
        $stmt->execute([
            'course_id' => $courseId,
            'user_id' => $userId,
        ]);
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function enroll(int $courseId, int $userId): bool
    {
        if ($courseId <= 0 || $userId <= 0) {
            return false;
        }
        if (self::isEnrolled($courseId, $userId)) {
            return true;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO course_enrollments (course_id, user_id) VALUES (:course_id, :user_id)');
        return $stmt->execute([
            'course_id' => $courseId,
            'user_id' => $userId,
        ]);
    }

    public static function unenroll(int $courseId, int $userId): bool
    {
        if ($courseId <= 0 || $userId <= 0) {
            return false;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM course_enrollments WHERE course_id = :course_id AND user_id = :user_id');
        return $stmt->execute([
            'course_id' => $courseId,
            'user_id' => $userId,
        ]);
    }

    public static function allByCourse(int $courseId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM course_enrollments WHERE course_id = :course_id ORDER BY created_at ASC');
        $stmt->execute(['course_id' => $courseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function allByUser(int $userId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM course_enrollments WHERE user_id = :user_id ORDER BY created_at DESC');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
