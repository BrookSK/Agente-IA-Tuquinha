<?php

namespace App\Services;

use App\Models\Setting;

class PerplexityNewsService
{
    public function fetchMarketingNewsBrazil(int $limit = 30): array
    {
        $apiKey = trim((string)Setting::get('perplexity_api_key', ''));
        if ($apiKey === '') {
            return [];
        }

        $model = trim((string)Setting::get('perplexity_model', 'sonar'));
        if ($model === '') {
            $model = 'sonar';
        }

        $system = 'Você é um agregador de notícias. Sua tarefa é retornar APENAS um JSON válido (sem markdown) com uma lista de notícias recentes sobre marketing, branding, publicidade, social media, e-commerce e comportamento do consumidor no Brasil. Evite política geral e notícias fora do tema.';

        $user = 'Busque as notícias mais recentes e relevantes (Brasil) para profissionais de marketing. Retorne exatamente neste formato JSON: {"items":[{"title":"...","summary":"...","url":"...","source_name":"...","published_at":"YYYY-MM-DD HH:MM:SS","image_url":"..."}]}. Regras: (1) no máximo ' . (int)$limit . ' itens; (2) title e url obrigatórios; (3) summary curto (1-2 frases); (4) published_at pode ser null se não souber; (5) image_url deve ser uma URL direta de imagem quando possível, caso contrário null.';

        $payload = json_encode([
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
        ]);

        $ch = curl_init('https://api.perplexity.ai/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 30,
        ]);

        $result = curl_exec($ch);
        if ($result === false) {
            curl_close($ch);
            return [];
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300) {
            return [];
        }

        $data = json_decode($result, true);
        $content = $data['choices'][0]['message']['content'] ?? null;
        if (!is_string($content) || trim($content) === '') {
            return [];
        }

        $json = trim($content);
        $parsed = json_decode($json, true);
        if (!is_array($parsed) || !isset($parsed['items']) || !is_array($parsed['items'])) {
            return [];
        }

        $items = [];
        $missingImageUrls = 0;
        foreach ($parsed['items'] as $it) {
            if (!is_array($it)) {
                continue;
            }
            $title = trim((string)($it['title'] ?? ''));
            $url = trim((string)($it['url'] ?? ''));
            if ($title === '' || $url === '') {
                continue;
            }

            $imageUrl = isset($it['image_url']) ? trim((string)$it['image_url']) : '';
            if ($imageUrl === '') {
                $imageUrl = null;
                $missingImageUrls++;
            }

            $items[] = [
                'title' => $title,
                'summary' => isset($it['summary']) ? (string)$it['summary'] : null,
                'url' => $url,
                'source_name' => isset($it['source_name']) ? (string)$it['source_name'] : null,
                'published_at' => isset($it['published_at']) ? (string)$it['published_at'] : null,
                'image_url' => $imageUrl,
            ];
        }

        // Fallback: algumas fontes não retornam image_url. Tentamos extrair og:image/twitter:image do link.
        // Para não travar a request, limitamos o número de tentativas.
        if ($items && $missingImageUrls > 0) {
            $maxAttempts = min(6, $missingImageUrls);
            $attempted = 0;
            foreach ($items as $idx => $row) {
                if ($attempted >= $maxAttempts) {
                    break;
                }
                if (!is_array($row)) {
                    continue;
                }
                $existingImg = (string)($row['image_url'] ?? '');
                $pageUrl = (string)($row['url'] ?? '');
                if (trim($existingImg) !== '' || trim($pageUrl) === '') {
                    continue;
                }

                $attempted++;
                try {
                    $og = OpenGraphImageService::fetchImageUrl($pageUrl, 4);
                    if (is_string($og) && trim($og) !== '') {
                        $items[$idx]['image_url'] = $og;
                    }
                } catch (\Throwable $e) {
                }
            }
        }

        return $items;
    }
}
