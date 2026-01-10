<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class NewsUserPreference
{
    public static function getByUserId(int $userId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM news_user_preferences WHERE user_id = :uid LIMIT 1');
        $stmt->execute(['uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function ensureForUserId(int $userId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT IGNORE INTO news_user_preferences (user_id, email_enabled) VALUES (:uid, 0)');
        $stmt->execute(['uid' => $userId]);
    }

    public static function setEmailEnabled(int $userId, bool $enabled): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO news_user_preferences (user_id, email_enabled)
            VALUES (:uid, :v)
            ON DUPLICATE KEY UPDATE email_enabled = VALUES(email_enabled)');
        $stmt->execute([
            'uid' => $userId,
            'v' => $enabled ? 1 : 0,
        ]);
    }
}
