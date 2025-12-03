<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class TokenTopup
{
    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO token_topups (user_id, tokens, amount_cents, asaas_payment_id, status, paid_at) VALUES (:user_id, :tokens, :amount_cents, :asaas_payment_id, :status, :paid_at)');
        $stmt->execute([
            'user_id' => $data['user_id'],
            'tokens' => $data['tokens'],
            'amount_cents' => $data['amount_cents'],
            'asaas_payment_id' => $data['asaas_payment_id'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'paid_at' => $data['paid_at'] ?? null,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function findByAsaasPaymentId(string $paymentId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM token_topups WHERE asaas_payment_id = :pid LIMIT 1');
        $stmt->execute(['pid' => $paymentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function markPaid(int $id): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE token_topups SET status = "paid", paid_at = NOW() WHERE id = :id AND status = "pending" LIMIT 1');
        $stmt->execute(['id' => $id]);
    }

    public static function attachPaymentId(int $id, string $paymentId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE token_topups SET asaas_payment_id = :pid WHERE id = :id LIMIT 1');
        $stmt->execute([
            'pid' => $paymentId,
            'id' => $id,
        ]);
    }

    public static function allByUserId(int $userId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM token_topups WHERE user_id = :uid ORDER BY created_at ASC');
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
