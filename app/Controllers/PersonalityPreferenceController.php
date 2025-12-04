<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Personality;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;

class PersonalityPreferenceController extends Controller
{
    private function requireLogin(): array
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        $user = User::findById((int)$_SESSION['user_id']);
        if (!$user) {
            unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email']);
            header('Location: /login');
            exit;
        }
        return $user;
    }

    private function resolvePlanForUser(array $user): ?array
    {
        $plan = null;
        if (!empty($user['email'])) {
            $sub = Subscription::findLastByEmail($user['email']);
            if ($sub && !empty($sub['plan_id'])) {
                $plan = Plan::findById((int)$sub['plan_id']);
            }
        }
        if (!$plan) {
            $plan = Plan::findBySessionSlug($_SESSION['plan_slug'] ?? null) ?: Plan::findBySlug('free');
        }
        return $plan;
    }

    public function index(): void
    {
        $user = $this->requireLogin();
        $plan = $this->resolvePlanForUser($user);

        if (empty($plan['allow_personalities'])) {
            header('Location: /conta');
            exit;
        }

        $personalities = Personality::allActive();

        $success = $_SESSION['personality_pref_success'] ?? null;
        if ($success !== null) {
            unset($_SESSION['personality_pref_success']);
        }

        $this->view('account/personalidade', [
            'pageTitle' => 'Personalidade padrão do Tuquinha',
            'user' => $user,
            'plan' => $plan,
            'personalities' => $personalities,
            'success' => $success,
        ]);
    }

    public function save(): void
    {
        $user = $this->requireLogin();
        $plan = $this->resolvePlanForUser($user);
        if (empty($plan['allow_personalities'])) {
            header('Location: /conta');
            exit;
        }

        $defaultPersonaIdRaw = isset($_POST['default_persona_id']) ? (int)$_POST['default_persona_id'] : 0;
        $defaultPersonaId = null;
        if ($defaultPersonaIdRaw > 0) {
            $persona = Personality::findById($defaultPersonaIdRaw);
            if ($persona && !empty($persona['active'])) {
                $defaultPersonaId = (int)$persona['id'];
            }
        }

        User::updateProfile((int)$user['id'], $user['name'], $user['preferred_name'] ?? null, $user['global_memory'] ?? null, $user['global_instructions'] ?? null, $defaultPersonaId);

        if ($defaultPersonaId) {
            $_SESSION['default_persona_id'] = $defaultPersonaId;
        } else {
            unset($_SESSION['default_persona_id']);
        }

        $_SESSION['personality_pref_success'] = 'Personalidade padrão atualizada com sucesso.';

        header('Location: /conta/personalidade');
        exit;
    }
}
