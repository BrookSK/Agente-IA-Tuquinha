<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Services\MailService;
use App\Core\Database;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        $this->view('auth/login', [
            'pageTitle' => 'Entrar - Tuquinha',
            'error' => null,
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
            ]);
            return;
        }

        $user = User::findByEmail($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->view('auth/login', [
                'pageTitle' => 'Entrar - Tuquinha',
                'error' => 'E-mail ou senha inválidos.',
            ]);
            return;
        }

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];

        // Marca sessão como admin se o usuário tiver is_admin = 1 no banco
        if (!empty($user['is_admin'])) {
            $_SESSION['is_admin'] = true;
        } else {
            unset($_SESSION['is_admin']);
        }

        $redirectPlan = $_SESSION['pending_plan_slug'] ?? null;
        unset($_SESSION['pending_plan_slug']);

        if ($redirectPlan) {
            header('Location: /checkout?plan=' . urlencode($redirectPlan));
        } else {
            header('Location: /');
        }
        exit;
    }

    public function showRegister(): void
    {
        $this->view('auth/register', [
            'pageTitle' => 'Criar conta - Tuquinha',
            'error' => null,
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

        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;

        $redirectPlan = $_SESSION['pending_plan_slug'] ?? null;
        unset($_SESSION['pending_plan_slug']);

        if ($redirectPlan) {
            header('Location: /checkout?plan=' . urlencode($redirectPlan));
        } else {
            header('Location: /');
        }
        exit;
    }

    public function logout(): void
    {
        unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email'], $_SESSION['is_admin']);
        header('Location: /');
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
          <div style="font-weight:700; font-size:15px;">Agente IA - Tuquinha</div>
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
}
