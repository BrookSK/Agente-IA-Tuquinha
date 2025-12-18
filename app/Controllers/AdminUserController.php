<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Plan;
use App\Models\TokenTopup;
use App\Models\CoursePartner;
use App\Models\UserReferral;
use App\Models\TokenTransaction;
use App\Services\AsaasClient;

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

        $asaasSub = null;
        if ($lastSub && !empty($lastSub['asaas_subscription_id'])) {
            try {
                $asaas = new AsaasClient();
                $asaasSub = $asaas->getSubscription((string)$lastSub['asaas_subscription_id']);
            } catch (\Throwable $e) {
                // não bloqueia a tela admin
                $asaasSub = null;
            }
        }

        $subscriptionAmountCents = 0;
        if (is_array($asaasSub) && isset($asaasSub['value'])) {
            $subscriptionAmountCents = (int)round(((float)$asaasSub['value']) * 100);
        } elseif ($plan && isset($plan['price_cents'])) {
            $subscriptionAmountCents = (int)$plan['price_cents'];
        }

        // Identifica se houve período grátis (por indicação ou outro motivo) comparando created_at vs started_at
        $trialDays = 0;
        $trialEndsAt = null;
        $paidStartsAt = null;
        if ($lastSub && !empty($lastSub['created_at']) && !empty($lastSub['started_at'])) {
            try {
                $c = new \DateTimeImmutable((string)$lastSub['created_at']);
                $s = new \DateTimeImmutable((string)$lastSub['started_at']);
                if ($s > $c) {
                    $diff = $c->diff($s);
                    $trialDays = (int)($diff->days ?? 0);
                    if ($trialDays > 0) {
                        $trialEndsAt = $s->format('Y-m-d H:i:s');
                        $paidStartsAt = $s->format('Y-m-d H:i:s');
                    }
                }
            } catch (\Throwable $e) {
            }
        }

        $referral = UserReferral::findLastForReferredUserOrEmail((int)$user['id'], (string)($user['email'] ?? ''));

        // Histórico completo de planos (assinaturas) e créditos de tokens avulsos
        $subscriptionsHistory = Subscription::allByEmailWithPlan($user['email']);
        $topups = TokenTopup::allByUserId((int)$user['id']);
        $tokenTx = TokenTransaction::allByUserId((int)$user['id'], 500);

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

        // Bônus de tokens por indicação (e outros créditos/débitos) registrados no ledger
        foreach ($tokenTx as $tx) {
            $reason = (string)($tx['reason'] ?? '');
            if (!in_array($reason, ['referral_friend_bonus', 'referral_referrer_bonus'], true)) {
                continue;
            }
            $timeline[] = [
                'type' => 'token_tx',
                'date' => $tx['created_at'] ?? '',
                'raw' => $tx,
            ];
        }

        // Eventos derivados: fim do período grátis / início do pago
        if ($trialDays > 0 && $trialEndsAt) {
            $timeline[] = [
                'type' => 'trial_end',
                'date' => $trialEndsAt,
                'raw' => [
                    'trial_days' => $trialDays,
                    'paid_starts_at' => $paidStartsAt,
                ],
            ];
        }

        // Evento: registro de indicação
        if ($referral) {
            $timeline[] = [
                'type' => 'referral',
                'date' => $referral['created_at'] ?? '',
                'raw' => $referral,
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
            'asaasSub' => $asaasSub,
            'subscriptionAmountCents' => $subscriptionAmountCents,
            'trialDays' => $trialDays,
            'trialEndsAt' => $trialEndsAt,
            'paidStartsAt' => $paidStartsAt,
            'referral' => $referral,
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
