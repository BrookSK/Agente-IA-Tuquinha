<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class CourseExamAttempt
{
    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO course_exam_attempts (exam_id, user_id, score_percent, is_passed)
            VALUES (:exam_id, :user_id, :score_percent, :is_passed)');
        $stmt->execute([
            'exam_id' => (int)($data['exam_id'] ?? 0),
            'user_id' => (int)($data['user_id'] ?? 0),
            'score_percent' => (int)($data['score_percent'] ?? 0),
            'is_passed' => !empty($data['is_passed']) ? 1 : 0,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function countAttemptsForUser(int $examId, int $userId): int
    {
        if ($examId <= 0 || $userId <= 0) {
            return 0;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM course_exam_attempts WHERE exam_id = :exam_id AND user_id = :user_id');
        $stmt->execute([
            'exam_id' => $examId,
            'user_id' => $userId,
        ]);
        return (int)$stmt->fetchColumn();
    }

    public static function findLastForUser(int $examId, int $userId): ?array
    {
        if ($examId <= 0 || $userId <= 0) {
            return null;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM course_exam_attempts WHERE exam_id = :exam_id AND user_id = :user_id ORDER BY created_at DESC, id DESC LIMIT 1');
        $stmt->execute([
            'exam_id' => $examId,
            'user_id' => $userId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function hasPassed(int $examId, int $userId): bool
    {
        if ($examId <= 0 || $userId <= 0) {
            return false;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT 1 FROM course_exam_attempts WHERE exam_id = :exam_id AND user_id = :user_id AND is_passed = 1 LIMIT 1');
        $stmt->execute([
            'exam_id' => $examId,
            'user_id' => $userId,
        ]);
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    }
}
