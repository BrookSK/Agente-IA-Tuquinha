<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class SocialPortfolioLike
{
    public static function isLikedByUser(int $itemId, int $userId): bool
    {
        if ($itemId <= 0 || $userId <= 0) {
            return false;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT 1 FROM social_portfolio_likes WHERE item_id = :iid AND user_id = :uid LIMIT 1');
        $stmt->execute([
            'iid' => $itemId,
            'uid' => $userId,
        ]);
        return (bool)$stmt->fetchColumn();
    }

    public static function countForItem(int $itemId): int
    {
        if ($itemId <= 0) {
            return 0;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM social_portfolio_likes WHERE item_id = :iid');
        $stmt->execute(['iid' => $itemId]);
        return (int)$stmt->fetchColumn();
    }

    public static function toggle(int $itemId, int $userId): bool
    {
        if ($itemId <= 0 || $userId <= 0) {
            return false;
        }
        $pdo = Database::getConnection();
        if (self::isLikedByUser($itemId, $userId)) {
            $stmt = $pdo->prepare('DELETE FROM social_portfolio_likes WHERE item_id = :iid AND user_id = :uid LIMIT 1');
            $stmt->execute([
                'iid' => $itemId,
                'uid' => $userId,
            ]);
            return false;
        }

        $stmt = $pdo->prepare('INSERT IGNORE INTO social_portfolio_likes (item_id, user_id) VALUES (:iid, :uid)');
        $stmt->execute([
            'iid' => $itemId,
            'uid' => $userId,
        ]);
        return true;
    }
}
