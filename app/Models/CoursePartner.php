<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class CoursePartner
{
    public static function allWithUser(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT p.*, u.name AS user_name, u.email AS user_email
            FROM course_partners p
            JOIN users u ON u.id = p.user_id
            ORDER BY u.name ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

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

    public static function updatePayoutDetails(int $id, array $data): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE course_partners SET
            pix_key = :pix_key,
            bank_name = :bank_name,
            bank_agency = :bank_agency,
            bank_account = :bank_account,
            bank_account_type = :bank_account_type,
            bank_holder_name = :bank_holder_name,
            bank_holder_document = :bank_holder_document
            WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'pix_key' => ($data['pix_key'] ?? '') !== '' ? (string)$data['pix_key'] : null,
            'bank_name' => ($data['bank_name'] ?? '') !== '' ? (string)$data['bank_name'] : null,
            'bank_agency' => ($data['bank_agency'] ?? '') !== '' ? (string)$data['bank_agency'] : null,
            'bank_account' => ($data['bank_account'] ?? '') !== '' ? (string)$data['bank_account'] : null,
            'bank_account_type' => ($data['bank_account_type'] ?? '') !== '' ? (string)$data['bank_account_type'] : null,
            'bank_holder_name' => ($data['bank_holder_name'] ?? '') !== '' ? (string)$data['bank_holder_name'] : null,
            'bank_holder_document' => ($data['bank_holder_document'] ?? '') !== '' ? (string)$data['bank_holder_document'] : null,
        ]);
    }
}
