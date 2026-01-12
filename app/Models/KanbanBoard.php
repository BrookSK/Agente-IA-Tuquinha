<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class KanbanBoard
{
    public static function listForUser(int $userId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM kanban_boards WHERE owner_user_id = :uid ORDER BY updated_at DESC');
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function findOwnedById(int $id, int $userId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM kanban_boards WHERE id = :id AND owner_user_id = :uid LIMIT 1');
        $stmt->execute(['id' => $id, 'uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(int $ownerUserId, string $title): int
    {
        $title = trim($title);
        if ($title === '') {
            $title = 'Sem título';
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO kanban_boards (owner_user_id, title) VALUES (:uid, :t)');
        $stmt->execute(['uid' => $ownerUserId, 't' => $title]);
        return (int)$pdo->lastInsertId();
    }

    public static function rename(int $boardId, int $userId, string $title): void
    {
        $title = trim($title);
        if ($title === '') {
            $title = 'Sem título';
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE kanban_boards SET title = :t WHERE id = :id AND owner_user_id = :uid');
        $stmt->execute(['t' => $title, 'id' => $boardId, 'uid' => $userId]);
    }

    public static function delete(int $boardId, int $userId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM kanban_boards WHERE id = :id AND owner_user_id = :uid');
        $stmt->execute(['id' => $boardId, 'uid' => $userId]);
    }
}
