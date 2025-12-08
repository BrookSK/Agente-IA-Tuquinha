<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Subscription
{
    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO subscriptions (
            plan_id, customer_name, customer_email, customer_cpf, customer_phone,
            customer_postal_code, customer_address, customer_address_number,
            customer_complement, customer_province, customer_city, customer_state,
            asaas_customer_id, asaas_subscription_id, status, started_at
        ) VALUES (
            :plan_id, :customer_name, :customer_email, :customer_cpf, :customer_phone,
            :customer_postal_code, :customer_address, :customer_address_number,
            :customer_complement, :customer_province, :customer_city, :customer_state,
            :asaas_customer_id, :asaas_subscription_id, :status, :started_at
        )');

        $stmt->execute($data);

        return (int)$pdo->lastInsertId();
    }

    public static function findLastByEmail(string $email): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM subscriptions WHERE customer_email = :email ORDER BY created_at DESC LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findByAsaasId(string $asaasSubscriptionId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM subscriptions WHERE asaas_subscription_id = :id LIMIT 1');
        $stmt->execute(['id' => $asaasSubscriptionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function countByStatus(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT status, COUNT(*) AS total FROM subscriptions GROUP BY status');
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function sumActiveRevenueCents(): int
    {
        $pdo = Database::getConnection();

        // Normaliza a receita das assinaturas ativas para uma base MENSAL,
        // considerando o período do plano inferido pelo slug:
        // - slug terminando com "-semestral": preço referente a 6 meses
        // - slug terminando com "-anual": preço referente a 12 meses
        // - demais casos: tratado como mensal
        $sql = 'SELECT p.price_cents, p.slug
                FROM subscriptions s
                INNER JOIN plans p ON p.id = s.plan_id
                WHERE s.status = "active"';

        $stmt = $pdo->query($sql);
        $totalMonthlyCents = 0;

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $priceCents = (int)($row['price_cents'] ?? 0);
            $slug = (string)($row['slug'] ?? '');

            $months = 1;
            if (substr($slug, -11) === '-semestral') {
                $months = 6;
            } elseif (substr($slug, -6) === '-anual') {
                $months = 12;
            }

            if ($months < 1) {
                $months = 1;
            }

            $monthlyCents = (int)round($priceCents / $months);
            $totalMonthlyCents += $monthlyCents;
        }

        return $totalMonthlyCents;
    }

    public static function allWithPlanAndStatus(string $statusFilter = ''): array
    {
        $pdo = Database::getConnection();
        $params = [];
        $where = '';
        if ($statusFilter !== '') {
            $where = 'WHERE s.status = :status';
            $params['status'] = $statusFilter;
        }

        $sql = 'SELECT s.*, p.name AS plan_name, p.slug AS plan_slug
                FROM subscriptions s
                INNER JOIN plans p ON p.id = s.plan_id
                ' . $where . '
                ORDER BY s.created_at DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function allByEmailWithPlan(string $email): array
    {
        $pdo = Database::getConnection();
        $sql = 'SELECT s.*, p.name AS plan_name, p.slug AS plan_slug
                FROM subscriptions s
                INNER JOIN plans p ON p.id = s.plan_id
                WHERE s.customer_email = :email
                ORDER BY s.created_at ASC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function updateStatusAndCanceledAt(int $id, string $status, ?string $canceledAt): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE subscriptions SET status = :status, canceled_at = :canceled_at WHERE id = :id');
        $stmt->execute([
            'status' => $status,
            'canceled_at' => $canceledAt,
            'id' => $id,
        ]);
    }

    public static function cancelOtherActivesForEmail(string $email, int $keepId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE subscriptions SET status = "canceled", canceled_at = NOW()
            WHERE customer_email = :email AND id <> :keep_id AND status = "active"');
        $stmt->execute([
            'email' => $email,
            'keep_id' => $keepId,
        ]);
    }
}
