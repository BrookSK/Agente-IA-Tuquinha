<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class CourseExamQuestion
{
    public static function allByExam(int $examId): array
    {
        if ($examId <= 0) {
            return [];
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM course_exam_questions WHERE exam_id = :exam_id ORDER BY sort_order ASC, id ASC');
        $stmt->execute(['exam_id' => $examId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO course_exam_questions (exam_id, question_text, sort_order) VALUES (:exam_id, :question_text, :sort_order)');
        $stmt->execute([
            'exam_id' => (int)($data['exam_id'] ?? 0),
            'question_text' => $data['question_text'] ?? '',
            'sort_order' => (int)($data['sort_order'] ?? 0),
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function deleteByExam(int $examId): void
    {
        if ($examId <= 0) {
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM course_exam_questions WHERE exam_id = :exam_id');
        $stmt->execute(['exam_id' => $examId]);
    }
}
