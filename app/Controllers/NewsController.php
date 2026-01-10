<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\NewsItem;
use App\Models\NewsUserPreference;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
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

        if (!empty($_SESSION['is_admin'])) {
            return $user;
        }

        $sub = Subscription::findLastByEmail((string)$user['email']);
        if (!$sub || empty($sub['plan_id'])) {
            header('Location: /planos');
            exit;
        }

        $status = strtolower((string)($sub['status'] ?? ''));
        if (in_array($status, ['canceled', 'expired'], true)) {
            header('Location: /planos');
            exit;
        }

        $plan = Plan::findById((int)$sub['plan_id']);
        $slug = is_array($plan) ? (string)($plan['slug'] ?? '') : '';
        if ($slug === '' || $slug === 'free') {
            header('Location: /planos');
            exit;
        }

        return $user;
    }

    public function index(): void
    {
        $user = $this->requirePaidSubscriber();

        $ttlSeconds = (int)Setting::get('news_fetch_ttl_seconds', '600');
        if ($ttlSeconds < 60) {
            $ttlSeconds = 600;
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
            }
        }

        NewsUserPreference::ensureForUserId((int)$user['id']);
        $pref = NewsUserPreference::getByUserId((int)$user['id']);
        $emailEnabled = !empty($pref) && !empty($pref['email_enabled']);

        $news = NewsItem::latest(30);

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
}
