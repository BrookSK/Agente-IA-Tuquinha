<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\NewsEmailDelivery;
use App\Models\NewsItem;
use App\Models\NewsUserPreference;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use App\Services\MailService;
use App\Services\PerplexityNewsService;

class CronNewsController extends Controller
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

    private function ensureCronToken(): bool
    {
        $expected = trim((string)Setting::get('news_cron_token', ''));
        $provided = trim((string)($_GET['token'] ?? ''));

        if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
            http_response_code(403);
            echo 'forbidden';
            return false;
        }

        return true;
    }

    private function isPaidActiveSubscriber(array $user): bool
    {
        if (!empty($user['is_admin'])) {
            return true;
        }

        $email = (string)($user['email'] ?? '');
        if ($email === '') {
            return false;
        }

        $sub = Subscription::findLastByEmail($email);
        if (!$sub || empty($sub['plan_id'])) {
            return false;
        }

        $status = strtolower((string)($sub['status'] ?? ''));
        if (in_array($status, ['canceled', 'expired'], true)) {
            return false;
        }

        $plan = Plan::findById((int)$sub['plan_id']);
        $slug = is_array($plan) ? (string)($plan['slug'] ?? '') : '';
        if ($slug === '' || $slug === 'free') {
            return false;
        }

        return true;
    }

    private function maybeRefreshNews(): bool
    {
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

        if (!$shouldFetch) {
            return false;
        }

        $svc = new PerplexityNewsService();
        $items = $svc->fetchMarketingNewsBrazil(30);
        if ($items) {
            NewsItem::upsertMany($items);
            return true;
        }

        return false;
    }

    public function send(): void
    {
        if (!$this->ensureCronToken()) {
            return;
        }

        $blockedDomains = $this->parseListSetting('news_blocked_domains', 'jornaldocomercio.com.br');
        $blockedSources = $this->parseListSetting('news_blocked_sources', 'jornal do comércio');

        $refreshed = $this->maybeRefreshNews();

        $users = User::all();
        $attemptedUsers = 0;
        $sentUsers = 0;
        $sentNewsTotal = 0;
        $skippedNotPaid = 0;
        $skippedNoOptIn = 0;

        foreach ($users as $u) {
            if (!is_array($u)) {
                continue;
            }
            $userId = (int)($u['id'] ?? 0);
            if ($userId <= 0) {
                continue;
            }
            if (empty($u['email'])) {
                continue;
            }

            NewsUserPreference::ensureForUserId($userId);
            $pref = NewsUserPreference::getByUserId($userId);
            if (empty($pref) || empty($pref['email_enabled'])) {
                $skippedNoOptIn++;
                continue;
            }

            if (!$this->isPaidActiveSubscriber($u)) {
                $skippedNotPaid++;
                continue;
            }

            $attemptedUsers++;

            $unsent = NewsItem::listUnsentForUser($userId, 10);
            if (!$unsent) {
                continue;
            }

            $content = '<p style="color:#b0b0b0; font-size:13px; margin:0 0 12px 0;">Separei novidades recentes sobre marketing/branding no Brasil:</p>';
            $content .= '<div style="display:flex; flex-direction:column; gap:10px;">';

            foreach ($unsent as $ni) {
                if (!is_array($ni)) {
                    continue;
                }
                $nid = (int)($ni['id'] ?? 0);
                $title = trim((string)($ni['title'] ?? ''));
                $url = trim((string)($ni['url'] ?? ''));
                $summary = trim((string)($ni['summary'] ?? ''));
                $source = trim((string)($ni['source_name'] ?? ''));

                if ($nid <= 0 || $title === '' || $url === '') {
                    continue;
                }

                if ($this->isBlockedByDomain($url, $blockedDomains)) {
                    continue;
                }
                if ($this->isBlockedBySource($source, $blockedSources)) {
                    continue;
                }
                if ($this->looksLikePaywallText($summary) || $this->looksLikePaywallText($source) || $this->looksLikePaywallText($url)) {
                    continue;
                }

                $content .= '<div style="padding:10px 12px; border-radius:12px; border:1px solid #272727; background:#0a0a10;">'
                    . '<div style="font-size:14px; font-weight:700; margin-bottom:6px;">'
                    . '<a href="' . htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" style="color:#ff6f60; text-decoration:none;">'
                    . htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    . '</a>'
                    . '</div>';

                if ($source !== '') {
                    $content .= '<div style="font-size:11px; color:#777; margin-bottom:6px;">Fonte: ' . htmlspecialchars($source, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</div>';
                }
                if ($summary !== '') {
                    $content .= '<div style="font-size:13px; color:#b0b0b0; line-height:1.45;">' . htmlspecialchars($summary, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</div>';
                }

                $content .= '</div>';
            }

            $content .= '</div>';

            $body = MailService::buildDefaultTemplate(
                (string)($u['preferred_name'] ?? $u['name'] ?? 'tudo bem?'),
                $content,
                'Abrir Notícias',
                rtrim((string)Setting::get('app_public_url', ''), '/') . '/noticias'
            );

            $ok = MailService::send((string)$u['email'], (string)($u['name'] ?? ''), 'Novas notícias de marketing (Tuquinha)', $body);
            if (!$ok) {
                continue;
            }

            $sentUsers++;

            foreach ($unsent as $ni) {
                $nid = (int)($ni['id'] ?? 0);
                if ($nid > 0) {
                    NewsEmailDelivery::markSent($userId, $nid);
                    $sentNewsTotal++;
                }
            }
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => true,
            'refreshed' => $refreshed,
            'attempted_users' => $attemptedUsers,
            'sent_users' => $sentUsers,
            'sent_news_total' => $sentNewsTotal,
            'skipped_no_opt_in' => $skippedNoOptIn,
            'skipped_not_paid' => $skippedNotPaid,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
