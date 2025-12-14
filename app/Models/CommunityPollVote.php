<?php

namespace App\Models;

use App\Core\Database;

class CommunityPollVote
{
    public static function vote(int $pollId, int $userId, int $optionNumber): void
    {
        if ($pollId <= 0 || $userId <= 0 || $optionNumber <= 0) {
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO community_poll_votes (poll_id, user_id, option_number, created_at)
            VALUES (:poll_id, :user_id, :option_number, NOW())
            ON DUPLICATE KEY UPDATE option_number = VALUES(option_number), created_at = NOW()');
        $stmt->execute([
            'poll_id' => $pollId,
            'user_id' => $userId,
            'option_number' => $optionNumber,
        ]);
    }
}
