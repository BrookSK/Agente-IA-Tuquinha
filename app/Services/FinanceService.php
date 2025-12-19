<?php

namespace App\Services;

use App\Core\Database;
use App\Models\SubscriptionPayment;
use PDO;

class FinanceService
{
    public static function periodRange(string $mode, int $year, int $month, int $semester): array
    {
        $mode = $mode !== '' ? $mode : 'month';

        if ($year < 2000) {
            $year = (int)date('Y');
        }

        if ($mode === 'year') {
            $start = sprintf('%04d-01-01 00:00:00', $year);
            $end = sprintf('%04d-01-01 00:00:00', $year + 1);
            return [$start, $end];
        }

        if ($mode === 'semester') {
            $semester = ($semester === 2) ? 2 : 1;
            $startMonth = $semester === 2 ? 7 : 1;
            $start = sprintf('%04d-%02d-01 00:00:00', $year, $startMonth);
            $end = date('Y-m-d H:i:s', strtotime($start . ' +6 months'));
            return [$start, $end];
        }

        // month
        if ($month < 1) $month = 1;
        if ($month > 12) $month = 12;
        $start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
        $end = date('Y-m-d H:i:s', strtotime($start . ' +1 month'));
        return [$start, $end];
    }

    public static function courseRevenueCents(string $start, string $end): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT COALESCE(SUM(amount_cents), 0) AS total
            FROM course_purchases
            WHERE status = "paid" AND paid_at IS NOT NULL
              AND paid_at >= :start AND paid_at < :end');
        $stmt->execute([
            'start' => $start,
            'end' => $end,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    public static function courseRevenueByCourse(string $start, string $end, int $limit = 50): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT c.id AS course_id, c.title, c.slug, COUNT(cp.id) AS paid_count, COALESCE(SUM(cp.amount_cents), 0) AS sales_cents
            FROM course_purchases cp
            JOIN courses c ON c.id = cp.course_id
            WHERE cp.status = "paid" AND cp.paid_at IS NOT NULL
              AND cp.paid_at >= :start AND cp.paid_at < :end
            GROUP BY c.id, c.title, c.slug
            ORDER BY sales_cents DESC
            LIMIT :lim');
        $stmt->bindValue(':start', $start);
        $stmt->bindValue(':end', $end);
        $stmt->bindValue(':lim', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function courseCommissionCents(string $start, string $end): int
    {
        // Comissão devida por vendas pagas no período
        $pdo = Database::getConnection();
        $sql = 'SELECT COALESCE(SUM(ROUND(cp.amount_cents * (COALESCE(cpc.commission_percent, p.default_commission_percent) / 100), 0)), 0) AS total
            FROM course_purchases cp
            JOIN courses c ON c.id = cp.course_id
            JOIN course_partners p ON p.user_id = c.owner_user_id
            LEFT JOIN course_partner_commissions cpc ON cpc.partner_id = p.id AND cpc.course_id = c.id
            WHERE cp.status = "paid" AND cp.paid_at IS NOT NULL
              AND cp.paid_at >= :start AND cp.paid_at < :end';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'start' => $start,
            'end' => $end,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    public static function planRevenueCents(string $start, string $end): int
    {
        return SubscriptionPayment::sumRevenueCentsByPeriod($start, $end);
    }

    public static function planRevenueByType(string $start, string $end): array
    {
        return SubscriptionPayment::sumRevenueCentsByPlanPeriodType($start, $end);
    }

    public static function summary(string $start, string $end): array
    {
        $plan = self::planRevenueCents($start, $end);
        $planByType = self::planRevenueByType($start, $end);
        $courses = self::courseRevenueCents($start, $end);
        $courseCommission = self::courseCommissionCents($start, $end);

        $totalRevenue = $plan + $courses;
        $netRevenue = $totalRevenue - $courseCommission;
        $netCourses = $courses - $courseCommission;

        return [
            'plan_revenue_cents' => $plan,
            'plan_revenue_by_type_cents' => $planByType,
            'course_revenue_cents' => $courses,
            'course_commission_cents' => $courseCommission,
            'course_net_cents' => $netCourses,
            'total_revenue_cents' => $totalRevenue,
            'total_net_cents' => $netRevenue,
        ];
    }
}
