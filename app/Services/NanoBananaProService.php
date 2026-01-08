<?php

namespace App\Services;

use App\Models\Setting;

class NanoBananaProService
{
    public static function generateImage(string $prompt, array $options = []): ?array
    {
        $apiKey = trim((string)Setting::get('nano_banana_pro_api_key', ''));
        if ($apiKey === '') {
            return null;
        }

        $endpoint = trim((string)Setting::get('nano_banana_pro_endpoint', ''));
        if ($endpoint === '') {
            $endpoint = 'https://api.openai.com/v1/images/generations';
        }

        $model = trim((string)Setting::get('nano_banana_pro_model', 'nano-banana-pro'));
        if ($model === '') {
            $model = 'nano-banana-pro';
        }

        $size = isset($options['size']) ? (string)$options['size'] : '1024x1024';
        $n = isset($options['n']) ? (int)$options['n'] : 1;
        if ($n <= 0) {
            $n = 1;
        }

        $responseFormat = isset($options['response_format']) ? (string)$options['response_format'] : 'b64_json';
        if ($responseFormat !== 'b64_json' && $responseFormat !== 'url') {
            $responseFormat = 'b64_json';
        }

        $payload = [
            'model' => $model,
            'prompt' => $prompt,
            'size' => $size,
            'n' => $n,
            'response_format' => $responseFormat,
        ];

        $ch = curl_init();
        if ($ch === false) {
            return null;
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $endpoint,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 60,
        ]);

        $raw = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!is_string($raw) || $raw === '' || $httpCode < 200 || $httpCode >= 300) {
            return null;
        }

        $json = json_decode($raw, true);
        if (!is_array($json)) {
            return null;
        }

        $data = $json['data'] ?? null;
        if (!is_array($data) || empty($data[0]) || !is_array($data[0])) {
            return null;
        }

        $first = $data[0];
        if (!empty($first['url']) && is_string($first['url'])) {
            return ['url' => $first['url']];
        }

        if (!empty($first['b64_json']) && is_string($first['b64_json'])) {
            return ['b64' => $first['b64_json']];
        }

        return null;
    }
}
