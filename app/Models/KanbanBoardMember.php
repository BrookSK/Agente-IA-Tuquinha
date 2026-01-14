<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class KanbanBoardMember
{
    public static function listForBoard(int $boardId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT m.*, u.name, u.preferred_name, u.email
            FROM kanban_board_members m
            INNER JOIN users u ON u.id = m.user_id
            WHERE m.board_id = :bid
            ORDER BY m.id ASC');
        $stmt->execute(['bid' => $boardId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function isMember(int $boardId, int $userId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT 1 FROM kanban_board_members WHERE board_id = :bid AND user_id = :uid LIMIT 1');
        $stmt->execute(['bid' => $boardId, 'uid' => $userId]);
        return (bool)$stmt->fetchColumn();
    }

    public static function add(int $boardId, int $userId, string $role = 'member'): void
    {
        $role = trim($role);
        if ($role === '') {
            $role = 'member';
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT IGNORE INTO kanban_board_members (board_id, user_id, role) VALUES (:bid, :uid, :r)');
        $stmt->execute(['bid' => $boardId, 'uid' => $userId, 'r' => $role]);
    }

    public static function remove(int $boardId, int $userId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM kanban_board_members WHERE board_id = :bid AND user_id = :uid');
        $stmt->execute(['bid' => $boardId, 'uid' => $userId]);
    }
}
