<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class SocialPortfolioMedia
{
    public static function create(int $itemId, string $kind, string $url, ?string $title = null, ?string $mimeType = null, ?int $sizeBytes = null): int
    {
        if ($itemId <= 0) {
            return 0;
        }
        $kind = in_array($kind, ['image', 'file'], true) ? $kind : 'image';
        $url = trim($url);
        if ($url === '') {
            return 0;
        }

        $title = $title !== null ? trim($title) : null;
        $mimeType = $mimeType !== null ? trim($mimeType) : null;
        $sizeBytes = $sizeBytes && $sizeBytes > 0 ? $sizeBytes : null;

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO social_portfolio_media (item_id, kind, title, url, mime_type, size_bytes)
            VALUES (:item_id, :kind, :title, :url, :mime_type, :size_bytes)');
        $stmt->execute([
            'item_id' => $itemId,
            'kind' => $kind,
            'title' => $title !== '' ? $title : null,
            'url' => $url,
            'mime_type' => $mimeType !== '' ? $mimeType : null,
            'size_bytes' => $sizeBytes,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function allForItem(int $itemId): array
    {
        if ($itemId <= 0) {
            return [];
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM social_portfolio_media WHERE item_id = :iid AND deleted_at IS NULL ORDER BY created_at ASC');
        $stmt->execute(['iid' => $itemId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function softDelete(int $id, int $itemId): void
    {
        if ($id <= 0 || $itemId <= 0) {
            return;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE social_portfolio_media SET deleted_at = NOW() WHERE id = :id AND item_id = :iid AND deleted_at IS NULL');
        $stmt->execute([
            'id' => $id,
            'iid' => $itemId,
        ]);
    }
}
