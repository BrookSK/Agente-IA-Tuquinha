<?php

namespace App\Controllers;

use App\Core\Controller;
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
}
