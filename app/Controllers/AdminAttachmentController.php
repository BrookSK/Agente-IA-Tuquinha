<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Attachment;

class AdminAttachmentController extends Controller
{
    private function requireAdmin(): void
    {
        if (empty($_SESSION['is_admin'])) {
            header('Location: /admin/login');
            exit;
        }
    }

    public function index(): void
    {
        $this->requireAdmin();

        $type = isset($_GET['type']) ? (string)$_GET['type'] : '';
        if (!in_array($type, ['image', 'file', 'audio'], true)) {
            $type = '';
        }

        $before = isset($_GET['before']) ? trim((string)$_GET['before']) : '';
        if ($before !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $before)) {
            $before = '';
        }

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) {
            $page = 1;
        }
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $attachments = Attachment::search($type !== '' ? $type : null, $before !== '' ? $before : null, $perPage, $offset);
        $total = Attachment::countAll($type !== '' ? $type : null, $before !== '' ? $before : null);

        $this->view('admin/attachments/index', [
            'pageTitle' => 'Anexos do chat',
            'attachments' => $attachments,
            'typeFilter' => $type,
            'before' => $before,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
        ]);
    }

    public function delete(): void
    {
        $this->requireAdmin();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            echo 'Método não permitido';
            return;
        }

        $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? $_POST['ids'] : [];
        Attachment::deleteByIds($ids);

        header('Location: /admin/anexos');
    }
}
