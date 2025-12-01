<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

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
        unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email']);
        header('Location: /');
        exit;
    }
}
