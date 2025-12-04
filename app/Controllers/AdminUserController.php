<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Plan;
use App\Models\TokenTopup;
use App\Models\CoursePartner;

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

        // Anexa informação de último pagamento (última assinatura) por usuário
        foreach ($users as &$u) {
            $email = $u['email'] ?? '';
            $lastPaymentAt = '';
            if ($email !== '') {
                $sub = Subscription::findLastByEmail($email);
                if ($sub) {
                    $lastPaymentAt = $sub['started_at'] ?? ($sub['created_at'] ?? '');
                }
            }
            $u['last_payment_at'] = $lastPaymentAt;
        }
        unset($u);

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

        $coursePartner = CoursePartner::findByUserId((int)$user['id']);

        $lastSub = Subscription::findLastByEmail($user['email']);
        $plan = null;
        if ($lastSub && !empty($lastSub['plan_id'])) {
            $plan = Plan::findById((int)$lastSub['plan_id']);
        }

        // Histórico completo de planos (assinaturas) e créditos de tokens avulsos
        $subscriptionsHistory = Subscription::allByEmailWithPlan($user['email']);
        $topups = TokenTopup::allByUserId((int)$user['id']);

        $timeline = [];

        foreach ($subscriptionsHistory as $s) {
            $date = $s['started_at'] ?? ($s['created_at'] ?? '');
            $timeline[] = [
                'type' => 'subscription',
                'date' => $date,
                'raw' => $s,
            ];
        }

        foreach ($topups as $t) {
            $date = $t['paid_at'] ?? ($t['created_at'] ?? '');
            $timeline[] = [
                'type' => 'topup',
                'date' => $date,
                'raw' => $t,
            ];
        }

        usort($timeline, static function (array $a, array $b): int {
            return strcmp((string)($a['date'] ?? ''), (string)($b['date'] ?? ''));
        });

        $this->view('admin/usuarios/show', [
            'pageTitle' => 'Detalhes do usuário',
            'user' => $user,
            'subscription' => $lastSub,
            'plan' => $plan,
            'timeline' => $timeline,
            'coursePartner' => $coursePartner,
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

    public function toggleAdmin(): void
    {
        $this->ensureAdmin();

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $value = isset($_POST['value']) ? (int)$_POST['value'] : 0;

        if ($id > 0) {
            User::setAdmin($id, $value === 1);
        }

        header('Location: /admin/usuarios/ver?id=' . $id);
        exit;
    }

    public function toggleProfessor(): void
    {
        $this->ensureAdmin();

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $value = isset($_POST['value']) ? (int)$_POST['value'] : 0;

        if ($id > 0) {
            if ($value === 1) {
                $existing = CoursePartner::findByUserId($id);
                if (!$existing) {
                    CoursePartner::create([
                        'user_id' => $id,
                        'default_commission_percent' => 0.0,
                    ]);
                }
            } else {
                CoursePartner::deleteByUserId($id);
            }
        }

        header('Location: /admin/usuarios/ver?id=' . $id);
        exit;
    }
}
