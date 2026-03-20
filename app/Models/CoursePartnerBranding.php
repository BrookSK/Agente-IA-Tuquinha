<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class CoursePartnerBranding
{
    public static function findBySubdomain(string $subdomain): ?array
    {
        $subdomain = trim(strtolower($subdomain));
        if ($subdomain === '') {
            return null;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM course_partner_branding WHERE subdomain = :s LIMIT 1');
        $stmt->execute(['s' => $subdomain]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function isSubdomainAvailable(string $subdomain, ?int $ignoreUserId = null): bool
    {
        $subdomain = trim(strtolower($subdomain));
        if ($subdomain === '') {
            return false;
        }

        $pdo = Database::getConnection();
        if ($ignoreUserId !== null && $ignoreUserId > 0) {
            $stmt = $pdo->prepare('SELECT id FROM course_partner_branding WHERE subdomain = :s AND user_id <> :uid LIMIT 1');
            $stmt->execute(['s' => $subdomain, 'uid' => $ignoreUserId]);
        } else {
            $stmt = $pdo->prepare('SELECT id FROM course_partner_branding WHERE subdomain = :s LIMIT 1');
            $stmt->execute(['s' => $subdomain]);
        }

        return !(bool)$stmt->fetch(PDO::FETCH_ASSOC);
    }

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
            $subdomain = array_key_exists('subdomain', $data)
                ? ((trim((string)($data['subdomain'] ?? '')) !== '') ? strtolower(trim((string)$data['subdomain'])) : null)
                : ($existing['subdomain'] ?? null);

            $subdomainStatus = array_key_exists('subdomain_status', $data)
                ? ((trim((string)($data['subdomain_status'] ?? '')) !== '') ? (string)$data['subdomain_status'] : (string)($existing['subdomain_status'] ?? 'none'))
                : (string)($existing['subdomain_status'] ?? 'none');

            $subdomainRequestedAt = array_key_exists('subdomain_requested_at', $data)
                ? ($data['subdomain_requested_at'] ?? null)
                : ($existing['subdomain_requested_at'] ?? null);

            $subdomainApprovedAt = array_key_exists('subdomain_approved_at', $data)
                ? ($data['subdomain_approved_at'] ?? null)
                : ($existing['subdomain_approved_at'] ?? null);

            $faviconUrl = array_key_exists('favicon_url', $data)
                ? ((trim((string)($data['favicon_url'] ?? '')) !== '') ? (string)$data['favicon_url'] : null)
                : ($existing['favicon_url'] ?? null);

            $stmt = $pdo->prepare('UPDATE course_partner_branding SET
                subdomain = :subdomain,
                subdomain_status = :subdomain_status,
                subdomain_requested_at = :subdomain_requested_at,
                subdomain_approved_at = :subdomain_approved_at,
                company_name = :company_name,
                logo_url = :logo_url,
                favicon_url = :favicon_url,
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
                'subdomain' => $subdomain,
                'subdomain_status' => $subdomainStatus,
                'subdomain_requested_at' => $subdomainRequestedAt,
                'subdomain_approved_at' => $subdomainApprovedAt,
                'company_name' => ($data['company_name'] ?? '') !== '' ? (string)$data['company_name'] : null,
                'logo_url' => ($data['logo_url'] ?? '') !== '' ? (string)$data['logo_url'] : null,
                'favicon_url' => $faviconUrl,
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

        $stmt = $pdo->prepare('INSERT INTO course_partner_branding (user_id, subdomain, subdomain_status, subdomain_requested_at, subdomain_approved_at, company_name, logo_url, favicon_url, primary_color, secondary_color, text_color, button_text_color, link_color, paragraph_color, header_image_url, footer_image_url, hero_image_url, background_image_url)
            VALUES (:uid, :subdomain, :subdomain_status, :subdomain_requested_at, :subdomain_approved_at, :company_name, :logo_url, :favicon_url, :primary_color, :secondary_color, :text_color, :button_text_color, :link_color, :paragraph_color, :header_image_url, :footer_image_url, :hero_image_url, :background_image_url)');
        $stmt->execute([
            'uid' => $userId,
            'subdomain' => ($data['subdomain'] ?? '') !== '' ? strtolower(trim((string)$data['subdomain'])) : null,
            'subdomain_status' => ($data['subdomain_status'] ?? '') !== '' ? (string)$data['subdomain_status'] : 'none',
            'subdomain_requested_at' => $data['subdomain_requested_at'] ?? null,
            'subdomain_approved_at' => $data['subdomain_approved_at'] ?? null,
            'company_name' => ($data['company_name'] ?? '') !== '' ? (string)$data['company_name'] : null,
            'logo_url' => ($data['logo_url'] ?? '') !== '' ? (string)$data['logo_url'] : null,
            'favicon_url' => ($data['favicon_url'] ?? '') !== '' ? (string)$data['favicon_url'] : null,
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
