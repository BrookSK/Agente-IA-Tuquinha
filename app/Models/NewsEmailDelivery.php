<?php

namespace App\Models;

use App\Core\Database;

class NewsEmailDelivery
{
    public static function markSent(int $userId, int $newsItemId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT IGNORE INTO news_email_deliveries (user_id, news_item_id) VALUES (:uid, :nid)');
        $stmt->execute([
            'uid' => $userId,
            'nid' => $newsItemId,
        ]);
    }
}
