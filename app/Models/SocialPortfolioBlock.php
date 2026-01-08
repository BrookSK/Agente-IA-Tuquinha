<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class SocialPortfolioBlock
{
    private static function sanitizeType(string $type): string
    {
        $type = strtolower(trim($type));
        $allowed = ['text', 'image', 'gallery', 'video', 'embed'];
        return in_array($type, $allowed, true) ? $type : 'text';
    }

    public static function allForItem(int $itemId): array
    {
        if ($itemId <= 0) {
            return [];
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM social_portfolio_blocks WHERE item_id = :iid AND deleted_at IS NULL ORDER BY sort_order ASC, created_at ASC');
        $stmt->execute(['iid' => $itemId]);
        $blocks = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        if (empty($blocks)) {
            return [];
        }

        $ids = [];
        foreach ($blocks as $b) {
            $bid = (int)($b['id'] ?? 0);
            if ($bid > 0) {
                $ids[] = $bid;
            }
        }

        $mediaByBlock = [];
        if (!empty($ids)) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            $stmt2 = $pdo->prepare('SELECT * FROM social_portfolio_block_media WHERE block_id IN (' . $in . ') AND deleted_at IS NULL ORDER BY block_id ASC, sort_order ASC, created_at ASC');
            $stmt2->execute($ids);
            $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($rows as $r) {
                $bid = (int)($r['block_id'] ?? 0);
                if (!isset($mediaByBlock[$bid])) {
                    $mediaByBlock[$bid] = [];
                }
                $mediaByBlock[$bid][] = $r;
            }
        }

        foreach ($blocks as &$b) {
            $bid = (int)($b['id'] ?? 0);
            $b['media'] = $mediaByBlock[$bid] ?? [];
        }
        unset($b);

        return $blocks;
    }

    public static function replaceForItem(int $itemId, array $blocks): void
    {
        if ($itemId <= 0) {
            return;
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $stmtDelMedia = $pdo->prepare('UPDATE social_portfolio_block_media SET deleted_at = NOW() WHERE block_id IN (SELECT id FROM social_portfolio_blocks WHERE item_id = :iid) AND deleted_at IS NULL');
            $stmtDelMedia->execute(['iid' => $itemId]);

            $stmtDelBlocks = $pdo->prepare('UPDATE social_portfolio_blocks SET deleted_at = NOW() WHERE item_id = :iid AND deleted_at IS NULL');
            $stmtDelBlocks->execute(['iid' => $itemId]);

            $stmtIns = $pdo->prepare('INSERT INTO social_portfolio_blocks (item_id, sort_order, type, text_content, media_url, media_mime, meta_json)
                VALUES (:item_id, :sort_order, :type, :text_content, :media_url, :media_mime, :meta_json)');
            $stmtInsMedia = $pdo->prepare('INSERT INTO social_portfolio_block_media (block_id, sort_order, url, mime_type, title, size_bytes)
                VALUES (:block_id, :sort_order, :url, :mime_type, :title, :size_bytes)');

            $sort = 0;
            foreach ($blocks as $b) {
                if (!is_array($b)) {
                    continue;
                }

                $type = self::sanitizeType((string)($b['type'] ?? 'text'));
                $text = isset($b['text']) ? (string)$b['text'] : null;
                $text = $text !== null ? trim($text) : null;

                $mediaUrl = isset($b['media_url']) ? trim((string)$b['media_url']) : null;
                $mediaUrl = $mediaUrl !== '' ? $mediaUrl : null;

                $mediaMime = isset($b['media_mime']) ? trim((string)$b['media_mime']) : null;
                $mediaMime = $mediaMime !== '' ? $mediaMime : null;

                $meta = null;
                if (isset($b['meta']) && (is_array($b['meta']) || is_object($b['meta']))) {
                    $meta = json_encode($b['meta'], JSON_UNESCAPED_UNICODE);
                } elseif (isset($b['meta']) && is_string($b['meta'])) {
                    $raw = trim($b['meta']);
                    if ($raw !== '') {
                        $meta = $raw;
                    }
                }

                $stmtIns->execute([
                    'item_id' => $itemId,
                    'sort_order' => $sort,
                    'type' => $type,
                    'text_content' => $text !== '' ? $text : null,
                    'media_url' => $mediaUrl,
                    'media_mime' => $mediaMime,
                    'meta_json' => $meta,
                ]);
                $blockId = (int)$pdo->lastInsertId();

                if ($blockId > 0 && $type === 'gallery' && !empty($b['media']) && is_array($b['media'])) {
                    $ms = 0;
                    foreach ($b['media'] as $m) {
                        if (!is_array($m)) {
                            continue;
                        }
                        $url = trim((string)($m['url'] ?? ''));
                        if ($url === '') {
                            continue;
                        }
                        $mime = isset($m['mime_type']) ? trim((string)$m['mime_type']) : null;
                        $mime = $mime !== '' ? $mime : null;
                        $title = isset($m['title']) ? trim((string)$m['title']) : null;
                        $title = $title !== '' ? $title : null;
                        $size = isset($m['size_bytes']) ? (int)$m['size_bytes'] : null;
                        $size = $size && $size > 0 ? $size : null;

                        $stmtInsMedia->execute([
                            'block_id' => $blockId,
                            'sort_order' => $ms,
                            'url' => $url,
                            'mime_type' => $mime,
                            'title' => $title,
                            'size_bytes' => $size,
                        ]);
                        $ms++;
                    }
                }

                $sort++;
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function firstCoverUrlForItem(int $itemId): ?string
    {
        if ($itemId <= 0) {
            return null;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT media_url FROM social_portfolio_blocks WHERE item_id = :iid AND deleted_at IS NULL AND type IN ('image','gallery') AND media_url IS NOT NULL AND media_url <> '' ORDER BY sort_order ASC, created_at ASC LIMIT 1");
        $stmt->execute(['iid' => $itemId]);
        $url = $stmt->fetchColumn();
        $url = is_string($url) ? trim($url) : '';
        return $url !== '' ? $url : null;
    }
}
