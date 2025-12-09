<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class CourseModuleExam
{
    public static function findByModuleId(int $moduleId): ?array
    {
        if ($moduleId <= 0) {
            return null;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM course_module_exams WHERE module_id = :module_id LIMIT 1');
        $stmt->execute(['module_id' => $moduleId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM course_module_exams WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function upsertForModule(int $moduleId, int $passScorePercent, int $maxAttempts, bool $isActive): int
    {
        $existing = self::findByModuleId($moduleId);

        $pdo = Database::getConnection();
        if ($existing) {
            $stmt = $pdo->prepare('UPDATE course_module_exams SET pass_score_percent = :pass, max_attempts = :max_attempts, is_active = :is_active, updated_at = NOW() WHERE id = :id');
            $stmt->execute([
                'id' => (int)$existing['id'],
                'pass' => $passScorePercent,
                'max_attempts' => $maxAttempts,
                'is_active' => $isActive ? 1 : 0,
            ]);
            return (int)$existing['id'];
        }

        $stmt = $pdo->prepare('INSERT INTO course_module_exams (module_id, pass_score_percent, max_attempts, is_active) VALUES (:module_id, :pass, :max_attempts, :is_active)');
        $stmt->execute([
            'module_id' => $moduleId,
            'pass' => $passScorePercent,
            'max_attempts' => $maxAttempts,
            'is_active' => $isActive ? 1 : 0,
        ]);
        return (int)$pdo->lastInsertId();
    }
}
