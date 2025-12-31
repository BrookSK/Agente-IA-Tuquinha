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
use App\Services\MailService;

class AdminUserController extends Controller
{
    private function ensureAdmin(): void
    {
        if (empty($_SESSION['is_admin'])) {
            header('Location: /admin/login');
            exit;
        }
    }

    private function sendTokenChangeEmail(array $user, int $amount, string $type): void
    {
        $email = (string)($user['email'] ?? '');
        if ($email === '') {
            return;
        }

        $name = (string)($user['preferred_name'] ?? $user['name'] ?? '');
        $name = trim($name) !== '' ? $name : 'tudo bem';

        $amount = abs($amount);
        if ($amount <= 0) {
            return;
        }

        $tokenBalance = (int)($user['token_balance'] ?? 0);

        $verb = $type === 'removed' ? 'removemos' : 'adicionamos';
        $subject = $type === 'removed'
            ? 'Atualização de tokens na sua conta'
            : 'Atualização de tokens na sua conta';

        $contentHtml = '';
        $contentHtml .= '<p style="font-size:13px; margin:0 0 10px 0; color:#f5f5f5;">'
            . 'Nós ' . $verb . ' <strong>' . (int)$amount . ' token(s)</strong> na sua conta.'
            . '</p>';
        $contentHtml .= '<div style="font-size:13px; color:#b0b0b0; margin:0;">'
            . '<strong style="color:#f5f5f5;">Saldo atual:</strong> ' . (int)$tokenBalance . ' token(s)'
            . '</div>';

        $body = MailService::buildDefaultTemplate($name, $contentHtml);
        try {
            MailService::send($email, (string)($user['name'] ?? ''), $subject, $body);
        } catch (\Throwable $e) {
        }
    }

    public function addTokens(): void
    {
        $this->ensureAdmin();

        $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        $amount = isset($_POST['amount']) ? (int)$_POST['amount'] : 0;

        if ($userId <= 0) {
            header('Location: /admin/usuarios');
            exit;
        }

        $user = User::findById($userId);
        if (!$user) {
            $_SESSION['admin_users_error'] = 'Usuário não encontrado.';
            header('Location: /admin/usuarios');
            exit;
        }

        if ($amount <= 0) {
            $_SESSION['admin_users_error'] = 'Informe uma quantidade válida de tokens para adicionar.';
            header('Location: /admin/usuarios/ver?id=' . $userId);
            exit;
        }

        $meta = [
            'admin_user_id' => (int)($_SESSION['user_id'] ?? 0),
            'admin_email' => (string)($_SESSION['user_email'] ?? ''),
        ];

        User::creditTokens($userId, $amount, 'admin_grant', $meta);

        $freshUser = User::findById($userId);
        if ($freshUser) {
            $this->sendTokenChangeEmail($freshUser, $amount, 'added');
        }

        $_SESSION['admin_users_success'] = 'Tokens adicionados com sucesso.';
        header('Location: /admin/usuarios/ver?id=' . $userId);
        exit;
    }

    public function removeTokens(): void
    {
        $this->ensureAdmin();

        $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        $amount = isset($_POST['amount']) ? (int)$_POST['amount'] : 0;

        if ($userId <= 0) {
            header('Location: /admin/usuarios');
            exit;
        }

        $user = User::findById($userId);
        if (!$user) {
            $_SESSION['admin_users_error'] = 'Usuário não encontrado.';
            header('Location: /admin/usuarios');
            exit;
        }

        $balance = (int)($user['token_balance'] ?? 0);

        if ($amount <= 0) {
            $_SESSION['admin_users_error'] = 'Informe uma quantidade válida de tokens para remover.';
            header('Location: /admin/usuarios/ver?id=' . $userId);
            exit;
        }

        if ($amount > $balance) {
            $_SESSION['admin_users_error'] = 'Não é possível remover mais tokens do que o saldo atual do usuário.';
            header('Location: /admin/usuarios/ver?id=' . $userId);
            exit;
        }

        $meta = [
            'admin_user_id' => (int)($_SESSION['user_id'] ?? 0),
            'admin_email' => (string)($_SESSION['user_email'] ?? ''),
            'previous_balance' => $balance,
        ];

        User::debitTokens($userId, $amount, 'admin_revoke', $meta);

        $freshUser = User::findById($userId);
        if ($freshUser) {
            $this->sendTokenChangeEmail($freshUser, $amount, 'removed');
        }

        $_SESSION['admin_users_success'] = 'Tokens removidos com sucesso.';
        header('Location: /admin/usuarios/ver?id=' . $userId);
        exit;
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

        $successMsg = $_SESSION['admin_users_success'] ?? null;
        $errorMsg = $_SESSION['admin_users_error'] ?? null;
        unset($_SESSION['admin_users_success'], $_SESSION['admin_users_error']);

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
            if (!in_array($reason, ['referral_friend_bonus', 'referral_referrer_bonus', 'admin_grant', 'admin_revoke'], true)) {
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
            'tokenBalance' => (int)($user['token_balance'] ?? 0),
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
            'success' => $successMsg,
            'error' => $errorMsg,
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
