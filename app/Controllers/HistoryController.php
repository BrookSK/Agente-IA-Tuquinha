<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Conversation;
use App\Models\Plan;
use App\Core\Database;
use App\Models\Setting;

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

        // Dias de retenção configuráveis: por plano, com fallback para valor global
        $defaultRetention = (int)Setting::get('chat_history_retention_days', '90');
        if ($defaultRetention <= 0) {
            $defaultRetention = 90;
        }

        $planRetention = isset($currentPlan['history_retention_days']) ? (int)$currentPlan['history_retention_days'] : 0;
        $retentionDays = $planRetention > 0 ? $planRetention : $defaultRetention;

        // Política de retenção: remove conversas mais antigas que X dias desta sessão
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM conversations WHERE session_id = :session_id AND created_at < (NOW() - INTERVAL :days DAY)');
        $stmt->bindValue('session_id', $sessionId);
        $stmt->bindValue('days', $retentionDays, \PDO::PARAM_INT);
        $stmt->execute();

        $conversations = Conversation::searchBySession($sessionId, $term);

        $this->view('chat/history', [
            'pageTitle' => 'Histórico de conversas',
            'conversations' => $conversations,
            'term' => $term,
            'retentionDays' => $retentionDays,
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
