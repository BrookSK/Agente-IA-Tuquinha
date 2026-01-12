<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class KanbanCardAttachment
{
    public static function listForCard(int $cardId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM kanban_card_attachments WHERE card_id = :cid ORDER BY id DESC');
        $stmt->execute(['cid' => $cardId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function create(int $cardId, string $url, ?string $originalName, ?string $mimeType, ?int $size): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO kanban_card_attachments (card_id, url, original_name, mime_type, size)
            VALUES (:cid, :u, :n, :m, :s)');
        $stmt->execute([
            'cid' => $cardId,
            'u' => $url,
            'n' => ($originalName !== null && trim($originalName) !== '') ? $originalName : null,
            'm' => ($mimeType !== null && trim($mimeType) !== '') ? $mimeType : null,
            's' => $size !== null && $size > 0 ? $size : null,
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
