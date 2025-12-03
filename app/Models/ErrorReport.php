<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class ErrorReport
{
    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO error_reports (
            user_id, conversation_id, message_id, tokens_used, refunded_tokens,
            error_message, user_comment, status
        ) VALUES (
            :user_id, :conversation_id, :message_id, :tokens_used, :refunded_tokens,
            :error_message, :user_comment, :status
        )');

        $stmt->execute([
            'user_id' => (int)($data['user_id'] ?? 0),
            'conversation_id' => !empty($data['conversation_id']) ? (int)$data['conversation_id'] : null,
            'message_id' => !empty($data['message_id']) ? (int)$data['message_id'] : null,
            'tokens_used' => (int)($data['tokens_used'] ?? 0),
            'refunded_tokens' => (int)($data['refunded_tokens'] ?? 0),
            'error_message' => $data['error_message'] ?? null,
            'user_comment' => $data['user_comment'] ?? null,
            'status' => $data['status'] ?? 'open',
        ]);

        return (int)$pdo->lastInsertId();
    }

    public static function allWithUser(string $statusFilter = ''): array
    {
        $pdo = Database::getConnection();

        $where = '';
        $params = [];
        if ($statusFilter !== '') {
            $where = 'WHERE er.status = :status';
            $params['status'] = $statusFilter;
        }

        $sql = 'SELECT er.*, u.name AS user_name, u.email AS user_email
                FROM error_reports er
                LEFT JOIN users u ON u.id = er.user_id
                ' . $where . '
                ORDER BY er.created_at DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM error_reports WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function updateStatus(int $id, string $status): bool
    {
        $allowed = ['open', 'resolved', 'dismissed'];
        if (!in_array($status, $allowed, true)) {
            return false;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE error_reports SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        return $stmt->execute([
            'status' => $status,
            'id' => $id,
        ]);
    }

    public static function markRefunded(int $id, int $tokens): void
    {
        if ($tokens <= 0) {
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE error_reports
            SET refunded_tokens = refunded_tokens + :tokens,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id');
        $stmt->execute([
            'tokens' => $tokens,
            'id' => $id,
        ]);
    }
}
