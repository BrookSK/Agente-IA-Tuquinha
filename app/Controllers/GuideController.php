<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;

class GuideController extends Controller
{
    private function requirePaidSubscriber(): array
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        if (!empty($_SESSION['is_admin'])) {
            return ['id' => (int)$_SESSION['user_id']];
        }

        $user = User::findById((int)$_SESSION['user_id']);
        if (!$user || empty($user['email'])) {
            header('Location: /login');
            exit;
        }

        $subscription = Subscription::findLastByEmail((string)$user['email']);
        if (!$subscription || empty($subscription['plan_id'])) {
            header('Location: /planos');
            exit;
        }

        $status = strtolower((string)($subscription['status'] ?? ''));
        if (in_array($status, ['canceled', 'expired'], true)) {
            header('Location: /planos');
            exit;
        }

        $plan = Plan::findById((int)$subscription['plan_id']);
        $slug = is_array($plan) ? (string)($plan['slug'] ?? '') : '';
        if ($slug === '' || $slug === 'free') {
            header('Location: /planos');
            exit;
        }

        return $user;
    }

    private function serveGuideFile(string $absolutePath): void
    {
        if (!is_file($absolutePath)) {
            http_response_code(404);
            echo '404 - Guia não encontrado';
            exit;
        }

        header('Content-Type: text/html; charset=utf-8');
        readfile($absolutePath);
        exit;
    }

    public function metodologia(): void
    {
        $this->requirePaidSubscriber();
        $this->serveGuideFile(__DIR__ . '/../../public/guias/metodologia.html');
    }

    public function projetoDeMarca(): void
    {
        $this->requirePaidSubscriber();
        $this->serveGuideFile(__DIR__ . '/../../public/guias/guia-projeto-de-marca.html');
    }
}
