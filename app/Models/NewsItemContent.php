<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class NewsItemContent
{
    public static function getByNewsItemId(int $newsItemId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM news_item_contents WHERE news_item_id = :id LIMIT 1');
        $stmt->execute(['id' => $newsItemId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    public static function upsert(int $newsItemId, ?string $title, ?string $description, ?string $text): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO news_item_contents (news_item_id, extracted_title, extracted_description, extracted_text, extracted_at)
            VALUES (:nid, :t, :d, :x, NOW())
            ON DUPLICATE KEY UPDATE
                extracted_title = VALUES(extracted_title),
                extracted_description = VALUES(extracted_description),
                extracted_text = VALUES(extracted_text),
                extracted_at = NOW()');

        $stmt->execute([
            'nid' => $newsItemId,
            't' => $title,
            'd' => $description,
            'x' => $text,
        ]);
    }
}
