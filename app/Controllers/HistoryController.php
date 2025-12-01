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
}
