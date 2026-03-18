<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class ProfessionalMetrics
{
    public static function findByPartnerId(int $partnerId): ?array
    {
        if ($partnerId <= 0) {
            return null;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM professional_metrics WHERE partner_id = :partner_id LIMIT 1');
        $stmt->execute(['partner_id' => $partnerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function updateMetrics(int $partnerId): void
    {
        if ($partnerId <= 0) {
            return;
        }

        $pdo = Database::getConnection();

        $partner = CoursePartner::findByUserId($partnerId);
        if (!$partner) {
            return;
        }

        $partnerDbId = (int)$partner['id'];

        $totalStudents = 0;
        $stmt = $pdo->prepare('SELECT COUNT(DISTINCT ce.user_id) AS total
            FROM course_enrollments ce
            JOIN courses c ON c.id = ce.course_id
            WHERE c.owner_user_id = :owner_id');
        $stmt->execute(['owner_id' => $partnerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $totalStudents = (int)($row['total'] ?? 0);
        }

        $totalRevenueCents = 0;
        $totalSales = 0;
        $stmt = $pdo->prepare('SELECT COUNT(*) AS total_sales, SUM(amount_cents) AS total_revenue
            FROM course_purchases cp
            JOIN courses c ON c.id = cp.course_id
            WHERE c.owner_user_id = :owner_id
              AND cp.status = "paid"');
        $stmt->execute(['owner_id' => $partnerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $totalSales = (int)($row['total_sales'] ?? 0);
            $totalRevenueCents = (int)($row['total_revenue'] ?? 0);
        }

        $activeCourses = 0;
        $stmt = $pdo->prepare('SELECT COUNT(*) AS total
            FROM courses
            WHERE owner_user_id = :owner_id
              AND is_active = 1');
        $stmt->execute(['owner_id' => $partnerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $activeCourses = (int)($row['total'] ?? 0);
        }

        $existing = self::findByPartnerId($partnerId);
        if ($existing) {
            $stmt = $pdo->prepare('UPDATE professional_metrics SET
                total_students = :total_students,
                total_revenue_cents = :total_revenue_cents,
                active_courses = :active_courses,
                total_sales = :total_sales,
                updated_at = NOW()
                WHERE partner_id = :partner_id');
            $stmt->execute([
                'partner_id' => $partnerId,
                'total_students' => $totalStudents,
                'total_revenue_cents' => $totalRevenueCents,
                'active_courses' => $activeCourses,
                'total_sales' => $totalSales,
            ]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO professional_metrics (partner_id, total_students, total_revenue_cents, active_courses, total_sales)
                VALUES (:partner_id, :total_students, :total_revenue_cents, :active_courses, :total_sales)');
            $stmt->execute([
                'partner_id' => $partnerId,
                'total_students' => $totalStudents,
                'total_revenue_cents' => $totalRevenueCents,
                'active_courses' => $activeCourses,
                'total_sales' => $totalSales,
            ]);
        }
    }

    public static function getOrCreate(int $partnerId): array
    {
        $metrics = self::findByPartnerId($partnerId);
        if (!$metrics) {
            self::updateMetrics($partnerId);
            $metrics = self::findByPartnerId($partnerId);
        }

        return $metrics ?: [
            'partner_id' => $partnerId,
            'total_students' => 0,
            'total_revenue_cents' => 0,
            'active_courses' => 0,
            'total_sales' => 0,
        ];
    }
}
