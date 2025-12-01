<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\AsaasClient;
use App\Models\User;

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

            $this->view('checkout/success', [
                'pageTitle' => 'Assinatura criada',
                'plan' => $plan,
            ]);
        } catch (\Throwable $e) {
            // Loga erro detalhado para depuração (incluindo resposta do Asaas quando houver)
            error_log('CheckoutController::process erro: ' . $e->getMessage());

            $friendlyError = 'Não consegui finalizar a assinatura agora. Tenta novamente em alguns minutos ou fala com o suporte.';

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
}
