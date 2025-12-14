<?php

namespace App\Services;

use App\Models\Setting;

class MediaStorageService
{
    /**
     * Envia um arquivo local para o servidor de mídia externo usando o endpoint padrão
     * configurado (MEDIA_UPLOAD_ENDPOINT / setting media_upload_endpoint).
     */
    public static function uploadFile(string $localPath, string $originalName, string $mimeType = ''): ?string
    {
        return self::uploadFileToEndpoint($localPath, $originalName, $mimeType, '');
    }

    /**
     * Envia um arquivo local para um endpoint específico ou, se não informado,
     * para o endpoint padrão configurado.
     */
    public static function uploadFileToEndpoint(string $localPath, string $originalName, string $mimeType = '', string $endpoint = ''): ?string
    {
        if (!is_file($localPath) || !is_readable($localPath)) {
            return null;
        }

        $defaultEndpoint = defined('MEDIA_UPLOAD_ENDPOINT') ? MEDIA_UPLOAD_ENDPOINT : '';
        $base = trim(Setting::get('media_upload_endpoint', $defaultEndpoint));
        $configured = $endpoint !== '' ? $endpoint : $base;
        if ($configured === '') {
            return null;
        }

        $url = $configured;

        // Garante que exista algum MIME simples
        $mime = $mimeType !== '' ? $mimeType : 'application/octet-stream';
        $name = $originalName !== '' ? $originalName : basename($localPath);

        $ch = curl_init();

        $file = new \CURLFile($localPath, $mime, $name);
        $postFields = [
            'file' => $file,
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_TIMEOUT => 60,
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

        if (($data['status'] ?? '') !== 'success') {
            return null;
        }

        $mediaUrl = $data['url'] ?? null;
        return is_string($mediaUrl) && $mediaUrl !== '' ? $mediaUrl : null;
    }
}
