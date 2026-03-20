<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseLesson;
use App\Models\CourseLessonComment;
use App\Models\CourseLessonProgress;
use App\Models\CoursePartnerBranding;
use App\Models\CoursePurchase;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AsaasClient;
use App\Services\MailService;

class ExternalCourseController extends Controller
{
    private function isPartnerSite(): bool
    {
        return !empty($_SERVER['TUQ_PARTNER_SITE']);
    }

    private function getPartnerOwnerUserId(): int
    {
        return (int)($_SERVER['TUQ_PARTNER_USER_ID'] ?? 0);
    }

    private function resolveExternalCourseFromGet(): array
    {
        if ($this->isPartnerSite()) {
            $slug = trim((string)($_GET['slug'] ?? ''));
            $ownerUserId = $this->getPartnerOwnerUserId();
            $course = ($slug !== '' && $ownerUserId > 0)
                ? Course::findExternalActiveBySlugAndOwner($slug, $ownerUserId)
                : null;

            $token = '';
            if ($course) {
                $token = trim((string)($course['external_token'] ?? ''));
                if ($token === '' && !empty($course['id'])) {
                    $token = (string)(Course::ensureExternalToken((int)$course['id']) ?? '');
                }
            }

            return ['course' => $course, 'token' => $token, 'slug' => $slug];
        }

        $slug = trim((string)($_GET['slug'] ?? ''));
        $course = null;
        $token = '';

        if ($slug !== '') {
            $course = Course::findBySlug($slug);
            if ($course && empty($course['is_external'])) {
                $course = null;
            }
        }

        if (!$course) {
            $token = isset($_GET['token']) ? trim((string)$_GET['token']) : '';
            $course = $token !== '' ? Course::findByExternalToken($token) : null;
            if ($course) {
                $slug = trim((string)($course['slug'] ?? ''));
            }
        }

        if ($course) {
            $token = $token !== '' ? $token : trim((string)($course['external_token'] ?? ''));
            if ($token === '' && !empty($course['id'])) {
                $token = (string)(Course::ensureExternalToken((int)$course['id']) ?? '');
            }
        }

        return ['course' => $course, 'token' => $token, 'slug' => $slug];
    }

    private function resolveExternalCourseFromPost(): array
    {
        if ($this->isPartnerSite()) {
            $slug = trim((string)($_GET['slug'] ?? ''));
            $ownerUserId = $this->getPartnerOwnerUserId();
            $course = ($slug !== '' && $ownerUserId > 0)
                ? Course::findExternalActiveBySlugAndOwner($slug, $ownerUserId)
                : null;

            $token = '';
            if ($course) {
                $token = trim((string)($course['external_token'] ?? ''));
                if ($token === '' && !empty($course['id'])) {
                    $token = (string)(Course::ensureExternalToken((int)$course['id']) ?? '');
                }
            }

            return ['course' => $course, 'token' => $token, 'slug' => $slug];
        }

        $slug = trim((string)($_GET['slug'] ?? ''));
        $course = null;
        $token = '';

        if ($slug !== '') {
            $course = Course::findBySlug($slug);
            if ($course && empty($course['is_external'])) {
                $course = null;
            }
        }

        if (!$course) {
            $token = isset($_POST['token']) ? trim((string)$_POST['token']) : '';
            $course = $token !== '' ? Course::findByExternalToken($token) : null;
            if ($course) {
                $slug = trim((string)($course['slug'] ?? ''));
            }
        }

        if ($course) {
            $token = $token !== '' ? $token : trim((string)($course['external_token'] ?? ''));
            if ($token === '' && !empty($course['id'])) {
                $token = (string)(Course::ensureExternalToken((int)$course['id']) ?? '');
            }
        }

        return ['course' => $course, 'token' => $token, 'slug' => $slug];
    }

    public function catalog(): void
    {
        $partnerId = $this->getPartnerOwnerUserId();
        if ($partnerId <= 0) {
            http_response_code(404);
            echo 'Página não encontrada';
            return;
        }

        $branding = CoursePartnerBranding::findByUserId($partnerId);
        $courses = Course::allExternalActiveByOwner($partnerId);
        $courses = is_array($courses) ? $courses : [];

        if (count($courses) === 1) {
            $slug = trim((string)($courses[0]['slug'] ?? ''));
            if ($slug !== '') {
                header('Location: /curso/' . urlencode($slug));
                exit;
            }
        }

        $this->view('external_courses/catalog', [
            'pageTitle' => (string)($branding['company_name'] ?? 'Cursos'),
            'branding' => $branding,
            'courses' => $courses,
            'layout' => 'external_course_modern',
            'isPartnerSite' => $this->isPartnerSite(),
        ]);
    }

