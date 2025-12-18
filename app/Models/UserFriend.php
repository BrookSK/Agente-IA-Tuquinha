<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class UserFriend
{
    private static function normalizePair(int $a, int $b): array
    {
        if ($a <= $b) {
            return [$a, $b];
        }
        return [$b, $a];
    }

    public static function findFriendship(int $userId1, int $userId2): ?array
    {
        if ($userId1 <= 0 || $userId2 <= 0 || $userId1 === $userId2) {
            return null;
        }

        [$u1, $u2] = self::normalizePair($userId1, $userId2);
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM user_friends WHERE user_id = :u1 AND friend_user_id = :u2 LIMIT 1');
        $stmt->execute(['u1' => $u1, 'u2' => $u2]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function request(int $fromUserId, int $toUserId): void
    {
        if ($fromUserId <= 0 || $toUserId <= 0 || $fromUserId === $toUserId) {
            return;
        }

        [$u1, $u2] = self::normalizePair($fromUserId, $toUserId);
        $existing = self::findFriendship($fromUserId, $toUserId);
        $pdo = Database::getConnection();

        if ($existing) {
            // Se já houver amizade aceita, nada a fazer
            if (($existing['status'] ?? '') === 'accepted') {
                return;
            }

            // Atualiza pedido pendente/rejeitado
            $stmt = $pdo->prepare('UPDATE user_friends SET status = :status, requested_by_user_id = :requested_by, responded_at = NULL WHERE id = :id');
            $stmt->execute([
                'id' => (int)$existing['id'],
                'status' => 'pending',
                'requested_by' => $fromUserId,
            ]);
            return;
        }

        $stmt = $pdo->prepare('INSERT INTO user_friends (user_id, friend_user_id, status, requested_by_user_id)
            VALUES (:u1, :u2, :status, :requested_by)');
        $stmt->execute([
            'u1' => $u1,
            'u2' => $u2,
            'status' => 'pending',
            'requested_by' => $fromUserId,
        ]);
    }

    public static function decide(int $currentUserId, int $otherUserId, string $decision): void
    {
        if ($currentUserId <= 0 || $otherUserId <= 0 || $currentUserId === $otherUserId) {
            return;
        }

        $decision = $decision === 'accepted' ? 'accepted' : 'rejected';

        $friendship = self::findFriendship($currentUserId, $otherUserId);
        if (!$friendship || ($friendship['status'] ?? '') !== 'pending') {
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE user_friends SET status = :status, responded_at = NOW() WHERE id = :id');
        $stmt->execute([
            'id' => (int)$friendship['id'],
            'status' => $decision,
        ]);
    }

    public static function friendsWithUsers(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $pdo = Database::getConnection();
        $sql = 'SELECT f.*, 
                CASE WHEN f.user_id = :uid THEN f.friend_user_id ELSE f.user_id END AS friend_id,
                u.name AS friend_name,
                sp.avatar_path AS friend_avatar_path
            FROM user_friends f
            JOIN users u ON u.id = CASE WHEN f.user_id = :uid THEN f.friend_user_id ELSE f.user_id END
            LEFT JOIN user_social_profiles sp ON sp.user_id = u.id
            WHERE (f.user_id = :uid OR f.friend_user_id = :uid)
              AND f.status = "accepted"
            ORDER BY u.name ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function pendingForUser(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $pdo = Database::getConnection();
        $sql = 'SELECT f.*, 
                CASE WHEN f.user_id = :uid THEN f.friend_user_id ELSE f.user_id END AS other_id,
                u.name AS other_name,
                sp.avatar_path AS other_avatar_path
            FROM user_friends f
            JOIN users u ON u.id = CASE WHEN f.user_id = :uid THEN f.friend_user_id ELSE f.user_id END
            LEFT JOIN user_social_profiles sp ON sp.user_id = u.id
            WHERE (f.user_id = :uid OR f.friend_user_id = :uid)
              AND f.status = "pending"
              AND f.requested_by_user_id != :uid
            ORDER BY f.created_at ASC, f.id ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
