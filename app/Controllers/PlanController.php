<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Plan;

class PlanController extends Controller
{
    public function index(): void
    {
        $plans = Plan::allActive();

        $currentPlan = Plan::findBySessionSlug($_SESSION['plan_slug'] ?? null);
        if (!$currentPlan) {
            $currentPlan = Plan::findBySlug('free');
            if ($currentPlan) {
                $_SESSION['plan_slug'] = $currentPlan['slug'];
            }
        }

        $this->view('plans/index', [
            'pageTitle' => 'Planos - Tuquinha',
            'plans' => $plans,
            'currentPlan' => $currentPlan,
        ]);
    }
}
