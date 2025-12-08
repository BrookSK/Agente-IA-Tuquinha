<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class CoursePurchase
{
    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO course_purchases (user_id, course_id, amount_cents, billing_type, asaas_payment_id, status, paid_at)
            VALUES (:user_id, :course_id, :amount_cents, :billing_type, :asaas_payment_id, :status, :paid_at)');
        $stmt->execute([
            'user_id' => (int)($data['user_id'] ?? 0),
            'course_id' => (int)($data['course_id'] ?? 0),
            'amount_cents' => (int)($data['amount_cents'] ?? 0),
            'billing_type' => (string)($data['billing_type'] ?? 'PIX'),
            'asaas_payment_id' => $data['asaas_payment_id'] ?? null,
            'status' => (string)($data['status'] ?? 'pending'),
            'paid_at' => $data['paid_at'] ?? null,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function findByAsaasPaymentId(string $paymentId): ?array
    {
        if ($paymentId === '') {
            return null;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM course_purchases WHERE asaas_payment_id = :pid LIMIT 1');
        $stmt->execute(['pid' => $paymentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function markPaid(int $id): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE course_purchases SET status = "paid", paid_at = NOW() WHERE id = :id AND status = "pending" LIMIT 1');
        $stmt->execute(['id' => $id]);
    }

    public static function attachPaymentId(int $id, string $paymentId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE course_purchases SET asaas_payment_id = :pid WHERE id = :id LIMIT 1');
        $stmt->execute([
            'pid' => $paymentId,
            'id' => $id,
        ]);
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM course_purchases WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
