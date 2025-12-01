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
        $allowAudio = !empty($_POST['allow_audio']) ? 1 : 0;
        $allowImages = !empty($_POST['allow_images']) ? 1 : 0;
        $allowFiles = !empty($_POST['allow_files']) ? 1 : 0;
        $isActive = !empty($_POST['is_active']) ? 1 : 0;

        $priceCents = (int)round(str_replace([',', ' '], ['.', ''], $price) * 100);
        if ($priceCents < 0) {
            $priceCents = 0;
        }

        $data = [
            'name' => $name,
            'slug' => $slug,
            'price_cents' => $priceCents,
            'description' => $description,
            'benefits' => $benefits,
            'allow_audio' => $allowAudio,
            'allow_images' => $allowImages,
            'allow_files' => $allowFiles,
            'is_active' => $isActive,
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
