<?php

namespace App\Models;

use App\Models\Setting;

class Branding
{
    private static ?array $cache = null;

    private static array $defaults = [
        'brand_platform_name'    => 'Resenha 2.0',
        'brand_platform_short'   => 'Tuquinha IA',
        'brand_mascot_name'      => 'Tuquinha',
        'brand_agency_name'      => 'Agência Tuca',
        'brand_slogan'           => 'Branding vivo na veia',
        'brand_company_name'     => 'Nuvem Labs',
        'brand_user_agent'       => 'TuquinhaApp',
        'brand_community_name'   => 'Comunidade do Tuquinha',
    ];

    private static function load(): void
    {
        if (self::$cache !== null) {
            return;
        }
        self::$cache = [];
        foreach (self::$defaults as $key => $default) {
            $val = Setting::get($key, '');
            self::$cache[$key] = ($val !== null && $val !== '') ? $val : $default;
        }
    }

    public static function get(string $key): string
    {
        self::load();
        return self::$cache[$key] ?? (self::$defaults[$key] ?? '');
    }

    public static function all(): array
    {
        self::load();
        return self::$cache;
    }

    public static function platformName(): string
    {
        return self::get('brand_platform_name');
    }

    public static function platformShort(): string
    {
        return self::get('brand_platform_short');
    }

    public static function mascotName(): string
    {
        return self::get('brand_mascot_name');
    }

    public static function agencyName(): string
    {
        return self::get('brand_agency_name');
    }

    public static function slogan(): string
    {
        return self::get('brand_slogan');
    }

    public static function companyName(): string
    {
        return self::get('brand_company_name');
    }

    public static function userAgent(): string
    {
        return self::get('brand_user_agent');
    }

    public static function communityName(): string
    {
        return self::get('brand_community_name');
    }

    public static function mascotInitials(): string
    {
        $name = self::mascotName();
        return $name !== '' ? mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8') : 'T';
    }

    /**
     * Retorna o bloco HTML do cabeçalho de e-mail com avatar, nome da plataforma e slogan.
     * Aceita uma URL de logo opcional; se vazia, usa a inicial do mascote como fallback.
     */
    public static function emailHeaderHtml(string $logoUrl = ''): string
    {
        $safeLogo = htmlspecialchars($logoUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $initial = htmlspecialchars(self::mascotInitials(), ENT_QUOTES, 'UTF-8');
        $platform = htmlspecialchars(self::platformName(), ENT_QUOTES, 'UTF-8');
        $slogan = htmlspecialchars(self::slogan(), ENT_QUOTES, 'UTF-8');
        $mascot = htmlspecialchars(self::mascotName(), ENT_QUOTES, 'UTF-8');

        if ($safeLogo !== '') {
            $avatar = '<div style="width:32px; height:32px; border-radius:50%; overflow:hidden; background:#050509; box-shadow:0 0 18px rgba(229,57,53,0.8);"><img src="' . $safeLogo . '" alt="' . $mascot . '" style="width:100%; height:100%; display:block; object-fit:cover;"></div>';
        } else {
            $avatar = '<div style="width:32px; height:32px; line-height:32px; border-radius:50%; background:radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%); text-align:center; font-weight:700; font-size:16px; color:#050509;">' . $initial . '</div>';
        }

        return '<div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">'
            . $avatar
            . '<div>'
            . '<div style="font-weight:700; font-size:15px;">' . $platform . '</div>'
            . '<div style="font-size:11px; color:#b0b0b0;">' . $slogan . '</div>'
            . '</div>'
            . '</div>';
    }

    /**
     * Retorna variáveis de branding já escapadas para uso em heredoc/templates.
     */
    public static function safeVars(): array
    {
        return [
            'platform' => htmlspecialchars(self::platformName(), ENT_QUOTES, 'UTF-8'),
            'short'    => htmlspecialchars(self::platformShort(), ENT_QUOTES, 'UTF-8'),
            'mascot'   => htmlspecialchars(self::mascotName(), ENT_QUOTES, 'UTF-8'),
            'agency'   => htmlspecialchars(self::agencyName(), ENT_QUOTES, 'UTF-8'),
            'slogan'   => htmlspecialchars(self::slogan(), ENT_QUOTES, 'UTF-8'),
            'company'  => htmlspecialchars(self::companyName(), ENT_QUOTES, 'UTF-8'),
            'initial'  => htmlspecialchars(self::mascotInitials(), ENT_QUOTES, 'UTF-8'),
            'community' => htmlspecialchars(self::communityName(), ENT_QUOTES, 'UTF-8'),
        ];
    }

    public static function defaults(): array
    {
        return self::$defaults;
    }

    public static function clearCache(): void
    {
        self::$cache = null;
    }
}
