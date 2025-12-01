<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Plan;

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

        if (!empty($user['email'])) {
            $subscription = Subscription::findLastByEmail($user['email']);
            if ($subscription) {
                $plan = Plan::findById((int)$subscription['plan_id']);
            }
        }

        $this->view('account/index', [
            'pageTitle' => 'Minha conta',
            'user' => $user,
            'subscription' => $subscription,
            'plan' => $plan,
            'error' => null,
            'success' => null,
        ]);
    }

    public function updateProfile(): void
    {
        $user = $this->requireLogin();

        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            $this->reloadWithMessages($user, 'Nome não pode ficar em branco.', null);
            return;
        }

        User::updateName((int)$user['id'], $name);
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
            }
        }

        $this->view('account/index', [
            'pageTitle' => 'Minha conta',
            'user' => $user,
            'subscription' => $subscription,
            'plan' => $plan,
            'error' => $error,
            'success' => $success,
        ]);
    }
}
