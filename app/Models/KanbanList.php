<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class KanbanList
{
    public static function listForBoard(int $boardId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM kanban_lists WHERE board_id = :bid ORDER BY position ASC, id ASC');
        $stmt->execute(['bid' => $boardId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM kanban_lists WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(int $boardId, string $title): int
    {
        $title = trim($title);
        if ($title === '') {
            $title = 'Sem título';
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT COALESCE(MAX(position), 0) + 1 AS p FROM kanban_lists WHERE board_id = :bid');
        $stmt->execute(['bid' => $boardId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $pos = (int)($row['p'] ?? 1);

        $ins = $pdo->prepare('INSERT INTO kanban_lists (board_id, title, position) VALUES (:bid, :t, :p)');
        $ins->execute(['bid' => $boardId, 't' => $title, 'p' => $pos]);
        return (int)$pdo->lastInsertId();
    }

    public static function rename(int $listId, string $title): void
    {
        $title = trim($title);
        if ($title === '') {
            $title = 'Sem título';
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE kanban_lists SET title = :t WHERE id = :id');
        $stmt->execute(['t' => $title, 'id' => $listId]);
    }

    public static function delete(int $listId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM kanban_lists WHERE id = :id');
        $stmt->execute(['id' => $listId]);
    }

    public static function setPosition(int $listId, int $position): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE kanban_lists SET position = :p WHERE id = :id');
        $stmt->execute(['p' => $position, 'id' => $listId]);
    }
}
