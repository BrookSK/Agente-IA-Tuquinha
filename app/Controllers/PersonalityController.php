<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Personality;
use App\Models\Plan;
use App\Models\Conversation;
use App\Models\ProjectMember;

class PersonalityController extends Controller
{
    public function index(): void
    {
        // Usuários deslogados não podem selecionar personalidade: vão direto para o chat padrão
        $userId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
        if ($userId <= 0 && empty($_SESSION['is_admin'])) {
            header('Location: /chat?new=1');
            exit;
        }

        $currentPlan = null;
        if (!empty($_SESSION['is_admin'])) {
            $currentPlan = Plan::findTopActive();
        } else {
            $currentPlan = Plan::findBySessionSlug($_SESSION['plan_slug'] ?? null);
            if (!$currentPlan) {
                $currentPlan = Plan::findDefaultForUsers() ?: Plan::findBySlug('free');
                if ($currentPlan && !empty($currentPlan['slug'])) {
                    $_SESSION['plan_slug'] = $currentPlan['slug'];
                }
            }
        }

        $planAllowsPersonalities = !empty($_SESSION['is_admin']) || (!empty($currentPlan['allow_personalities']));
        if (!$planAllowsPersonalities) {
            header('Location: /chat?new=1');
            exit;
        }

        if (!Personality::hasAnyUsableForUsers()) {
            header('Location: /chat?new=1');
            exit;
        }

        $conversationId = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;
        if ($conversationId > 0) {
            // Seleção de personalidade por chat: só permite se o usuário atual tem acesso à conversa
            if ($userId <= 0 && empty($_SESSION['is_admin'])) {
                header('Location: /chat?new=1');
                exit;
            }

            $row = null;
            if (!empty($_SESSION['is_admin'])) {
                // Admin pode abrir o seletor sem ser dono, mas ainda assim evita apontar para conversas inexistentes
                $row = Conversation::findByIdAndSession($conversationId, session_id())
                    ?: Conversation::findByIdForUser($conversationId, $userId);
            } else {
                $row = Conversation::findByIdForUser($conversationId, $userId);
            }

            if (!$row) {
                header('Location: /chat');
                exit;
            }

            $projectId = isset($row['project_id']) ? (int)$row['project_id'] : 0;
            if ($projectId > 0 && $userId > 0 && !ProjectMember::canRead($projectId, $userId)) {
                header('Location: /projetos');
                exit;
            }
        }

        $planId = isset($currentPlan['id']) ? (int)$currentPlan['id'] : 0;
        if (!empty($_SESSION['is_admin']) || $planId <= 0) {
            $personalities = Personality::allVisibleForUsers();
        } else {
            $personalities = Personality::allVisibleForUsersByPlan($planId);
        }

        $this->view('personalities/index', [
            'pageTitle' => 'Escolha a personalidade do Tuquinha',
            'personalities' => $personalities,
            'conversationId' => $conversationId,
        ]);
    }
}
