<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Plan;
use App\Models\Setting;

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

        $defaultRetention = (int)Setting::get('chat_history_retention_days', '90');
        if ($defaultRetention <= 0) {
            $defaultRetention = 90;
        }

        $planRetention = isset($currentPlan['history_retention_days']) ? (int)$currentPlan['history_retention_days'] : 0;
        $retentionDays = $planRetention > 0 ? $planRetention : $defaultRetention;

        $this->view('plans/index', [
            'pageTitle' => 'Planos - Tuquinha',
            'plans' => $plans,
            'currentPlan' => $currentPlan,
            'retentionDays' => $retentionDays,
        ]);
    }
}
