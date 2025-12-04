<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class CoursePartner
{
    public static function findByUserId(int $userId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM course_partners WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO course_partners (user_id, default_commission_percent)
            VALUES (:user_id, :default_commission_percent)');
        $stmt->execute([
            'user_id' => (int)($data['user_id'] ?? 0),
            'default_commission_percent' => (float)($data['default_commission_percent'] ?? 0.0),
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE course_partners SET
            default_commission_percent = :default_commission_percent
            WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'default_commission_percent' => (float)($data['default_commission_percent'] ?? 0.0),
        ]);
    }

    public static function deleteByUserId(int $userId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM course_partners WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
    }
}
