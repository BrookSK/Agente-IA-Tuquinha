<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;

class PlanController extends Controller
{
    public function index(): void
    {
        $plans = Plan::allActive();
        $currentPlan = null;
        $hasPaidActiveSubscription = false;

        // Se o usuário estiver logado, tenta descobrir o plano pela assinatura (igual à Minha Conta)
        $userId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
        if ($userId > 0) {
            $user = User::findById($userId);
            if ($user && !empty($user['email'])) {
                $subscription = Subscription::findLastByEmail($user['email']);
                if ($subscription && !empty($subscription['plan_id'])) {
                    $planFromSub = Plan::findById((int)$subscription['plan_id']);
                    if ($planFromSub) {
                        $currentPlan = $planFromSub;

                        // Considera como assinatura paga ativa se o plano não for free e a assinatura não estiver cancelada
                        $status = strtolower((string)($subscription['status'] ?? ''));
                        $slug = (string)($planFromSub['slug'] ?? '');
                        if ($slug !== 'free' && !in_array($status, ['canceled', 'expired'], true)) {
                            $hasPaidActiveSubscription = true;
                        }

                        // Mantém a session em sincronia para outras telas que usam plan_slug
                        if (!empty($currentPlan['slug'])) {
                            $_SESSION['plan_slug'] = $currentPlan['slug'];
                        }
                    }
                }
            }
        }

        // Se não encontrou plano via assinatura (usuário não logado ou sem assinatura), usa plan_slug da sessão / free
        if (!$currentPlan) {
            $currentPlan = Plan::findBySessionSlug($_SESSION['plan_slug'] ?? null);
            if (!$currentPlan) {
                $currentPlan = Plan::findBySlug('free');
                if ($currentPlan && !empty($currentPlan['slug'])) {
                    $_SESSION['plan_slug'] = $currentPlan['slug'];
                }
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
            'hasPaidActiveSubscription' => $hasPaidActiveSubscription,
        ]);
    }
}
