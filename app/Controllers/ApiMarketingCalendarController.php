<?php

namespace App\Controllers;

use App\Models\MarketingEvent;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\UserApiKey;

/**
 * API REST para integração externa da Agenda de Marketing.
 *
 * Autenticação: Header "Authorization: Bearer tuq_..."
 *
 * Endpoints:
 *   GET  /api/marketing-calendar/events?year=YYYY&month=MM
 *   GET  /api/marketing-calendar/events/{id}
 *   POST /api/marketing-calendar/events          (criar)
 *   POST /api/marketing-calendar/events/update    (atualizar)
 *   POST /api/marketing-calendar/events/delete    (excluir)
 */
class ApiMarketingCalendarController
{
    private function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Autentica via Bearer token e retorna [user_id, api_key_row].
     * Encerra com 401 se inválido.
     */
    private function authenticate(): array
    {
        // 1. Header padrão
        $header = trim((string)($_SERVER['HTTP_AUTHORIZATION'] ?? ''));

        // 2. Fallback: Apache CGI/FastCGI repassa via REDIRECT_
        if ($header === '') {
            $header = trim((string)($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? ''));
        }

        // 3. Fallback: query parameter ?api_token=tuq_...
        if (($header === '' || stripos($header, 'Bearer ') !== 0) && isset($_GET['api_token'])) {
            $header = 'Bearer ' . trim((string)$_GET['api_token']);
        }

        if ($header === '' || stripos($header, 'Bearer ') !== 0) {
            $this->json(['ok' => false, 'error' => 'Token de autenticação ausente. Envie o header Authorization: Bearer <api_key> ou o parâmetro ?api_token=<api_key>.'], 401);
        }

        $token = trim(substr($header, 7));
        $apiKey = UserApiKey::findByKey($token);
        if (!$apiKey) {
            $this->json(['ok' => false, 'error' => 'Token inválido ou revogado.'], 401);
        }

        UserApiKey::touchLastUsed((int)$apiKey['id']);

        return [(int)$apiKey['user_id'], $apiKey];
    }

    /**
     * Verifica se o plano do usuário permite a agenda de marketing.
     */
    private function requireCalendarAccess(int $userId): void
    {
        // Admins passam direto
        if (!empty($_SESSION['is_admin'])) {
            return;
        }

        $pdo = \App\Core\Database::getConnection();
        $stmt = $pdo->prepare('SELECT email FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$user) {
            $this->json(['ok' => false, 'error' => 'Usuário não encontrado.'], 404);
        }

        $email = trim((string)($user['email'] ?? ''));
        $subscription = Subscription::findLastByEmail($email);
        if (!$subscription || empty($subscription['plan_id'])) {
            $this->json(['ok' => false, 'error' => 'Sem assinatura ativa.'], 403);
        }

        $status = strtolower((string)($subscription['status'] ?? ''));
        if (in_array($status, ['canceled', 'expired'], true)) {
            $this->json(['ok' => false, 'error' => 'Assinatura inativa.'], 403);
        }

        $plan = Plan::findById((int)$subscription['plan_id']);
        if (!$plan || empty($plan['allow_marketing_calendar'])) {
            $this->json(['ok' => false, 'error' => 'Seu plano não permite a Agenda de Marketing.'], 403);
        }
    }

    /**
     * GET /api/marketing-calendar/events?year=YYYY&month=MM
     *
     * Retorna todos os eventos do mês para o usuário autenticado.
     */
    public function list(): void
    {
        [$userId] = $this->authenticate();
        $this->requireCalendarAccess($userId);

        $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
        if ($month < 1) { $month = 1; }
        if ($month > 12) { $month = 12; }

        $events = MarketingEvent::listForUserMonth($userId, $year, $month);
        foreach ($events as &$e) {
            $e['reference_links'] = !empty($e['reference_links']) ? json_decode($e['reference_links'], true) : [];
        }

        $this->json(['ok' => true, 'year' => $year, 'month' => $month, 'events' => $events]);
    }

    /**
     * GET /api/marketing-calendar/events/show?id=ID
     *
     * Retorna um evento específico.
     */
    public function show(): void
    {
        [$userId] = $this->authenticate();
        $this->requireCalendarAccess($userId);

        $id = (int)($_GET['id'] ?? 0);
        $event = MarketingEvent::findAccessible($id, $userId);
        if (!$event) {
            $this->json(['ok' => false, 'error' => 'Evento não encontrado.'], 404);
        }

        $event['reference_links'] = !empty($event['reference_links']) ? json_decode($event['reference_links'], true) : [];
        $this->json(['ok' => true, 'event' => $event]);
    }

