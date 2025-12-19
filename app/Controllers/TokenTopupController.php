<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use App\Models\TokenTopup;
use App\Models\Plan;
use App\Services\AsaasClient;

class TokenTopupController extends Controller
{
    private function requireLogin(): array
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $user = User::findById((int)$_SESSION['user_id']);
        if (!$user) {
            header('Location: /login');
            exit;
        }

        return $user;
    }

    public function show(): void
    {
        $user = $this->requireLogin();

        $priceGlobal = Setting::get('extra_token_price_per_1k_global', '');
        $pricePer1k = $priceGlobal !== '' ? (float)$priceGlobal : 0.0;

        $subscription = null;
        $currentPlan = null;
        $hasLimit = true;

        if (!empty($user['email'])) {
            $subscription = Subscription::findLastByEmail($user['email']);

            if ($subscription && !empty($subscription['plan_id'])) {
                $currentPlan = Plan::findById((int)$subscription['plan_id']);
                if ($currentPlan && isset($currentPlan['monthly_token_limit'])) {
                    $limit = (int)$currentPlan['monthly_token_limit'];
                    $hasLimit = $limit > 0;
                }
            }
        }

        $tokenBalance = User::getTokenBalance((int)$user['id']);

        $this->view('tokens/comprar', [
            'pageTitle'    => 'Comprar tokens extras',
            'user'         => $user,
            'subscription' => $subscription,
            'currentPlan'  => $currentPlan,
            'hasLimit'     => $hasLimit,
            'pricePer1k'   => $pricePer1k,
            'tokenBalance' => $tokenBalance,
            'error'        => null,
        ]);
    }

    public function create(): void
    {
        $user = $this->requireLogin();

        $priceGlobal = Setting::get('extra_token_price_per_1k_global', '');
        $pricePer1k = $priceGlobal !== '' ? (float)$priceGlobal : 0.0;
        $minAmountReais = 25.0; // mínimo em R$

        if ($pricePer1k <= 0) {
            $this->view('tokens/comprar', [
                'pageTitle'    => 'Comprar tokens extras',
                'user'         => $user,
                'subscription' => null,
                'currentPlan'  => null,
                'hasLimit'     => true,
                'pricePer1k'   => 0.0,
                'tokenBalance' => User::getTokenBalance((int)$user['id']),
                'error'        => 'O preço global por 1.000 tokens extras ainda não foi configurado pelo administrador.',
            ]);
            return;
        }

        if (empty($user['email'])) {
            $this->view('tokens/comprar', [
                'pageTitle'    => 'Comprar tokens extras',
                'user'         => $user,
                'subscription' => null,
                'currentPlan'  => null,
                'hasLimit'     => true,
                'pricePer1k'   => $pricePer1k,
                'tokenBalance' => User::getTokenBalance((int)$user['id']),
                'error'        => 'Não encontrei uma assinatura ativa vinculada ao seu e-mail. Assine um plano primeiro para depois comprar tokens extras.',
            ]);
            return;
        }

        $subscription = Subscription::findLastByEmail($user['email']);
        $currentPlan = null;
        $hasLimit = true;

        if ($subscription && !empty($subscription['plan_id'])) {
            $currentPlan = Plan::findById((int)$subscription['plan_id']);
            if ($currentPlan && isset($currentPlan['monthly_token_limit'])) {
                $limit = (int)$currentPlan['monthly_token_limit'];
                $hasLimit = $limit > 0;
            }
        }

        if (
            !$subscription
            || empty($subscription['asaas_subscription_id'])
            || empty($subscription['asaas_customer_id'])
        ) {
            $this->view('tokens/comprar', [
                'pageTitle'    => 'Comprar tokens extras',
                'user'         => $user,
                'subscription' => $subscription,
                'currentPlan'  => $currentPlan,
                'hasLimit'     => $hasLimit,
                'pricePer1k'   => $pricePer1k,
                'tokenBalance' => User::getTokenBalance((int)$user['id']),
                'error'        => 'Não encontrei um cadastro válido no gateway de pagamento. Conclua uma assinatura primeiro.',
            ]);
            return;
        }

        // Plano sem limite: não deixa comprar
        if (!$hasLimit) {
            $this->view('tokens/comprar', [
                'pageTitle'    => 'Comprar tokens extras',
                'user'         => $user,
                'subscription' => $subscription,
                'currentPlan'  => $currentPlan,
                'hasLimit'     => $hasLimit,
                'pricePer1k'   => $pricePer1k,
                'tokenBalance' => User::getTokenBalance((int)$user['id']),
                'error'        => 'Seu plano atual não possui limite mensal de tokens, então não é necessário (nem possível) comprar tokens extras.',
            ]);
            return;
        }

        // Quantidade de tokens desejada pelo usuário (será arredondada para blocos de 1.000)
        $rawTokens = isset($_POST['tokens']) ? (int)$_POST['tokens'] : 0;
        if ($rawTokens <= 0) {
            $rawTokens = 1000;
        }

        $blocks = (int)ceil($rawTokens / 1000);

        // garante mínimo em reais
        $minBlocks = (int)ceil($minAmountReais / $pricePer1k);
        if ($blocks < $minBlocks) {
            $blocks = $minBlocks;
        }

        // tipo de cobrança: PIX, BOLETO ou CARTÃO vindo do formulário
        $billingType = $_POST['billing_type'] ?? 'PIX';
        if (!in_array($billingType, ['PIX', 'BOLETO', 'CREDIT_CARD'], true)) {
            $billingType = 'PIX';
        }

        $tokens = 1000 * $blocks;
        $amount = $pricePer1k * $blocks; // em reais
        $amountCents = (int)round($amount * 100);

        if ($amountCents <= 0 || $tokens <= 0) {
            $this->view('tokens/comprar', [
                'pageTitle'    => 'Comprar tokens extras',
                'user'         => $user,
                'subscription' => $subscription,
                'currentPlan'  => $currentPlan,
                'hasLimit'     => $hasLimit,
                'pricePer1k'   => $pricePer1k,
                'tokenBalance' => User::getTokenBalance((int)$user['id']),
                'error'        => 'Valor inválido para a compra de tokens extras.',
            ]);
            return;
        }

        $topupId = TokenTopup::create([
            'user_id'         => (int)$user['id'],
            'tokens'          => $tokens,
            'amount_cents'    => $amountCents,
            'asaas_payment_id' => null,
            'status'          => 'pending',
            'paid_at'         => null,
        ]);

        try {
            $asaas = new AsaasClient();

            // Define dueDate conforme o tipo de cobrança exigido pelo Asaas
            $dueDate = date('Y-m-d'); // hoje por padrão
            if ($billingType === 'BOLETO') {
                $dueDate = date('Y-m-d', strtotime('+3 days'));
            } elseif ($billingType === 'PIX') {
                $dueDate = date('Y-m-d', strtotime('+1 day'));
            }

            $payload = [
                'customer'          => $subscription['asaas_customer_id'],
                'billingType'       => $billingType,
                'value'             => $amountCents / 100,
                'description'       => 'Crédito de ' . $tokens . ' tokens extras no Tuquinha',
                'externalReference' => 'token_topup:' . $topupId,
                'dueDate'           => $dueDate,
            ];

            $resp = $asaas->createPayment($payload);
            $paymentId = $resp['id'] ?? null;

            if ($paymentId) {
                TokenTopup::attachPaymentId($topupId, (string)$paymentId);
            }

            $redirectUrl = $resp['invoiceUrl'] ?? null;
            if (!$redirectUrl && !empty($resp['bankSlipUrl'])) {
                $redirectUrl = $resp['bankSlipUrl'];
            }

            if ($redirectUrl) {
                // tela intermediária: abre o link em nova aba e mantém o Tuquinha aberto
                $this->view('tokens/abrir_pagamento', [
                    'pageTitle'    => 'Pagamento de tokens extras',
                    'redirectUrl'  => $redirectUrl,
                    'billingType'  => $billingType,
                    'amountReais'  => $amountCents / 100,
                    'tokens'       => $tokens,
                ]);
                return;
            }

            // fallback: volta para conta com mensagem se não veio URL de pagamento
            $_SESSION['topup_success'] = 'Pedido de compra de tokens extras criado. Assim que o pagamento for confirmado, seu saldo será atualizado.';
            header('Location: /conta');
            exit;
        } catch (\Throwable $e) {
            $this->view('tokens/comprar', [
                'pageTitle'    => 'Comprar tokens extras',
                'user'         => $user,
                'subscription' => $subscription,
                'currentPlan'  => $currentPlan,
                'hasLimit'     => $hasLimit,
                'pricePer1k'   => $pricePer1k,
                'tokenBalance' => User::getTokenBalance((int)$user['id']),
                'error'        => 'Não consegui criar a cobrança para compra de tokens extras. Tente novamente em alguns minutos ou fale com o suporte.',
            ]);
        }
    }

    public function history(): void
    {
        $user = $this->requireLogin();

        $topups = TokenTopup::allByUserId((int)$user['id']);

        $this->view('tokens/historico', [
            'pageTitle' => 'Histórico de tokens extras',
            'user'      => $user,
            'topups'    => $topups,
        ]);
    }
}
