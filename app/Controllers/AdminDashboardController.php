<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Plan;

class AdminDashboardController extends Controller
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

        $totalUsers = User::countAll();
        $totalAdmins = User::countAdmins();
        $totalPlans = Plan::countAll();
        $subsByStatus = Subscription::countByStatus();
        $activeRevenueCents = Subscription::sumActiveRevenueCents();

        $this->view('admin/dashboard/index', [
            'pageTitle' => 'Dashboard do administrador',
            'totalUsers' => $totalUsers,
            'totalAdmins' => $totalAdmins,
            'totalPlans' => $totalPlans,
            'subsByStatus' => $subsByStatus,
            'activeRevenueCents' => $activeRevenueCents,
        ]);
    }
}
