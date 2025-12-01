<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\AsaasClient;
use App\Services\MailService;
use App\Models\User;
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

        $step = (int)($_POST['step'] ?? 1);

        if ($step === 1) {
            // Validação de dados pessoais/endereço
            $required = ['name', 'email', 'cpf', 'birthdate', 'postal_code', 'address', 'address_number', 'province', 'city', 'state'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    $this->view('checkout/step1', [
                        'pageTitle' => 'Checkout - ' . $plan['name'],
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

            $this->view('checkout/show', [
                'pageTitle' => 'Checkout - ' . $plan['name'],
                'plan' => $plan,
                'customer' => $customerForSession,
                'birthdate' => $customerForSession['birthdate'],
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

        $requiredCard = ['card_number', 'card_holder', 'card_exp_month', 'card_exp_year', 'card_cvv'];
        foreach ($requiredCard as $field) {
            if (empty($_POST[$field])) {
                $this->view('checkout/show', [
                    'pageTitle' => 'Checkout - ' . $plan['name'],
                    'plan' => $plan,
                    'customer' => $sessionCustomer,
                    'birthdate' => $sessionCustomer['birthdate'] ?? '',
                    'error' => 'Por favor, preencha todos os dados do cartão.',
                ]);
                return;
            }
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

        $creditCard = [
            'holderName' => trim($_POST['card_holder']),
            'number' => preg_replace('/\s+/', '', $_POST['card_number']),
            'expiryMonth' => (int)$_POST['card_exp_month'],
            'expiryYear' => (int)$_POST['card_exp_year'],
            'ccv' => trim($_POST['card_cvv']),
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

        $subscriptionPayload = [
            'billingType' => 'CREDIT_CARD',
            'value' => $plan['price_cents'] / 100,
            'cycle' => 'MONTHLY',
            'description' => $plan['name'],
            'creditCard' => $creditCard,
            'creditCardHolderInfo' => [
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
            ],
        ];

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

            Subscription::create($subData);

            $_SESSION['plan_slug'] = $plan['slug'];

            // Envia e-mail de confirmação da assinatura
            try {
                $priceFormatted = number_format($plan['price_cents'] / 100, 2, ',', '.');
                $subject = 'Sua assinatura do Tuquinha está ativa';
                $safeName = htmlspecialchars($customer['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safePlan = htmlspecialchars($plan['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safePrice = htmlspecialchars($priceFormatted, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $body = <<<HTML
<html>
<body style="margin:0; padding:0; background:#050509; font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color:#f5f5f5;">
  <div style="width:100%; padding:24px 0;">
    <div style="max-width:520px; margin:0 auto; background:#111118; border-radius:16px; border:1px solid #272727; padding:18px 20px;">
      <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
        <div style="width:32px; height:32px; line-height:32px; border-radius:50%; background:radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%); text-align:center; font-weight:700; font-size:16px; color:#050509;">T</div>
        <div>
          <div style="font-weight:700; font-size:15px;">Agente IA - Tuquinha</div>
          <div style="font-size:11px; color:#b0b0b0;">Branding vivo na veia</div>
        </div>
      </div>

      <p style="font-size:14px; margin:0 0 10px 0;">Oi, {$safeName} 👋</p>
      <p style="font-size:14px; margin:0 0 10px 0;">Sua assinatura foi criada com sucesso. A partir de agora, você tem acesso ao plano <strong>{$safePlan}</strong> no Tuquinha.</p>
      <p style="font-size:14px; margin:0 0 10px 0;">Valor da assinatura: <strong>R\$ {$safePrice}/mês</strong>.</p>

      <p style="font-size:13px; margin:0 0 8px 0;">Com esse plano você pode:</p>
      <ul style="font-size:13px; color:#b0b0b0; padding-left:18px; margin:0 0 10px 0;">
        <li>Usar o Tuquinha para apoiar sua criação de marcas no dia a dia;</li>
        <li>Organizar seus projetos de branding com histórico de conversas salvo;</li>
        <li>Explorar prompts e ideias guiadas pelo próprio Tuquinha.</li>
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
                        $subject = 'Falha ao processar o pagamento da sua assinatura';
                        $safeName = htmlspecialchars($sessionCustomer['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                        $safePlan = htmlspecialchars($plan['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                        $safePrice = htmlspecialchars($priceFormatted, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                        $body = <<<HTML
<html>
<body style="margin:0; padding:0; background:#050509; font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color:#f5f5f5;">
  <div style="width:100%; padding:24px 0;">
    <div style="max-width:520px; margin:0 auto; background:#111118; border-radius:16px; border:1px solid #272727; padding:18px 20px;">
      <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
        <div style="width:32px; height:32px; line-height:32px; border-radius:50%; background:radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%); text-align:center; font-weight:700; font-size:16px; color:#050509;">T</div>
        <div>
          <div style="font-weight:700; font-size:15px;">Agente IA - Tuquinha</div>
          <div style="font-size:11px; color:#b0b0b0;">Branding vivo na veia</div>
        </div>
      </div>

      <p style="font-size:14px; margin:0 0 10px 0;">Oi, {$safeName} 👋</p>
      <p style="font-size:14px; margin:0 0 10px 0;">Tentamos processar o pagamento da sua assinatura do plano <strong>{$safePlan}</strong>, mas o cartão não foi aprovado.</p>
      <p style="font-size:14px; margin:0 0 10px 0;">Valor da assinatura: <strong>R\$ {$safePrice}/mês</strong>.</p>

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
                'customer' => $sessionCustomer ?: [],
                'birthdate' => $sessionCustomer['birthdate'] ?? '',
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