    public function showLogin(): void
    {
        $resolved = $this->resolveExternalCourseFromGet();
        $token = (string)$resolved['token'];
        $slug = (string)$resolved['slug'];
        $course = $resolved['course'];
        if (!$course) {
            header('Location: /');
            exit;
        }

        $branding = null;
        if (!empty($course['owner_user_id'])) {
            $branding = CoursePartnerBranding::findByUserId((int)$course['owner_user_id']);
        }

        $this->view('external_courses/login_modern', [
            'pageTitle' => 'Login',
            'course' => $course,
            'branding' => $branding,
            'token' => $token,
            'slug' => $slug,
            'isPartnerSite' => $this->isPartnerSite(),
            'error' => null,
            'layout' => 'external_course_modern',
        ]);
    }

    public function login(): void
    {
        $resolved = $this->resolveExternalCourseFromPost();
        $token = (string)$resolved['token'];
        $slug = (string)$resolved['slug'];
        $course = $resolved['course'];
        if (!$course) {
            header('Location: /');
            exit;
        }

        $branding = null;
        if (!empty($course['owner_user_id'])) {
            $branding = CoursePartnerBranding::findByUserId((int)$course['owner_user_id']);
        }

        $email = trim($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            $this->view('external_courses/login_modern', [
                'pageTitle' => 'Login',
                'course' => $course,
                'branding' => $branding,
                'token' => $token,
                'slug' => $slug,
                'isPartnerSite' => $this->isPartnerSite(),
                'error' => 'Informe seu e-mail e senha.',
                'layout' => 'external_course_modern',
            ]);
            return;
        }

        $user = User::findByEmail($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->view('external_courses/login_modern', [
                'pageTitle' => 'Login',
                'course' => $course,
                'branding' => $branding,
                'token' => $token,
                'slug' => $slug,
                'isPartnerSite' => $this->isPartnerSite(),
                'error' => 'E-mail ou senha inválidos.',
                'layout' => 'external_course_modern',
            ]);
            return;
        }

        if (empty($user['is_admin']) && empty($user['email_verified_at'])) {
            $this->view('external_courses/checkout', [
                'pageTitle' => 'Comprar: ' . (string)($course['title'] ?? 'Curso'),
                'course' => $course,
                'branding' => $branding,
                'token' => $token,
                'slug' => $slug,
                'isPartnerSite' => $this->isPartnerSite(),
                'savedCustomer' => null,
                'error' => 'Antes de entrar, confirme seu e-mail.',
                'layout' => 'external_course',
            ]);
            return;
        }

        if (isset($user['is_active']) && (int)$user['is_active'] === 0) {
            $this->view('external_courses/checkout', [
                'pageTitle' => 'Comprar: ' . (string)($course['title'] ?? 'Curso'),
                'course' => $course,
                'branding' => $branding,
                'token' => $token,
                'slug' => $slug,
                'isPartnerSite' => $this->isPartnerSite(),
                'savedCustomer' => null,
                'error' => 'Sua conta foi desativada.',
                'layout' => 'external_course',
            ]);
            return;
        }

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['external_course_token'] = $token;

        if (!empty($user['is_external_course_user'])) {
            header('Location: /painel-externo');
        } else {
            if ($slug !== '') {
                header('Location: /curso/' . urlencode($slug) . '/checkout');
            } else {
                header('Location: /painel-externo');
            }
        }
        exit;
    }

    public function showForgotPassword(): void
    {
        $resolved = $this->resolveExternalCourseFromGet();
        $token = (string)$resolved['token'];
        $slug = (string)$resolved['slug'];
        $course = $resolved['course'];
        if (!$course) {
            header('Location: /');
            exit;
        }

        $branding = null;
        if (!empty($course['owner_user_id'])) {
            $branding = CoursePartnerBranding::findByUserId((int)$course['owner_user_id']);
        }

        $this->view('external_courses/forgot_password', [
            'pageTitle' => 'Recuperar Senha',
            'course' => $course,
            'branding' => $branding,
            'token' => $token,
            'slug' => $slug,
            'isPartnerSite' => $this->isPartnerSite(),
            'error' => null,
            'success' => null,
            'layout' => 'external_course_modern',
        ]);
    }

