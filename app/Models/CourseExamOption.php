<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class CourseExamOption
{
    public static function allByQuestion(int $questionId): array
    {
        if ($questionId <= 0) {
            return [];
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM course_exam_options WHERE question_id = :question_id ORDER BY sort_order ASC, id ASC');
        $stmt->execute(['question_id' => $questionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO course_exam_options (question_id, option_text, is_correct, sort_order) VALUES (:question_id, :option_text, :is_correct, :sort_order)');
        $stmt->execute([
            'question_id' => (int)($data['question_id'] ?? 0),
            'option_text' => $data['option_text'] ?? '',
            'is_correct' => !empty($data['is_correct']) ? 1 : 0,
            'sort_order' => (int)($data['sort_order'] ?? 0),
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function deleteByQuestionIds(array $questionIds): void
    {
        if (empty($questionIds)) {
            return;
        }

        $ids = array_map('intval', $questionIds);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM course_exam_options WHERE question_id IN ($placeholders)");
        $stmt->execute($ids);
    }
}
