<?php

namespace App\Services;

use App\Core\Database;
use App\Models\CoursePartner;
use PDO;

class CourseCommissionService
{
    public static function computePartnerMonthByPartnerId(int $partnerId, int $year, int $month): array
    {
        $start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
        $end = date('Y-m-d H:i:s', strtotime($start . ' +1 month'));

        $pdo = Database::getConnection();

        $partnerStmt = $pdo->prepare('SELECT p.*, u.name AS user_name, u.email AS user_email
            FROM course_partners p
            JOIN users u ON u.id = p.user_id
            WHERE p.id = :id
            LIMIT 1');
        $partnerStmt->execute(['id' => $partnerId]);
        $partner = $partnerStmt->fetch(PDO::FETCH_ASSOC);
        if (!$partner) {
            return [
                'partner' => null,
                'total_sales_cents' => 0,
                'total_commission_cents' => 0,
                'by_course' => [],
            ];
        }

        $sql = 'SELECT c.id AS course_id, c.title AS course_title, c.slug AS course_slug,
                       COUNT(cp.id) AS paid_count,
                       COALESCE(SUM(cp.amount_cents), 0) AS sales_cents,
                       COALESCE(cpc.commission_percent, p.default_commission_percent) AS commission_percent,
                       COALESCE(SUM(ROUND(cp.amount_cents * (COALESCE(cpc.commission_percent, p.default_commission_percent) / 100), 0)), 0) AS commission_cents
                FROM course_partners p
                JOIN courses c ON c.owner_user_id = p.user_id
                JOIN course_purchases cp ON cp.course_id = c.id
                LEFT JOIN course_partner_commissions cpc ON cpc.partner_id = p.id AND cpc.course_id = c.id
                WHERE p.id = :partner_id
                  AND cp.status = "paid" AND cp.paid_at IS NOT NULL
                  AND cp.paid_at >= :start AND cp.paid_at < :end
                GROUP BY c.id, c.title, c.slug, commission_percent
                ORDER BY sales_cents DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'partner_id' => $partnerId,
            'start' => $start,
            'end' => $end,
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalSales = 0;
        $totalCommission = 0;
        $byCourse = [];

        foreach ($rows as $r) {
            $salesCents = (int)($r['sales_cents'] ?? 0);
            $commissionCents = (int)($r['commission_cents'] ?? 0);
            $totalSales += $salesCents;
            $totalCommission += $commissionCents;
            $byCourse[] = [
                'course' => [
                    'id' => (int)($r['course_id'] ?? 0),
                    'title' => (string)($r['course_title'] ?? ''),
                    'slug' => (string)($r['course_slug'] ?? ''),
                ],
                'paid_count' => (int)($r['paid_count'] ?? 0),
                'sales_cents' => $salesCents,
                'commission_percent' => (float)($r['commission_percent'] ?? 0.0),
                'commission_cents' => $commissionCents,
            ];
        }

        return [
            'partner' => $partner,
            'total_sales_cents' => $totalSales,
            'total_commission_cents' => $totalCommission,
            'by_course' => $byCourse,
        ];
    }

    public static function sumPartnerCommissionUpToByPartnerId(int $partnerId, int $year, int $month): int
    {
        $endExclusive = sprintf('%04d-%02d-01 00:00:00', $year, $month);
        $endExclusive = date('Y-m-d H:i:s', strtotime($endExclusive . ' +1 month'));

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT COALESCE(SUM(ROUND(cp.amount_cents * (COALESCE(cpc.commission_percent, p.default_commission_percent) / 100), 0)), 0) AS total
            FROM course_partners p
            JOIN courses c ON c.owner_user_id = p.user_id
            JOIN course_purchases cp ON cp.course_id = c.id
            LEFT JOIN course_partner_commissions cpc ON cpc.partner_id = p.id AND cpc.course_id = c.id
            WHERE p.id = :partner_id
              AND cp.status = "paid" AND cp.paid_at IS NOT NULL
              AND cp.paid_at < :end');
        $stmt->execute([
            'partner_id' => $partnerId,
            'end' => $endExclusive,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }
}
