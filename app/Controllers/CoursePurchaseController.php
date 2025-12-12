<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CoursePurchase;
use App\Models\User;
use App\Services\AsaasClient;

class CoursePurchaseController extends Controller
{
    private function requireLogin(): array
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

    public function show(): void
    {
        $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
        if ($courseId <= 0) {
            header('Location: /cursos');
            exit;
        }

        $course = Course::findById($courseId);
        if (!$course || empty($course['is_active'])) {
            header('Location: /cursos');
            exit;
        }

        $priceCents = isset($course['price_cents']) ? (int)$course['price_cents'] : 0;
        $isPaid = !empty($course['is_paid']) && $priceCents > 0;
        $allowPublicPurchase = !empty($course['allow_public_purchase']);

        // Compra avulsa só faz sentido para cursos pagos que estejam marcados como disponíveis para compra pública
        if (!$isPaid || !$allowPublicPurchase) {
            $_SESSION['courses_error'] = 'Este curso não está disponível para compra avulsa.';
            header('Location: ' . CourseController::buildCourseUrl($course));
            exit;
        }

        if (empty($_SESSION['user_id'])) {
            $_SESSION['pending_course_id'] = $courseId;
            header('Location: /login');
            exit;
        }

        $user = $this->requireLogin();

        if (CourseEnrollment::isEnrolled($courseId, (int)$user['id'])) {
            $_SESSION['courses_success'] = 'Você já está inscrito neste curso.';
            header('Location: ' . CourseController::buildCourseUrl($course));
            exit;
        }

        $savedCustomer = $_SESSION['course_checkout_customer'] ?? null;

        $this->view('courses/purchase', [
            'pageTitle' => 'Comprar curso: ' . (string)($course['title'] ?? ''),
            'user' => $user,
            'course' => $course,
            'savedCustomer' => $savedCustomer,
            'error' => null,
        ]);
    }

    public function process(): void
    {
        $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
        if ($courseId <= 0) {
            header('Location: /cursos');
            exit;
        }

        if (empty($_SESSION['user_id'])) {
            $_SESSION['pending_course_id'] = $courseId;
            header('Location: /login');
            exit;
        }

        $user = $this->requireLogin();

        $course = Course::findById($courseId);
        if (!$course || empty($course['is_active'])) {
            $_SESSION['courses_error'] = 'Curso não encontrado.';
            header('Location: /cursos');
            exit;
        }

        $priceCents = isset($course['price_cents']) ? (int)$course['price_cents'] : 0;
        $isPaid = !empty($course['is_paid']) && $priceCents > 0;
        $allowPublicPurchase = !empty($course['allow_public_purchase']);

        if (!$isPaid || !$allowPublicPurchase) {
            $_SESSION['courses_error'] = 'Este curso não está disponível para compra avulsa.';
            header('Location: ' . CourseController::buildCourseUrl($course));
            exit;
        }

        $required = ['name', 'email', 'cpf', 'birthdate', 'postal_code', 'address', 'address_number', 'province', 'city', 'state'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                $this->view('courses/purchase', [
                    'pageTitle' => 'Comprar curso: ' . (string)($course['title'] ?? ''),
                    'user' => $user,
                    'course' => $course,
                    'savedCustomer' => $_SESSION['course_checkout_customer'] ?? null,
                    'error' => 'Por favor, preencha todos os campos obrigatórios.',
                ]);
                return;
            }
        }

        $customerForSession = [
            'name' => trim((string)$_POST['name']),
            'email' => trim((string)$_POST['email']),
            'cpf' => (string)$_POST['cpf'],
            'cpfCnpj' => preg_replace('/\D+/', '', (string)$_POST['cpf']),
            'phone' => (string)($_POST['phone'] ?? ''),
            'postal_code' => (string)$_POST['postal_code'],
            'postalCode' => preg_replace('/\D+/', '', (string)$_POST['postal_code']),
            'address' => trim((string)$_POST['address']),
            'address_number' => trim((string)$_POST['address_number']),
            'complement' => (string)($_POST['complement'] ?? ''),
            'province' => trim((string)$_POST['province']),
            'city' => trim((string)$_POST['city']),
            'state' => trim((string)$_POST['state']),
            'birthdate' => (string)$_POST['birthdate'],
        ];

