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
        return self::awardWithCertificate($userId, $courseId, $testimonialText, $rating, null, null, null);
    }

    public static function awardWithCertificate(
        int $userId,
        int $courseId,
        ?string $testimonialText,
        ?int $rating,
        ?string $certificateCode,
        ?string $startedAt,
        ?string $finishedAt
    ): bool {
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

        $code = $certificateCode !== null ? trim($certificateCode) : null;
        if ($code === '') {
            $code = null;
        }

        $start = $startedAt !== null ? trim($startedAt) : null;
        if ($start === '') {
            $start = null;
        }

        $finish = $finishedAt !== null ? trim($finishedAt) : null;
        if ($finish === '') {
            $finish = null;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO user_course_badges (user_id, course_id, testimonial_text, rating, certificate_code, started_at, finished_at, certificate_issued_at, earned_at)
            VALUES (:user_id, :course_id, :testimonial_text, :rating, :certificate_code, :started_at, :finished_at, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                testimonial_text = VALUES(testimonial_text),
                rating = VALUES(rating),
                certificate_code = COALESCE(user_course_badges.certificate_code, VALUES(certificate_code)),
                started_at = COALESCE(user_course_badges.started_at, VALUES(started_at)),
                finished_at = COALESCE(user_course_badges.finished_at, VALUES(finished_at)),
                certificate_issued_at = COALESCE(user_course_badges.certificate_issued_at, VALUES(certificate_issued_at))');

        return (bool)$stmt->execute([
            'user_id' => $userId,
            'course_id' => $courseId,
            'testimonial_text' => $text,
            'rating' => $rate,
            'certificate_code' => $code,
            'started_at' => $start,
            'finished_at' => $finish,
        ]);
    }

    public static function findByUserAndCourse(int $userId, int $courseId): ?array
    {
        if ($userId <= 0 || $courseId <= 0) {
            return null;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM user_course_badges WHERE user_id = :user_id AND course_id = :course_id LIMIT 1');
        $stmt->execute([
            'user_id' => $userId,
            'course_id' => $courseId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function hasAnyByUserId(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT 1 FROM user_course_badges WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function findByCertificateCode(string $code): ?array
    {
        $code = trim($code);
        if ($code === '') {
            return null;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM user_course_badges WHERE certificate_code = :code LIMIT 1');
        $stmt->execute(['code' => $code]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
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
