<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Subscription;
use App\Models\Plan;
use App\Models\User;
use App\Models\TokenTransaction;

class AsaasWebhookController extends Controller
{
    public function handle(): void
    {
        $raw = file_get_contents('php://input') ?: '';
        $data = json_decode($raw, true);

        if (!is_array($data) || empty($data['event']) || empty($data['payment']['subscription'])) {
            http_response_code(400);
            echo 'invalid payload';
            return;
        }

        $event = (string)$data['event'];
        $asaasSubscriptionId = (string)$data['payment']['subscription'];

        // Considera eventos de confirmação de pagamento/renovação
        $isPaidEvent = in_array($event, [
            'PAYMENT_RECEIVED',
            'PAYMENT_CONFIRMED',
        ], true);

        if (!$isPaidEvent) {
            http_response_code(200);
            echo 'ignored';
            return;
        }

        $subscription = Subscription::findByAsaasId($asaasSubscriptionId);
        if (!$subscription) {
            http_response_code(200);
            echo 'subscription not found';
            return;
        }

        $plan = Plan::findById((int)$subscription['plan_id']);
        if (!$plan) {
            http_response_code(200);
            echo 'plan not found';
            return;
        }

        $monthlyLimit = isset($plan['monthly_token_limit']) ? (int)$plan['monthly_token_limit'] : 0;
        if ($monthlyLimit < 0) {
            $monthlyLimit = 0;
        }

        // Localiza o usuário pelo e-mail da assinatura
        $user = User::findByEmail($subscription['customer_email']);
        if (!$user) {
            http_response_code(200);
            echo 'user not found';
            return;
        }

        $userId = (int)$user['id'];

        // Reseta saldo de tokens para o limite do plano
        User::resetTokenBalanceForPlan($userId, $monthlyLimit);

        if ($monthlyLimit > 0) {
            TokenTransaction::create([
                'user_id' => $userId,
                'amount' => $monthlyLimit,
                'reason' => 'plan_monthly_reset',
                'meta' => json_encode([
                    'plan_id' => (int)$subscription['plan_id'],
                    'asaas_subscription_id' => $asaasSubscriptionId,
                    'event' => $event,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
        }

        http_response_code(200);
        echo 'ok';
    }
}