        $_SESSION['course_checkout_customer'] = $customerForSession;

        // Atualiza também os dados de cobrança do usuário
        User::updateBillingData(
            (int)$user['id'],
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

        $priceCents = isset($course['price_cents']) ? (int)$course['price_cents'] : 0;
        if ($priceCents <= 0) {
            $this->view('courses/purchase', [
                'pageTitle' => 'Comprar curso: ' . (string)($course['title'] ?? ''),
                'user' => $user,
                'course' => $course,
                'savedCustomer' => $customerForSession,
                'error' => 'Valor inválido para este curso.',
            ]);
            return;
        }

        $billingType = isset($_POST['billing_type']) ? (string)$_POST['billing_type'] : 'PIX';
        if (!in_array($billingType, ['PIX', 'BOLETO', 'CREDIT_CARD'], true)) {
            $billingType = 'PIX';
        }

        $purchaseId = CoursePurchase::create([
            'user_id' => (int)$user['id'],
            'course_id' => $courseId,
            'amount_cents' => $priceCents,
            'billing_type' => $billingType,
            'asaas_payment_id' => null,
            'status' => 'pending',
            'paid_at' => null,
        ]);

        try {
            $asaas = new AsaasClient();

            $customer = [
                'name' => $customerForSession['name'],
                'email' => $customerForSession['email'],
                'cpfCnpj' => $customerForSession['cpfCnpj'],
                'phone' => $customerForSession['phone'],
                'postalCode' => $customerForSession['postalCode'],
                'address' => $customerForSession['address'],
                'addressNumber' => $customerForSession['address_number'],
                'complement' => $customerForSession['complement'],
                'province' => $customerForSession['province'],
                'city' => $customerForSession['city'],
                'state' => $customerForSession['state'],
            ];

            $customerResp = $asaas->createOrUpdateCustomer($customer);
            $customerId = $customerResp['id'] ?? null;

            if (!$customerId) {
                throw new \RuntimeException('Falha ao criar cliente no Asaas.');
            }

            // Define dueDate conforme o tipo de cobrança exigido pelo Asaas
            $dueDate = date('Y-m-d'); // hoje por padrão
            if ($billingType === 'BOLETO') {
                $dueDate = date('Y-m-d', strtotime('+3 days'));
            } elseif ($billingType === 'PIX') {
                $dueDate = date('Y-m-d', strtotime('+1 day'));
            }

            $payload = [
                'customer' => $customerId,
                'billingType' => $billingType,
                'value' => $priceCents / 100,
                'description' => 'Compra avulsa do curso ' . (string)($course['title'] ?? ''),
                'externalReference' => 'course_purchase:' . $purchaseId,
                'dueDate' => $dueDate,
            ];

            $resp = $asaas->createPayment($payload);
            $paymentId = $resp['id'] ?? null;

            if ($paymentId) {
                CoursePurchase::attachPaymentId($purchaseId, (string)$paymentId);
            }

            $redirectUrl = $resp['invoiceUrl'] ?? null;
            if (!$redirectUrl && !empty($resp['bankSlipUrl'])) {
                $redirectUrl = (string)$resp['bankSlipUrl'];
            }

            if ($redirectUrl) {
                $this->view('courses/abrir_pagamento', [
                    'pageTitle' => 'Pagamento do curso',
                    'course' => $course,
                    'billingType' => $billingType,
                    'amountReais' => $priceCents / 100,
                    'redirectUrl' => $redirectUrl,
                ]);
                return;
            }

            $_SESSION['courses_success'] = 'Criamos o pedido de compra deste curso. Assim que o pagamento for confirmado, seu acesso será liberado.';
            header('Location: ' . CourseController::buildCourseUrl($course));
            exit;
        } catch (\Throwable $e) {
            $this->view('courses/purchase', [
                'pageTitle' => 'Comprar curso: ' . (string)($course['title'] ?? ''),
                'user' => $user,
                'course' => $course,
                'savedCustomer' => $customerForSession,
                'error' => 'Não consegui criar a cobrança para este curso. Tente novamente em alguns minutos ou fale com o suporte.',
            ]);
        }
    }
}
