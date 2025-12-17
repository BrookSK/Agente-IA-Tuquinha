<?php

namespace App\Services;

use App\Models\Setting;

class TextExtractionService
{
    public static function extractFromFile(string $localPath, string $originalName, string $mimeType = ''): ?string
    {
        if (!is_file($localPath) || !is_readable($localPath)) {
            return null;
        }

        $endpoint = trim((string)Setting::get('text_extraction_endpoint', ''));
        if ($endpoint === '') {
            return null;
        }

        $size = @filesize($localPath);
        if (is_int($size) && $size > 15 * 1024 * 1024) {
            return null;
        }

        $mime = $mimeType !== '' ? $mimeType : 'application/octet-stream';
        $name = $originalName !== '' ? $originalName : basename($localPath);

        $ch = curl_init();
        $file = new \CURLFile($localPath, $mime, $name);

        curl_setopt_array($ch, [
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [
                'file' => $file,
            ],
            CURLOPT_TIMEOUT => 120,
        ]);

        $response = curl_exec($ch);
        if ($response === false) {
            curl_close($ch);
            return null;
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300) {
            return null;
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            return null;
        }

        $text = $data['extracted_text'] ?? ($data['text'] ?? ($data['content'] ?? null));
        if (!is_string($text)) {
            return null;
        }

        $text = trim($text);
        if ($text === '') {
            return null;
        }

        if (mb_strlen($text, 'UTF-8') > 200000) {
            $text = mb_substr($text, 0, 200000, 'UTF-8');
        }

        return $text;
    }
}
