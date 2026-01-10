<?php

namespace App\Services;

class NewsArticleExtractorService
{
    public static function extract(string $url, int $timeoutSeconds = 7): array
    {
        $url = trim($url);
        if ($url === '' || !preg_match('#^https?://#i', $url)) {
            return [
                'title' => null,
                'description' => null,
                'text' => null,
            ];
        }

        $html = self::fetchHtml($url, $timeoutSeconds);
        if ($html === null) {
            return [
                'title' => null,
                'description' => null,
                'text' => null,
            ];
        }

        $title = self::extractMeta($html, 'property', 'og:title')
            ?? self::extractMeta($html, 'name', 'twitter:title')
            ?? self::extractTitleTag($html);

        $description = self::extractMeta($html, 'property', 'og:description')
            ?? self::extractMeta($html, 'name', 'description')
            ?? self::extractMeta($html, 'name', 'twitter:description');

        $text = self::extractText($html);

        return [
            'title' => $title,
            'description' => $description,
            'text' => $text,
        ];
    }

    private static function fetchHtml(string $url, int $timeoutSeconds): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => max(1, $timeoutSeconds),
            CURLOPT_CONNECTTIMEOUT => min(4, max(1, $timeoutSeconds)),
            CURLOPT_USERAGENT => 'TuquinhaNewsBot/1.0',
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ],
        ]);

        $body = curl_exec($ch);
        if ($body === false) {
            curl_close($ch);
            return null;
        }

        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300) {
            return null;
        }
        if ($contentType !== '' && stripos($contentType, 'text/html') === false && stripos($contentType, 'application/xhtml') === false) {
            return null;
        }

        $html = (string)$body;
        if (strlen($html) > 800_000) {
            $html = substr($html, 0, 800_000);
        }

        return $html;
    }

    private static function extractMeta(string $html, string $attrName, string $attrValue): ?string
    {
        $attrName = preg_quote($attrName, '/');
        $attrValue = preg_quote($attrValue, '/');

        if (preg_match('/<meta[^>]+'.$attrName.'=["\']'.$attrValue.'["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/i', $html, $m)) {
            $val = trim((string)$m[1]);
            return $val !== '' ? html_entity_decode($val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : null;
        }
        if (preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+'.$attrName.'=["\']'.$attrValue.'["\'][^>]*>/i', $html, $m)) {
            $val = trim((string)$m[1]);
            return $val !== '' ? html_entity_decode($val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : null;
        }

        return null;
    }

    private static function extractTitleTag(string $html): ?string
    {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m)) {
            $val = trim((string)$m[1]);
            $val = preg_replace('/\s+/', ' ', $val);
            $val = trim((string)$val);
            return $val !== '' ? html_entity_decode($val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : null;
        }
        return null;
    }

    private static function extractText(string $html): ?string
    {
        $clean = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $html);
        $clean = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', (string)$clean);

        $blocks = [];

        if (preg_match('/<article\b[^>]*>(.*?)<\/article>/is', (string)$clean, $m)) {
            $articleHtml = (string)$m[1];
            if (preg_match_all('/<p\b[^>]*>(.*?)<\/p>/is', $articleHtml, $pm)) {
                $blocks = $pm[1];
            }
        }

        if (!$blocks) {
            if (preg_match_all('/<p\b[^>]*>(.*?)<\/p>/is', (string)$clean, $pm)) {
                $blocks = $pm[1];
            }
        }

        $out = [];
        foreach ($blocks as $b) {
            $txt = trim(strip_tags((string)$b));
            $txt = html_entity_decode($txt, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $txt = preg_replace('/\s+/', ' ', (string)$txt);
            $txt = trim((string)$txt);
            if ($txt === '') {
                continue;
            }
            if (mb_strlen($txt, 'UTF-8') < 40) {
                continue;
            }
            $out[] = $txt;
            if (count($out) >= 20) {
                break;
            }
        }

        if (!$out) {
            return null;
        }

        $text = implode("\n\n", $out);
        if (mb_strlen($text, 'UTF-8') > 8000) {
            $text = mb_substr($text, 0, 8000, 'UTF-8');
        }

        return $text;
    }
}
