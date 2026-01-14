<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class KanbanCardAttachment
{
    public static function listForCard(int $cardId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM kanban_card_attachments WHERE card_id = :cid AND is_cover = 0 ORDER BY id DESC');
        $stmt->execute(['cid' => $cardId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function findCoverForCard(int $cardId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM kanban_card_attachments WHERE card_id = :cid AND is_cover = 1 ORDER BY id DESC LIMIT 1');
        $stmt->execute(['cid' => $cardId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function deleteCoverForCard(int $cardId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM kanban_card_attachments WHERE card_id = :cid AND is_cover = 1');
        $stmt->execute(['cid' => $cardId]);
    }

    public static function create(int $cardId, string $url, ?string $originalName, ?string $mimeType, ?int $size, int $isCover = 0): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO kanban_card_attachments (card_id, url, original_name, mime_type, size, is_cover)
            VALUES (:cid, :u, :n, :m, :s, :c)');
        $stmt->execute([
            'cid' => $cardId,
            'u' => $url,
            'n' => ($originalName !== null && trim($originalName) !== '') ? $originalName : null,
            'm' => ($mimeType !== null && trim($mimeType) !== '') ? $mimeType : null,
            's' => $size !== null && $size > 0 ? $size : null,
            'c' => $isCover ? 1 : 0,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM kanban_card_attachments WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function deleteById(int $id): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM kanban_card_attachments WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
