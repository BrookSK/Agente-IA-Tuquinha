<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Conversation;

class HistoryController extends Controller
{
    public function index(): void
    {
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
