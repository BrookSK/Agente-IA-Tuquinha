<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class CoursePartnerBranding
{
    public static function findByUserId(int $userId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM course_partner_branding WHERE user_id = :uid LIMIT 1');
        $stmt->execute(['uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function upsert(int $userId, array $data): void
    {
        $pdo = Database::getConnection();

        $existing = self::findByUserId($userId);
        if ($existing) {
            $stmt = $pdo->prepare('UPDATE course_partner_branding SET
                company_name = :company_name,
                logo_url = :logo_url,
                primary_color = :primary_color,
                secondary_color = :secondary_color,
                text_color = :text_color,
                button_text_color = :button_text_color,
                updated_at = NOW()
                WHERE user_id = :uid
                LIMIT 1');
            $stmt->execute([
                'uid' => $userId,
                'company_name' => ($data['company_name'] ?? '') !== '' ? (string)$data['company_name'] : null,
                'logo_url' => ($data['logo_url'] ?? '') !== '' ? (string)$data['logo_url'] : null,
                'primary_color' => ($data['primary_color'] ?? '') !== '' ? (string)$data['primary_color'] : null,
                'secondary_color' => ($data['secondary_color'] ?? '') !== '' ? (string)$data['secondary_color'] : null,
                'text_color' => ($data['text_color'] ?? '') !== '' ? (string)$data['text_color'] : null,
                'button_text_color' => ($data['button_text_color'] ?? '') !== '' ? (string)$data['button_text_color'] : null,
            ]);
            return;
        }

        $stmt = $pdo->prepare('INSERT INTO course_partner_branding (user_id, company_name, logo_url, primary_color, secondary_color, text_color, button_text_color)
            VALUES (:uid, :company_name, :logo_url, :primary_color, :secondary_color, :text_color, :button_text_color)');
        $stmt->execute([
            'uid' => $userId,
            'company_name' => ($data['company_name'] ?? '') !== '' ? (string)$data['company_name'] : null,
            'logo_url' => ($data['logo_url'] ?? '') !== '' ? (string)$data['logo_url'] : null,
            'primary_color' => ($data['primary_color'] ?? '') !== '' ? (string)$data['primary_color'] : null,
            'secondary_color' => ($data['secondary_color'] ?? '') !== '' ? (string)$data['secondary_color'] : null,
            'text_color' => ($data['text_color'] ?? '') !== '' ? (string)$data['text_color'] : null,
            'button_text_color' => ($data['button_text_color'] ?? '') !== '' ? (string)$data['button_text_color'] : null,
        ]);
    }
}
