<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class TokenTransaction
{
    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO token_transactions (user_id, amount, reason, meta) VALUES (:user_id, :amount, :reason, :meta)');
        $stmt->execute([
            'user_id' => (int)($data['user_id'] ?? 0),
            'amount' => (int)($data['amount'] ?? 0),
            'reason' => (string)($data['reason'] ?? ''),
            'meta' => $data['meta'] ?? null,
        ]);

        return (int)$pdo->lastInsertId();
    }

    public static function allByUserId(int $userId, int $limit = 500): array
    {
        if ($userId <= 0) {
            return [];
        }

        $limit = $limit > 0 ? $limit : 500;

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM token_transactions WHERE user_id = :uid ORDER BY created_at ASC LIMIT ' . (int)$limit);
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
