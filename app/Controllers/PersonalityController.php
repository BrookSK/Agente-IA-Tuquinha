<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Personality;
use App\Models\Plan;

class PersonalityController extends Controller
{
    public function index(): void
    {
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
