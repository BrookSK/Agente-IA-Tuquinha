<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\MarketingEvent;
use App\Models\MarketingEventShare;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;

class MarketingCalendarController extends Controller
{
    private function wantsJson(): bool
    {
        $accept = (string)($_SERVER['HTTP_ACCEPT'] ?? '');
        if ($accept !== '' && stripos($accept, 'application/json') !== false) {
            return true;
        }
        $xrw = (string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
        if ($xrw !== '' && strtolower($xrw) === 'xmlhttprequest') {
            return true;
        }
        return false;
    }

    private function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
        exit;
    }

    private function requireLogin(): array
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        $user = User::findById((int)$_SESSION['user_id']);
        if (!$user) {
            unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email']);
            header('Location: /login');
            exit;
        }
        return $user;
    }

    private function getActivePlanForEmail(string $email): ?array
    {
        $email = trim($email);
        if ($email === '') {
            return null;
        }
        $subscription = Subscription::findLastByEmail($email);
        if (!$subscription || empty($subscription['plan_id'])) {
            return null;
        }
        $status = strtolower((string)($subscription['status'] ?? ''));
        $isActive = !in_array($status, ['canceled', 'expired'], true);
        if (!$isActive) {
            return null;
        }
        $plan = Plan::findById((int)$subscription['plan_id']);
        return $plan ?: null;
    }

    private function requireCalendarAccess(array $user): array
    {
        if (!empty($_SESSION['is_admin'])) {
            return ['plan' => null, 'subscription_active' => true];
        }
        $plan = $this->getActivePlanForEmail((string)($user['email'] ?? ''));
        if (!$plan) {
            if ($this->wantsJson()) {
                $this->json(['ok' => false, 'error' => 'Sem assinatura ativa.'], 403);
            }
            header('Location: /planos');
            exit;
        }
        if (empty($plan['allow_marketing_calendar'])) {
            if ($this->wantsJson()) {
                $this->json(['ok' => false, 'error' => 'Seu plano não permite a Agenda de Marketing.'], 403);
            }
            header('Location: /planos');
            exit;
        }
        return ['plan' => $plan, 'subscription_active' => true];
    }

    private function canShare(array $user): bool
    {
        if (!empty($_SESSION['is_admin'])) {
            return true;
        }
        $plan = $this->getActivePlanForEmail((string)($user['email'] ?? ''));
        return $plan && !empty($plan['allow_marketing_calendar_sharing']);
    }

    public function index(): void
    {
        $user = $this->requireLogin();
        $access = $this->requireCalendarAccess($user);
        $uid = (int)$user['id'];

        $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
        if ($month < 1) { $month = 1; }
        if ($month > 12) { $month = 12; }
        if ($year < 2020) { $year = 2020; }
        if ($year > 2100) { $year = 2100; }

        $events = MarketingEvent::listForUserMonth($uid, $year, $month);
        $shares = MarketingEventShare::listForOwner($uid);
        $publicToken = MarketingEvent::getPublicTokenForUser($uid);
        $canShare = $this->canShare($user);

        $this->view('marketing_calendar/index', [
            'pageTitle' => 'Agenda de Marketing',
            'user' => $user,
            'events' => $events,
            'shares' => $shares,
            'publicToken' => $publicToken,
            'canShare' => $canShare,
            'year' => $year,
            'month' => $month,
        ]);
    }

    public function create(): void
    {
        $user = $this->requireLogin();
        $this->requireCalendarAccess($user);
        $uid = (int)$user['id'];

        $title = trim($_POST['title'] ?? '');
        $eventDate = trim($_POST['event_date'] ?? '');
        $eventType = trim($_POST['event_type'] ?? 'post');
        $status = trim($_POST['status'] ?? 'planejado');
        $responsible = trim($_POST['responsible'] ?? '');
        $color = trim($_POST['color'] ?? '#e53935');
        $notes = trim($_POST['notes'] ?? '');
        $links = $_POST['reference_links'] ?? [];
        if (is_array($links)) {
            $links = array_values(array_filter(array_map('trim', $links)));
        } else {
            $links = [];
        }

        if ($title === '' || $eventDate === '') {
            $this->json(['ok' => false, 'error' => 'Título e data são obrigatórios.'], 400);
        }

        $validTypes = ['post', 'story', 'reels', 'video', 'email', 'anuncio', 'outro'];
        if (!in_array($eventType, $validTypes, true)) {
            $eventType = 'post';
        }
        $validStatuses = ['planejado', 'produzido', 'postado'];
        if (!in_array($status, $validStatuses, true)) {
            $status = 'planejado';
        }

        $id = MarketingEvent::create([
            'owner_user_id' => $uid,
            'title' => $title,
            'event_date' => $eventDate,
            'event_type' => $eventType,
            'status' => $status,
            'responsible' => $responsible !== '' ? $responsible : null,
            'color' => $color,
            'notes' => $notes !== '' ? $notes : null,
            'reference_links' => !empty($links) ? json_encode($links) : null,
        ]);

        $this->json(['ok' => true, 'id' => $id]);
    }

    public function update(): void
    {
        $user = $this->requireLogin();
        $this->requireCalendarAccess($user);
        $uid = (int)$user['id'];

        $id = (int)($_POST['id'] ?? 0);
        $event = MarketingEvent::findAccessible($id, $uid);
        if (!$event) {
            $this->json(['ok' => false, 'error' => 'Evento não encontrado.'], 404);
        }

        $title = trim($_POST['title'] ?? '');
        $eventDate = trim($_POST['event_date'] ?? '');
        $eventType = trim($_POST['event_type'] ?? 'post');
        $status = trim($_POST['status'] ?? 'planejado');
        $responsible = trim($_POST['responsible'] ?? '');
        $color = trim($_POST['color'] ?? '#e53935');
        $notes = trim($_POST['notes'] ?? '');
        $links = $_POST['reference_links'] ?? [];
        if (is_array($links)) {
            $links = array_values(array_filter(array_map('trim', $links)));
        } else {
            $links = [];
        }

        if ($title === '' || $eventDate === '') {
            $this->json(['ok' => false, 'error' => 'Título e data são obrigatórios.'], 400);
        }

        $validTypes = ['post', 'story', 'reels', 'video', 'email', 'anuncio', 'outro'];
        if (!in_array($eventType, $validTypes, true)) { $eventType = 'post'; }
        $validStatuses = ['planejado', 'produzido', 'postado'];
        if (!in_array($status, $validStatuses, true)) { $status = 'planejado'; }

        MarketingEvent::updateById($id, [
            'title' => $title,
            'event_date' => $eventDate,
            'event_type' => $eventType,
            'status' => $status,
            'responsible' => $responsible !== '' ? $responsible : null,
            'color' => $color,
            'notes' => $notes !== '' ? $notes : null,
            'reference_links' => !empty($links) ? json_encode($links) : null,
        ]);

        $this->json(['ok' => true]);
    }

    public function delete(): void
    {
        $user = $this->requireLogin();
        $this->requireCalendarAccess($user);
        $uid = (int)$user['id'];

        $id = (int)($_POST['id'] ?? 0);
        $event = MarketingEvent::findById($id);
        if (!$event || (int)($event['owner_user_id'] ?? 0) !== $uid) {
            $this->json(['ok' => false, 'error' => 'Sem permissão.'], 403);
        }

        MarketingEvent::deleteById($id);
        $this->json(['ok' => true]);
    }

    public function getEvent(): void
    {
        $user = $this->requireLogin();
        $this->requireCalendarAccess($user);
        $uid = (int)$user['id'];

        $id = (int)($_GET['id'] ?? 0);
        $event = MarketingEvent::findAccessible($id, $uid);
        if (!$event) {
            $this->json(['ok' => false, 'error' => 'Evento não encontrado.'], 404);
        }

        $event['reference_links'] = !empty($event['reference_links']) ? json_decode($event['reference_links'], true) : [];
        $this->json(['ok' => true, 'event' => $event]);
    }

    public function publish(): void
    {
        $user = $this->requireLogin();
        $this->requireCalendarAccess($user);
        if (!$this->canShare($user)) {
            $this->json(['ok' => false, 'error' => 'Seu plano não permite compartilhar.'], 403);
        }
        $uid = (int)$user['id'];
        $publish = !empty($_POST['publish']);

        $token = null;
        if ($publish) {
            $token = bin2hex(random_bytes(24));
        }
        MarketingEvent::setPublished($uid, $publish, $token);

        $publicUrl = null;
        if ($publish && $token) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $publicUrl = $scheme . '://' . $host . '/agenda-marketing/publico?token=' . urlencode($token);
        }
        $this->json(['ok' => true, 'public_url' => $publicUrl]);
    }

    public function shareAdd(): void
    {
        $user = $this->requireLogin();
        $this->requireCalendarAccess($user);
        if (!$this->canShare($user)) {
            $this->json(['ok' => false, 'error' => 'Seu plano não permite compartilhar.'], 403);
        }
        $uid = (int)$user['id'];
        $email = trim((string)($_POST['email'] ?? ''));
        $role = trim((string)($_POST['role'] ?? 'view'));

        if ($email === '') {
            $this->json(['ok' => false, 'error' => 'Informe o e-mail.'], 400);
        }

        $target = User::findByEmail($email);
        if (!$target || empty($target['id'])) {
            $this->json(['ok' => false, 'error' => 'Usuário não encontrado.'], 404);
        }
        $targetId = (int)$target['id'];
        if ($targetId === $uid) {
            $this->json(['ok' => false, 'error' => 'Você já é o dono.'], 400);
        }

        MarketingEventShare::upsert($uid, $targetId, $role);
        $shares = MarketingEventShare::listForOwner($uid);
        $this->json(['ok' => true, 'shares' => $shares]);
    }

    public function shareRemove(): void
    {
        $user = $this->requireLogin();
        $this->requireCalendarAccess($user);
        $uid = (int)$user['id'];

        $targetId = (int)($_POST['user_id'] ?? 0);
        if ($targetId <= 0) {
            $this->json(['ok' => false, 'error' => 'Usuário inválido.'], 400);
        }

        MarketingEventShare::remove($uid, $targetId);
        $shares = MarketingEventShare::listForOwner($uid);
        $this->json(['ok' => true, 'shares' => $shares]);
    }

    public function publico(): void
    {
        $token = trim((string)($_GET['token'] ?? ''));
        if ($token === '') {
            http_response_code(404);
            echo 'Link inválido.';
            return;
        }

        $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
        if ($month < 1) { $month = 1; }
        if ($month > 12) { $month = 12; }

        $events = MarketingEvent::listPublicByToken($token, $year, $month);

        // Render standalone (no layout)
        $viewFile = __DIR__ . '/../Views/marketing_calendar/publico.php';
        $pageTitle = 'Agenda de Marketing';
        $data = [
            'events' => $events,
            'token' => $token,
            'year' => $year,
            'month' => $month,
        ];
        extract($data);
        include $viewFile;
    }

    public function generateApiKey(): void
    {
        $user = $this->requireLogin();
        $this->requireCalendarAccess($user);
        $uid = (int)$user['id'];

        $label = trim((string)($_POST['label'] ?? 'Integração'));
        $key = \App\Models\UserApiKey::generate($uid, $label);
        $this->json(['ok' => true, 'api_key' => $key]);
    }

    public function revokeApiKey(): void
    {
        $user = $this->requireLogin();
        $this->requireCalendarAccess($user);
        $uid = (int)$user['id'];

        $id = (int)($_POST['id'] ?? 0);
        \App\Models\UserApiKey::revoke($id, $uid);
        $this->json(['ok' => true]);
    }

    public function listApiKeys(): void
    {
        $user = $this->requireLogin();
        $this->requireCalendarAccess($user);
        $uid = (int)$user['id'];

        $keys = \App\Models\UserApiKey::listForUser($uid);
        // Mascarar as keys
        foreach ($keys as &$k) {
            $full = (string)($k['api_key'] ?? '');
            $k['api_key_masked'] = strlen($full) > 12 ? substr($full, 0, 8) . '...' . substr($full, -4) : $full;
            unset($k['api_key']);
        }
        $this->json(['ok' => true, 'keys' => $keys]);
    }

    public function eventsJson(): void
    {
        $user = $this->requireLogin();
        $this->requireCalendarAccess($user);
        $uid = (int)$user['id'];

        $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');

        $events = MarketingEvent::listForUserMonth($uid, $year, $month);
        foreach ($events as &$e) {
            $e['reference_links'] = !empty($e['reference_links']) ? json_decode($e['reference_links'], true) : [];
        }
        $this->json(['ok' => true, 'events' => $events]);
    }
}
