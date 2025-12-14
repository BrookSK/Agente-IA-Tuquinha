<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CommunityCategory;
use App\Models\CommunityUserBlock;

class AdminCommunityController extends Controller
{
    private function ensureAdmin(): void
    {
        if (empty($_SESSION['is_admin'])) {
            header('Location: /admin/login');
            exit;
        }
    }

    public function blocks(): void
    {
        $this->ensureAdmin();

        $blocks = CommunityUserBlock::allActiveWithUsers();

        $success = $_SESSION['admin_community_success'] ?? null;
        $error = $_SESSION['admin_community_error'] ?? null;
        unset($_SESSION['admin_community_success'], $_SESSION['admin_community_error']);

        $this->view('admin/community/blocks', [
            'pageTitle' => 'Bloqueios da comunidade',
            'blocks' => $blocks,
            'success' => $success,
            'error' => $error,
        ]);
    }

    public function categories(): void
    {
        $this->ensureAdmin();

        $categories = CommunityCategory::all();

        $success = $_SESSION['admin_community_success'] ?? null;
        $error = $_SESSION['admin_community_error'] ?? null;
        unset($_SESSION['admin_community_success'], $_SESSION['admin_community_error']);

        $this->view('admin/community/categories', [
            'pageTitle' => 'Categorias de comunidades',
            'categories' => $categories,
            'success' => $success,
            'error' => $error,
        ]);
    }

    public function createCategory(): void
    {
        $this->ensureAdmin();

        $name = isset($_POST['name']) ? trim((string)$_POST['name']) : '';
        if ($name === '') {
            $_SESSION['admin_community_error'] = 'Informe um nome para a categoria.';
            header('Location: /admin/comunidade/categorias');
            exit;
        }

        CommunityCategory::create($name);

        $_SESSION['admin_community_success'] = 'Categoria criada com sucesso.';
        header('Location: /admin/comunidade/categorias');
        exit;
    }

    public function toggleCategory(): void
    {
        $this->ensureAdmin();

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id > 0) {
            CommunityCategory::toggleActive($id);
            $_SESSION['admin_community_success'] = 'Status da categoria atualizado.';
        }

        header('Location: /admin/comunidade/categorias');
        exit;
    }
}
