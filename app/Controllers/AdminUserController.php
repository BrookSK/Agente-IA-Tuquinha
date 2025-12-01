<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Plan;

class AdminUserController extends Controller
{
    private function ensureAdmin(): void
    {
        if (empty($_SESSION['is_admin'])) {
            header('Location: /admin/login');
            exit;
        }
    }

    public function index(): void
    {
        $this->ensureAdmin();

        $query = trim($_GET['q'] ?? '');
        $users = $query !== '' ? User::search($query) : User::all();

        $this->view('admin/usuarios/index', [
            'pageTitle' => 'Usuários do sistema',
            'users' => $users,
            'query' => $query,
        ]);
    }

    public function show(): void
    {
        $this->ensureAdmin();

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            header('Location: /admin/usuarios');
            exit;
        }

        $user = User::findById($id);
        if (!$user) {
            header('Location: /admin/usuarios');
            exit;
        }

        $lastSub = Subscription::findLastByEmail($user['email']);
        $plan = null;
        if ($lastSub && !empty($lastSub['plan_id'])) {
            $plan = Plan::findById((int)$lastSub['plan_id']);
        }

        $this->view('admin/usuarios/show', [
            'pageTitle' => 'Detalhes do usuário',
            'user' => $user,
            'subscription' => $lastSub,
            'plan' => $plan,
        ]);
    }

    public function toggleActive(): void
    {
        $this->ensureAdmin();

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $value = isset($_POST['value']) ? (int)$_POST['value'] : 0;

        if ($id > 0) {
            User::setActive($id, $value === 1);
        }

        header('Location: /admin/usuarios/ver?id=' . $id);
        exit;
    }
}
