<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class NewsItem
{
    public static function latest(int $limit = 30): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM news_items ORDER BY COALESCE(published_at, fetched_at) DESC, id DESC LIMIT :lim');
        $stmt->bindValue(':lim', max(1, min(200, $limit)), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function getLastFetchedAt(): ?string
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT MAX(fetched_at) AS last_fetched_at FROM news_items');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $val = $row['last_fetched_at'] ?? null;
        return is_string($val) && $val !== '' ? $val : null;
    }

    public static function upsertMany(array $items): int
    {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare('INSERT INTO news_items (
                title, summary, url, source_name, image_url, published_at, fetched_at
            ) VALUES (
                :title, :summary, :url, :source_name, :image_url, :published_at, NOW()
            )
            ON DUPLICATE KEY UPDATE
                title = VALUES(title),
                summary = VALUES(summary),
                source_name = VALUES(source_name),
                image_url = VALUES(image_url),
                published_at = VALUES(published_at),
                fetched_at = NOW()');

        $count = 0;
        foreach ($items as $it) {
            if (!is_array($it)) {
                continue;
            }
            $url = trim((string)($it['url'] ?? ''));
            $title = trim((string)($it['title'] ?? ''));
            if ($url === '' || $title === '') {
                continue;
            }

            $publishedAt = $it['published_at'] ?? null;
            if (is_string($publishedAt)) {
                $publishedAt = trim($publishedAt);
                if ($publishedAt === '') {
                    $publishedAt = null;
                }
            } else {
                $publishedAt = null;
            }

            $stmt->execute([
                'title' => $title,
                'summary' => (($it['summary'] ?? null) !== null && trim((string)$it['summary']) !== '') ? (string)$it['summary'] : null,
                'url' => $url,
                'source_name' => (($it['source_name'] ?? null) !== null && trim((string)$it['source_name']) !== '') ? (string)$it['source_name'] : null,
                'image_url' => (($it['image_url'] ?? null) !== null && trim((string)$it['image_url']) !== '') ? (string)$it['image_url'] : null,
                'published_at' => $publishedAt,
            ]);
            $count++;
        }

        return $count;
    }

    public static function listUnsentForUser(int $userId, int $limit = 10): array
    {
        $pdo = Database::getConnection();
        $sql = 'SELECT ni.*
                FROM news_items ni
                LEFT JOIN news_email_deliveries ned
                  ON ned.news_item_id = ni.id AND ned.user_id = :uid
                WHERE ned.id IS NULL
                ORDER BY COALESCE(ni.published_at, ni.fetched_at) DESC, ni.id DESC
                LIMIT :lim';

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', max(1, min(50, $limit)), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
