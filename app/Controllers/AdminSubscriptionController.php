<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Subscription;

class AdminSubscriptionController extends Controller
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

        $status = $_GET['status'] ?? '';
        $subs = Subscription::allWithPlanAndStatus($status);

        $this->view('admin/assinaturas/index', [
            'pageTitle' => 'Assinaturas',
            'subscriptions' => $subs,
            'status' => $status,
        ]);
    }
}
