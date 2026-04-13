<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserReferral;
use App\Services\AsaasClient;
use App\Services\MailService;
use App\Models\AsaasConfig;

class CheckoutController extends Controller
{
    public function show(): void
    {
        $slug = $_GET['plan'] ?? '';
        $plan = $slug ? Plan::findBySlug($slug) : null;

        if (!$plan) {
            http_response_code(404);
            echo 'Plano não encontrado';
            return;
        }

        // Exige que o usuário esteja logado antes de assinar um plano
        if (empty($_SESSION['user_id'])) {
            $_SESSION['pending_plan_slug'] = $plan['slug'];
            header('Location: /login');
            exit;
        }

        $currentUser = null;
        if (!empty($_SESSION['user_id'])) {
            $currentUser = User::findById((int)$_SESSION['user_id']);
        }

        $savedCustomer = $_SESSION['checkout_customer'] ?? null;

        // Passo 1: dados pessoais/endereço
        $this->view('checkout/step1', [
            'pageTitle' => 'Checkout - ' . $plan['name'],
            'plan' => $plan,
            'checkoutPlan' => $plan,
            'error' => null,
            'currentUser' => $currentUser,
            'savedCustomer' => $savedCustomer,
        ]);
    }

    public function process(): void
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $slug = $_POST['plan_slug'] ?? '';
        $plan = $slug ? Plan::findBySlug($slug) : null;

        if (!$plan) {
            http_response_code(404);
            echo 'Plano não encontrado';
            return;
        }

        $loggedUserId = (int)($_SESSION['user_id'] ?? 0);
        $pendingReferral = null;
        $referralFreeDays = 0;
        $requiresCardNow = true;
        $nextDueDate = null;
        $cardVerification = [
            'attempted' => false,
            'paymentId' => null,
            'value' => null,
            'refunded' => false,
        ];
        if ($loggedUserId > 0 && !empty($plan['referral_enabled'])) {
            try {
                $pendingReferral = UserReferral::findPendingForUserAndPlan($loggedUserId, (int)$plan['id']);
                if ($pendingReferral && isset($plan['referral_free_days'])) {
                    $tmpFree = (int)$plan['referral_free_days'];
                    if ($tmpFree > 0) {
                        $referralFreeDays = $tmpFree;
                    }
                }
                if ($pendingReferral && $referralFreeDays > 0 && empty($plan['referral_require_card'])) {
                    $requiresCardNow = false;
                }
            } catch (\Throwable $e) {
                error_log('CheckoutController::process erro ao verificar indicação pendente: ' . $e->getMessage());
            }
        }

        $step = (int)($_POST['step'] ?? 1);

        if ($step === 1) {
            // Validação de dados pessoais/endereço
            $required = ['name', 'email', 'cpf', 'birthdate', 'postal_code', 'address', 'address_number', 'province', 'city', 'state'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    $this->view('checkout/step1', [
                        'pageTitle' => 'Checkout - ' . $plan['name'],
                        'checkoutPlan' => $plan,
                        'plan' => $plan,
                        'error' => 'Por favor, preencha todos os campos obrigatórios.',
                    ]);
                    return;
                }
            }

            $customerForSession = [
                'name' => trim($_POST['name']),
                'email' => trim($_POST['email']),
                'cpf' => $_POST['cpf'],
                'cpfCnpj' => preg_replace('/\D+/', '', $_POST['cpf']),
                'phone' => $_POST['phone'] ?? '',
                'postal_code' => $_POST['postal_code'],
                'postalCode' => preg_replace('/\D+/', '', $_POST['postal_code']),
                'address' => trim($_POST['address']),
                'address_number' => trim($_POST['address_number']),
                'complement' => $_POST['complement'] ?? '',
                'province' => trim($_POST['province']),
                'city' => trim($_POST['city']),
                'state' => trim($_POST['state']),
                'birthdate' => $_POST['birthdate'],
            ];

            $_SESSION['checkout_customer'] = $customerForSession;

            // Atualiza também o cadastro do usuário com esses dados de cobrança
            $userId = (int)$_SESSION['user_id'];
            User::updateBillingData(
                $userId,
                $customerForSession['cpf'],
                $customerForSession['birthdate'],
                $customerForSession['phone'],
                $customerForSession['postal_code'],
                $customerForSession['address'],
                $customerForSession['address_number'],
                $customerForSession['complement'],
                $customerForSession['province'],
                $customerForSession['city'],
                $customerForSession['state']
            );

