<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

class AdminAuthController extends Controller
{
    public function login(): void
    {
        $this->view('admin/login', [
            'pageTitle' => 'Login do admin',
            'error' => null,
        ]);
    }

    public function authenticate(): void
    {
        $emailOrUser = trim($_POST['username'] ?? '');
        $pass = trim($_POST['password'] ?? '');

        // Primeiro tenta autenticar via usuário admin no banco (email)
        if ($emailOrUser !== '' && $pass !== '') {
            $admin = User::findAdminByEmail($emailOrUser);
            if ($admin && password_verify($pass, $admin['password_hash'])) {
                if (isset($admin['is_active']) && (int)$admin['is_active'] === 0) {
                    $this->view('admin/login', [
                        'pageTitle' => 'Login do admin',
                        'error' => 'Este usuário admin está desativado.',
                    ]);
                    return;
                }

                $_SESSION['is_admin'] = true;
                $_SESSION['admin_email'] = $admin['email'];
                header('Location: /admin');
                exit;
            }
        }

        // Fallback para credenciais fixas caso queira manter
        if ($emailOrUser === ADMIN_USERNAME && $pass === ADMIN_PASSWORD) {
            $_SESSION['is_admin'] = true;
            $_SESSION['admin_email'] = ADMIN_USERNAME;
            header('Location: /admin/config');
            exit;
        }

        $this->view('admin/login', [
            'pageTitle' => 'Login do admin',
            'error' => 'Credenciais inválidas.',
        ]);
    }

    public function logout(): void
    {
        unset($_SESSION['is_admin']);
        header('Location: /');
        exit;
    }
}
