<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Plan;
use App\Models\Personality;
use App\Services\AsaasClient;
use App\Services\MailService;

class AccountController extends Controller
{
    private function requireLogin(): ?array
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $user = User::findById((int)$_SESSION['user_id']);
        if (!$user) {
            unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email']);
            header('Location: /login');
            exit;
        }

        return $user;
    }

    public function index(): void
    {
        $user = $this->requireLogin();

        $subscription = null;
        $plan = null;
        $cardLast4 = null;
        $subscriptionStart = null;
        $subscriptionNext = null;

        if (!empty($user['email'])) {
            $subscription = Subscription::findLastByEmail($user['email']);
            if ($subscription) {
                $plan = Plan::findById((int)$subscription['plan_id']);

                $subscriptionStart = $subscription['created_at'] ?? null;
                $subscriptionNext = $subscription['started_at'] ?? null;

                if (!empty($subscription['asaas_subscription_id'])) {
                    try {
                        $asaas = new AsaasClient();
                        $asaasSub = $asaas->getSubscription($subscription['asaas_subscription_id']);
                        if (!empty($asaasSub['creditCard']['creditCardNumber'])) {
                            $num = (string)$asaasSub['creditCard']['creditCardNumber'];
                            $cardLast4 = substr($num, -4);
                        }
                        if (!empty($asaasSub['nextDueDate'])) {
                            $subscriptionNext = $asaasSub['nextDueDate'];
                        }
                    } catch (\Throwable $e) {
                        // Falha ao consultar detalhes da assinatura no gateway não deve quebrar a tela
                    }
                }
            }
        }

        $tokenBalance = \App\Models\User::getTokenBalance((int)$user['id']);

        $personalities = Personality::allActive();

        // Dados do programa de indicação (Indique e ganhe)
        $referralData = null;
        if ($plan && $subscription && !empty($plan['referral_enabled'])) {
            $status = strtolower((string)($subscription['status'] ?? ''));
            $minDays = isset($plan['referral_min_active_days']) ? (int)$plan['referral_min_active_days'] : 0;
            $currentDays = 0;

            if (!empty($subscription['created_at'])) {
                try {
                    $now = new \DateTimeImmutable('now');
                    $createdAt = new \DateTimeImmutable($subscription['created_at']);
                    $currentDays = (int)$now->diff($createdAt)->days;
                } catch (\Throwable $e) {
                    $currentDays = 0;
                }
            }

            $hasMinDays = $currentDays >= $minDays;
            $canRefer = ($status === 'active') && $hasMinDays;

            $link = '';
            $referralCode = '';
            if ($canRefer) {
                $referralCode = User::getOrCreateReferralCode((int)$user['id']);
                if ($referralCode !== '') {
                    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
                    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                    $baseUrl = $scheme . $host;
                    $link = $baseUrl . '/registrar?ref=' . urlencode($referralCode) . '&plan=' . urlencode((string)$plan['slug']);
                }
            }

            $referralData = [
                'enabled' => true,
                'canRefer' => $canRefer,
                'minDays' => $minDays,
                'currentDays' => $currentDays,
                'planName' => $plan['name'] ?? '',
                'planSlug' => $plan['slug'] ?? '',
                'link' => $link,
                'referralCode' => $referralCode,
                'friendTokens' => (int)($plan['referral_friend_tokens'] ?? 0),
                'referrerTokens' => (int)($plan['referral_referrer_tokens'] ?? 0),
                'freeDays' => (int)($plan['referral_free_days'] ?? 0),
            ];
        }

        $this->view('account/index', [
            'pageTitle' => 'Minha conta',
            'user' => $user,
            'subscription' => $subscription,
            'plan' => $plan,
            'cardLast4' => $cardLast4,
            'subscriptionStart' => $subscriptionStart,
            'subscriptionNext' => $subscriptionNext,
            'tokenBalance' => $tokenBalance,
            'personalities' => $personalities,
            'referralData' => $referralData,
            'error' => null,
            'success' => null,
        ]);
    }

    public function updateProfile(): void
    {
        $user = $this->requireLogin();

        $name = trim($_POST['name'] ?? '');
        $preferredName = trim($_POST['preferred_name'] ?? '');
        $globalMemory = trim($_POST['global_memory'] ?? '');
        $globalInstructions = trim($_POST['global_instructions'] ?? '');
        if ($name === '') {
            $this->reloadWithMessages($user, 'Nome não pode ficar em branco.', null);
            return;
        }

        // Mantém a personalidade padrão já configurada (se houver) sem alterá-la aqui
        $currentDefaultPersonaId = isset($user['default_persona_id']) ? (int)$user['default_persona_id'] : null;

        User::updateProfile((int)$user['id'], $name, $preferredName, $globalMemory, $globalInstructions, $currentDefaultPersonaId);

        // Campos extras de cobrança (dados usados no checkout)
        $billingCpf = trim($_POST['billing_cpf'] ?? '');
        $billingBirthdate = trim($_POST['billing_birthdate'] ?? '');
        $billingPhone = trim($_POST['billing_phone'] ?? '');
        $billingPostalCode = trim($_POST['billing_postal_code'] ?? '');
        $billingAddress = trim($_POST['billing_address'] ?? '');
        $billingAddressNumber = trim($_POST['billing_address_number'] ?? '');
        $billingComplement = trim($_POST['billing_complement'] ?? '');
        $billingProvince = trim($_POST['billing_province'] ?? '');
        $billingCity = trim($_POST['billing_city'] ?? '');
        $billingState = trim($_POST['billing_state'] ?? '');

        User::updateBillingData(
            (int)$user['id'],
            $billingCpf,
            $billingBirthdate,
            $billingPhone,
            $billingPostalCode,
            $billingAddress,
            $billingAddressNumber,
            $billingComplement,
            $billingProvince,
            $billingCity,
            $billingState
        );
        $_SESSION['user_name'] = $name;
        $user = User::findById((int)$user['id']) ?? $user;
        $this->reloadWithMessages($user, null, 'Dados atualizados com sucesso.');
    }

    public function updatePassword(): void
    {
        $user = $this->requireLogin();

        $current = (string)($_POST['current_password'] ?? '');
        $new = (string)($_POST['new_password'] ?? '');
        $confirm = (string)($_POST['new_password_confirmation'] ?? '');

        if ($current === '' || $new === '' || $confirm === '') {
            $this->reloadWithMessages($user, 'Preencha todos os campos de senha.', null);
            return;
        }

        if (!password_verify($current, $user['password_hash'])) {
            $this->reloadWithMessages($user, 'Senha atual incorreta.', null);
            return;
        }

        if ($new !== $confirm) {
            $this->reloadWithMessages($user, 'A confirmação da nova senha não confere.', null);
            return;
        }

        $hash = password_hash($new, PASSWORD_BCRYPT);
        User::updatePassword((int)$user['id'], $hash);

        $user = User::findById((int)$user['id']) ?? $user;
        $this->reloadWithMessages($user, null, 'Senha alterada com sucesso.');
    }

    private function reloadWithMessages(array $user, ?string $error, ?string $success): void
    {
        $subscription = null;
        $plan = null;

        if (!empty($user['email'])) {
            $subscription = Subscription::findLastByEmail($user['email']);
            if ($subscription) {
                $plan = Plan::findById((int)$subscription['plan_id']);

                $subscriptionStart = $subscription['created_at'] ?? null;
                $subscriptionNext = $subscription['started_at'] ?? null;

                if (!empty($subscription['asaas_subscription_id'])) {
                    try {
                        $asaas = new AsaasClient();
                        $asaasSub = $asaas->getSubscription($subscription['asaas_subscription_id']);
                        if (!empty($asaasSub['creditCard']['creditCardNumber'])) {
                            $num = (string)$asaasSub['creditCard']['creditCardNumber'];
                            $cardLast4 = substr($num, -4);
                        }
                        if (!empty($asaasSub['nextDueDate'])) {
                            $subscriptionNext = $asaasSub['nextDueDate'];
                        }
                    } catch (\Throwable $e) {
                        // ignora erro de consulta ao gateway
                    }
                }
            }
        }

        $tokenBalance = \App\Models\User::getTokenBalance((int)$user['id']);

        $personalities = Personality::allActive();

        // Dados do programa de indicação (Indique e ganhe) também neste fluxo
        $referralData = null;
        if ($plan && $subscription && !empty($plan['referral_enabled'])) {
            $status = strtolower((string)($subscription['status'] ?? ''));
            $minDays = isset($plan['referral_min_active_days']) ? (int)$plan['referral_min_active_days'] : 0;
            $currentDays = 0;

            if (!empty($subscription['created_at'])) {
                try {
                    $now = new \DateTimeImmutable('now');
                    $createdAt = new \DateTimeImmutable($subscription['created_at']);
                    $currentDays = (int)$now->diff($createdAt)->days;
                } catch (\Throwable $e) {
                    $currentDays = 0;
                }
            }

            $hasMinDays = $currentDays >= $minDays;
            $canRefer = ($status === 'active') && $hasMinDays;

            $link = '';
            $referralCode = '';
            if ($canRefer) {
                $referralCode = User::getOrCreateReferralCode((int)$user['id']);
                if ($referralCode !== '') {
                    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
                    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                    $baseUrl = $scheme . $host;
                    $link = $baseUrl . '/registrar?ref=' . urlencode($referralCode) . '&plan=' . urlencode((string)$plan['slug']);
                }
            }

            $referralData = [
                'enabled' => true,
                'canRefer' => $canRefer,
                'minDays' => $minDays,
                'currentDays' => $currentDays,
                'planName' => $plan['name'] ?? '',
                'planSlug' => $plan['slug'] ?? '',
                'link' => $link,
                'referralCode' => $referralCode,
                'friendTokens' => (int)($plan['referral_friend_tokens'] ?? 0),
                'referrerTokens' => (int)($plan['referral_referrer_tokens'] ?? 0),
                'freeDays' => (int)($plan['referral_free_days'] ?? 0),
            ];
        }

        $this->view('account/index', [
            'pageTitle' => 'Minha conta',
            'user' => $user,
            'subscription' => $subscription,
            'plan' => $plan,
            'cardLast4' => $cardLast4,
            'subscriptionStart' => $subscriptionStart,
            'subscriptionNext' => $subscriptionNext,
            'tokenBalance' => $tokenBalance,
            'personalities' => $personalities,
            'referralData' => $referralData,
            'error' => $error,
            'success' => $success,
        ]);
    }

    public function cancelSubscription(): void
    {
        $user = $this->requireLogin();

        if (empty($user['email'])) {
            $this->reloadWithMessages($user, 'Não encontrei uma assinatura vinculada ao seu e-mail.', null);
            return;
        }

        $subscription = Subscription::findLastByEmail($user['email']);
        if (!$subscription || empty($subscription['asaas_subscription_id'])) {
            $this->reloadWithMessages($user, 'Nenhuma assinatura ativa encontrada para cancelamento.', null);
            return;
        }

        try {
            $asaas = new AsaasClient();
            $validUntil = null;

            try {
                $asaasSub = $asaas->getSubscription($subscription['asaas_subscription_id']);
                if (!empty($asaasSub['nextDueDate'])) {
                    $validUntil = $asaasSub['nextDueDate'];
                }
            } catch (\Throwable $e) {
                // se não conseguir ler os dados, segue apenas com o cancelamento
            }

            $asaas->cancelSubscription($subscription['asaas_subscription_id']);

            $now = date('Y-m-d H:i:s');
            Subscription::updateStatusAndCanceledAt((int)$subscription['id'], 'canceled', $now);

            $successMsg = 'Sua assinatura foi cancelada.';
            if ($validUntil) {
                $successMsg .= ' O acesso atual permanece até ' . date('d/m/Y', strtotime($validUntil)) . ', depois disso não haverá novas cobranças.';
            } else {
                $successMsg .= ' Você ainda pode ter acesso até o fim do ciclo já pago, dependendo das regras do meio de pagamento.';
            }

            try {
                $plan = Plan::findById((int)$subscription['plan_id']);
                $planName = $plan['name'] ?? 'seu plano atual';
                $safeName = htmlspecialchars($user['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safePlan = htmlspecialchars($planName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $subject = 'Confirmação de cancelamento da sua assinatura do Tuquinha';

                $benefitsLines = [];
                if (!empty($plan['benefits'])) {
                    $benefitsLines = preg_split('/\r?\n/', (string)$plan['benefits']);
                }

                $benefitsHtml = '';
                if ($benefitsLines) {
                    $benefitsHtml .= '<ul style="font-size:13px; color:#b0b0b0; padding-left:18px; margin:0 0 10px 0;">';
                    foreach ($benefitsLines as $line) {
                        $line = trim($line);
                        if ($line === '') continue;
                        $benefitsHtml .= '<li>' . htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>';
                    }
                    $benefitsHtml .= '</ul>';
                }

                $validUntilText = '';
                if ($validUntil) {
                    $validUntilText = 'Sua assinatura continua válida até <strong>' . htmlspecialchars(date('d/m/Y', strtotime($validUntil))) . '</strong>. Depois dessa data, nenhuma nova cobrança será feita.';
                } else {
                    $validUntilText = 'Dependendo das regras do meio de pagamento, você ainda pode ter acesso ao plano até o fim do ciclo já pago.';
                }

                $body = <<<HTML
<html>
<body style="margin:0; padding:0; background:#050509; font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color:#f5f5f5;">
  <div style="width:100%; padding:24px 0;">
    <div style="max-width:520px; margin:0 auto; background:#111118; border-radius:16px; border:1px solid #272727; padding:18px 20px;">
      <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
        <div style="width:32px; height:32px; line-height:32px; border-radius:50%; background:radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%); text-align:center; font-weight:700; font-size:16px; color:#050509;">T</div>
        <div>
          <div style="font-weight:700; font-size:15px;">Resenha 2.0 - Tuquinha</div>
          <div style="font-size:11px; color:#b0b0b0;">Branding vivo na veia</div>
        </div>
      </div>

      <p style="font-size:14px; margin:0 0 10px 0;">Oi, {$safeName} 👋</p>
      <p style="font-size:14px; margin:0 0 10px 0;">Confirmamos o cancelamento da sua assinatura do plano <strong>{$safePlan}</strong> no Tuquinha.</p>

      <p style="font-size:13px; margin:0 0 8px 0;">Com o cancelamento, você deixa de contar com os benefícios deste plano, como por exemplo:</p>
      {$benefitsHtml}

      <p style="font-size:13px; margin:0 0 8px 0;">{$validUntilText}</p>

      <p style="font-size:12px; margin:12px 0 0 0; color:#b0b0b0;">Se isso foi um engano ou se quiser voltar em algum momento, é só assinar novamente um dos planos disponíveis dentro do próprio Tuquinha.</p>
    </div>
  </div>
</body>
</html>
HTML;

                MailService::send($user['email'], $user['name'], $subject, $body);
            } catch (\Throwable $mailEx) {
                // falha ao enviar e-mail de cancelamento não deve impedir o fluxo
            }

            $this->reloadWithMessages($user, null, $successMsg);
        } catch (\Throwable $e) {
            $this->reloadWithMessages($user, 'Não consegui cancelar a assinatura agora. Tente novamente em alguns minutos ou fale com o suporte.', null);
        }
    }
}
