<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Personality;
use App\Models\Plan;

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

        $personalities = Personality::allActive();

        $this->view('personalities/index', [
            'pageTitle' => 'Escolha a personalidade do Tuquinha',
            'personalities' => $personalities,
        ]);
    }
}
