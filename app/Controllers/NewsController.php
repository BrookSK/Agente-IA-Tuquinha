<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\NewsItem;
use App\Models\NewsItemContent;
use App\Models\NewsUserPreference;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use App\Services\NewsArticleExtractorService;
use App\Services\OpenGraphImageService;
use App\Services\PerplexityNewsService;

class NewsController extends Controller
{
    private function parseListSetting(string $key, string $fallback = ''): array
    {
        $raw = (string)Setting::get($key, $fallback);
        $raw = str_replace(["\r\n", "\r"], "\n", $raw);
        $parts = preg_split('/[\n,;]+/', $raw) ?: [];
        $out = [];
        foreach ($parts as $p) {
            $p = strtolower(trim((string)$p));
            if ($p === '') {
                continue;
            }
            $out[$p] = true;
        }
        return array_keys($out);
    }

    private function getHost(string $url): string
    {
        $host = (string)(parse_url($url, PHP_URL_HOST) ?? '');
        $host = strtolower(trim($host));
        if ($host === '') {
            return '';
        }
        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }
        return $host;
    }

    private function isBlockedByDomain(string $url, array $blockedDomains): bool
    {
        $host = $this->getHost($url);
        if ($host === '' || !$blockedDomains) {
            return false;
        }
        foreach ($blockedDomains as $d) {
            $d = strtolower(trim((string)$d));
            if ($d === '') {
                continue;
            }
            if ($host === $d) {
                return true;
            }
            if (str_ends_with($host, '.' . $d)) {
                return true;
            }
        }
        return false;
    }

    private function isBlockedBySource(?string $sourceName, array $blockedSources): bool
    {
        $s = strtolower(trim((string)($sourceName ?? '')));
        if ($s === '' || !$blockedSources) {
            return false;
        }
        foreach ($blockedSources as $b) {
            $b = strtolower(trim((string)$b));
            if ($b === '') {
                continue;
            }
            if ($s === $b) {
                return true;
            }
            if (strpos($s, $b) !== false) {
                return true;
            }
        }
        return false;
    }

    private function looksLikePaywallText(string $text): bool
    {
        $t = strtolower(trim($text));
        if ($t === '') {
            return false;
        }
        $markers = [
            'assine',
            'assinatura',
            'conteúdo exclusivo',
            'conteudo exclusivo',
            'para continuar lendo',
            'continue lendo',
            'faça login',
            'faca login',
            'cadastre-se',
            'cadastro',
            'acesso livre',
            'seja assinante',
        ];
        foreach ($markers as $m) {
            if (strpos($t, $m) !== false) {
                return true;
            }
        }
        return false;
    }

    private function requirePaidSubscriber(): array
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $user = User::findById((int)$_SESSION['user_id']);
        if (!$user || empty($user['email'])) {
            header('Location: /login');
            exit;
        }

        return $user;
    }

    public function index(): void
    {
        $user = $this->requirePaidSubscriber();

        $blockedDomains = $this->parseListSetting('news_blocked_domains', 'jornaldocomercio.com.br');
        $blockedSources = $this->parseListSetting('news_blocked_sources', 'jornal do comércio');

        $timesPerDay = (int)Setting::get('news_fetch_times_per_day', '0');
        if ($timesPerDay > 0) {
            $timesPerDay = max(1, min(48, $timesPerDay));
            $ttlSeconds = (int)floor(86400 / $timesPerDay);
        } else {
            $ttlSeconds = (int)Setting::get('news_fetch_ttl_seconds', '600');
        }
        if ($ttlSeconds < 60) {
            $ttlSeconds = 60;
        }

        $shouldFetch = true;
        $lastFetchedAt = NewsItem::getLastFetchedAt();
        if (is_string($lastFetchedAt) && $lastFetchedAt !== '') {
            try {
                $last = new \DateTimeImmutable($lastFetchedAt);
                $now = new \DateTimeImmutable('now');
                $diff = $now->getTimestamp() - $last->getTimestamp();
                if ($diff >= 0 && $diff < $ttlSeconds) {
                    $shouldFetch = false;
                }
            } catch (\Throwable $e) {
                $shouldFetch = true;
            }
        }

        $fetchedNow = false;
        if ($shouldFetch) {
            $svc = new PerplexityNewsService();
            $items = $svc->fetchMarketingNewsBrazil(30);
            if ($items) {
                NewsItem::upsertMany($items);
                $fetchedNow = true;
                $lastFetchedAt = NewsItem::getLastFetchedAt();
            }
        }

        NewsUserPreference::ensureForUserId((int)$user['id']);
        $pref = NewsUserPreference::getByUserId((int)$user['id']);
        $emailEnabled = !empty($pref) && !empty($pref['email_enabled']);

        // Busca mais itens para conseguir preencher o grid apenas com notícias que tenham imagem válida.
        $desiredCount = 30;
        if ($desiredCount < 10) {
            $desiredCount = 10;
        }
        $candidates = NewsItem::latest(200);
        $final = [];
        $attemptedOg = 0;
        $validated = 0;
        $seenUrls = [];
        $seenTitles = [];

        foreach ($candidates as $row) {
            if (count($final) >= $desiredCount) {
                break;
            }
            if (!is_array($row)) {
                continue;
            }

            $nid = (int)($row['id'] ?? 0);
            $url = (string)($row['url'] ?? '');
            $img = (string)($row['image_url'] ?? '');
            $sourceName = isset($row['source_name']) ? (string)$row['source_name'] : null;
            $summary = isset($row['summary']) ? (string)$row['summary'] : '';
            if ($nid <= 0 || trim($url) === '') {
                continue;
            }

            $urlKey = strtolower(trim($url));
            if ($urlKey !== '' && isset($seenUrls[$urlKey])) {
                continue;
            }
            $titleKey = strtolower(trim((string)($row['title'] ?? '')));
            if ($titleKey !== '' && isset($seenTitles[$titleKey])) {
                continue;
            }

            if ($this->isBlockedByDomain($url, $blockedDomains)) {
                continue;
            }
            if ($this->isBlockedBySource($sourceName, $blockedSources)) {
                continue;
            }
            if ($this->looksLikePaywallText($summary) || $this->looksLikePaywallText($url) || $this->looksLikePaywallText((string)$sourceName)) {
                continue;
            }

            $img = trim($img);

            // Se tiver imagem, valida rapidamente (evita quebradas/hotlink bloqueado)
            if ($img !== '') {
                $validated++;
                if (!OpenGraphImageService::isLikelyValidImageUrl($img, 3)) {
                    NewsItem::clearImageUrl($nid);
                    $img = '';
                }
            }

            // Se não tiver imagem (ou foi invalidada), tenta backfill via OpenGraph
            if ($img === '' && $attemptedOg < 8) {
                $attemptedOg++;
                try {
                    $og = OpenGraphImageService::fetchImageUrl($url, 4);
                    if (is_string($og) && trim($og) !== '') {
                        NewsItem::updateImageUrl($nid, $og);
                        $row['image_url'] = $og;
                        $img = $og;
                    }
                } catch (\Throwable $e) {
                }
            }

            // Só exibe se tiver imagem válida
            if (trim($img) === '') {
                continue;
            }

            $final[] = $row;
            if ($urlKey !== '') {
                $seenUrls[$urlKey] = true;
            }
            if ($titleKey !== '') {
                $seenTitles[$titleKey] = true;
            }
        }

        $news = $final;

        $this->view('news/index', [
            'pageTitle' => 'Notícias - Tuquinha',
            'user' => $user,
            'news' => $news,
            'emailEnabled' => $emailEnabled,
            'fetchedNow' => $fetchedNow,
            'lastFetchedAt' => $lastFetchedAt,
        ]);
    }

    public function toggleEmail(): void
    {
        $user = $this->requirePaidSubscriber();

        $enabled = !empty($_POST['email_enabled']);
        NewsUserPreference::setEmailEnabled((int)$user['id'], $enabled);

        header('Location: /noticias');
        exit;
    }

    public function show(): void
    {
        $user = $this->requirePaidSubscriber();

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(404);
            echo 'Notícia não encontrada';
            return;
        }

        $newsItem = NewsItem::findById($id);
        if (!$newsItem || empty($newsItem['id'])) {
            http_response_code(404);
            echo 'Notícia não encontrada';
            return;
        }

        $content = null;
        try {
            $content = NewsItemContent::getByNewsItemId((int)$newsItem['id']);
        } catch (\Throwable $e) {
            $content = null;
        }

        $shouldExtract = false;
        if (!$content) {
            $shouldExtract = true;
        } else {
            $exAt = $content['extracted_at'] ?? null;
            if (is_string($exAt) && $exAt !== '') {
                try {
                    $last = new \DateTimeImmutable($exAt);
                    $now = new \DateTimeImmutable('now');
                    $diff = $now->getTimestamp() - $last->getTimestamp();
                    if ($diff < 0 || $diff > 86400) {
                        $shouldExtract = true;
                    }
                } catch (\Throwable $e) {
                    $shouldExtract = true;
                }
            } else {
                $shouldExtract = true;
            }
        }

        if ($shouldExtract) {
            $url = (string)($newsItem['url'] ?? '');
            $ex = NewsArticleExtractorService::extract($url, 7);
            try {
                NewsItemContent::upsert(
                    (int)$newsItem['id'],
                    isset($ex['title']) ? (string)$ex['title'] : null,
                    isset($ex['description']) ? (string)$ex['description'] : null,
                    isset($ex['text']) ? (string)$ex['text'] : null
                );
                $content = NewsItemContent::getByNewsItemId((int)$newsItem['id']);
            } catch (\Throwable $e) {
                // sem cache
            }
        }

        $this->view('news/view', [
            'pageTitle' => 'Notícias - Tuquinha',
            'user' => $user,
            'newsItem' => $newsItem,
            'content' => $content,
        ]);
    }
}
