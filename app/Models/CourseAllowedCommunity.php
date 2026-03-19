<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class CourseAllowedCommunity
{
    public static function allByCourse(int $courseId): array
    {
        if ($courseId <= 0) {
            return [];
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT cac.*, c.name AS community_name, c.slug AS community_slug
            FROM course_allowed_communities cac
            JOIN communities c ON c.id = cac.community_id
            WHERE cac.course_id = :course_id
            ORDER BY c.name ASC');
        $stmt->execute(['course_id' => $courseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function communityIdsByCourse(int $courseId): array
    {
        if ($courseId <= 0) {
            return [];
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT community_id FROM course_allowed_communities WHERE course_id = :course_id');
        $stmt->execute(['course_id' => $courseId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $ids = [];
        foreach ($rows as $row) {
            $ids[] = (int)$row['community_id'];
        }
        return $ids;
    }

    public static function saveByCourse(int $courseId, array $communityIds): void
    {
        if ($courseId <= 0) {
            return;
        }

        $pdo = Database::getConnection();

        $pdo->prepare('DELETE FROM course_allowed_communities WHERE course_id = :course_id')
            ->execute(['course_id' => $courseId]);

        if (empty($communityIds)) {
            return;
        }

        $stmt = $pdo->prepare('INSERT INTO course_allowed_communities (course_id, community_id) VALUES (:course_id, :community_id)');
        
        foreach ($communityIds as $communityId) {
            $communityId = (int)$communityId;
            if ($communityId > 0) {
                $stmt->execute([
                    'course_id' => $courseId,
                    'community_id' => $communityId,
                ]);
            }
        }
    }

    public static function deleteByCourse(int $courseId): void
    {
        if ($courseId <= 0) {
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM course_allowed_communities WHERE course_id = :course_id');
        $stmt->execute(['course_id' => $courseId]);
    }

    public static function userHasAccessToCommunity(int $userId, int $communityId): bool
    {
        if ($userId <= 0 || $communityId <= 0) {
            return false;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT cac.id
            FROM course_allowed_communities cac
            JOIN course_enrollments ce ON ce.course_id = cac.course_id
            WHERE cac.community_id = :community_id
              AND ce.user_id = :user_id
            LIMIT 1');
        $stmt->execute([
            'community_id' => $communityId,
            'user_id' => $userId,
        ]);

        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function allowedCommunitiesByUser(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT DISTINCT 
                c.*,
                (SELECT COUNT(*) FROM community_topics ct WHERE ct.community_id = c.id) as topics_count,
                (SELECT COUNT(*) FROM community_members cm WHERE cm.community_id = c.id AND cm.left_at IS NULL) as members_count
            FROM communities c
            JOIN course_allowed_communities cac ON cac.community_id = c.id
            WHERE c.is_active = 1
            AND (
                EXISTS (
                    SELECT 1 FROM course_enrollments ce 
                    WHERE ce.course_id = cac.course_id AND ce.user_id = :user_id
                )
                OR EXISTS (
                    SELECT 1 FROM course_purchases cp 
                    WHERE cp.course_id = cac.course_id AND cp.user_id = :user_id AND cp.status = "paid"
                )
            )
            ORDER BY c.name ASC');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
