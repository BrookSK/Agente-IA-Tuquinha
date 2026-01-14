<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\KanbanBoard;
use App\Models\KanbanCard;
use App\Models\KanbanCardAttachment;
use App\Models\KanbanCardChecklistItem;
use App\Models\KanbanList;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\MediaStorageService;

class KanbanController extends Controller
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

    private function requireKanbanAccess(array $user): array
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

        if (empty($plan['allow_kanban'])) {
            if ($this->wantsJson()) {
                $this->json(['ok' => false, 'error' => 'Seu plano não permite o Kanban.'], 403);
            }
            header('Location: /planos');
            exit;
        }

        return ['plan' => $plan, 'subscription_active' => true];
    }

    private function loadBoardOrDeny(int $boardId, int $userId): array
    {
        $board = KanbanBoard::findOwnedById($boardId, $userId);
        if (!$board) {
            if ($this->wantsJson()) {
                $this->json(['ok' => false, 'error' => 'Quadro não encontrado.'], 404);
            }
            header('Location: /kanban');
            exit;
        }
        return $board;
    }

    private function loadCardContextOrDeny(int $cardId, int $userId): array
    {
        $card = KanbanCard::findById($cardId);
        if (!$card) {
            $this->json(['ok' => false, 'error' => 'Cartão não encontrado.'], 404);
        }

        $list = KanbanList::findById((int)($card['list_id'] ?? 0));
        if (!$list) {
            $this->json(['ok' => false, 'error' => 'Lista não encontrada.'], 404);
        }

        $board = KanbanBoard::findOwnedById((int)($list['board_id'] ?? 0), $userId);
        if (!$board) {
            $this->json(['ok' => false, 'error' => 'Sem acesso ao cartão.'], 403);
        }

        return ['card' => $card, 'list' => $list, 'board' => $board];
    }

    public function index(): void
    {
        $user = $this->requireLogin();
        $this->requireKanbanAccess($user);

        $uid = (int)$user['id'];
        $boards = KanbanBoard::listForUser($uid);

        $boardId = isset($_GET['board_id']) ? (int)$_GET['board_id'] : 0;
        $currentBoard = null;
        $lists = [];
        $cardsByList = [];

        if ($boardId > 0) {
            $currentBoard = $this->loadBoardOrDeny($boardId, $uid);
        } elseif (!empty($boards)) {
            $first = $boards[0] ?? null;
            if (is_array($first) && !empty($first['id'])) {
                $currentBoard = $this->loadBoardOrDeny((int)$first['id'], $uid);
                $boardId = (int)$currentBoard['id'];
            }
        }

        if ($currentBoard) {
            $lists = KanbanList::listForBoard((int)$currentBoard['id']);
            $cards = KanbanCard::listForBoard((int)$currentBoard['id']);
            foreach ($cards as $c) {
                $lid = (int)($c['list_id'] ?? 0);
                if (!isset($cardsByList[$lid])) {
                    $cardsByList[$lid] = [];
                }
                $cardsByList[$lid][] = $c;
            }
        }

        $this->view('kanban/index', [
            'pageTitle' => 'Kanban - Tuquinha',
            'user' => $user,
            'boards' => $boards,
            'currentBoard' => $currentBoard,
            'lists' => $lists,
            'cardsByList' => $cardsByList,
        ]);
    }

    public function createBoard(): void
    {
        $user = $this->requireLogin();
        $access = $this->requireKanbanAccess($user);

        if (empty($_SESSION['is_admin'])) {
            $plan = is_array($access) ? ($access['plan'] ?? null) : null;
            if (is_array($plan) && array_key_exists('kanban_boards_limit', $plan) && $plan['kanban_boards_limit'] !== null && $plan['kanban_boards_limit'] !== '') {
                $limit = (int)$plan['kanban_boards_limit'];
                $current = KanbanBoard::countForUser((int)$user['id']);
                if ($current >= $limit) {
                    $this->json([
                        'ok' => false,
                        'error' => 'Seu plano permite no máximo ' . $limit . ' quadros no Kanban.',
                    ], 403);
                }
            }
        }

        $title = trim($_POST['title'] ?? '');
        $id = KanbanBoard::create((int)$user['id'], $title);
        $this->json(['ok' => true, 'board_id' => $id]);
    }

    public function renameBoard(): void
    {
        $user = $this->requireLogin();
        $this->requireKanbanAccess($user);

        $boardId = (int)($_POST['board_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        if ($boardId <= 0) {
            $this->json(['ok' => false, 'error' => 'board_id inválido.'], 400);
        }
        $this->loadBoardOrDeny($boardId, (int)$user['id']);
        KanbanBoard::rename($boardId, (int)$user['id'], $title);
        $this->json(['ok' => true]);
    }

    public function deleteBoard(): void
    {
        $user = $this->requireLogin();
        $this->requireKanbanAccess($user);

        $boardId = (int)($_POST['board_id'] ?? 0);
        if ($boardId <= 0) {
            $this->json(['ok' => false, 'error' => 'board_id inválido.'], 400);
        }
        $this->loadBoardOrDeny($boardId, (int)$user['id']);
        KanbanBoard::delete($boardId, (int)$user['id']);
        $this->json(['ok' => true]);
    }

    public function createList(): void
    {
        $user = $this->requireLogin();
        $this->requireKanbanAccess($user);

        $boardId = (int)($_POST['board_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        if ($boardId <= 0) {
            $this->json(['ok' => false, 'error' => 'board_id inválido.'], 400);
        }
        $this->loadBoardOrDeny($boardId, (int)$user['id']);
        $id = KanbanList::create($boardId, $title);
        $this->json(['ok' => true, 'list_id' => $id]);
    }

    public function renameList(): void
    {
        $user = $this->requireLogin();
        $this->requireKanbanAccess($user);

        $listId = (int)($_POST['list_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        if ($listId <= 0) {
            $this->json(['ok' => false, 'error' => 'list_id inválido.'], 400);
        }

        $list = KanbanList::findById($listId);
        if (!$list) {
            $this->json(['ok' => false, 'error' => 'Lista não encontrada.'], 404);
        }

        $board = $this->loadBoardOrDeny((int)$list['board_id'], (int)$user['id']);
        KanbanList::rename($listId, $title);
        $this->json(['ok' => true, 'board_id' => (int)$board['id']]);
    }

    public function deleteList(): void
    {
        $user = $this->requireLogin();
        $this->requireKanbanAccess($user);

        $listId = (int)($_POST['list_id'] ?? 0);
        if ($listId <= 0) {
            $this->json(['ok' => false, 'error' => 'list_id inválido.'], 400);
        }

        $list = KanbanList::findById($listId);
        if (!$list) {
            $this->json(['ok' => false, 'error' => 'Lista não encontrada.'], 404);
        }

        $this->loadBoardOrDeny((int)$list['board_id'], (int)$user['id']);
        KanbanList::delete($listId);
        $this->json(['ok' => true]);
    }

    public function reorderLists(): void
    {
        $user = $this->requireLogin();
        $this->requireKanbanAccess($user);

        $boardId = (int)($_POST['board_id'] ?? 0);
        $orderJson = (string)($_POST['order'] ?? '');
        if ($boardId <= 0) {
            $this->json(['ok' => false, 'error' => 'board_id inválido.'], 400);
        }
        $this->loadBoardOrDeny($boardId, (int)$user['id']);

        $order = json_decode($orderJson, true);
        if (!is_array($order)) {
            $this->json(['ok' => false, 'error' => 'order inválido.'], 400);
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $pos = 1;
            foreach ($order as $listId) {
                $lid = (int)$listId;
                if ($lid <= 0) {
                    continue;
                }
                // garante que pertence ao board
                $list = KanbanList::findById($lid);
                if (!$list || (int)$list['board_id'] !== $boardId) {
                    continue;
                }
                KanbanList::setPosition($lid, $pos);
                $pos++;
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            $this->json(['ok' => false, 'error' => 'Falha ao reordenar listas.'], 500);
        }

        $this->json(['ok' => true]);
    }

    public function createCard(): void
    {
        $user = $this->requireLogin();
        $this->requireKanbanAccess($user);

        $listId = (int)($_POST['list_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = isset($_POST['description']) ? (string)$_POST['description'] : null;
        if ($listId <= 0) {
            $this->json(['ok' => false, 'error' => 'list_id inválido.'], 400);
        }

        $list = KanbanList::findById($listId);
        if (!$list) {
            $this->json(['ok' => false, 'error' => 'Lista não encontrada.'], 404);
        }
        $this->loadBoardOrDeny((int)$list['board_id'], (int)$user['id']);

        $id = KanbanCard::create($listId, $title, $description);

        $dueDate = isset($_POST['due_date']) ? trim((string)$_POST['due_date']) : '';
        KanbanCard::setDueDate($id, $dueDate !== '' ? $dueDate : null);

        $card = KanbanCard::findById($id);
        $this->json(['ok' => true, 'card' => $card]);
    }

    public function updateCard(): void
    {
        $user = $this->requireLogin();
        $this->requireKanbanAccess($user);

        $cardId = (int)($_POST['card_id'] ?? 0);
        $title = (string)($_POST['title'] ?? '');
        $description = isset($_POST['description']) ? (string)$_POST['description'] : null;
        if ($cardId <= 0) {
            $this->json(['ok' => false, 'error' => 'card_id inválido.'], 400);
        }

        $card = KanbanCard::findById($cardId);
        if (!$card) {
            $this->json(['ok' => false, 'error' => 'Cartão não encontrado.'], 404);
        }

        $list = KanbanList::findById((int)$card['list_id']);
        if (!$list) {
            $this->json(['ok' => false, 'error' => 'Lista não encontrada.'], 404);
        }
        $this->loadBoardOrDeny((int)$list['board_id'], (int)$user['id']);

        KanbanCard::update($cardId, $title, $description);

        $dueDate = isset($_POST['due_date']) ? trim((string)$_POST['due_date']) : '';
        KanbanCard::setDueDate($cardId, $dueDate !== '' ? $dueDate : null);

        $this->json(['ok' => true]);
    }

    public function setCardCover(): void
    {
        $user = $this->requireLogin();
        $this->requireKanbanAccess($user);

        $cardId = (int)($_POST['card_id'] ?? 0);
        $attachmentIdRaw = (int)($_POST['attachment_id'] ?? 0);
        if ($cardId <= 0) {
            $this->json(['ok' => false, 'error' => 'card_id inválido.'], 400);
        }

        $ctx = $this->loadCardContextOrDeny($cardId, (int)$user['id']);
        $attachmentId = null;
        $coverUrl = null;
        $coverMime = null;
        if ($attachmentIdRaw > 0) {
            $att = KanbanCardAttachment::findById($attachmentIdRaw);
            if (!$att || (int)($att['card_id'] ?? 0) !== $cardId) {
                $this->json(['ok' => false, 'error' => 'Anexo inválido.'], 404);
            }
            $mime = strtolower(trim((string)($att['mime_type'] ?? '')));
            if ($mime === '' || strpos($mime, 'image/') !== 0) {
                $this->json(['ok' => false, 'error' => 'A capa deve ser uma imagem.'], 422);
            }
            $attachmentId = (int)$att['id'];
            $coverUrl = (string)($att['url'] ?? '');
            $coverMime = (string)($att['mime_type'] ?? '');
        }

        KanbanCard::setCoverAttachmentId($cardId, $attachmentId);
        $this->json([
            'ok' => true,
            'cover_attachment_id' => $attachmentId,
            'cover_url' => $coverUrl,
            'cover_mime_type' => $coverMime,
        ]);
    }

    public function toggleCardDone(): void
    {
        $user = $this->requireLogin();
        $this->requireKanbanAccess($user);

        $cardId = (int)($_POST['card_id'] ?? 0);
        if ($cardId <= 0) {
            $this->json(['ok' => false, 'error' => 'card_id inválido.'], 400);
        }

        $ctx = $this->loadCardContextOrDeny($cardId, (int)$user['id']);
        $card = is_array($ctx) ? ($ctx['card'] ?? null) : null;
        if (!$card) {
            $this->json(['ok' => false, 'error' => 'Cartão não encontrado.'], 404);
        }

        $current = !empty($card['is_done']) && (string)$card['is_done'] !== '0';
        $new = !$current;
        KanbanCard::setDone($cardId, $new);
        $this->json(['ok' => true, 'is_done' => $new ? 1 : 0]);
    }

    public function listChecklist(): void
    {
        $user = $this->requireLogin();
        $this->requireKanbanAccess($user);

        $cardId = (int)($_POST['card_id'] ?? 0);
        if ($cardId <= 0) {
            $this->json(['ok' => false, 'error' => 'card_id inválido.'], 400);
        }
        $this->loadCardContextOrDeny($cardId, (int)$user['id']);
        $items = KanbanCardChecklistItem::listForCard($cardId);
        $this->json(['ok' => true, 'items' => $items]);
    }

    public function addChecklistItem(): void
    {
        $user = $this->requireLogin();
        $this->requireKanbanAccess($user);

        $cardId = (int)($_POST['card_id'] ?? 0);
        $content = trim((string)($_POST['content'] ?? ''));
        if ($cardId <= 0) {
            $this->json(['ok' => false, 'error' => 'card_id inválido.'], 400);
        }
        $this->loadCardContextOrDeny($cardId, (int)$user['id']);
        if ($content === '') {
            $this->json(['ok' => false, 'error' => 'Informe o texto do item.'], 422);
        }

        $id = KanbanCardChecklistItem::create($cardId, $content);
        $item = KanbanCardChecklistItem::findById($id);
        $this->json(['ok' => true, 'item' => $item]);
    }

    public function toggleChecklistItem(): void
    {
        $user = $this->requireLogin();
        $this->requireKanbanAccess($user);

        $itemId = (int)($_POST['item_id'] ?? 0);
        $done = !empty($_POST['done']) ? 1 : 0;
        if ($itemId <= 0) {
            $this->json(['ok' => false, 'error' => 'item_id inválido.'], 400);
        }
        $item = KanbanCardChecklistItem::findById($itemId);
        if (!$item) {
            $this->json(['ok' => false, 'error' => 'Item não encontrado.'], 404);
        }
        $cardId = (int)($item['card_id'] ?? 0);
        if ($cardId <= 0) {
            $this->json(['ok' => false, 'error' => 'Item inválido.'], 400);
        }
        $this->loadCardContextOrDeny($cardId, (int)$user['id']);

        KanbanCardChecklistItem::toggleDone($itemId, $done === 1);
        $this->json(['ok' => true]);
    }

    public function deleteChecklistItem(): void
    {
        $user = $this->requireLogin();
        $this->requireKanbanAccess($user);

        $itemId = (int)($_POST['item_id'] ?? 0);
        if ($itemId <= 0) {
            $this->json(['ok' => false, 'error' => 'item_id inválido.'], 400);
        }
        $item = KanbanCardChecklistItem::findById($itemId);
        if (!$item) {
            $this->json(['ok' => false, 'error' => 'Item não encontrado.'], 404);
        }
        $cardId = (int)($item['card_id'] ?? 0);
        $this->loadCardContextOrDeny($cardId, (int)$user['id']);

        KanbanCardChecklistItem::deleteById($itemId);
        $this->json(['ok' => true]);
    }

    public function deleteCard(): void
    {
        $user = $this->requireLogin();
        $this->requireKanbanAccess($user);

        $cardId = (int)($_POST['card_id'] ?? 0);
        if ($cardId <= 0) {
            $this->json(['ok' => false, 'error' => 'card_id inválido.'], 400);
        }

        $card = KanbanCard::findById($cardId);
        if (!$card) {
            $this->json(['ok' => false, 'error' => 'Cartão não encontrado.'], 404);
        }

        $list = KanbanList::findById((int)$card['list_id']);
        if (!$list) {
            $this->json(['ok' => false, 'error' => 'Lista não encontrada.'], 404);
        }
        $this->loadBoardOrDeny((int)$list['board_id'], (int)$user['id']);

        KanbanCard::delete($cardId);
        $this->json(['ok' => true]);
    }

    public function listCardAttachments(): void
    {
        $user = $this->requireLogin();
        $this->requireKanbanAccess($user);

        $cardId = (int)($_POST['card_id'] ?? 0);
        if ($cardId <= 0) {
            $this->json(['ok' => false, 'error' => 'card_id inválido.'], 400);
        }

        $this->loadCardContextOrDeny($cardId, (int)$user['id']);
        $attachments = KanbanCardAttachment::listForCard($cardId);
        $this->json(['ok' => true, 'attachments' => $attachments]);
    }

    public function uploadCardAttachment(): void
    {
        $user = $this->requireLogin();
        $this->requireKanbanAccess($user);

        $cardId = (int)($_POST['card_id'] ?? 0);
        if ($cardId <= 0) {
            $this->json(['ok' => false, 'error' => 'card_id inválido.'], 400);
        }
        $this->loadCardContextOrDeny($cardId, (int)$user['id']);

        if (empty($_FILES['file']) || !is_array($_FILES['file'])) {
            $this->json(['ok' => false, 'error' => 'Envie um arquivo.'], 422);
        }

        $err = (int)($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err !== UPLOAD_ERR_OK) {
            $this->json(['ok' => false, 'error' => 'Erro no upload.'], 422);
        }

        $tmp = (string)($_FILES['file']['tmp_name'] ?? '');
        $originalName = (string)($_FILES['file']['name'] ?? 'arquivo');
        $type = (string)($_FILES['file']['type'] ?? '');
        $size = (int)($_FILES['file']['size'] ?? 0);
        if (!is_file($tmp) || $size <= 0) {
            $this->json(['ok' => false, 'error' => 'Arquivo inválido.'], 422);
        }

        $remoteUrl = MediaStorageService::uploadFile($tmp, $originalName, $type);
        if (!is_string($remoteUrl) || trim($remoteUrl) === '') {
            $this->json(['ok' => false, 'error' => 'Não foi possível enviar a mídia para o servidor.'], 500);
        }

        $id = KanbanCardAttachment::create($cardId, $remoteUrl, $originalName, $type !== '' ? $type : null, $size);
        $row = KanbanCardAttachment::findById($id);
        $this->json(['ok' => true, 'attachment' => $row]);
    }

    public function deleteCardAttachment(): void
    {
        $user = $this->requireLogin();
        $this->requireKanbanAccess($user);

        $attachmentId = (int)($_POST['attachment_id'] ?? 0);
        if ($attachmentId <= 0) {
            $this->json(['ok' => false, 'error' => 'attachment_id inválido.'], 400);
        }

        $att = KanbanCardAttachment::findById($attachmentId);
        if (!$att) {
            $this->json(['ok' => false, 'error' => 'Anexo não encontrado.'], 404);
        }

        $cardId = (int)($att['card_id'] ?? 0);
        if ($cardId <= 0) {
            $this->json(['ok' => false, 'error' => 'Anexo inválido.'], 400);
        }

        $this->loadCardContextOrDeny($cardId, (int)$user['id']);
        KanbanCardAttachment::deleteById($attachmentId);
        $this->json(['ok' => true]);
    }

    public function moveCard(): void
    {
        $user = $this->requireLogin();
        $this->requireKanbanAccess($user);

        $cardId = (int)($_POST['card_id'] ?? 0);
        $toListId = (int)($_POST['to_list_id'] ?? 0);
        $position = (int)($_POST['position'] ?? 0);

        if ($cardId <= 0 || $toListId <= 0) {
            $this->json(['ok' => false, 'error' => 'Parâmetros inválidos.'], 400);
        }
        if ($position <= 0) {
            $position = 1;
        }

        $card = KanbanCard::findById($cardId);
        if (!$card) {
            $this->json(['ok' => false, 'error' => 'Cartão não encontrado.'], 404);
        }

        $toList = KanbanList::findById($toListId);
        if (!$toList) {
            $this->json(['ok' => false, 'error' => 'Lista destino não encontrada.'], 404);
        }

        $board = $this->loadBoardOrDeny((int)$toList['board_id'], (int)$user['id']);

        // garante que o card original também pertence ao mesmo board
        $fromList = KanbanList::findById((int)$card['list_id']);
        if (!$fromList || (int)$fromList['board_id'] !== (int)$board['id']) {
            $this->json(['ok' => false, 'error' => 'Cartão não pertence a este quadro.'], 403);
        }

        KanbanCard::move($cardId, $toListId, $position);
        $this->json(['ok' => true]);
    }

    public function reorderCards(): void
    {
        $user = $this->requireLogin();
        $this->requireKanbanAccess($user);

        $listId = (int)($_POST['list_id'] ?? 0);
        $orderJson = (string)($_POST['order'] ?? '');
        if ($listId <= 0) {
            $this->json(['ok' => false, 'error' => 'list_id inválido.'], 400);
        }

        $list = KanbanList::findById($listId);
        if (!$list) {
            $this->json(['ok' => false, 'error' => 'Lista não encontrada.'], 404);
        }
        $this->loadBoardOrDeny((int)$list['board_id'], (int)$user['id']);

        $order = json_decode($orderJson, true);
        if (!is_array($order)) {
            $this->json(['ok' => false, 'error' => 'order inválido.'], 400);
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $pos = 1;
            foreach ($order as $cardId) {
                $cid = (int)$cardId;
                if ($cid <= 0) {
                    continue;
                }
                $card = KanbanCard::findById($cid);
                if (!$card || (int)$card['list_id'] !== $listId) {
                    continue;
                }
                KanbanCard::setPosition($cid, $pos);
                $pos++;
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            $this->json(['ok' => false, 'error' => 'Falha ao reordenar cartões.'], 500);
        }

        $this->json(['ok' => true]);
    }

    public function sync(): void
    {
        $user = $this->requireLogin();
        $this->requireKanbanAccess($user);

        $payloadRaw = (string)($_POST['payload'] ?? '');
        $payload = json_decode($payloadRaw, true);
        if (!is_array($payload)) {
            $this->json(['ok' => false, 'error' => 'payload inválido.'], 400);
        }

        $boardId = (int)($payload['board_id'] ?? 0);
        if ($boardId <= 0) {
            $this->json(['ok' => false, 'error' => 'board_id inválido.'], 400);
        }
        $this->loadBoardOrDeny($boardId, (int)$user['id']);

        $listOrder = $payload['list_order'] ?? null;
        $cardsByList = $payload['cards_by_list'] ?? null;

        if ($listOrder !== null && !is_array($listOrder)) {
            $this->json(['ok' => false, 'error' => 'list_order inválido.'], 400);
        }
        if ($cardsByList !== null && !is_array($cardsByList)) {
            $this->json(['ok' => false, 'error' => 'cards_by_list inválido.'], 400);
        }

        $pdo = Database::getConnection();

        // cache: listas válidas do board
        $stmtLists = $pdo->prepare('SELECT id FROM kanban_lists WHERE board_id = :bid');
        $stmtLists->execute(['bid' => $boardId]);
        $validListIds = [];
        foreach (($stmtLists->fetchAll() ?: []) as $r) {
            $validListIds[(int)$r['id']] = true;
        }

        // cache: cartões válidos do board
        $stmtCards = $pdo->prepare('SELECT c.id, c.list_id FROM kanban_cards c INNER JOIN kanban_lists l ON l.id = c.list_id WHERE l.board_id = :bid');
        $stmtCards->execute(['bid' => $boardId]);
        $validCards = [];
        foreach (($stmtCards->fetchAll() ?: []) as $r) {
            $validCards[(int)$r['id']] = (int)$r['list_id'];
        }

        $pdo->beginTransaction();
        try {
            if (is_array($listOrder)) {
                $pos = 1;
                foreach ($listOrder as $lidRaw) {
                    $lid = (int)$lidRaw;
                    if ($lid <= 0 || empty($validListIds[$lid])) {
                        continue;
                    }
                    KanbanList::setPosition($lid, $pos);
                    $pos++;
                }
            }

            if (is_array($cardsByList)) {
                $update = $pdo->prepare('UPDATE kanban_cards SET list_id = :lid, position = :p WHERE id = :id');

                foreach ($cardsByList as $listIdKey => $cardIds) {
                    $lid = (int)$listIdKey;
                    if ($lid <= 0 || empty($validListIds[$lid])) {
                        continue;
                    }
                    if (!is_array($cardIds)) {
                        continue;
                    }

                    $pos = 1;
                    foreach ($cardIds as $cidRaw) {
                        $cid = (int)$cidRaw;
                        if ($cid <= 0 || !array_key_exists($cid, $validCards)) {
                            continue;
                        }

                        $update->execute([
                            'lid' => $lid,
                            'p' => $pos,
                            'id' => $cid,
                        ]);
                        $pos++;
                    }
                }
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            $this->json(['ok' => false, 'error' => 'Falha ao sincronizar.'], 500);
        }

        $this->json(['ok' => true]);
    }
}