    public function sendForgotPassword(): void
    {
        $resolved = $this->resolveExternalCourseFromPost();
        $token = (string)$resolved['token'];
        $slug = (string)$resolved['slug'];
        $course = $resolved['course'];
        if (!$course) {
            header('Location: /');
            exit;
        }

        $branding = null;
        if (!empty($course['owner_user_id'])) {
            $branding = CoursePartnerBranding::findByUserId((int)$course['owner_user_id']);
        }

        $email = trim((string)($_POST['email'] ?? ''));
        if ($email === '') {
            $this->view('external_courses/forgot_password', [
                'pageTitle' => 'Recuperar Senha',
                'course' => $course,
                'branding' => $branding,
                'token' => $token,
                'slug' => $slug,
                'isPartnerSite' => $this->isPartnerSite(),
                'error' => 'Por favor, informe seu e-mail.',
                'success' => null,
                'layout' => 'external_course_modern',
            ]);
            return;
        }

        $user = User::findByEmail($email);
        if (!$user) {
            $this->view('external_courses/forgot_password', [
                'pageTitle' => 'Recuperar Senha',
                'course' => $course,
                'branding' => $branding,
                'token' => $token,
                'slug' => $slug,
                'isPartnerSite' => $this->isPartnerSite(),
                'error' => 'Não encontramos uma conta com este e-mail.',
                'success' => null,
                'layout' => 'external_course_modern',
            ]);
            return;
        }

        // Aqui você implementaria o envio do email de recuperação
        // Por enquanto, apenas mostra mensagem de sucesso
        $this->view('external_courses/forgot_password', [
            'pageTitle' => 'Recuperar Senha',
            'course' => $course,
            'branding' => $branding,
            'token' => $token,
            'slug' => $slug,
            'isPartnerSite' => $this->isPartnerSite(),
            'error' => null,
            'success' => 'Enviamos um link de recuperação para seu e-mail. Verifique sua caixa de entrada.',
            'layout' => 'external_course_modern',
        ]);
    }

    private function requireLogin(): array
    {
        if (empty($_SESSION['user_id'])) {
            $slug = trim((string)($_GET['slug'] ?? ''));
            if ($slug !== '') {
                header('Location: /curso/' . urlencode($slug) . '/login');
            } else {
                header('Location: /login');
            }
            exit;
        }

        $user = User::findById((int)$_SESSION['user_id']);
        if (!$user) {
            unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email']);
            $slug = trim((string)($_GET['slug'] ?? ''));
            if ($slug !== '') {
                header('Location: /curso/' . urlencode($slug) . '/login');
            } else {
                header('Location: /login');
            }
            exit;
        }

        return $user;
    }

    private function canAccessExternalCourse(array $course, array $user): bool
    {
        $courseId = (int)($course['id'] ?? 0);
        $userId = (int)($user['id'] ?? 0);
        if ($courseId <= 0 || $userId <= 0) {
            return false;
        }

        if (!empty($_SESSION['is_admin'])) {
            return true;
        }

        if (!empty($course['owner_user_id']) && (int)$course['owner_user_id'] === $userId) {
            return true;
        }

        if (CoursePurchase::userHasPaidPurchase($userId, $courseId)) {
            return true;
        }

        if (CourseEnrollment::isEnrolled($courseId, $userId)) {
            return true;
        }

        return false;
    }

    private function resolvePlanForUser(?array $user): ?array
    {
        $plan = null;
        if ($user && !empty($user['email'])) {
            $sub = Subscription::findLastByEmail((string)$user['email']);
            if ($sub && !empty($sub['plan_id'])) {
                $plan = Plan::findById((int)$sub['plan_id']);
            }
        }
        if (!$plan) {
            $plan = Plan::findBySessionSlug($_SESSION['plan_slug'] ?? null) ?: Plan::findBySlug('free');
        }
        return $plan;
    }

    private function applyCoursePlanDiscountCents(int $priceCents, ?array $plan): int
    {
        if ($priceCents <= 0) {
            return 0;
        }
        $p = 0.0;
        if ($plan && isset($plan['course_discount_percent']) && $plan['course_discount_percent'] !== null && $plan['course_discount_percent'] !== '') {
            $p = (float)$plan['course_discount_percent'];
        }
        if ($p <= 0) {
            return $priceCents;
        }
        if ($p > 100) {
            $p = 100.0;
        }
        $final = (int)round($priceCents * (1.0 - ($p / 100.0)));
        return max(0, $final);
    }

