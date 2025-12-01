<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class EmailVerification
{
    public static function create(int $userId, string $code, string $expiresAt): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO email_verifications (user_id, code, expires_at) VALUES (:user_id, :code, :expires_at)');
        $stmt->execute([
            'user_id' => $userId,
            'code' => $code,
            'expires_at' => $expiresAt,
        ]);
    }

    public static function findValidByUserAndCode(int $userId, string $code): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM email_verifications WHERE user_id = :user_id AND code = :code AND used_at IS NULL AND expires_at >= NOW() ORDER BY id DESC LIMIT 1');
        $stmt->execute([
            'user_id' => $userId,
            'code' => $code,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function markUsed(int $id): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE email_verifications SET used_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
