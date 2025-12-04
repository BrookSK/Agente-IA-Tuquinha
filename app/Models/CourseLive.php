<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class CourseLive
{
    public static function allByCourse(int $courseId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM course_lives WHERE course_id = :course_id ORDER BY scheduled_at ASC');
        $stmt->execute(['course_id' => $courseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM course_lives WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO course_lives (course_id, title, description, scheduled_at, meet_link, google_event_id, is_published)
            VALUES (:course_id, :title, :description, :scheduled_at, :meet_link, :google_event_id, :is_published)');
        $stmt->execute([
            'course_id' => (int)($data['course_id'] ?? 0),
            'title' => $data['title'] ?? '',
            'description' => $data['description'] ?? null,
            'scheduled_at' => $data['scheduled_at'] ?? '',
            'meet_link' => $data['meet_link'] ?? null,
            'google_event_id' => $data['google_event_id'] ?? null,
            'is_published' => (int)($data['is_published'] ?? 1),
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE course_lives SET
            title = :title,
            description = :description,
            scheduled_at = :scheduled_at,
            meet_link = :meet_link,
            google_event_id = :google_event_id,
            is_published = :is_published,
            updated_at = NOW()
            WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'title' => $data['title'] ?? '',
            'description' => $data['description'] ?? null,
            'scheduled_at' => $data['scheduled_at'] ?? '',
            'meet_link' => $data['meet_link'] ?? null,
            'google_event_id' => $data['google_event_id'] ?? null,
            'is_published' => (int)($data['is_published'] ?? 1),
        ]);
    }
}