    private function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
        exit;
    }

    private function buildExternalBaseUrl(): string
    {
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . $host;
    }

    private function buildLoginUrlWithPendingCourse(int $courseId): string
    {
        return '/login?pending_external_course_id=' . urlencode((string)$courseId);
    }

    public function show(): void
    {
        $resolved = $this->resolveExternalCourseFromGet();
        $token = (string)$resolved['token'];
        $slug = (string)$resolved['slug'];
        $course = $resolved['course'];
        if (!$course) {
            http_response_code(404);
            echo 'Curso não encontrado';
            return;
        }

        $branding = null;
        if (!empty($course['owner_user_id'])) {
            $branding = CoursePartnerBranding::findByUserId((int)$course['owner_user_id']);
        }

        // Salva slug do curso na sessão para usar no logout
        if ($slug !== '') {
            $_SESSION['external_course_slug'] = $slug;
        }
        
        $this->view('external_courses/home', [
            'pageTitle' => (string)($branding['company_name'] ?? ($course['title'] ?? 'Curso')),
            'course' => $course,
            'branding' => $branding,
            'token' => $token,
            'slug' => $slug,
            'isPartnerSite' => $this->isPartnerSite(),
            'layout' => 'external_course_modern',
        ]);
    }

    public function checkout(): void
    {
        $resolved = $this->resolveExternalCourseFromGet();
        $token = (string)$resolved['token'];
        $slug = (string)$resolved['slug'];
        $course = $resolved['course'];
        if (!$course) {
            header('Location: /');
            exit;
        }

        $branding = null;
        if (!empty($course['owner_user_id'])) {
            $branding = CoursePartnerBranding::findByUserId((int)$course['owner_user_id']);
        }

        // Recupera dados pré-preenchidos da sessão ou do usuário logado
        $prefilledData = $_SESSION['checkout_prefill'] ?? [];
        unset($_SESSION['checkout_prefill']); // Limpa após usar

        // Se usuário está logado e não há dados pré-preenchidos, usa dados do usuário
        if (empty($prefilledData) && !empty($_SESSION['user_id'])) {
            $user = User::findById((int)$_SESSION['user_id']);
            if ($user) {
                $prefilledData = [
                    'name' => trim((string)($user['name'] ?? '')),
                    'email' => trim((string)($user['email'] ?? '')),
                ];
            }
        }

        // Buscar dados dinâmicos do curso usando helper
        $courseDetails = \App\Helpers\CourseHelper::getCourseDetails((int)($course['id'] ?? 0));

        $this->view('external_courses/checkout_modern', [
            'pageTitle' => 'Comprar: ' . (string)($course['title'] ?? 'Curso'),
            'course' => $course,
            'branding' => $branding,
            'token' => $token,
            'slug' => $slug,
            'isPartnerSite' => $this->isPartnerSite(),
            'error' => null,
            'prefilledData' => $prefilledData,
            'courseDetails' => $courseDetails,
            'layout' => 'external_course_modern',
        ]);
    }

    public function processCheckout(): void
    {
        $resolved = $this->resolveExternalCourseFromPost();
        $token = (string)$resolved['token'];
        $slug = (string)$resolved['slug'];
        $course = $resolved['course'];
        if (!$course) {
            header('Location: /');
            exit;
        }

        $branding = null;
        if (!empty($course['owner_user_id'])) {
            $branding = CoursePartnerBranding::findByUserId((int)$course['owner_user_id']);
        }

        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        $required = ['name', 'email', 'password', 'cpf', 'birthdate', 'postal_code', 'address', 'address_number', 'province', 'city', 'state'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                $this->view('external_courses/checkout', [
                    'pageTitle' => 'Comprar: ' . (string)($course['title'] ?? 'Curso'),
                    'course' => $course,
                    'branding' => $branding,
                    'token' => $token,
                    'slug' => $slug,
                    'isPartnerSite' => $this->isPartnerSite(),
                    'savedCustomer' => null,
                    'error' => 'Por favor, preencha todos os campos obrigatórios.',
                    'layout' => 'external_course',
                ]);
                return;
            }
        }

        if (strlen($password) < 8) {
            $this->view('external_courses/checkout', [
                'pageTitle' => 'Comprar: ' . (string)($course['title'] ?? 'Curso'),
                'course' => $course,
                'branding' => $branding,
                'token' => $token,
                'slug' => $slug,
                'isPartnerSite' => $this->isPartnerSite(),
                'savedCustomer' => null,
                'error' => 'Sua senha deve ter pelo menos 8 caracteres.',
                'layout' => 'external_course',
            ]);
            return;
        }

        $existingUser = User::findByEmail($email);
        $userId = 0;

        if ($existingUser) {
            // Usuário já existe: exige que ele informe a senha atual (para evitar conflito de contas)
            $this->view('external_courses/checkout', [
                'pageTitle' => 'Comprar: ' . (string)($course['title'] ?? 'Curso'),
                'course' => $course,
                'branding' => $branding,
                'token' => $token,
                'slug' => $slug,
                'isPartnerSite' => $this->isPartnerSite(),
                'savedCustomer' => null,
                'error' => 'Já existe uma conta com este e-mail. Faça login e depois tente novamente.',
                'layout' => 'external_course',
            ]);
            return;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $userId = User::createUser($name, $email, $hash);

        // Marca como verificado para permitir login imediato
        try {
            User::setEmailVerifiedAt($userId, date('Y-m-d H:i:s'));
        } catch (\Throwable $e) {
        }

        // Marca como usuário de curso externo
        $partnerId = !empty($course['owner_user_id']) ? (int)$course['owner_user_id'] : 0;
        if ($partnerId > 0) {
            User::markAsExternalCourseUser($userId, $partnerId);
        }

        // Login automático
        $_SESSION['user_id'] = (int)$userId;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['external_course_token'] = $token;
        unset($_SESSION['is_admin']);

        $plan = $this->resolvePlanForUser(['id' => $userId, 'email' => $email]);

        $originalPriceCents = isset($course['price_cents']) ? (int)$course['price_cents'] : 0;
        $finalPriceCents = $this->applyCoursePlanDiscountCents($originalPriceCents, $plan);
        
        // Se for cadastro gratuito (curso sem preço ou preço zero)
        if ($finalPriceCents <= 0) {
            // Envia email com credenciais
            try {
                $companyName = (string)($branding['company_name'] ?? '');
                $logoUrl = (string)($branding['logo_url'] ?? '');
                $greeting = $name !== '' ? $name : 'cliente';
                $loginPath = $slug !== '' ? '/curso/' . urlencode($slug) . '/login' : '/login';
                $loginUrl = $this->buildExternalBaseUrl() . $loginPath;
                $content = '<p style="font-size:13px; color:#b0b0b0; line-height:1.55;">Sua conta foi criada com sucesso!</p>'
                    . '<p style="font-size:13px; color:#b0b0b0; line-height:1.55;">Dados de acesso:</p>'
                    . '<div style="font-size:13px; color:#f5f5f5; line-height:1.55; border:1px solid #272727; border-radius:12px; padding:10px 12px; background:#050509;">'
                    . '<div><b>E-mail:</b> ' . htmlspecialchars($email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</div>'
                    . '<div><b>Senha:</b> ' . htmlspecialchars($password, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</div>'
                    . '</div>'
                    . '<p style="font-size:12px; color:#777; line-height:1.55; margin-top:10px;">Guarde esta senha em local seguro.</p>';

                $subject = ($companyName !== '' ? $companyName . ' - ' : '') . 'Bem-vindo!';
                $body = MailService::buildDefaultTemplate($greeting, $content, 'Acessar Painel', $loginUrl, $logoUrl);
                MailService::send($email, $name, $subject, $body);
            } catch (\Throwable $eMail) {
            }
            
            // Redireciona para o painel externo
            header('Location: /painel-externo');
            exit;
        }

        $billingType = isset($_POST['billing_type']) ? (string)$_POST['billing_type'] : 'PIX';
        if (!in_array($billingType, ['PIX', 'BOLETO', 'CREDIT_CARD'], true)) {
            $billingType = 'PIX';
        }

        $purchaseId = CoursePurchase::create([
            'user_id' => (int)$userId,
            'course_id' => (int)($course['id'] ?? 0),
            'amount_cents' => $finalPriceCents,
            'billing_type' => $billingType,
            'asaas_payment_id' => null,
            'external_token' => $token,
            'redirect_after_payment' => 1,
            'status' => 'pending',
            'paid_at' => null,
        ]);

        $customer = [
            'name' => $name,
            'email' => $email,
            'cpfCnpj' => preg_replace('/\D+/', '', (string)($_POST['cpf'] ?? '')),
            'phone' => (string)($_POST['phone'] ?? ''),
            'postalCode' => preg_replace('/\D+/', '', (string)($_POST['postal_code'] ?? '')),
            'address' => trim((string)($_POST['address'] ?? '')),
            'addressNumber' => trim((string)($_POST['address_number'] ?? '')),
            'complement' => (string)($_POST['complement'] ?? ''),
            'province' => trim((string)($_POST['province'] ?? '')),
            'city' => trim((string)($_POST['city'] ?? '')),
            'state' => trim((string)($_POST['state'] ?? '')),
        ];

        try {
            $asaas = new AsaasClient();

            $customerResp = $asaas->createOrUpdateCustomer($customer);
            $customerId = $customerResp['id'] ?? null;
            if (!$customerId) {
                throw new \RuntimeException('Falha ao criar cliente no Asaas.');
            }

            $dueDate = date('Y-m-d');
            if ($billingType === 'BOLETO') {
                $dueDate = date('Y-m-d', strtotime('+3 days'));
            } elseif ($billingType === 'PIX') {
                $dueDate = date('Y-m-d', strtotime('+1 day'));
            }

            $payload = [
                'customer' => $customerId,
                'billingType' => $billingType,
                'value' => $finalPriceCents / 100,
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

            // Email com branding e senha em texto (conforme solicitado)
            try {
                $companyName = (string)($branding['company_name'] ?? '');
                $logoUrl = (string)($branding['logo_url'] ?? '');
                $greeting = $name !== '' ? $name : 'cliente';
                $loginPath = $slug !== '' ? '/curso/' . urlencode($slug) . '/login' : '/login';
                $loginUrl = $this->buildExternalBaseUrl() . $loginPath;
                $content = '<p style="font-size:13px; color:#b0b0b0; line-height:1.55;">Sua conta foi criada para acessar o curso <b>' . htmlspecialchars((string)($course['title'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</b>.</p>'
                    . '<p style="font-size:13px; color:#b0b0b0; line-height:1.55;">Dados de acesso:</p>'
                    . '<div style="font-size:13px; color:#f5f5f5; line-height:1.55; border:1px solid #272727; border-radius:12px; padding:10px 12px; background:#050509;">'
                    . '<div><b>E-mail:</b> ' . htmlspecialchars($email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</div>'
                    . '<div><b>Senha:</b> ' . htmlspecialchars($password, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</div>'
                    . '</div>'
                    . '<p style="font-size:12px; color:#777; line-height:1.55; margin-top:10px;">Guarde esta senha em local seguro.</p>';

                $subject = ($companyName !== '' ? $companyName . ' - ' : '') . 'Acesso ao curso: ' . (string)($course['title'] ?? '');
                $body = MailService::buildDefaultTemplate($greeting, $content, 'Entrar', $loginUrl, $logoUrl);
                MailService::send($email, $name, $subject, $body);
            } catch (\Throwable $eMail) {
            }

            $_SESSION['external_purchase_id'] = $purchaseId;

            $this->view('external_courses/awaiting_payment', [
                'pageTitle' => 'Aguardando pagamento',
                'course' => $course,
                'branding' => $branding,
                'token' => $token,
                'slug' => $slug,
                'isPartnerSite' => $this->isPartnerSite(),
                'purchaseId' => $purchaseId,
                'billingType' => $billingType,
                'amountReais' => $finalPriceCents / 100,
                'paymentUrl' => $redirectUrl ?? null,
                'layout' => 'external_course',
                'hideTopbarAction' => true,
                'brandSubtitle' => 'Pagamento',
            ]);
            return;
        } catch (\Throwable $e) {
            $this->view('external_courses/checkout', [
                'pageTitle' => 'Comprar: ' . (string)($course['title'] ?? 'Curso'),
                'course' => $course,
                'branding' => $branding,
                'token' => $token,
                'slug' => $slug,
                'isPartnerSite' => $this->isPartnerSite(),
                'savedCustomer' => null,
                'error' => 'Não consegui criar a cobrança para este curso. Tente novamente em alguns minutos.',
                'layout' => 'external_course',
            ]);
            return;
        }
    }

    public function registerFree(): void
    {
        $resolved = $this->resolveExternalCourseFromPost();
        $token = (string)$resolved['token'];
        $slug = (string)$resolved['slug'];
        $course = $resolved['course'];
        if (!$course) {
            header('Location: /');
            exit;
        }

        $branding = null;
        if (!empty($course['owner_user_id'])) {
            $branding = CoursePartnerBranding::findByUserId((int)$course['owner_user_id']);
        }

        $firstName = trim((string)($_POST['first_name'] ?? ''));
        $lastName = trim((string)($_POST['last_name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($firstName === '' || $lastName === '' || $email === '' || $password === '') {
            header('Location: ' . ($slug !== '' ? '/curso/' . urlencode($slug) : '/'));
            exit;
        }

        if (strlen($password) < 8) {
            header('Location: ' . ($slug !== '' ? '/curso/' . urlencode($slug) : '/'));
            exit;
        }

        $existingUser = User::findByEmail($email);
        if ($existingUser) {
            // Usuário já existe, redireciona para login
            if ($slug !== '') {
                header('Location: /curso/' . urlencode($slug) . '/login');
            } else {
                header('Location: /login');
            }
            exit;
        }

        $name = trim($firstName . ' ' . $lastName);
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $userId = User::createUser($name, $email, $hash);

        // Marca como verificado para permitir login imediato
        try {
            User::setEmailVerifiedAt($userId, date('Y-m-d H:i:s'));
        } catch (\Throwable $e) {
        }

        // Marca como usuário de curso externo
        $partnerId = !empty($course['owner_user_id']) ? (int)$course['owner_user_id'] : 0;
        if ($partnerId > 0) {
            User::markAsExternalCourseUser($userId, $partnerId);
        }

        // Se o curso for gratuito, matricula automaticamente
        $priceCents = isset($course['price_cents']) ? (int)$course['price_cents'] : 0;
        if ($priceCents <= 0) {
            $courseId = (int)($course['id'] ?? 0);
            if ($courseId > 0) {
                CourseEnrollment::enroll($courseId, $userId);
            }
        }

        // Envia email com credenciais
        try {
            $companyName = (string)($branding['company_name'] ?? '');
            $logoUrl = (string)($branding['logo_url'] ?? '');
            $greeting = $name !== '' ? $name : 'cliente';
            $loginPath = $slug !== '' ? '/curso/' . urlencode($slug) . '/login' : '/login';
            $loginUrl = $this->buildExternalBaseUrl() . $loginPath;
            $content = '<p style="font-size:13px; color:#b0b0b0; line-height:1.55;">Sua conta foi criada com sucesso!</p>'
                . '<p style="font-size:13px; color:#b0b0b0; line-height:1.55;">Dados de acesso:</p>'
                . '<div style="font-size:13px; color:#f5f5f5; line-height:1.55; border:1px solid #272727; border-radius:12px; padding:10px 12px; background:#050509;">'
                . '<div><b>E-mail:</b> ' . htmlspecialchars($email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</div>'
                . '<div><b>Senha:</b> ' . htmlspecialchars($password, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</div>'
                . '</div>'
                . '<p style="font-size:12px; color:#777; line-height:1.55; margin-top:10px;">Guarde esta senha em local seguro.</p>';

            $subject = ($companyName !== '' ? $companyName . ' - ' : '') . 'Bem-vindo!';
            $body = MailService::buildDefaultTemplate($greeting, $content, 'Acessar Painel', $loginUrl, $logoUrl);
            MailService::send($email, $name, $subject, $body);
        } catch (\Throwable $eMail) {
        }

        // Login automático
        $_SESSION['user_id'] = (int)$userId;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['external_course_token'] = $token;
        unset($_SESSION['is_admin']);

        // Redireciona para o painel externo
        header('Location: /painel-externo');
        exit;
    }

    public function members(): void
    {
        $resolved = $this->resolveExternalCourseFromGet();
        $token = (string)$resolved['token'];
        $slug = (string)$resolved['slug'];
        $course = $resolved['course'];
        if (!$course) {
            http_response_code(404);
            echo 'Curso não encontrado';
            return;
        }

        $user = $this->requireLogin();

        if (!$this->canAccessExternalCourse($course, $user)) {
            if ($slug !== '') {
                header('Location: /curso/' . urlencode($slug));
            } else {
                header('Location: /');
            }
            exit;
        }

        $branding = null;
        if (!empty($course['owner_user_id'])) {
            $branding = CoursePartnerBranding::findByUserId((int)$course['owner_user_id']);
        }

        $lessons = CourseLesson::allByCourseId((int)($course['id'] ?? 0));
        $firstLessonId = 0;
        foreach ($lessons as $l) {
            if (empty($l['is_published'])) {
                continue;
            }
            $lid = (int)($l['id'] ?? 0);
            if ($lid > 0) {
                $firstLessonId = $lid;
                break;
            }
        }

        $this->view('external_courses/members', [
            'pageTitle' => (string)($branding['company_name'] ?? ($course['title'] ?? 'Curso')),
            'course' => $course,
            'branding' => $branding,
            'token' => $token,
            'slug' => $slug,
            'isPartnerSite' => $this->isPartnerSite(),
            'firstLessonId' => $firstLessonId,
            'layout' => 'external_course',
        ]);
    }

    public function lesson(): void
    {
        $resolved = $this->resolveExternalCourseFromGet();
        $token = (string)$resolved['token'];
        $slug = (string)$resolved['slug'];
        $course = $resolved['course'];
        $lessonId = isset($_GET['lesson_id']) ? (int)$_GET['lesson_id'] : 0;
        if ($lessonId <= 0 || !$course) {
            header('Location: /');
            exit;
        }

        $lesson = CourseLesson::findById($lessonId);
        if (!$lesson || empty($lesson['is_published']) || (int)($lesson['course_id'] ?? 0) !== (int)($course['id'] ?? 0)) {
            http_response_code(404);
            echo 'Aula não encontrada';
            return;
        }

        $user = $this->requireLogin();
        if (!$this->canAccessExternalCourse($course, $user)) {
            if ($slug !== '') {
                header('Location: /curso/' . urlencode($slug));
            } else {
                header('Location: /');
            }
            exit;
        }

        $branding = null;
        if (!empty($course['owner_user_id'])) {
            $branding = CoursePartnerBranding::findByUserId((int)$course['owner_user_id']);
        }

        $courseId = (int)($course['id'] ?? 0);
        $userId = (int)($user['id'] ?? 0);

        $lessons = CourseLesson::allByCourseId($courseId);
        $completedLessonIds = CourseLessonProgress::completedLessonIdsByUserAndCourse($courseId, $userId);
        $lessonComments = CourseLessonComment::allByLessonWithUser($lessonId);

        $prevLessonId = 0;
        $nextLessonId = 0;
        $publishedLessonIds = [];
        foreach ($lessons as $l) {
            if (empty($l['is_published'])) {
                continue;
            }
            $lid = (int)($l['id'] ?? 0);
            if ($lid > 0) {
                $publishedLessonIds[] = $lid;
            }
        }
        $count = count($publishedLessonIds);
        for ($i = 0; $i < $count; $i++) {
            if ($publishedLessonIds[$i] === $lessonId) {
                if ($i - 1 >= 0) {
                    $prevLessonId = (int)$publishedLessonIds[$i - 1];
                }
                if ($i + 1 < $count) {
                    $nextLessonId = (int)$publishedLessonIds[$i + 1];
                }
                break;
            }
        }

        $this->view('external_courses/lesson_player', [
            'pageTitle' => 'Aula: ' . (string)($lesson['title'] ?? ''),
            'course' => $course,
            'lesson' => $lesson,
            'lessons' => $lessons,
            'completedLessonIds' => $completedLessonIds,
            'lessonComments' => $lessonComments,
            'token' => $token,
            'slug' => $slug,
            'isPartnerSite' => $this->isPartnerSite(),
            'prevLessonId' => $prevLessonId,
            'nextLessonId' => $nextLessonId,
            'layout' => 'external_course',
            'branding' => $branding,
            'user' => $user,
        ]);
    }

    public function completeLesson(): void
    {
        $user = $this->requireLogin();

        $resolved = $this->resolveExternalCourseFromPost();
        $token = (string)$resolved['token'];
        $slug = (string)$resolved['slug'];
        $course = $resolved['course'];
        $lessonId = isset($_POST['lesson_id']) ? (int)$_POST['lesson_id'] : 0;
        if ($lessonId <= 0 || !$course) {
            header('Location: /');
            exit;
        }

        if (!$this->canAccessExternalCourse($course, $user)) {
            if ($slug !== '') {
                header('Location: /curso/' . urlencode($slug));
            } else {
                header('Location: /');
            }
            exit;
        }

        $lesson = CourseLesson::findById($lessonId);
        if (!$lesson || empty($lesson['is_published']) || (int)($lesson['course_id'] ?? 0) !== (int)($course['id'] ?? 0)) {
            if ($slug !== '') {
                header('Location: /curso/' . urlencode($slug) . '/membros');
            } else {
                header('Location: /');
            }
            exit;
        }

        CourseLessonProgress::markCompleted((int)($course['id'] ?? 0), $lessonId, (int)($user['id'] ?? 0));
        if ($slug !== '') {
            header('Location: /curso/' . urlencode($slug) . '/aula?lesson_id=' . $lessonId);
        } else {
            header('Location: /');
        }
        exit;
    }

    public function commentLesson(): void
    {
        $user = $this->requireLogin();

        $resolved = $this->resolveExternalCourseFromPost();
        $token = (string)$resolved['token'];
        $slug = (string)$resolved['slug'];
        $course = $resolved['course'];
        $lessonId = isset($_POST['lesson_id']) ? (int)$_POST['lesson_id'] : 0;
        $body = trim((string)($_POST['body'] ?? ''));
        if ($lessonId <= 0 || $body === '' || !$course) {
            header('Location: /');
            exit;
        }

        if (!$this->canAccessExternalCourse($course, $user)) {
            if ($slug !== '') {
                header('Location: /curso/' . urlencode($slug));
            } else {
                header('Location: /');
            }
            exit;
        }

        $lesson = CourseLesson::findById($lessonId);
        if (!$lesson || empty($lesson['is_published']) || (int)($lesson['course_id'] ?? 0) !== (int)($course['id'] ?? 0)) {
            if ($slug !== '') {
                header('Location: /curso/' . urlencode($slug) . '/membros');
            } else {
                header('Location: /');
            }
            exit;
        }

        CourseLessonComment::create([
            'course_id' => (int)($course['id'] ?? 0),
            'lesson_id' => $lessonId,
            'user_id' => (int)($user['id'] ?? 0),
            'body' => $body,
        ]);

        if ($slug !== '') {
            header('Location: /curso/' . urlencode($slug) . '/aula?lesson_id=' . $lessonId);
        } else {
            header('Location: /');
        }
        exit;
    }

    public function checkPaymentStatus(): void
    {
        $purchaseId = isset($_GET['purchase_id']) ? (int)$_GET['purchase_id'] : 0;
        
        if ($purchaseId <= 0) {
            $this->json(['status' => 'error', 'message' => 'ID de compra inválido'], 400);
            return;
        }

        $purchase = CoursePurchase::findById($purchaseId);
        
        if (!$purchase) {
            $this->json(['status' => 'error', 'message' => 'Compra não encontrada'], 404);
            return;
        }

        if ($purchase['status'] === 'paid') {
            $redirectUrl = '/painel-externo/meus-cursos';
            
            $this->json([
                'status' => 'paid',
                'redirect' => $redirectUrl,
            ]);
            return;
        }

        $this->json(['status' => 'pending']);
    }
}
