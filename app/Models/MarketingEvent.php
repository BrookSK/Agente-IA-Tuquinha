<?php

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * SQL para criar a tabela (executar manualmente):
 *
 * ALTER TABLE plans ADD COLUMN allow_marketing_calendar TINYINT(1) NOT NULL DEFAULT 0 AFTER allow_kanban_sharing;
 * ALTER TABLE plans ADD COLUMN allow_marketing_calendar_sharing TINYINT(1) NOT NULL DEFAULT 0 AFTER allow_marketing_calendar;
 *
 * CREATE TABLE marketing_events (
 *     id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 *     owner_user_id INT UNSIGNED NOT NULL,
 *     title VARCHAR(255) NOT NULL DEFAULT '',
 *     event_date DATE NOT NULL,
 *     event_type ENUM('post','story','reels','video','email','anuncio','outro') NOT NULL DEFAULT 'post',
 *     status ENUM('planejado','produzido','postado') NOT NULL DEFAULT 'planejado',
 *     responsible VARCHAR(255) DEFAULT NULL,
 *     color VARCHAR(20) NOT NULL DEFAULT '#e53935',
 *     notes TEXT DEFAULT NULL,
 *     reference_links JSON DEFAULT NULL,
 *     is_published TINYINT(1) NOT NULL DEFAULT 0,
 *     public_token VARCHAR(64) DEFAULT NULL,
 *     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 *     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 *     INDEX idx_owner_date (owner_user_id, event_date),
 *     INDEX idx_public_token (public_token)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 *
 * CREATE TABLE marketing_event_shares (
 *     id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 *     owner_user_id INT UNSIGNED NOT NULL,
 *     shared_with_user_id INT UNSIGNED NOT NULL,
 *     role ENUM('view','edit') NOT NULL DEFAULT 'view',
 *     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 *     UNIQUE KEY uq_owner_shared (owner_user_id, shared_with_user_id)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 */
class MarketingEvent
{
    public static function listForUserMonth(int $userId, int $year, int $month): array
    {
        $pdo = Database::getConnection();
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        $sql = 'SELECT e.* FROM marketing_events e
                WHERE e.owner_user_id = :uid AND e.event_date BETWEEN :start AND :end
                UNION
                SELECT e.* FROM marketing_events e
                INNER JOIN marketing_event_shares s ON s.owner_user_id = e.owner_user_id
                WHERE s.shared_with_user_id = :uid2 AND e.event_date BETWEEN :start2 AND :end2
                ORDER BY event_date ASC, id ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'uid' => $userId,
            'start' => $startDate,
            'end' => $endDate,
            'uid2' => $userId,
            'start2' => $startDate,
            'end2' => $endDate,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM marketing_events WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findAccessible(int $id, int $userId): ?array
    {
        $event = self::findById($id);
        if (!$event) {
            return null;
        }
        if ((int)($event['owner_user_id'] ?? 0) === $userId) {
            return $event;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id FROM marketing_event_shares WHERE owner_user_id = :oid AND shared_with_user_id = :uid LIMIT 1');
        $stmt->execute(['oid' => (int)$event['owner_user_id'], 'uid' => $userId]);
        if ($stmt->fetch()) {
            return $event;
        }
        return null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO marketing_events
            (owner_user_id, title, event_date, event_type, status, responsible, color, notes, reference_links)
            VALUES (:owner_user_id, :title, :event_date, :event_type, :status, :responsible, :color, :notes, :reference_links)');
        $stmt->execute([
            'owner_user_id' => (int)($data['owner_user_id'] ?? 0),
            'title' => $data['title'] ?? '',
            'event_date' => $data['event_date'] ?? date('Y-m-d'),
            'event_type' => $data['event_type'] ?? 'post',
            'status' => $data['status'] ?? 'planejado',
            'responsible' => $data['responsible'] ?? null,
            'color' => $data['color'] ?? '#e53935',
            'notes' => $data['notes'] ?? null,
            'reference_links' => $data['reference_links'] ?? null,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function updateById(int $id, array $data): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE marketing_events SET
            title = :title,
            event_date = :event_date,
            event_type = :event_type,
            status = :status,
            responsible = :responsible,
            color = :color,
            notes = :notes,
            reference_links = :reference_links
            WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'title' => $data['title'] ?? '',
            'event_date' => $data['event_date'] ?? date('Y-m-d'),
            'event_type' => $data['event_type'] ?? 'post',
            'status' => $data['status'] ?? 'planejado',
            'responsible' => $data['responsible'] ?? null,
            'color' => $data['color'] ?? '#e53935',
            'notes' => $data['notes'] ?? null,
            'reference_links' => $data['reference_links'] ?? null,
        ]);
    }

    public static function deleteById(int $id): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM marketing_events WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function setPublished(int $ownerId, bool $publish, ?string $token): void
    {
        $pdo = Database::getConnection();
        if ($publish) {
            $stmt = $pdo->prepare('UPDATE marketing_events SET is_published = 1, public_token = :token WHERE owner_user_id = :uid');
            $stmt->execute(['token' => $token, 'uid' => $ownerId]);
        } else {
            $stmt = $pdo->prepare('UPDATE marketing_events SET is_published = 0, public_token = NULL WHERE owner_user_id = :uid');
            $stmt->execute(['uid' => $ownerId]);
        }
    }

    public static function listPublicByToken(string $token, int $year, int $month): array
    {
        $pdo = Database::getConnection();
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        $stmt = $pdo->prepare('SELECT * FROM marketing_events WHERE public_token = :token AND is_published = 1 AND event_date BETWEEN :start AND :end ORDER BY event_date ASC, id ASC');
        $stmt->execute(['token' => $token, 'start' => $startDate, 'end' => $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function getPublicTokenForUser(int $userId): ?string
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT public_token FROM marketing_events WHERE owner_user_id = :uid AND is_published = 1 AND public_token IS NOT NULL LIMIT 1');
        $stmt->execute(['uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? ($row['public_token'] ?? null) : null;
    }
}
