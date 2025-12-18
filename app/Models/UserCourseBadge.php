<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class UserCourseBadge
{
    public static function hasEarned(int $userId, int $courseId): bool
    {
        if ($userId <= 0 || $courseId <= 0) {
            return false;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT 1 FROM user_course_badges WHERE user_id = :user_id AND course_id = :course_id LIMIT 1');
        $stmt->execute([
            'user_id' => $userId,
            'course_id' => $courseId,
        ]);
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function award(int $userId, int $courseId, ?string $testimonialText, ?int $rating): bool
    {
        if ($userId <= 0 || $courseId <= 0) {
            return false;
        }

        $text = $testimonialText !== null ? trim($testimonialText) : null;
        if ($text === '') {
            $text = null;
        }

        $rate = $rating !== null ? (int)$rating : null;
        if ($rate !== null && ($rate < 1 || $rate > 5)) {
            $rate = null;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO user_course_badges (user_id, course_id, testimonial_text, rating, earned_at)
            VALUES (:user_id, :course_id, :testimonial_text, :rating, NOW())
            ON DUPLICATE KEY UPDATE testimonial_text = VALUES(testimonial_text), rating = VALUES(rating)');

        return (bool)$stmt->execute([
            'user_id' => $userId,
            'course_id' => $courseId,
            'testimonial_text' => $text,
            'rating' => $rate,
        ]);
    }

    public static function allWithCoursesByUserId(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $pdo = Database::getConnection();
        $sql = 'SELECT ucb.*, c.title AS course_title, c.slug AS course_slug, c.badge_image_path AS badge_image_path
                FROM user_course_badges ucb
                INNER JOIN courses c ON c.id = ucb.course_id
                WHERE ucb.user_id = :user_id
                ORDER BY ucb.earned_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
