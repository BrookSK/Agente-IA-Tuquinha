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
            $endpoint = 'https://generativelanguage.googleapis.com';
        }

        $model = isset($options['model']) ? trim((string)$options['model']) : '';
        if ($model === '') {
            $model = trim((string)Setting::get('nano_banana_pro_model', 'nano-banana-pro'));
        }
        if ($model === '') {
            $model = 'gemini-2.5-flash-image';
        }

        // Compatibilidade: se alguém deixar o valor padrão antigo, assume o modelo Nano Banana compatível.
        if ($model === 'nano-banana-pro') {
            $model = 'gemini-2.5-flash-image';
        }

        // API Gemini/Nano Banana não usa size="1024x1024"; usa aspectRatio (e, em alguns modelos, imageSize).
        $size = isset($options['size']) ? (string)$options['size'] : '1024x1024';
        $aspectRatio = '1:1';
        if ($size === '1792x1024' || $size === '1536x864') {
            $aspectRatio = '16:9';
        } elseif ($size === '1024x1792' || $size === '864x1536') {
            $aspectRatio = '9:16';
        } elseif ($size === '1024x768' || $size === '1536x1152') {
            $aspectRatio = '4:3';
        } elseif ($size === '768x1024' || $size === '1152x1536') {
            $aspectRatio = '3:4';
        }

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'imageConfig' => [
                    'aspectRatio' => $aspectRatio,
                ],
            ],
        ];

        // Endpoint pode ser base (host) ou URL completa. Normaliza para .../v1beta/models/{model}:generateContent
        $finalUrl = $endpoint;
        $finalUrl = rtrim($finalUrl, '/');
        if (stripos($finalUrl, ':generateContent') === false) {
            if (stripos($finalUrl, '/v1beta') === false) {
                $finalUrl .= '/v1beta';
            }
            $finalUrl .= '/models/' . rawurlencode($model) . ':generateContent';
        }

        $ch = curl_init();
        if ($ch === false) {
            return null;
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $finalUrl,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-goog-api-key: ' . $apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 60,
        ]);

        $raw = curl_exec($ch);
        $curlErrNo = curl_errno($ch);
        $curlErr = $curlErrNo ? (string)curl_error($ch) : '';
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!is_string($raw) || $raw === '' || $httpCode < 200 || $httpCode >= 300) {
            $details = '';
            if ($curlErrNo) {
                $details = 'cURL(' . $curlErrNo . '): ' . $curlErr;
            } else {
                $details = 'HTTP ' . $httpCode;
            }

            $bodyMsg = '';
            $retrySeconds = null;
            if (is_string($raw) && $raw !== '') {
                $maybeJson = json_decode($raw, true);
                if (is_array($maybeJson)) {
                    if (isset($maybeJson['error'])) {
                        if (is_string($maybeJson['error'])) {
                            $bodyMsg = $maybeJson['error'];
                        } elseif (is_array($maybeJson['error'])) {
                            $bodyMsg = (string)($maybeJson['error']['message'] ?? $maybeJson['error']['error'] ?? '');
                        }
                    }
                    if ($bodyMsg === '' && isset($maybeJson['message']) && is_string($maybeJson['message'])) {
                        $bodyMsg = $maybeJson['message'];
                    }
                }

                if ($bodyMsg === '') {
                    $bodyMsg = mb_substr(trim($raw), 0, 280, 'UTF-8');
                }

                if (preg_match('/retry\s+in\s+([0-9]+(?:\.[0-9]+)?)s/i', $raw, $m)) {
                    $retrySeconds = (float)$m[1];
                }
            }

            $logLine = '[NanoBananaPro] Falha ao gerar imagem. endpoint=' . $finalUrl
                . ' model=' . $model
                . ' ' . $details
                . ($bodyMsg !== '' ? ' body=' . $bodyMsg : '');
            error_log($logLine);

            $friendly = '';
            if ($httpCode === 429) {
                $friendly = 'A API do Nano Banana/Gemini bloqueou por limite de uso (HTTP 429 - quota/rate limit).';
                if ($retrySeconds !== null) {
                    $friendly .= ' Tente novamente em aproximadamente ' . number_format($retrySeconds, 1, ',', '.') . 's.';
                }
                $friendly .= ' Verifique se sua chave do Gemini tem billing/quota habilitados no Google Cloud.';
            } else {
                $friendly = 'Falha ao gerar imagem (' . $details . ').';
            }

            if ($bodyMsg !== '' && $bodyMsg !== 'HTTP ' . $httpCode) {
                $friendly .= ' ' . $bodyMsg;
            }
            return [
                'error' => $friendly,
                'http_code' => $httpCode,
            ];
        }

        $json = json_decode($raw, true);
        if (!is_array($json)) {
            return null;
        }

        $candidates = $json['candidates'] ?? null;
        if (!is_array($candidates) || empty($candidates[0]) || !is_array($candidates[0])) {
            error_log('[NanoBananaPro] Resposta inválida: não encontrei candidates[0]. endpoint=' . $finalUrl . ' model=' . $model);
            return [
                'error' => 'Resposta inválida do provedor de imagens (faltou candidates[0]).',
                'http_code' => $httpCode,
            ];
        }

        $content = $candidates[0]['content'] ?? null;
        $parts = is_array($content) ? ($content['parts'] ?? null) : null;
        if (!is_array($parts)) {
            error_log('[NanoBananaPro] Resposta inválida: candidates[0].content.parts ausente. endpoint=' . $finalUrl . ' model=' . $model);
            return [
                'error' => 'Resposta inválida do provedor de imagens (faltou content.parts).',
                'http_code' => $httpCode,
            ];
        }

        foreach ($parts as $p) {
            if (!is_array($p)) {
                continue;
            }
            $inline = $p['inlineData'] ?? null;
            if (is_array($inline)) {
                $dataB64 = $inline['data'] ?? null;
                if (is_string($dataB64) && trim($dataB64) !== '') {
                    return ['b64' => $dataB64];
                }
            }
        }

        error_log('[NanoBananaPro] Resposta sem inlineData.data (imagem). endpoint=' . $finalUrl . ' model=' . $model);
        return [
            'error' => 'Resposta inválida do provedor de imagens (faltou inlineData.data).',
            'http_code' => $httpCode,
        ];
    }
}
