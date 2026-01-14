<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class KanbanCard
{
    public static function listForBoard(int $boardId): array
    {
        $pdo = Database::getConnection();
        $sql = 'SELECT c.*, l.board_id,
                    att.url AS cover_url,
                    att.mime_type AS cover_mime_type,
                    att.original_name AS cover_original_name,
                    COALESCE(attc.attachments_count, 0) AS attachments_count,
                    COALESCE(chk.checklist_total, 0) AS checklist_total,
                    COALESCE(chk.checklist_done, 0) AS checklist_done
                FROM kanban_cards c
                INNER JOIN kanban_lists l ON l.id = c.list_id
                LEFT JOIN kanban_card_attachments att ON att.id = c.cover_attachment_id
                LEFT JOIN (
                    SELECT card_id, COUNT(*) AS attachments_count
                    FROM kanban_card_attachments
                    GROUP BY card_id
                ) attc ON attc.card_id = c.id
                LEFT JOIN (
                    SELECT card_id,
                           COUNT(*) AS checklist_total,
                           SUM(CASE WHEN is_done = 1 THEN 1 ELSE 0 END) AS checklist_done
                    FROM kanban_card_checklist_items
                    GROUP BY card_id
                ) chk ON chk.card_id = c.id
                WHERE l.board_id = :bid
                ORDER BY l.position ASC, c.position ASC, c.id ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['bid' => $boardId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM kanban_cards WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(int $listId, string $title, ?string $description = null): int
    {
        $title = trim($title);
        if ($title === '') {
            $title = 'Sem título';
        }

        $description = $description !== null ? trim($description) : null;
        if ($description === '') {
            $description = null;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT COALESCE(MAX(position), 0) + 1 AS p FROM kanban_cards WHERE list_id = :lid');
        $stmt->execute(['lid' => $listId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $pos = (int)($row['p'] ?? 1);

        $ins = $pdo->prepare('INSERT INTO kanban_cards (list_id, title, description, position) VALUES (:lid, :t, :d, :p)');
        $ins->execute(['lid' => $listId, 't' => $title, 'd' => $description, 'p' => $pos]);
        return (int)$pdo->lastInsertId();
    }

    public static function update(int $cardId, string $title, ?string $description): void
    {
        $title = trim($title);
        if ($title === '') {
            $title = 'Sem título';
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE kanban_cards SET title = :t, description = :d WHERE id = :id');
        $stmt->execute([
            't' => $title,
            'd' => ($description !== null && trim($description) !== '') ? $description : null,
            'id' => $cardId,
        ]);
    }

    public static function setDueDate(int $cardId, ?string $dueDate): void
    {
        $pdo = Database::getConnection();

        $dueDate = $dueDate !== null ? trim($dueDate) : null;
        if ($dueDate === '') {
            $dueDate = null;
        }

        // Espera formato YYYY-MM-DD. Se vier inválido, zera.
        if ($dueDate !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) {
            $dueDate = null;
        }

        $stmt = $pdo->prepare('UPDATE kanban_cards SET due_date = :d WHERE id = :id');
        $stmt->execute([
            'd' => $dueDate,
            'id' => $cardId,
        ]);
    }

    public static function setCoverAttachmentId(int $cardId, ?int $attachmentId): void
    {
        $pdo = Database::getConnection();

        if ($attachmentId !== null && $attachmentId <= 0) {
            $attachmentId = null;
        }

        $stmt = $pdo->prepare('UPDATE kanban_cards SET cover_attachment_id = :aid WHERE id = :id');
        $stmt->execute([
            'aid' => $attachmentId,
            'id' => $cardId,
        ]);
    }

    public static function setDone(int $cardId, bool $done): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE kanban_cards SET is_done = :d WHERE id = :id');
        $stmt->execute([
            'd' => $done ? 1 : 0,
            'id' => $cardId,
        ]);
    }

    public static function delete(int $cardId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM kanban_cards WHERE id = :id');
        $stmt->execute(['id' => $cardId]);
    }

    public static function move(int $cardId, int $toListId, int $position): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE kanban_cards SET list_id = :lid, position = :p WHERE id = :id');
        $stmt->execute(['lid' => $toListId, 'p' => $position, 'id' => $cardId]);
    }

    public static function setPosition(int $cardId, int $position): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE kanban_cards SET position = :p WHERE id = :id');
        $stmt->execute(['p' => $position, 'id' => $cardId]);
    }
}
