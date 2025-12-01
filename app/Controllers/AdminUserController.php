<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

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
}
