<?php

namespace App\Services;

class RssNewsService
{
    public static function fetch(array $feedUrls, int $limit = 30, int $timeoutSeconds = 8): array
    {
        $limit = max(1, min(200, $limit));

        $items = [];
        $seen = [];

        foreach ($feedUrls as $feedUrl) {
            $feedUrl = trim((string)$feedUrl);
            if ($feedUrl === '' || !preg_match('#^https?://#i', $feedUrl)) {
                continue;
            }

            $xml = self::fetchXml($feedUrl, $timeoutSeconds);
            if ($xml === null) {
                continue;
            }

            $parsed = self::parseRssXml($xml);
            foreach ($parsed as $it) {
                if (!is_array($it)) {
                    continue;
                }
                $url = trim((string)($it['url'] ?? ''));
                $title = trim((string)($it['title'] ?? ''));
                if ($url === '' || $title === '') {
                    continue;
                }

                $key = strtolower($url);
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;

                $items[] = $it;
                if (count($items) >= $limit) {
                    break 2;
                }
            }
        }

        return $items;
    }

    private static function fetchXml(string $url, int $timeoutSeconds): ?string
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
                'Accept: application/rss+xml, application/xml;q=0.9, text/xml;q=0.8, */*;q=0.5',
            ],
        ]);

        $body = curl_exec($ch);
        if ($body === false) {
            curl_close($ch);
            return null;
        }

        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300) {
            return null;
        }

        $xml = (string)$body;
        if (strlen($xml) > 2_000_000) {
            $xml = substr($xml, 0, 2_000_000);
        }

        return $xml;
    }

    private static function parseRssXml(string $xml): array
    {
        libxml_use_internal_errors(true);

        $sx = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        if (!$sx) {
            return [];
        }

        $items = [];

        // RSS 2.0
        if (isset($sx->channel) && isset($sx->channel->item)) {
            foreach ($sx->channel->item as $item) {
                $items[] = self::parseRssItem($item);
            }
            return array_values(array_filter($items));
        }

        // Atom
        if (isset($sx->entry)) {
            foreach ($sx->entry as $entry) {
                $items[] = self::parseAtomEntry($entry);
            }
        }

        return array_values(array_filter($items));
    }

    private static function parseRssItem(\SimpleXMLElement $item): ?array
    {
        $title = trim((string)($item->title ?? ''));
        $link = trim((string)($item->link ?? ''));

        if ($link === '' && isset($item->guid)) {
            $link = trim((string)$item->guid);
        }

        if ($title === '' || $link === '') {
            return null;
        }

        $publishedAt = null;
        $pubDate = trim((string)($item->pubDate ?? ''));
        if ($pubDate !== '') {
            try {
                $dt = new \DateTimeImmutable($pubDate);
                $publishedAt = $dt->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {
                $publishedAt = null;
            }
        }

        $source = null;
        if (isset($item->source)) {
            $source = trim((string)$item->source);
        }

        $desc = '';
        if (isset($item->description)) {
            $desc = trim((string)$item->description);
        }

        // content:encoded
        $contentEncoded = '';
        try {
            $namespaces = $item->getNamespaces(true);
            if (isset($namespaces['content'])) {
                $content = $item->children($namespaces['content']);
                if (isset($content->encoded)) {
                    $contentEncoded = trim((string)$content->encoded);
                }
            }
        } catch (\Throwable $e) {
            $contentEncoded = '';
        }

        $summary = $contentEncoded !== '' ? $contentEncoded : $desc;
        $summary = self::htmlToTextSnippet($summary, 280);

        return [
            'title' => $title,
            'summary' => $summary !== '' ? $summary : null,
            'url' => $link,
            'source_name' => $source,
            'published_at' => $publishedAt,
            'image_url' => null,
        ];
    }

    private static function parseAtomEntry(\SimpleXMLElement $entry): ?array
    {
        $title = trim((string)($entry->title ?? ''));
        $link = '';
        if (isset($entry->link)) {
            foreach ($entry->link as $lnk) {
                $href = (string)($lnk['href'] ?? '');
                if ($href !== '') {
                    $link = $href;
                    break;
                }
            }
        }

        if ($title === '' || $link === '') {
            return null;
        }

        $publishedAt = null;
        $pub = trim((string)($entry->published ?? $entry->updated ?? ''));
        if ($pub !== '') {
            try {
                $dt = new \DateTimeImmutable($pub);
                $publishedAt = $dt->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {
                $publishedAt = null;
            }
        }

        $summary = '';
        if (isset($entry->summary)) {
            $summary = trim((string)$entry->summary);
        } elseif (isset($entry->content)) {
            $summary = trim((string)$entry->content);
        }
        $summary = self::htmlToTextSnippet($summary, 280);

        return [
            'title' => $title,
            'summary' => $summary !== '' ? $summary : null,
            'url' => $link,
            'source_name' => null,
            'published_at' => $publishedAt,
            'image_url' => null,
        ];
    }

    private static function htmlToTextSnippet(string $html, int $maxChars): string
    {
        $txt = trim(strip_tags($html));
        $txt = html_entity_decode($txt, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $txt = preg_replace('/\s+/', ' ', $txt);
        $txt = trim((string)$txt);

        if ($txt === '') {
            return '';
        }

        $maxChars = max(80, min(1000, $maxChars));
        if (mb_strlen($txt, 'UTF-8') > $maxChars) {
            $txt = mb_substr($txt, 0, $maxChars, 'UTF-8');
            $txt = rtrim($txt, " \t\n\r\0\x0B" . "-–—,.;:");
            $txt .= '…';
        }

        return $txt;
    }
}
