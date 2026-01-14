<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class KanbanCardChecklistItem
{
    public static function listForCard(int $cardId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM kanban_card_checklist_items WHERE card_id = :cid ORDER BY position ASC, id ASC');
        $stmt->execute(['cid' => $cardId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function create(int $cardId, string $content): int
    {
        $content = trim($content);
        if ($content === '') {
            $content = 'Item';
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT COALESCE(MAX(position), 0) + 1 AS p FROM kanban_card_checklist_items WHERE card_id = :cid');
        $stmt->execute(['cid' => $cardId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $pos = (int)($row['p'] ?? 1);

        $ins = $pdo->prepare('INSERT INTO kanban_card_checklist_items (card_id, content, is_done, position) VALUES (:cid, :c, 0, :p)');
        $ins->execute([
            'cid' => $cardId,
            'c' => $content,
            'p' => $pos,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function toggleDone(int $id, bool $done): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE kanban_card_checklist_items SET is_done = :d WHERE id = :id');
        $stmt->execute([
            'd' => $done ? 1 : 0,
            'id' => $id,
        ]);
    }

    public static function deleteById(int $id): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM kanban_card_checklist_items WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM kanban_card_checklist_items WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