    /**
     * POST /api/marketing-calendar/events
     *
     * Cria um novo evento. Aceita JSON no body.
     *
     * Body JSON:
     * {
     *   "title": "Post de lançamento",
     *   "event_date": "2026-04-20",
     *   "event_type": "post",        // post|story|reels|video|email|anuncio|outro
     *   "status": "planejado",        // planejado|produzido|postado
     *   "responsible": "João",
     *   "color": "#e53935",
     *   "notes": "Ideias...",
     *   "reference_links": ["https://..."]
     * }
     */
    public function create(): void
    {
        [$userId] = $this->authenticate();
        $this->requireCalendarAccess($userId);

        $input = $this->getJsonInput();

        $title = trim((string)($input['title'] ?? ''));
        $eventDate = trim((string)($input['event_date'] ?? ''));
        if ($title === '' || $eventDate === '') {
            $this->json(['ok' => false, 'error' => 'title e event_date são obrigatórios.'], 400);
        }

        $eventType = trim((string)($input['event_type'] ?? 'post'));
        $status = trim((string)($input['status'] ?? 'planejado'));
        $responsible = trim((string)($input['responsible'] ?? ''));
        $color = trim((string)($input['color'] ?? '#e53935'));
        $notes = trim((string)($input['notes'] ?? ''));
        $links = isset($input['reference_links']) && is_array($input['reference_links'])
            ? array_values(array_filter(array_map('trim', $input['reference_links'])))
            : [];

        $validTypes = ['post', 'story', 'reels', 'video', 'email', 'anuncio', 'outro'];
        if (!in_array($eventType, $validTypes, true)) { $eventType = 'post'; }
        $validStatuses = ['planejado', 'produzido', 'postado'];
        if (!in_array($status, $validStatuses, true)) { $status = 'planejado'; }

        $id = MarketingEvent::create([
            'owner_user_id' => $userId,
            'title' => $title,
            'event_date' => $eventDate,
            'event_type' => $eventType,
            'status' => $status,
            'responsible' => $responsible !== '' ? $responsible : null,
            'color' => $color,
            'notes' => $notes !== '' ? $notes : null,
            'reference_links' => !empty($links) ? json_encode($links) : null,
        ]);

        $event = MarketingEvent::findById($id);
        $event['reference_links'] = !empty($event['reference_links']) ? json_decode($event['reference_links'], true) : [];

        $this->json(['ok' => true, 'event' => $event], 201);
    }

    /**
     * POST /api/marketing-calendar/events/update
     *
     * Atualiza um evento existente. Aceita JSON no body.
     *
     * Body JSON:
     * {
     *   "id": 123,
     *   "title": "Novo título",
     *   "event_date": "2026-04-21",
     *   ...
     * }
     */
    public function update(): void
    {
        [$userId] = $this->authenticate();
        $this->requireCalendarAccess($userId);

        $input = $this->getJsonInput();

        $id = (int)($input['id'] ?? 0);
        $event = MarketingEvent::findAccessible($id, $userId);
        if (!$event) {
            $this->json(['ok' => false, 'error' => 'Evento não encontrado.'], 404);
        }

        $title = trim((string)($input['title'] ?? $event['title']));
        $eventDate = trim((string)($input['event_date'] ?? $event['event_date']));
        $eventType = trim((string)($input['event_type'] ?? $event['event_type']));
        $status = trim((string)($input['status'] ?? $event['status']));
        $responsible = array_key_exists('responsible', $input) ? trim((string)($input['responsible'] ?? '')) : ($event['responsible'] ?? '');
        $color = trim((string)($input['color'] ?? $event['color']));
        $notes = array_key_exists('notes', $input) ? trim((string)($input['notes'] ?? '')) : ($event['notes'] ?? '');
        $links = isset($input['reference_links']) && is_array($input['reference_links'])
            ? array_values(array_filter(array_map('trim', $input['reference_links'])))
            : (!empty($event['reference_links']) ? json_decode($event['reference_links'], true) : []);

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

        $updated = MarketingEvent::findById($id);
        $updated['reference_links'] = !empty($updated['reference_links']) ? json_decode($updated['reference_links'], true) : [];

        $this->json(['ok' => true, 'event' => $updated]);
    }

    /**
     * POST /api/marketing-calendar/events/delete
     *
     * Exclui um evento. Aceita JSON no body.
     *
     * Body JSON: { "id": 123 }
     */
    public function delete(): void
    {
        [$userId] = $this->authenticate();
        $this->requireCalendarAccess($userId);

        $input = $this->getJsonInput();
        $id = (int)($input['id'] ?? 0);

        $event = MarketingEvent::findById($id);
        if (!$event || (int)($event['owner_user_id'] ?? 0) !== $userId) {
            $this->json(['ok' => false, 'error' => 'Sem permissão ou evento não encontrado.'], 403);
        }

        MarketingEvent::deleteById($id);
        $this->json(['ok' => true]);
    }

    private function getJsonInput(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
