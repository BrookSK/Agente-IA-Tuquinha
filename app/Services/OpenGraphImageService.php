<?php

namespace App\Services;

class OpenGraphImageService
{
    public static function fetchImageUrl(string $pageUrl, int $timeoutSeconds = 4): ?string
    {
        $pageUrl = trim($pageUrl);
        if ($pageUrl === '' || !preg_match('#^https?://#i', $pageUrl)) {
            return null;
        }

        $html = self::fetchHtml($pageUrl, $timeoutSeconds);
        if ($html === null) {
            return null;
        }

        $candidates = [];

        if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/i', $html, $m)) {
            $candidates[] = $m[1];
        }
        if (preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image["\'][^>]*>/i', $html, $m)) {
            $candidates[] = $m[1];
        }

        if (preg_match('/<meta[^>]+name=["\']twitter:image["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/i', $html, $m)) {
            $candidates[] = $m[1];
        }
        if (preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+name=["\']twitter:image["\'][^>]*>/i', $html, $m)) {
            $candidates[] = $m[1];
        }

        foreach ($candidates as $candidate) {
            $img = trim((string)$candidate);
            if ($img === '') {
                continue;
            }
            $abs = self::resolveUrl($pageUrl, $img);
            if ($abs !== null && preg_match('#^https?://#i', $abs)) {
                return $abs;
            }
        }

        return null;
    }

    private static function fetchHtml(string $url, int $timeoutSeconds): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => max(1, $timeoutSeconds),
            CURLOPT_CONNECTTIMEOUT => min(3, max(1, $timeoutSeconds)),
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

        $bodyStr = (string)$body;
        if (strlen($bodyStr) > 350_000) {
            $bodyStr = substr($bodyStr, 0, 350_000);
        }

        return $bodyStr;
    }

    private static function resolveUrl(string $baseUrl, string $maybeRelative): ?string
    {
        $maybeRelative = trim($maybeRelative);
        if ($maybeRelative === '') {
            return null;
        }
        if (preg_match('#^https?://#i', $maybeRelative)) {
            return $maybeRelative;
        }
        if (strpos($maybeRelative, '//') === 0) {
            $scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https';
            return $scheme . ':' . $maybeRelative;
        }

        $scheme = (string)(parse_url($baseUrl, PHP_URL_SCHEME) ?? '');
        $host = (string)(parse_url($baseUrl, PHP_URL_HOST) ?? '');
        if ($scheme === '' || $host === '') {
            return null;
        }

        $port = parse_url($baseUrl, PHP_URL_PORT);
        $origin = $scheme . '://' . $host;
        if (is_int($port) && $port > 0 && !in_array($port, [80, 443], true)) {
            $origin .= ':' . $port;
        }

        if (strpos($maybeRelative, '/') === 0) {
            return $origin . $maybeRelative;
        }

        $path = (string)(parse_url($baseUrl, PHP_URL_PATH) ?? '/');
        $dir = rtrim(str_replace('\\', '/', (string)dirname($path)), '/');
        if ($dir === '.') {
            $dir = '';
        }

        return $origin . ($dir !== '' ? '/' . $dir : '') . '/' . $maybeRelative;
    }
}
