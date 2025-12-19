<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class SubscriptionPayment
{
    public static function upsertPaid(array $data): void
    {
        $pdo = Database::getConnection();

        $subscriptionId = (int)($data['subscription_id'] ?? 0);
        $asaasPaymentId = (string)($data['asaas_payment_id'] ?? '');
        if ($subscriptionId <= 0 || $asaasPaymentId === '') {
            return;
        }

        $existingStmt = $pdo->prepare('SELECT id FROM subscription_payments WHERE subscription_id = :sid AND asaas_payment_id = :pid LIMIT 1');
        $existingStmt->execute([
            'sid' => $subscriptionId,
            'pid' => $asaasPaymentId,
        ]);
        $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            return;
        }

        $stmt = $pdo->prepare('INSERT INTO subscription_payments (subscription_id, plan_id, amount_cents, asaas_payment_id, billing_type, paid_at)
            VALUES (:subscription_id, :plan_id, :amount_cents, :asaas_payment_id, :billing_type, :paid_at)');
        $stmt->execute([
            'subscription_id' => $subscriptionId,
            'plan_id' => (int)($data['plan_id'] ?? 0),
            'amount_cents' => (int)($data['amount_cents'] ?? 0),
            'asaas_payment_id' => $asaasPaymentId,
            'billing_type' => ($data['billing_type'] ?? null) !== '' ? (string)$data['billing_type'] : null,
            'paid_at' => (string)($data['paid_at'] ?? date('Y-m-d H:i:s')),
        ]);
    }

    public static function sumRevenueCentsByPeriod(string $start, string $end): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT COALESCE(SUM(amount_cents), 0) AS total
            FROM subscription_payments
            WHERE paid_at >= :start AND paid_at < :end');
        $stmt->execute([
            'start' => $start,
            'end' => $end,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    public static function sumRevenueCentsByPlanPeriodType(string $start, string $end): array
    {
        $pdo = Database::getConnection();
        $sql = 'SELECT
                    CASE
                        WHEN p.slug LIKE "%-%" AND RIGHT(p.slug, 11) = "-semestral" THEN "semestral"
                        WHEN p.slug LIKE "%-%" AND RIGHT(p.slug, 6) = "-anual" THEN "anual"
                        ELSE "mensal"
                    END AS period_type,
                    COALESCE(SUM(sp.amount_cents), 0) AS total_cents
                FROM subscription_payments sp
                JOIN plans p ON p.id = sp.plan_id
                WHERE sp.paid_at >= :start AND sp.paid_at < :end
                GROUP BY period_type';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'start' => $start,
            'end' => $end,
        ]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $out = [
            'mensal' => 0,
            'semestral' => 0,
            'anual' => 0,
        ];
        foreach ($rows as $r) {
            $k = (string)($r['period_type'] ?? '');
            if (!isset($out[$k])) {
                continue;
            }
            $out[$k] = (int)($r['total_cents'] ?? 0);
        }
        return $out;
    }

    public static function listByPeriod(string $start, string $end, int $limit = 200): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT sp.*, p.name AS plan_name, p.slug AS plan_slug
            FROM subscription_payments sp
            JOIN plans p ON p.id = sp.plan_id
            WHERE sp.paid_at >= :start AND sp.paid_at < :end
            ORDER BY sp.paid_at DESC
            LIMIT :lim');
        $stmt->bindValue(':start', $start);
        $stmt->bindValue(':end', $end);
        $stmt->bindValue(':lim', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
