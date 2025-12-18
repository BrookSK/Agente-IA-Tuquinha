<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class UserReferral
{
    public static function createPending(int $referrerUserId, int $planId, string $referredEmail, ?int $referredUserId = null): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO user_referrals (referrer_user_id, referred_user_id, referred_email, plan_id, status, created_at)
            VALUES (:referrer_user_id, :referred_user_id, :referred_email, :plan_id, "pending", NOW())');
        $stmt->execute([
            'referrer_user_id' => $referrerUserId,
            'referred_user_id' => $referredUserId,
            'referred_email' => $referredEmail,
            'plan_id' => $planId,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function findPendingForUserAndPlan(int $referredUserId, int $planId): ?array
    {
        if ($referredUserId <= 0 || $planId <= 0) {
            return null;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM user_referrals WHERE referred_user_id = :uid AND plan_id = :pid AND status = "pending" ORDER BY created_at ASC LIMIT 1');
        $stmt->execute([
            'uid' => $referredUserId,
            'pid' => $planId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findFirstPendingForUser(int $referredUserId): ?array
    {
        if ($referredUserId <= 0) {
            return null;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM user_referrals WHERE referred_user_id = :uid AND status = "pending" ORDER BY created_at ASC LIMIT 1');
        $stmt->execute([
            'uid' => $referredUserId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function markCompleted(int $id): void
    {
        if ($id <= 0) {
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE user_referrals SET status = "completed", completed_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function findLastForReferredUserOrEmail(int $referredUserId, string $referredEmail): ?array
    {
        $referredEmail = trim($referredEmail);
        if ($referredUserId <= 0 && $referredEmail === '') {
            return null;
        }

        $pdo = Database::getConnection();

        if ($referredUserId > 0) {
            $stmt = $pdo->prepare('SELECT * FROM user_referrals WHERE referred_user_id = :uid ORDER BY created_at DESC LIMIT 1');
            $stmt->execute(['uid' => $referredUserId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return $row;
            }
        }

        if ($referredEmail !== '') {
            $stmt = $pdo->prepare('SELECT * FROM user_referrals WHERE referred_email = :email ORDER BY created_at DESC LIMIT 1');
            $stmt->execute(['email' => $referredEmail]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        }

        return null;
    }
}