            $this->view('checkout/show', [
                'pageTitle' => 'Checkout - ' . $plan['name'],
                'plan' => $plan,
                'checkoutPlan' => $plan,
                'customer' => $customerForSession,
                'birthdate' => $customerForSession['birthdate'],
                'requiresCardNow' => $requiresCardNow,
                'error' => null,
            ]);
            return;
        }

        // Passo 2: dados do cartão, usando dados do cliente da sessão
        $sessionCustomer = $_SESSION['checkout_customer'] ?? null;
        if (!$sessionCustomer) {
            // sessão perdida, volta para passo 1
            $this->view('checkout/step1', [
                'pageTitle' => 'Checkout - ' . $plan['name'],
                'plan' => $plan,
                'error' => 'Sua sessão expirou. Preencha novamente seus dados para continuar.',
            ]);
            return;
        }

        $creditCard = null;
        if ($requiresCardNow) {
            $requiredCard = ['card_number', 'card_holder', 'card_exp_month', 'card_exp_year', 'card_cvv'];
            foreach ($requiredCard as $field) {
                if (empty($_POST[$field])) {
                    $this->view('checkout/show', [
                        'pageTitle' => 'Checkout - ' . $plan['name'],
                        'plan' => $plan,
                        'customer' => $sessionCustomer,
                        'birthdate' => $sessionCustomer['birthdate'] ?? '',
                        'requiresCardNow' => $requiresCardNow,
                        'error' => 'Por favor, preencha todos os dados do cartão.',
                    ]);
                    return;
                }
            }

            $creditCard = [
                'holderName' => trim($_POST['card_holder']),
                'number' => preg_replace('/\s+/', '', $_POST['card_number']),
                'expiryMonth' => (int)$_POST['card_exp_month'],
                'expiryYear' => (int)$_POST['card_exp_year'],
                'ccv' => trim($_POST['card_cvv']),
            ];
        }

        $customer = [
            'name' => $sessionCustomer['name'],
            'email' => $sessionCustomer['email'],
            'cpfCnpj' => $sessionCustomer['cpfCnpj'],
            'phone' => $sessionCustomer['phone'],
            'postalCode' => $sessionCustomer['postalCode'],
            'address' => $sessionCustomer['address'],
            'addressNumber' => $sessionCustomer['address_number'],
            'complement' => $sessionCustomer['complement'],
            'province' => $sessionCustomer['province'],
            'city' => $sessionCustomer['city'],
            'state' => $sessionCustomer['state'],
        ];

        $birthdateInput = $sessionCustomer['birthdate'] ?? '';
        $birthdate = $birthdateInput;
        $birthdateForAsaas = $birthdateInput;
        if ($birthdateInput !== '') {
            try {
                $dt = new \DateTime($birthdateInput);
                // Asaas espera formato dd/MM/yyyy
                $birthdateForAsaas = $dt->format('d/m/Y');
            } catch (\Throwable $e) {
                // mantém valor original se algo der errado
                $birthdateForAsaas = $birthdateInput;
            }
        }

        // Define o ciclo de cobrança no Asaas com base no sufixo do slug do plano
        $planSlug = (string)($plan['slug'] ?? '');
        $asaasCycle = 'MONTHLY';
        if (substr($planSlug, -7) === '-mensal') {
            $asaasCycle = 'MONTHLY';
        } elseif (substr($planSlug, -11) === '-semestral') {
            $asaasCycle = 'SEMIANNUAL';
        } elseif (substr($planSlug, -6) === '-anual') {
            $asaasCycle = 'YEARLY';
        }

        $subscriptionPayload = [
            'billingType' => $requiresCardNow ? 'CREDIT_CARD' : 'BOLETO',
            'value' => $plan['price_cents'] / 100,
            'cycle' => $asaasCycle,
            'description' => $plan['name'],
        ];

        if ($requiresCardNow && $creditCard !== null) {
            $subscriptionPayload['creditCard'] = $creditCard;
            $subscriptionPayload['creditCardHolderInfo'] = [
                'name' => $customer['name'],
                'email' => $customer['email'],
                'cpfCnpj' => $customer['cpfCnpj'],
                'phone' => $customer['phone'],
                'postalCode' => $customer['postalCode'],
                'address' => $customer['address'],
                'addressNumber' => $customer['addressNumber'],
                'complement' => $customer['complement'],
                'province' => $customer['province'],
                'city' => $customer['city'],
                'state' => $customer['state'],
                'birthDate' => $birthdateForAsaas,
            ];
        }

        // Aplica dias grátis do plano para o indicado, empurrando o primeiro vencimento
        if ($pendingReferral && $referralFreeDays > 0) {
            try {
                $nextDue = (new \DateTimeImmutable('now'))
                    ->modify('+' . $referralFreeDays . ' days')
                    ->format('Y-m-d');
                $subscriptionPayload['nextDueDate'] = $nextDue;
                $nextDueDate = $nextDue;
            } catch (\Throwable $e) {
                // Se der erro ao calcular data, segue sem alterar o nextDueDate
                error_log('CheckoutController::process erro ao calcular nextDueDate de indicação: ' . $e->getMessage());
            }
        }

        // Guarda payloads em sessão para debug posterior
        $_SESSION['asaas_debug_customer'] = $customer;
        $_SESSION['asaas_debug_subscription'] = $subscriptionPayload;

        try {
            $asaas = new AsaasClient();
            $customerResp = $asaas->createOrUpdateCustomer($customer);
            $subscriptionPayload['customer'] = $customerResp['id'] ?? null;

            if (empty($subscriptionPayload['customer'])) {
                throw new \RuntimeException('Falha ao criar cliente no Asaas.');
            }

            $subResp = $asaas->createSubscription($subscriptionPayload);

            $subData = [
                'plan_id' => $plan['id'],
                'customer_name' => $customer['name'],
                'customer_email' => $customer['email'],
                'customer_cpf' => $customer['cpfCnpj'],
                'customer_phone' => $customer['phone'],
                'customer_postal_code' => $customer['postalCode'],
                'customer_address' => $customer['address'],
                'customer_address_number' => $customer['addressNumber'],
                'customer_complement' => $customer['complement'],
                'customer_province' => $customer['province'],
                'customer_city' => $customer['city'],
                'customer_state' => $customer['state'],
                'asaas_customer_id' => $customerResp['id'] ?? null,
                'asaas_subscription_id' => $subResp['id'] ?? null,
                'status' => ($subResp['status'] ?? '') === 'ACTIVE' ? 'active' : 'pending',
                'started_at' => $subResp['nextDueDate'] ?? null,
            ];

            $subId = Subscription::create($subData);

            // Se a nova assinatura já estiver ativa, cancela outras assinaturas ativas do mesmo e-mail (troca de plano)
            if (($subData['status'] ?? '') === 'active') {
                Subscription::cancelOtherActivesForEmail($customer['email'], $subId);
            }

            $_SESSION['plan_slug'] = $plan['slug'];

            // Verificação de cartão (opção 3): pré-autorização com valor simbólico e estorno automático
            // Apenas quando veio por indicação, há dias grátis e o plano exige cartão.
            if ($pendingReferral && $referralFreeDays > 0 && !empty($plan['referral_require_card']) && $requiresCardNow && $creditCard !== null) {
                $cardVerification['attempted'] = true;
                $cardVerification['value'] = 1.00;

                try {
                    $verifyPayload = [
                        'customer' => $customerResp['id'] ?? null,
                        'billingType' => 'CREDIT_CARD',
                        'value' => $cardVerification['value'],
                        'dueDate' => (new \DateTimeImmutable('now'))->format('Y-m-d'),
                        'description' => 'Validação de cartão (indicação) - estorno automático',
                        'authorizedOnly' => true,
                        'creditCard' => $creditCard,
                        'creditCardHolderInfo' => $subscriptionPayload['creditCardHolderInfo'] ?? null,
                    ];

                    if (empty($verifyPayload['customer']) || empty($verifyPayload['creditCardHolderInfo'])) {
                        throw new \RuntimeException('Dados insuficientes para validação do cartão.');
                    }

                    $verifyResp = $asaas->createPayment($verifyPayload);
                    $verifyPaymentId = (string)($verifyResp['id'] ?? '');
                    if ($verifyPaymentId !== '') {
                        $cardVerification['paymentId'] = $verifyPaymentId;

                        try {
                            $asaas->refundPayment($verifyPaymentId, [
                                'description' => 'Estorno automático - validação de cartão (indicação)',
                            ]);
                            $cardVerification['refunded'] = true;
                        } catch (\Throwable $refundEx) {
                            error_log('CheckoutController::process erro ao estornar validação de cartão: ' . $refundEx->getMessage());
                        }
                    }
                } catch (\Throwable $verifyEx) {
                    error_log('CheckoutController::process erro ao validar cartão (cobrança simbólica): ' . $verifyEx->getMessage());
                }
            }

            // Bônus de indicação (Indique e ganhe)
            try {
                if ($pendingReferral && !empty($plan['referral_enabled'])) {
                    $referrerId = (int)($pendingReferral['referrer_user_id'] ?? 0);
                    $referrer = $referrerId > 0 ? User::findById($referrerId) : null;

                    $minDays = isset($plan['referral_min_active_days']) ? (int)$plan['referral_min_active_days'] : 0;
                    $eligibleReferrer = false;

                    if ($referrer && !empty($referrer['email'])) {
                        $referrerSubs = Subscription::allByEmailWithPlan($referrer['email']);
                        $now = new \DateTimeImmutable('now');

                        foreach ($referrerSubs as $rs) {
                            if ((int)($rs['plan_id'] ?? 0) !== (int)$plan['id']) {
                                continue;
                            }
                            if ((string)($rs['status'] ?? '') !== 'active') {
                                continue;
                            }
                            if (empty($rs['created_at'])) {
                                continue;
                            }

                            try {
                                $createdAt = new \DateTimeImmutable($rs['created_at']);
                                $days = (int)$now->diff($createdAt)->days;
                                if ($days >= $minDays) {
                                    $eligibleReferrer = true;
                                    break;
                                }
                            } catch (\Throwable $e) {
                                continue;
                            }
                        }
                    }

                    $friendTokens = isset($plan['referral_friend_tokens']) ? (int)$plan['referral_friend_tokens'] : 0;
                    $referrerTokens = isset($plan['referral_referrer_tokens']) ? (int)$plan['referral_referrer_tokens'] : 0;

                    $metaBase = [
                        'plan_id' => (int)$plan['id'],
                        'referral_id' => (int)$pendingReferral['id'],
                        'asaas_subscription_id' => (string)($subResp['id'] ?? ''),
                    ];

                    // Credita tokens para o indicado (usuário atual) assim que o checkout é concluído
                    if ($friendTokens > 0 && $loggedUserId > 0) {
                        User::creditTokens($loggedUserId, $friendTokens, 'referral_friend_bonus', $metaBase);
                    }

                    // Credita tokens para quem indicou apenas se ele estiver elegível
                    if ($eligibleReferrer && $referrer && $referrerTokens > 0) {
                        User::creditTokens((int)$referrer['id'], $referrerTokens, 'referral_referrer_bonus', $metaBase);
                    }

                    // Marca indicação como concluída para não repetir bônus
                    UserReferral::markCompleted((int)$pendingReferral['id']);

                    unset($_SESSION['pending_referral']);
                    unset($_SESSION['pending_plan_slug']);

                    // Envia e-mails específicos de bônus de indicação
                    try {
                        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
                        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                        $appUrl = $scheme . $host;

                            // E-mail para o indicado
                            if (!empty($customer['email'])) {
                                $safeNameFriend = htmlspecialchars($customer['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                                $safePlanName = htmlspecialchars($plan['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                                $safeAppUrl = htmlspecialchars($appUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                                $bonusParts = [];
                                if ($referralFreeDays > 0) {
                                    $bonusParts[] = "<li>" . $referralFreeDays . " dias grátis antes da primeira cobrança;</li>";
                                }
                                if ($friendTokens > 0) {
                                    $bonusParts[] = "<li>" . number_format($friendTokens, 0, ',', '.') . " tokens extras para usar com o " . htmlspecialchars(\App\Models\Branding::mascotName(), ENT_QUOTES, 'UTF-8') . ";</li>";
                                }
                                $bonusListHtml = '';
                                if ($bonusParts) {
                                    $bonusListHtml = '<ul style="font-size:13px; color:#b0b0b0; padding-left:18px; margin:0 0 10px 0;">' . implode('', $bonusParts) . '</ul>';
                                }

                                $brandHeader = \App\Models\Branding::emailHeaderHtml();
                                $brandMascot = htmlspecialchars(\App\Models\Branding::mascotName(), ENT_QUOTES, 'UTF-8');
                                $subjectFriend = "Você ganhou bônus pela indicação no {$brandMascot}";
                                $bodyFriend = <<<HTML
<html>
<body style="margin:0; padding:0; background:#050509; font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color:#f5f5f5;">
  <div style="width:100%; padding:24px 0;">
    <div style="max-width:520px; margin:0 auto; background:#111118; border-radius:16px; border:1px solid #272727; padding:18px 20px;">
      {$brandHeader}

      <p style="font-size:14px; margin:0 0 10px 0;">Oi, {$safeNameFriend} 👋</p>
      <p style="font-size:14px; margin:0 0 10px 0;">Sua assinatura do plano <strong>{$safePlanName}</strong> foi feita por indicação e, por isso, você ganhou alguns bônus para começar com o pé direito:</p>
      {$bonusListHtml}

      <p style="font-size:13px; margin:8px 0 0 0;">É só entrar no {$brandMascot} e aproveitar. Se tiver qualquer dúvida, fale com a gente pelo suporte.</p>

      <div style="text-align:center; margin:14px 0 0 0;">
        <a href="{$safeAppUrl}" style="display:inline-block; padding:9px 18px; border-radius:999px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:600; font-size:13px; text-decoration:none;">Abrir o {$brandMascot}</a>
      </div>
    </div>
  </div>
</body>
</html>
HTML;

                                MailService::send($customer['email'], $customer['name'], $subjectFriend, $bodyFriend);
                            }

                            // E-mail para quem indicou
                            if ($referrer && !empty($referrer['email'])) {
                                $safeNameRef = htmlspecialchars($referrer['name'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                                $safeFriendName = htmlspecialchars($customer['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                                $safePlanName2 = htmlspecialchars($plan['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                                $tokensText = $referrerTokens > 0
                                    ? ' ' . number_format($referrerTokens, 0, ',', '.') . ' tokens foram adicionados ao seu saldo.'
                                    : '';

                                $subjectRef = "Um amigo assinou pelo seu link no {$brandMascot}";
                                $bodyRef = <<<HTML
<html>
<body style="margin:0; padding:0; background:#050509; font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color:#f5f5f5;">
  <div style="width:100%; padding:24px 0;">
    <div style="max-width:520px; margin:0 auto; background:#111118; border-radius:16px; border:1px solid #272727; padding:18px 20px;">
      {$brandHeader}

      <p style="font-size:14px; margin:0 0 10px 0;">Oi, {$safeNameRef} 👋</p>
      <p style="font-size:14px; margin:0 0 10px 0;">Boas notícias: <strong>{$safeFriendName}</strong> acabou de assinar o plano <strong>{$safePlanName2}</strong> usando o seu link de indicação.</p>
      <p style="font-size:14px; margin:0 0 10px 0;">Como agradecimento,{$tokensText}</p>

      <p style="font-size:13px; margin:8px 0 0 0;">Obrigado por indicar o {$brandMascot}. Sempre que um amigo assinar pelo seu link, você acumula mais bônus para usar no dia a dia.</p>
    </div>
  </div>
</body>
</html>
HTML;

                                MailService::send($referrer['email'], $referrer['name'] ?? '', $subjectRef, $bodyRef);
                            }
                    } catch (\Throwable $mailRefEx) {
                        error_log('CheckoutController::process erro ao enviar e-mails de indicação: ' . $mailRefEx->getMessage());
                    }
                }
            } catch (\Throwable $referralEx) {
                error_log('CheckoutController::process erro no fluxo de indicação: ' . $referralEx->getMessage());
            }

            // Envia e-mail de confirmação da assinatura
            try {
                $priceFormatted = number_format($plan['price_cents'] / 100, 2, ',', '.');
                // Define rótulo do período (mês / semestre / ano) com base no sufixo do slug para o e-mail
                $slug = (string)($plan['slug'] ?? '');
                $periodLabel = 'mês';
                if (substr($slug, -11) === '-semestral') {
                    $periodLabel = 'semestre';
                } elseif (substr($slug, -6) === '-anual') {
                    $periodLabel = 'ano';
                }

                $brandHeader = \App\Models\Branding::emailHeaderHtml();
                $brandMascot = htmlspecialchars(\App\Models\Branding::mascotName(), ENT_QUOTES, 'UTF-8');
                $subject = "Sua assinatura do {$brandMascot} está ativa";
                $safeName = htmlspecialchars($customer['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safePlan = htmlspecialchars($plan['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safePrice = htmlspecialchars($priceFormatted, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safePeriod = htmlspecialchars($periodLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $body = <<<HTML
<html>
<body style="margin:0; padding:0; background:#050509; font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color:#f5f5f5;">
  <div style="width:100%; padding:24px 0;">
    <div style="max-width:520px; margin:0 auto; background:#111118; border-radius:16px; border:1px solid #272727; padding:18px 20px;">
      {$brandHeader}

      <p style="font-size:14px; margin:0 0 10px 0;">Oi, {$safeName} 👋</p>
      <p style="font-size:14px; margin:0 0 10px 0;">Sua assinatura foi criada com sucesso. A partir de agora, você tem acesso ao plano <strong>{$safePlan}</strong> no {$brandMascot}.</p>
      <p style="font-size:14px; margin:0 0 10px 0;">Valor da assinatura: <strong>R\$ {$safePrice}/{$safePeriod}</strong>.</p>

      <p style="font-size:13px; margin:0 0 8px 0;">Com esse plano você pode:</p>
      <ul style="font-size:13px; color:#b0b0b0; padding-left:18px; margin:0 0 10px 0;">
        <li>Usar o {$brandMascot} para apoiar sua criação de marcas no dia a dia;</li>
        <li>Organizar seus projetos de branding com histórico de conversas salvo;</li>
        <li>Explorar prompts e ideias guiadas pelo próprio {$brandMascot}.</li>
      </ul>

      <p style="font-size:13px; margin:0 0 10px 0;">Se perceber qualquer problema com a cobrança ou acesso, responda este e-mail ou fale com a gente pelo suporte.</p>
    </div>
  </div>
</body>
</html>
HTML;

                MailService::send($customer['email'], $customer['name'], $subject, $body);
            } catch (\Throwable $mailEx) {
                error_log('CheckoutController::process erro ao enviar e-mail de confirmação: ' . $mailEx->getMessage());
            }

            $this->view('checkout/success', [
                'pageTitle' => 'Assinatura criada',
                'plan' => $plan,
                'referralFreeDays' => $referralFreeDays,
                'nextDueDate' => $nextDueDate,
                'requiresCardNow' => $requiresCardNow,
                'cardVerification' => $cardVerification,
            ]);
        } catch (\Throwable $e) {
            // Loga erro detalhado para depuração (incluindo resposta do Asaas quando houver)
            error_log('CheckoutController::process erro: ' . $e->getMessage());

            // Log geral dos payloads em sessão (se existirem)
            $debugCustomer = $_SESSION['asaas_debug_customer'] ?? null;
            $debugSubscription = $_SESSION['asaas_debug_subscription'] ?? null;
            error_log('CheckoutController::process payload customer: ' . json_encode($debugCustomer, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            error_log('CheckoutController::process payload subscription: ' . json_encode($debugSubscription, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            // Mensagem amigável diferente para erros do Asaas (cartão/dados de cobrança)
            $msg = $e->getMessage();
            if (strpos($msg, 'Erro Asaas HTTP') !== false) {
                $friendlyError = 'Não consegui aprovar o pagamento no cartão. Confira se os dados estão corretos (número, validade, CVV e limite) ou tente outro cartão. Se o problema continuar, fale com o suporte.';

                // Tenta enviar e-mail avisando sobre a falha no pagamento
                try {
                    $sessionCustomer = $_SESSION['checkout_customer'] ?? null;
                    if ($sessionCustomer) {
                        $priceFormatted = number_format($plan['price_cents'] / 100, 2, ',', '.');
                        // Define rótulo do período para o e-mail de falha
                        $slug = (string)($plan['slug'] ?? '');
                        $periodLabel = 'mês';
                        if (substr($slug, -11) === '-semestral') {
                            $periodLabel = 'semestre';
                        } elseif (substr($slug, -6) === '-anual') {
                            $periodLabel = 'ano';
                        }

                        $brandHeader = \App\Models\Branding::emailHeaderHtml();
                        $subject = 'Falha ao processar o pagamento da sua assinatura';
                        $safeName = htmlspecialchars($sessionCustomer['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                        $safePlan = htmlspecialchars($plan['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                        $safePrice = htmlspecialchars($priceFormatted, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                        $safePeriod = htmlspecialchars($periodLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                        $body = <<<HTML
<html>
<body style="margin:0; padding:0; background:#050509; font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color:#f5f5f5;">
  <div style="width:100%; padding:24px 0;">
    <div style="max-width:520px; margin:0 auto; background:#111118; border-radius:16px; border:1px solid #272727; padding:18px 20px;">
      {$brandHeader}

      <p style="font-size:14px; margin:0 0 10px 0;">Oi, {$safeName} 👋</p>
      <p style="font-size:14px; margin:0 0 10px 0;">Tentamos processar o pagamento da sua assinatura do plano <strong>{$safePlan}</strong>, mas o cartão não foi aprovado.</p>
      <p style="font-size:14px; margin:0 0 10px 0;">Valor da assinatura: <strong>R\$ {$safePrice}/{$safePeriod}</strong>.</p>

      <p style="font-size:13px; margin:0 0 8px 0;">Geralmente isso acontece por algum destes motivos:</p>
      <ul style="font-size:13px; color:#b0b0b0; padding-left:18px; margin:0 0 10px 0;">
        <li>dados do cartão incorretos (número, validade ou CVV);</li>
        <li>cartão sem limite disponível ou com bloqueio para compras online;</li>
        <li>restrição temporária da operadora do cartão.</li>
      </ul>

      <p style="font-size:13px; margin:0 0 8px 0;">Você pode tentar novamente com o mesmo cartão, conferir os dados ou usar outro cartão. Se continuar com dificuldade, é só responder este e-mail ou falar com a gente pelo suporte.</p>
    </div>
  </div>
</body>
</html>
HTML;

                        MailService::send($sessionCustomer['email'], $sessionCustomer['name'], $subject, $body);
                    }
                } catch (\Throwable $mailFail) {
                    error_log('CheckoutController::process erro ao enviar e-mail de falha de pagamento: ' . $mailFail->getMessage());
                }
            } else {
                $friendlyError = 'Não consegui finalizar a assinatura agora. Tenta novamente em alguns minutos ou fala com o suporte.';
            }

            $sessionCustomer = $_SESSION['checkout_customer'] ?? null;

            $this->view('checkout/show', [
                'pageTitle' => 'Checkout - ' . $plan['name'],
                'plan' => $plan,
                'checkoutPlan' => $plan,
                'customer' => $sessionCustomer ?: [],
                'birthdate' => $sessionCustomer['birthdate'] ?? '',
                'requiresCardNow' => $requiresCardNow,
                'error' => $friendlyError,
            ]);
        }
    }

    /**
     * Rota de debug: reenvia o último payload salvo para o Asaas e mostra a resposta bruta.
     * Disponível apenas para admin e quando o Asaas estiver em ambiente sandbox.
     */
    public function debugLastAsaas(): void
    {
        $config = AsaasConfig::getActive();
        $env = $config['environment'] ?? 'sandbox';
        if ($env === 'production') {
            echo 'Debug do Asaas disponível apenas em ambiente sandbox.';
            return;
        }

        $customer = $_SESSION['asaas_debug_customer'] ?? null;
        $subscription = $_SESSION['asaas_debug_subscription'] ?? null;

        if (!$customer || !$subscription) {
            echo 'Nenhum payload Asaas salvo na sessão. Tente primeiro fazer um checkout que gere erro.';
            return;
        }

        header('Content-Type: text/html; charset=UTF-8');
        echo '<h1>Debug Asaas - Último payload</h1>';
        echo '<h2>Ambiente: ' . htmlspecialchars($env) . '</h2>';

        try {
            $asaas = new AsaasClient();

            echo '<h3>Enviando customer...</h3>';
            $custResp = $asaas->createOrUpdateCustomer($customer);
            echo '<pre>' . htmlspecialchars(json_encode($custResp, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '</pre>';

            if (!empty($custResp['id'])) {
                $subscription['customer'] = $custResp['id'];
            }

            echo '<h3>Enviando subscription...</h3>';
            $subResp = $asaas->createSubscription($subscription);
            echo '<pre>' . htmlspecialchars(json_encode($subResp, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '</pre>';
        } catch (\Throwable $e) {
            echo '<h3>Exceção capturada</h3>';
            echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<h4>Payload customer</h4>';
            echo '<pre>' . htmlspecialchars(json_encode($customer, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '</pre>';
            echo '<h4>Payload subscription</h4>';
            echo '<pre>' . htmlspecialchars(json_encode($subscription, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '</pre>';
        }
    }
}
