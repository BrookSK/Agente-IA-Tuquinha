<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class KanbanCard
{
    public static function listForBoard(int $boardId): array
    {
        $pdo = Database::getConnection();
        $sql = 'SELECT c.*, l.board_id
                FROM kanban_cards c
                INNER JOIN kanban_lists l ON l.id = c.list_id
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
