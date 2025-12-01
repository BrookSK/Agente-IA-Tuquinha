<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\AsaasClient;

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

        $this->view('checkout/show', [
            'pageTitle' => 'Checkout - ' . $plan['name'],
            'plan' => $plan,
            'error' => null,
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

        $required = ['name', 'email', 'cpf', 'birthdate', 'postal_code', 'address', 'address_number', 'province', 'city', 'state', 'card_number', 'card_holder', 'card_exp_month', 'card_exp_year', 'card_cvv'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                $this->view('checkout/show', [
                    'pageTitle' => 'Checkout - ' . $plan['name'],
                    'plan' => $plan,
                    'error' => 'Por favor, preencha todos os campos obrigatórios.',
                ]);
                return;
            }
        }

        $customer = [
            'name' => trim($_POST['name']),
            'email' => trim($_POST['email']),
            'cpfCnpj' => preg_replace('/\D+/', '', $_POST['cpf']),
            'phone' => $_POST['phone'] ?? '',
            'postalCode' => preg_replace('/\D+/', '', $_POST['postal_code']),
            'address' => trim($_POST['address']),
            'addressNumber' => trim($_POST['address_number']),
            'complement' => $_POST['complement'] ?? '',
            'province' => trim($_POST['province']),
            'city' => trim($_POST['city']),
            'state' => trim($_POST['state']),
        ];

        $creditCard = [
            'holderName' => trim($_POST['card_holder']),
            'number' => preg_replace('/\s+/', '', $_POST['card_number']),
            'expiryMonth' => (int)$_POST['card_exp_month'],
            'expiryYear' => (int)$_POST['card_exp_year'],
            'ccv' => trim($_POST['card_cvv']),
        ];

        $birthdate = $_POST['birthdate'];

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
                'birthDate' => $birthdate,
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
            $this->view('checkout/show', [
                'pageTitle' => 'Checkout - ' . $plan['name'],
                'plan' => $plan,
                'error' => 'Não consegui finalizar a assinatura agora. Tenta novamente em alguns minutos ou fala com o suporte.',
            ]);
        }
    }
}
