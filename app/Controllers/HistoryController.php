<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Conversation;
use App\Models\Plan;

class HistoryController extends Controller
{
    public function index(): void
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $currentPlan = Plan::findBySessionSlug($_SESSION['plan_slug'] ?? null);
        if (!$currentPlan || ($currentPlan['slug'] ?? null) === 'free') {
            header('Location: /planos');
            exit;
        }

        $sessionId = session_id();
        $term = trim($_GET['q'] ?? '');

        $conversations = Conversation::searchBySession($sessionId, $term);

        $this->view('chat/history', [
            'pageTitle' => 'Histórico de conversas',
            'conversations' => $conversations,
            'term' => $term,
        ]);
    }

    public function rename(): void
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $currentPlan = Plan::findBySessionSlug($_SESSION['plan_slug'] ?? null);
        if (!$currentPlan || ($currentPlan['slug'] ?? null) === 'free') {
            header('Location: /planos');
            exit;
        }

        $sessionId = session_id();
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $title = trim((string)($_POST['title'] ?? ''));

        if ($id > 0) {
            $conv = Conversation::findByIdAndSession($id, $sessionId);
            if ($conv) {
                if ($title === '') {
                    $title = 'Chat com o Tuquinha';
                }
                Conversation::updateTitle($id, $title);
            }
        }

        $q = isset($_GET['q']) ? (string)$_GET['q'] : '';
        $redirect = '/historico';
        if ($q !== '') {
            $redirect .= '?q=' . urlencode($q);
        }
        header('Location: ' . $redirect);
        exit;
    }
}
