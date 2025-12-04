<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class CoursePartnerCommission
{
    public static function findByPartnerAndCourse(int $partnerId, int $courseId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM course_partner_commissions WHERE partner_id = :partner_id AND course_id = :course_id LIMIT 1');
        $stmt->execute([
            'partner_id' => $partnerId,
            'course_id' => $courseId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function setCommission(int $partnerId, int $courseId, float $percent): void
    {
        $pdo = Database::getConnection();
        $existing = self::findByPartnerAndCourse($partnerId, $courseId);
        if ($existing) {
            $stmt = $pdo->prepare('UPDATE course_partner_commissions SET commission_percent = :percent WHERE id = :id');
            $stmt->execute([
                'id' => (int)$existing['id'],
                'percent' => $percent,
            ]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO course_partner_commissions (partner_id, course_id, commission_percent)
                VALUES (:partner_id, :course_id, :percent)');
            $stmt->execute([
                'partner_id' => $partnerId,
                'course_id' => $courseId,
                'percent' => $percent,
            ]);
        }
    }

    public static function deleteByPartnerAndCourse(int $partnerId, int $courseId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM course_partner_commissions WHERE partner_id = :partner_id AND course_id = :course_id');
        $stmt->execute([
            'partner_id' => $partnerId,
            'course_id' => $courseId,
        ]);
    }
}
