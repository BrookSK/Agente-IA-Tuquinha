<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\UserReferral;
use App\Services\MailService;
use App\Core\Database;
use App\Models\EmailVerification;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        $this->view('auth/login', [
            'pageTitle' => 'Entrar - Tuquinha',
            'error' => null,
            'showVerifyLink' => false,
        ]);
    }

    public function login(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            $this->view('auth/login', [
                'pageTitle' => 'Entrar - Tuquinha',
                'error' => 'Informe seu e-mail e senha.',
                'showVerifyLink' => false,
            ]);
            return;
        }

        $user = User::findByEmail($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->view('auth/login', [
                'pageTitle' => 'Entrar - Tuquinha',
                'error' => 'E-mail ou senha inválidos.',
                'showVerifyLink' => false,
            ]);
            return;
        }

        // Apenas usuários não-admin precisam ter o e-mail verificado para entrar
        if (empty($user['is_admin']) && empty($user['email_verified_at'])) {
            $_SESSION['pending_verify_user_id'] = (int)$user['id'];
            $_SESSION['pending_verify_email'] = $user['email'];

            $this->view('auth/login', [
                'pageTitle' => 'Entrar - Tuquinha',
                'error' => 'Antes de entrar, confirme seu e-mail. Enviamos um código de verificação para você.',
                'showVerifyLink' => true,
            ]);
            return;
        }

        if (isset($user['is_active']) && (int)$user['is_active'] === 0) {
            $this->view('auth/login', [
                'pageTitle' => 'Entrar - Tuquinha',
                'error' => 'Sua conta foi desativada por tempo indeterminado. Se você não solicitou isso ou não sabe o motivo, <a href="/suporte" style="color:#ffcc80; text-decoration:none;">fale com o suporte</a> para revisar o seu caso.',
                'showVerifyLink' => false,
            ]);
            return;
        }

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        if (isset($user['default_persona_id']) && $user['default_persona_id']) {
            $_SESSION['default_persona_id'] = (int)$user['default_persona_id'];
        } else {
            unset($_SESSION['default_persona_id']);
        }
        if (isset($user['default_persona_id']) && $user['default_persona_id']) {
            $_SESSION['default_persona_id'] = (int)$user['default_persona_id'];
        } else {
            unset($_SESSION['default_persona_id']);
        }

        // Marca sessão como admin se o usuário tiver is_admin = 1 no banco
        if (!empty($user['is_admin'])) {
            $_SESSION['is_admin'] = true;

            // Admin sempre usa o plano mais "top" disponível
            $topPlan = Plan::findTopActive();
            if ($topPlan && !empty($topPlan['slug'])) {
                $_SESSION['plan_slug'] = $topPlan['slug'];
            }
        } else {
            unset($_SESSION['is_admin']);

            // Usuário comum sempre começa com o plano padrão (free ou equivalente)
            $defaultPlan = Plan::findDefaultForUsers();
            if ($defaultPlan && !empty($defaultPlan['slug'])) {
                $_SESSION['plan_slug'] = $defaultPlan['slug'];
            } else {
                unset($_SESSION['plan_slug']);
            }

            try {
                $sub = Subscription::findLastByEmail((string)$user['email']);
                if ($sub && !empty($sub['plan_id'])) {
                    $planFromSub = Plan::findById((int)$sub['plan_id']);
                    if ($planFromSub && !empty($planFromSub['slug'])) {
                        $status = strtolower((string)($sub['status'] ?? ''));
                        $slug = (string)$planFromSub['slug'];
                        if ($slug !== 'free' && !in_array($status, ['canceled', 'expired'], true)) {
                            $_SESSION['plan_slug'] = $slug;
                        }
                    }
                }
            } catch (\Throwable $e) {
            }
        }

        // Restaura indicação pendente do banco para manter o benefício mesmo após reload/logout/login
        try {
            $pendingReferralDb = UserReferral::findFirstPendingForUser((int)$user['id']);
            if ($pendingReferralDb && !empty($pendingReferralDb['plan_id']) && !empty($pendingReferralDb['referrer_user_id'])) {
                $pendingPlan = Plan::findById((int)$pendingReferralDb['plan_id']);
                if ($pendingPlan && !empty($pendingPlan['slug'])) {
                    $_SESSION['pending_referral'] = [
                        'referrer_user_id' => (int)$pendingReferralDb['referrer_user_id'],
                        'plan_id' => (int)$pendingReferralDb['plan_id'],
                        'plan_slug' => (string)$pendingPlan['slug'],
                    ];

                    // Garante que o fluxo continue apontando para o checkout do plano indicado
                    $_SESSION['pending_plan_slug'] = (string)$pendingPlan['slug'];
                }
            }
        } catch (\Throwable $e) {
            // Se falhar, não bloqueia login
        }

        $redirectCourseId = $_SESSION['pending_course_id'] ?? null;
        $redirectPlan = $_SESSION['pending_plan_slug'] ?? null;
        unset($_SESSION['pending_course_id'], $_SESSION['pending_plan_slug']);

        if (!empty($user['is_external_course_user'])) {
            header('Location: /painel-externo');
            exit;
        }

        if ($redirectCourseId) {
            header('Location: /cursos/comprar?course_id=' . (int)$redirectCourseId);
        } elseif ($redirectPlan) {
            header('Location: /checkout?plan=' . urlencode($redirectPlan));
        } else {
            header('Location: /');
        }
        exit;
    }

    public function showRegister(): void
    {
        $referralPlan = null;

        $refCode = isset($_GET['ref']) ? trim((string)$_GET['ref']) : '';
        $planSlug = isset($_GET['plan']) ? trim((string)$_GET['plan']) : '';

        if ($refCode !== '' && $planSlug !== '') {
            $plan = Plan::findBySlug($planSlug);
            $referrer = User::findByReferralCode($refCode);

            if ($plan && $referrer && !empty($plan['referral_enabled']) && !empty($referrer['email'])) {
                // Verifica se o usuário que indicou é assinante ativo deste mesmo plano
                $minDays = isset($plan['referral_min_active_days']) ? (int)$plan['referral_min_active_days'] : 0;
                $eligible = false;

                try {
                    $subs = Subscription::allByEmailWithPlan($referrer['email']);
                    $now = new \DateTimeImmutable('now');

                    foreach ($subs as $s) {
                        if ((int)($s['plan_id'] ?? 0) !== (int)$plan['id']) {
                            continue;
                        }
                        if ((string)($s['status'] ?? '') !== 'active') {
                            continue;
                        }
                        if (empty($s['created_at'])) {
                            continue;
                        }

                        try {
                            $createdAt = new \DateTimeImmutable($s['created_at']);
                            $days = (int)$now->diff($createdAt)->days;
                            if ($days >= $minDays) {
                                $eligible = true;
                                break;
                            }
                        } catch (\Throwable $e) {
                            continue;
                        }
                    }
                } catch (\Throwable $e) {
                    // Se houver erro ao ler assinaturas, não considera a indicação
                }

                if ($eligible) {
                    $_SESSION['pending_referral'] = [
                        'referrer_user_id' => (int)$referrer['id'],
                        'plan_id' => (int)$plan['id'],
                        'plan_slug' => (string)$plan['slug'],
                    ];

                    // Garante que, depois de confirmar o e-mail, o usuário seja levado para o checkout deste plano
                    $_SESSION['pending_plan_slug'] = (string)$plan['slug'];
                    $referralPlan = $plan;
                } else {
                    unset($_SESSION['pending_referral']);
                }
            } else {
                unset($_SESSION['pending_referral']);
            }
        }

        $this->view('auth/register', [
            'pageTitle' => 'Criar conta - Tuquinha',
            'error' => null,
            'referralPlan' => $referralPlan,
        ]);
    }

    public function register(): void
    {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $passwordConfirm = (string)($_POST['password_confirmation'] ?? '');

        if ($name === '' || $email === '' || $password === '' || $passwordConfirm === '') {
            $this->view('auth/register', [
                'pageTitle' => 'Criar conta - Tuquinha',
                'error' => 'Preencha todos os campos.',
            ]);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->view('auth/register', [
                'pageTitle' => 'Criar conta - Tuquinha',
                'error' => 'E-mail inválido.',
            ]);
            return;
        }

        if ($password !== $passwordConfirm) {
            $this->view('auth/register', [
                'pageTitle' => 'Criar conta - Tuquinha',
                'error' => 'As senhas não conferem.',
            ]);
            return;
        }

        if (User::findByEmail($email)) {
            $this->view('auth/register', [
                'pageTitle' => 'Criar conta - Tuquinha',
                'error' => 'Já existe uma conta com esse e-mail.',
            ]);
            return;
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $userId = User::createUser($name, $email, $hash);

        // Marca que este usuário acabou de se cadastrar (para exibir onboarding/tour após confirmar e-mail)
        $_SESSION['tuq_just_registered_user_id'] = (int)$userId;

        // Se o cadastro veio de um link de indicação válido, registra a indicação pendente
        $pendingReferral = $_SESSION['pending_referral'] ?? null;
        if ($pendingReferral && !empty($pendingReferral['referrer_user_id']) && !empty($pendingReferral['plan_id'])) {
            $plan = Plan::findById((int)$pendingReferral['plan_id']);
            if ($plan && !empty($plan['referral_enabled'])) {
                $referrerId = (int)$pendingReferral['referrer_user_id'];
                if ($referrerId !== $userId) {
                    UserReferral::createPending($referrerId, (int)$plan['id'], $email, $userId);
                }
            }
        }

        // cria código de verificação de e-mail
        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = (new \DateTime('+30 minutes'))->format('Y-m-d H:i:s');
        EmailVerification::create($userId, $code, $expiresAt);

        // envia e-mail de boas-vindas com código
        $subject = 'Bem-vindo(a) ao Tuquinha - Confirme seu e-mail';
        $safeName = htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeCode = htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $body = <<<HTML
<html>
<body style="margin:0; padding:0; background:#050509; font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color:#f5f5f5;">
  <div style="width:100%; padding:24px 0;">
    <div style="max-width:520px; margin:0 auto; background:#111118; border-radius:16px; border:1px solid #272727; padding:18px 20px;">
      <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
        <div style="width:32px; height:32px; line-height:32px; border-radius:50%; background:radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%); text-align:center; font-weight:700; font-size:16px; color:#050509;">T</div>
        <div>
          <div style="font-weight:700; font-size:15px;">Resenha 2.0</div>
          <div style="font-size:11px; color:#b0b0b0;">Branding vivo na veia</div>
        </div>
      </div>

      <p style="font-size:14px; margin:0 0 10px 0;">Oi, {$safeName} 👋</p>
      <p style="font-size:14px; margin:0 0 10px 0;">Que bom te ver por aqui! Antes de começar a brincar com o Tuquinha, precisamos confirmar que este e-mail é seu.</p>
      <p style="font-size:14px; margin:0 0 10px 0;">Use o código abaixo na tela de confirmação para ativar sua conta:</p>

      <div style="text-align:center; margin:12px 0 16px 0;">
        <div style="display:inline-block; padding:10px 18px; border-radius:999px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:700; font-size:18px; letter-spacing:0.35em;">
          {$safeCode}
        </div>
      </div>

      <p style="font-size:12px; color:#b0b0b0; margin:0 0 8px 0;">Por segurança, esse código vale por <strong>30 minutos</strong>. Depois disso, é só pedir um código novo.</p>

      <p style="font-size:12px; color:#777; margin:0;">Se você não criou uma conta no Tuquinha, pode ignorar este e-mail.</p>
    </div>
  </div>
</body>
</html>
HTML;

        MailService::send($email, $name, $subject, $body);

        $_SESSION['pending_verify_user_id'] = $userId;
        $_SESSION['pending_verify_email'] = $email;

        $this->view('auth/verify_email', [
            'pageTitle' => 'Confirmar e-mail',
            'email' => $email,
            'error' => null,
            'success' => 'Enviamos um código de verificação para o seu e-mail.',
        ]);
        return;
    }

    public function logout(): void
    {
        $isExternalUser = !empty($_SESSION['user_id']) && User::isExternalCourseUser((int)$_SESSION['user_id']);
        $courseSlug = $_SESSION['external_course_slug'] ?? '';
        
        $isPartnerSite = !empty($_SERVER['TUQ_PARTNER_SITE']);
        
        unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email'], $_SESSION['is_admin'], $_SESSION['external_course_token']);
        
        if ($isPartnerSite) {
            header('Location: /');
        } elseif ($isExternalUser && $courseSlug !== '') {
            // Redireciona para a home do curso de onde o usuário veio
            header('Location: /curso/' . urlencode($courseSlug));
        } else {
            header('Location: /');
        }
        exit;
    }

    public function showForgotPassword(): void
    {
        $this->view('auth/forgot', [
            'pageTitle' => 'Esqueci minha senha',
            'error' => null,
            'success' => null,
        ]);
    }

    public function sendForgotPassword(): void
    {
        $email = trim($_POST['email'] ?? '');
        if ($email === '') {
            $this->view('auth/forgot', [
                'pageTitle' => 'Esqueci minha senha',
                'error' => 'Informe o e-mail da sua conta.',
                'success' => null,
            ]);
            return;
        }

        $user = User::findByEmail($email);
        if (!$user) {
            $this->view('auth/forgot', [
                'pageTitle' => 'Esqueci minha senha',
                'error' => 'Se existir uma conta com esse e-mail, você receberá um link para redefinir a senha.',
                'success' => null,
            ]);
            return;
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = (new \DateTime('+1 hour'))->format('Y-m-d H:i:s');

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)');
        $stmt->execute([
            'user_id' => $user['id'],
            'token' => $token,
            'expires_at' => $expiresAt,
        ]);

        $link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/senha/reset?token=' . urlencode($token);

        $subject = 'Redefinição de senha - Tuquinha';
        $body = '
<html>
<body style="margin:0; padding:0; background:#050509; font-family:system-ui, -apple-system, BlinkMacSystemFont, \'Segoe UI\', sans-serif; color:#f5f5f5;">
  <div style="width:100%; padding:24px 0;">
    <div style="max-width:520px; margin:0 auto; background:#111118; border-radius:16px; border:1px solid #272727; padding:18px 20px;">
      <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
        <div style="width:32px; height:32px; border-radius:50%; background:radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:16px; color:#050509;">T</div>
        <div>
          <div style="font-weight:700; font-size:15px;">Resenha 2.0</div>
          <div style="font-size:11px; color:#b0b0b0;">Branding vivo na veia</div>
        </div>
      </div>

      <p style="font-size:14px; margin:0 0 10px 0;">Oi, ' . htmlspecialchars($user['name']) . ' 👋</p>
      <p style="font-size:14px; margin:0 0 10px 0;">Recebemos um pedido para redefinir a senha da sua conta no Tuquinha.</p>
      <p style="font-size:14px; margin:0 0 14px 0;">Clique no botão abaixo para criar uma nova senha. Esse link vale por <strong>1 hora</strong>:</p>

      <div style="text-align:center; margin-bottom:14px;">
        <a href="' . htmlspecialchars($link) . '" style="display:inline-block; padding:9px 18px; border-radius:999px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:600; font-size:13px; text-decoration:none;">Redefinir minha senha</a>
      </div>

      <p style="font-size:12px; color:#b0b0b0; margin:0 0 8px 0;">Se o botão não funcionar, copie e cole este link no navegador:</p>
      <p style="font-size:11px; word-break:break-all; margin:0 0 14px 0;">
        <a href="' . htmlspecialchars($link) . '" style="color:#ff6f60; text-decoration:none;">' . htmlspecialchars($link) . '</a>
      </p>

      <p style="font-size:12px; color:#777; margin:0;">Se você não fez esse pedido, pode ignorar este e-mail com segurança.</p>
    </div>
  </div>
</body>
</html>';

        $sent = MailService::send($user['email'], $user['name'], $subject, $body);

        $this->view('auth/forgot', [
            'pageTitle' => 'Esqueci minha senha',
            'error' => $sent ? null : 'Não consegui enviar o e-mail agora. Verifique as configurações SMTP.',
            'success' => $sent ? 'Enviamos um link de redefinição para o seu e-mail, caso ele esteja cadastrado.' : null,
        ]);
    }

    public function showResetPassword(): void
    {
        $token = $_GET['token'] ?? '';
        if ($token === '') {
            http_response_code(400);
            echo 'Token inválido.';
            return;
        }

        $this->view('auth/reset', [
            'pageTitle' => 'Redefinir senha',
            'token' => $token,
            'error' => null,
        ]);
    }

    public function resetPassword(): void
    {
        $token = $_POST['token'] ?? '';
        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['password_confirmation'] ?? '');

        if ($token === '' || $password === '' || $confirm === '') {
            $this->view('auth/reset', [
                'pageTitle' => 'Redefinir senha',
                'token' => $token,
                'error' => 'Preencha todos os campos.',
            ]);
            return;
        }

        if ($password !== $confirm) {
            $this->view('auth/reset', [
                'pageTitle' => 'Redefinir senha',
                'token' => $token,
                'error' => 'A confirmação da senha não confere.',
            ]);
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM password_resets WHERE token = :token LIMIT 1');
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            $this->view('auth/reset', [
                'pageTitle' => 'Redefinir senha',
                'token' => $token,
                'error' => 'Token inválido ou expirado.',
            ]);
            return;
        }

        $now = new \DateTimeImmutable();
        if ($now > new \DateTimeImmutable($row['expires_at'])) {
            $this->view('auth/reset', [
                'pageTitle' => 'Redefinir senha',
                'token' => $token,
                'error' => 'Token expirado. Faça um novo pedido de redefinição.',
            ]);
            return;
        }

        $user = User::findById((int)$row['user_id']);
        if (!$user) {
            $this->view('auth/reset', [
                'pageTitle' => 'Redefinir senha',
                'token' => $token,
                'error' => 'Usuário não encontrado.',
            ]);
            return;
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        User::updatePassword((int)$user['id'], $hash);

        $pdo->prepare('DELETE FROM password_resets WHERE token = :token')->execute(['token' => $token]);

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];

        header('Location: /conta');
        exit;
    }

    public function showVerifyEmail(): void
    {
        $userId = $_SESSION['pending_verify_user_id'] ?? null;
        $email = $_SESSION['pending_verify_email'] ?? null;

        if (!$userId || !$email) {
            header('Location: /login');
            exit;
        }

        $this->view('auth/verify_email', [
            'pageTitle' => 'Confirmar e-mail',
            'email' => (string)$email,
            'error' => null,
            'success' => null,
        ]);
    }

    public function verifyEmail(): void
    {
        $userId = $_SESSION['pending_verify_user_id'] ?? null;
        $email = $_SESSION['pending_verify_email'] ?? null;
        $code = trim((string)($_POST['code'] ?? ''));

        if (!$userId || !$email) {
            header('Location: /login');
            exit;
        }

        if ($code === '') {
            $this->view('auth/verify_email', [
                'pageTitle' => 'Confirmar e-mail',
                'email' => (string)$email,
                'error' => 'Informe o código que enviamos para o seu e-mail.',
                'success' => null,
            ]);
            return;
        }

        $userId = (int)$userId;
        $verification = EmailVerification::findValidByUserAndCode($userId, $code);
        if (!$verification) {
            $this->view('auth/verify_email', [
                'pageTitle' => 'Confirmar e-mail',
                'email' => (string)$email,
                'error' => 'Código inválido ou expirado. Confira o código ou peça um novo.',
                'success' => null,
            ]);
            return;
        }

        // marca como usado e atualiza usuário
        EmailVerification::markUsed((int)$verification['id']);
        $now = (new \DateTime())->format('Y-m-d H:i:s');
        User::setEmailVerifiedAt($userId, $now);

        // carrega usuário atualizado
        $user = User::findById($userId);
        if (!$user) {
            unset($_SESSION['pending_verify_user_id'], $_SESSION['pending_verify_email']);
            header('Location: /login');
            exit;
        }

        // cria sessão de login
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];

        // Só ativa onboarding/tour automático quando o fluxo veio de um cadastro recém-criado
        $justRegisteredId = isset($_SESSION['tuq_just_registered_user_id']) ? (int)$_SESSION['tuq_just_registered_user_id'] : 0;
        if ($justRegisteredId > 0 && $justRegisteredId === (int)$user['id']) {
            $_SESSION['tuq_onboarding_tour'] = 1;
            unset($_SESSION['tuq_just_registered_user_id']);
        }
        if (isset($user['default_persona_id']) && $user['default_persona_id']) {
            $_SESSION['default_persona_id'] = (int)$user['default_persona_id'];
        } else {
            unset($_SESSION['default_persona_id']);
        }

        if (!empty($user['is_admin'])) {
            $_SESSION['is_admin'] = true;
            $topPlan = Plan::findTopActive();
            if ($topPlan && !empty($topPlan['slug'])) {
                $_SESSION['plan_slug'] = $topPlan['slug'];
            }
        } else {
            unset($_SESSION['is_admin']);

            // Usuário comum sempre começa com o plano padrão (free ou equivalente)
            $defaultPlan = Plan::findDefaultForUsers();
            if ($defaultPlan && !empty($defaultPlan['slug'])) {
                $_SESSION['plan_slug'] = $defaultPlan['slug'];
            } else {
                unset($_SESSION['plan_slug']);
            }

            try {
                $sub = Subscription::findLastByEmail((string)$user['email']);
                if ($sub && !empty($sub['plan_id'])) {
                    $planFromSub = Plan::findById((int)$sub['plan_id']);
                    if ($planFromSub && !empty($planFromSub['slug'])) {
                        $status = strtolower((string)($sub['status'] ?? ''));
                        $slug = (string)$planFromSub['slug'];
                        if ($slug !== 'free' && !in_array($status, ['canceled', 'expired'], true)) {
                            $_SESSION['plan_slug'] = $slug;
                        }
                    }
                }
            } catch (\Throwable $e) {
            }
        }

        unset($_SESSION['pending_verify_user_id'], $_SESSION['pending_verify_email']);

        $redirectCourseId = $_SESSION['pending_course_id'] ?? null;
        $redirectPlan = $_SESSION['pending_plan_slug'] ?? null;
        unset($_SESSION['pending_course_id'], $_SESSION['pending_plan_slug']);

        if (!empty($user['is_external_course_user'])) {
            header('Location: /painel-externo');
            exit;
        }

        if ($redirectCourseId) {
            header('Location: /cursos/comprar?course_id=' . (int)$redirectCourseId);
        } elseif ($redirectPlan) {
            header('Location: /checkout?plan=' . urlencode((string)$redirectPlan));
        } else {
            header('Location: /');
        }
        exit;
    }

    public function resendVerification(): void
    {
        $userId = $_SESSION['pending_verify_user_id'] ?? null;
        $email = $_SESSION['pending_verify_email'] ?? null;

        if (!$userId || !$email) {
            header('Location: /login');
            exit;
        }

        $user = User::findById((int)$userId);
        if (!$user) {
            unset($_SESSION['pending_verify_user_id'], $_SESSION['pending_verify_email']);
            header('Location: /login');
            exit;
        }

        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = (new \DateTime('+30 minutes'))->format('Y-m-d H:i:s');
        EmailVerification::create((int)$userId, $code, $expiresAt);

        $subject = 'Seu novo código para confirmar o e-mail - Tuquinha';
        $safeName = htmlspecialchars($user['name'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeCode = htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $body = <<<HTML
<html>
<body style="margin:0; padding:0; background:#050509; font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color:#f5f5f5;">
  <div style="width:100%; padding:24px 0;">
    <div style="max-width:520px; margin:0 auto; background:#111118; border-radius:16px; border:1px solid #272727; padding:18px 20px;">
      <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
        <div style="width:32px; height:32px; border-radius:50%; background:radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:16px; color:#050509;">T</div>
        <div>
          <div style="font-weight:700; font-size:15px;">Resenha 2.0</div>
          <div style="font-size:11px; color:#b0b0b0;">Branding vivo na veia</div>
        </div>
      </div>

      <p style="font-size:14px; margin:0 0 10px 0;">Oi, {$safeName} 👋</p>
      <p style="font-size:14px; margin:0 0 10px 0;">Aqui vai um novo código para você confirmar o seu e-mail no Tuquinha.</p>
      <p style="font-size:14px; margin:0 0 10px 0;">Digite o código abaixo na tela de confirmação:</p>

      <div style="text-align:center; margin:12px 0 16px 0;">
        <div style="display:inline-block; padding:10px 18px; border-radius:999px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:700; font-size:18px; letter-spacing:0.35em;">
          {$safeCode}
        </div>
      </div>

      <p style="font-size:12px; color:#b0b0b0; margin:0 0 8px 0;">Este código é válido por <strong>30 minutos</strong>.</p>
    </div>
  </div>
</body>
</html>
HTML;

        MailService::send((string)$email, $user['name'] ?? '', $subject, $body);

        $this->view('auth/verify_email', [
            'pageTitle' => 'Confirmar e-mail',
            'email' => (string)$email,
            'error' => null,
            'success' => 'Enviamos um novo código para o seu e-mail.',
        ]);
    }
}
