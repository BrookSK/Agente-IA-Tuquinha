<?php

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Gerencia API keys dos usuários para integração externa.
 *
 * Migration em: database/migrations/136_create_user_api_keys.sql
 */
class UserApiKey
{
    public static function findByKey(string $key): ?array
    {
        $key = trim($key);
        if ($key === '') {
            return null;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM user_api_keys WHERE api_key = :k AND is_active = 1 LIMIT 1');
        $stmt->execute(['k' => $key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function listForUser(int $userId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM user_api_keys WHERE user_id = :uid ORDER BY created_at DESC');
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function generate(int $userId, string $label = ''): string
    {
        $key = 'tuq_' . bin2hex(random_bytes(32));
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO user_api_keys (user_id, api_key, label) VALUES (:uid, :k, :l)');
        $stmt->execute(['uid' => $userId, 'k' => $key, 'l' => $label]);
        return $key;
    }

    public static function revoke(int $id, int $userId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE user_api_keys SET is_active = 0 WHERE id = :id AND user_id = :uid');
        $stmt->execute(['id' => $id, 'uid' => $userId]);
    }

    public static function touchLastUsed(int $id): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE user_api_keys SET last_used_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
