<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Plan;

class AdminPlanController extends Controller
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
        $plans = Plan::all();

        $this->view('admin/planos/index', [
            'pageTitle' => 'Gerenciar planos',
            'plans' => $plans,
        ]);
    }

    public function form(): void
    {
        $this->ensureAdmin();
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $plan = null;
        if ($id > 0) {
            $plan = Plan::findById($id);
        }

        $this->view('admin/planos/form', [
            'pageTitle' => $plan ? 'Editar plano' : 'Novo plano',
            'plan' => $plan,
        ]);
    }

    public function save(): void
    {
        $this->ensureAdmin();

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $price = trim($_POST['price'] ?? '0');
        $description = trim($_POST['description'] ?? '');
        $benefits = trim($_POST['benefits'] ?? '');
        $monthlyTokenLimitRaw = trim($_POST['monthly_token_limit'] ?? '');
        $allowAudio = !empty($_POST['allow_audio']) ? 1 : 0;
        $allowImages = !empty($_POST['allow_images']) ? 1 : 0;
        $allowFiles = !empty($_POST['allow_files']) ? 1 : 0;
        $allowPersonalities = !empty($_POST['allow_personalities']) ? 1 : 0;
        $isActive = !empty($_POST['is_active']) ? 1 : 0;
        $isDefaultForUsers = !empty($_POST['is_default_for_users']) ? 1 : 0;
        $allowedModels = isset($_POST['allowed_models']) && is_array($_POST['allowed_models'])
            ? array_values(array_filter(array_map('trim', $_POST['allowed_models'])))
            : [];
        $defaultModel = trim($_POST['default_model'] ?? '');
        $historyRetentionDays = isset($_POST['history_retention_days']) && $_POST['history_retention_days'] !== ''
            ? max(1, (int)$_POST['history_retention_days'])
            : null;

        $billingCycle = $_POST['billing_cycle'] ?? 'monthly';

        $priceCents = (int)round(str_replace([',', ' '], ['.', ''], $price) * 100);
        if ($priceCents < 0) {
            $priceCents = 0;
        }

        $monthlyTokenLimit = null;
        if ($monthlyTokenLimitRaw !== '') {
            $monthlyTokenLimit = max(0, (int)$monthlyTokenLimitRaw);
        }

        // Normaliza slug base removendo possíveis sufixos anteriores de ciclo
        $baseSlug = $slug;
        if ($baseSlug !== '' && $baseSlug !== 'free') {
            if (substr($baseSlug, -11) === '-semestral') {
                $baseSlug = substr($baseSlug, 0, -11);
            } elseif (substr($baseSlug, -6) === '-anual') {
                $baseSlug = substr($baseSlug, 0, -6);
            } elseif (substr($baseSlug, -7) === '-mensal') {
                $baseSlug = substr($baseSlug, 0, -7);
            }

            // Aplica sufixo conforme ciclo escolhido
            if ($billingCycle === 'semiannual') {
                $slug = $baseSlug . '-semestral';
            } elseif ($billingCycle === 'annual') {
                $slug = $baseSlug . '-anual';
            } else {
                $slug = $baseSlug . '-mensal';
            }
        }

        $data = [
            'name' => $name,
            'slug' => $slug,
            'price_cents' => $priceCents,
            'description' => $description,
            'benefits' => $benefits,
            'monthly_token_limit' => $monthlyTokenLimit,
            'allowed_models' => $allowedModels ? json_encode($allowedModels) : null,
            'default_model' => $defaultModel !== '' ? $defaultModel : null,
            'history_retention_days' => $historyRetentionDays,
            'allow_audio' => $allowAudio,
            'allow_images' => $allowImages,
            'allow_files' => $allowFiles,
            'allow_personalities' => $allowPersonalities,
            'is_active' => $isActive,
            'is_default_for_users' => $isDefaultForUsers,
        ];

        if ($id > 0) {
            Plan::updateById($id, $data);
        } else {
            Plan::create($data);
        }

        header('Location: /admin/planos');
        exit;
    }

    public function toggleActive(): void
    {
        $this->ensureAdmin();
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $value = isset($_GET['v']) ? (int)$_GET['v'] : 0;
        if ($id > 0) {
            Plan::setActive($id, $value === 1);
        }
        header('Location: /admin/planos');
        exit;
    }
}
