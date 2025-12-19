<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class CoursePartnerPayout
{
    public static function findByPartnerAndPeriod(int $partnerId, int $year, int $month): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM course_partner_payouts WHERE partner_id = :partner_id AND period_year = :y AND period_month = :m LIMIT 1');
        $stmt->execute([
            'partner_id' => $partnerId,
            'y' => $year,
            'm' => $month,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function listByPartner(int $partnerId, int $limit = 36): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM course_partner_payouts WHERE partner_id = :partner_id ORDER BY period_year DESC, period_month DESC LIMIT :lim');
        $stmt->bindValue(':partner_id', $partnerId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function sumPaidUpTo(int $partnerId, int $year, int $month): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT COALESCE(SUM(amount_cents), 0) AS total FROM course_partner_payouts WHERE partner_id = :partner_id AND (period_year < :y OR (period_year = :y AND period_month <= :m))');
        $stmt->execute([
            'partner_id' => $partnerId,
            'y' => $year,
            'm' => $month,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    public static function createPaid(int $partnerId, int $year, int $month, int $amountCents): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO course_partner_payouts (partner_id, period_year, period_month, amount_cents, status, paid_at)
            VALUES (:partner_id, :y, :m, :amount_cents, "paid", NOW())');
        $stmt->execute([
            'partner_id' => $partnerId,
            'y' => $year,
            'm' => $month,
            'amount_cents' => $amountCents,
        ]);
        return (int)$pdo->lastInsertId();
    }
}
