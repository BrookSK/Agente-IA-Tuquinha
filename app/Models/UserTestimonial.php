<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class UserTestimonial
{
    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO user_testimonials (from_user_id, to_user_id, body, is_public, status)
            VALUES (:from_user_id, :to_user_id, :body, :is_public, :status)');
        $stmt->execute([
            'from_user_id' => (int)($data['from_user_id'] ?? 0),
            'to_user_id' => (int)($data['to_user_id'] ?? 0),
            'body' => $data['body'] ?? '',
            'is_public' => !empty($data['is_public']) ? 1 : 0,
            'status' => $data['status'] ?? 'pending',
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function allPublicForUser(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT t.*, u.name AS from_user_name
            FROM user_testimonials t
            JOIN users u ON u.id = t.from_user_id
            WHERE t.to_user_id = :uid AND t.status = "accepted" AND t.is_public = 1
            ORDER BY t.created_at DESC, t.id DESC');
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function pendingForUser(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT t.*, u.name AS from_user_name
            FROM user_testimonials t
            JOIN users u ON u.id = t.from_user_id
            WHERE t.to_user_id = :uid AND t.status = "pending"
            ORDER BY t.created_at ASC, t.id ASC');
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function decide(int $id, int $toUserId, string $decision): void
    {
        if ($id <= 0 || $toUserId <= 0) {
            return;
        }

        $decision = $decision === 'accepted' ? 'accepted' : 'rejected';

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE user_testimonials SET status = :status, decided_at = NOW()
            WHERE id = :id AND to_user_id = :to_user_id AND status = "pending"');
        $stmt->execute([
            'id' => $id,
            'to_user_id' => $toUserId,
            'status' => $decision,
        ]);
    }
}
