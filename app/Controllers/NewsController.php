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
        $candidates = NewsItem::latest(80);
        $final = [];
        $attemptedOg = 0;
        $validated = 0;

        foreach ($candidates as $row) {
            if (count($final) >= 30) {
                break;
            }
            if (!is_array($row)) {
                continue;
            }

            $nid = (int)($row['id'] ?? 0);
            $url = (string)($row['url'] ?? '');
            $img = (string)($row['image_url'] ?? '');
            if ($nid <= 0 || trim($url) === '') {
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
