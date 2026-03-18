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
                link_color = :link_color,
                paragraph_color = :paragraph_color,
                header_image_url = :header_image_url,
                footer_image_url = :footer_image_url,
                hero_image_url = :hero_image_url,
                background_image_url = :background_image_url,
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
                'link_color' => ($data['link_color'] ?? '') !== '' ? (string)$data['link_color'] : null,
                'paragraph_color' => ($data['paragraph_color'] ?? '') !== '' ? (string)$data['paragraph_color'] : null,
                'header_image_url' => ($data['header_image_url'] ?? '') !== '' ? (string)$data['header_image_url'] : null,
                'footer_image_url' => ($data['footer_image_url'] ?? '') !== '' ? (string)$data['footer_image_url'] : null,
                'hero_image_url' => ($data['hero_image_url'] ?? '') !== '' ? (string)$data['hero_image_url'] : null,
                'background_image_url' => ($data['background_image_url'] ?? '') !== '' ? (string)$data['background_image_url'] : null,
            ]);
            return;
        }

        $stmt = $pdo->prepare('INSERT INTO course_partner_branding (user_id, company_name, logo_url, primary_color, secondary_color, text_color, button_text_color, link_color, paragraph_color, header_image_url, footer_image_url, hero_image_url, background_image_url)
            VALUES (:uid, :company_name, :logo_url, :primary_color, :secondary_color, :text_color, :button_text_color, :link_color, :paragraph_color, :header_image_url, :footer_image_url, :hero_image_url, :background_image_url)');
        $stmt->execute([
            'uid' => $userId,
            'company_name' => ($data['company_name'] ?? '') !== '' ? (string)$data['company_name'] : null,
            'logo_url' => ($data['logo_url'] ?? '') !== '' ? (string)$data['logo_url'] : null,
            'primary_color' => ($data['primary_color'] ?? '') !== '' ? (string)$data['primary_color'] : null,
            'secondary_color' => ($data['secondary_color'] ?? '') !== '' ? (string)$data['secondary_color'] : null,
            'text_color' => ($data['text_color'] ?? '') !== '' ? (string)$data['text_color'] : null,
            'button_text_color' => ($data['button_text_color'] ?? '') !== '' ? (string)$data['button_text_color'] : null,
            'link_color' => ($data['link_color'] ?? '') !== '' ? (string)$data['link_color'] : null,
            'paragraph_color' => ($data['paragraph_color'] ?? '') !== '' ? (string)$data['paragraph_color'] : null,
            'header_image_url' => ($data['header_image_url'] ?? '') !== '' ? (string)$data['header_image_url'] : null,
            'footer_image_url' => ($data['footer_image_url'] ?? '') !== '' ? (string)$data['footer_image_url'] : null,
            'hero_image_url' => ($data['hero_image_url'] ?? '') !== '' ? (string)$data['hero_image_url'] : null,
            'background_image_url' => ($data['background_image_url'] ?? '') !== '' ? (string)$data['background_image_url'] : null,
        ]);
    }
}
