<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class CourseLiveParticipant
{
    public static function isParticipant(int $liveId, int $userId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id FROM course_live_participants WHERE live_id = :live_id AND user_id = :user_id LIMIT 1');
        $stmt->execute([
            'live_id' => $liveId,
            'user_id' => $userId,
        ]);
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function addParticipant(int $liveId, int $userId): bool
    {
        if ($liveId <= 0 || $userId <= 0) {
            return false;
        }
        if (self::isParticipant($liveId, $userId)) {
            return true;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO course_live_participants (live_id, user_id) VALUES (:live_id, :user_id)');
        return $stmt->execute([
            'live_id' => $liveId,
            'user_id' => $userId,
        ]);
    }

    public static function markReminderSent(int $id): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE course_live_participants SET reminder_sent_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function allByLive(int $liveId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM course_live_participants WHERE live_id = :live_id');
        $stmt->execute(['live_id' => $liveId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
