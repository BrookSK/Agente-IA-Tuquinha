<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ErrorReport;
use App\Models\User;

class AdminErrorReportController extends Controller
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

        $status = isset($_GET['status']) ? (string)$_GET['status'] : '';
        if (!in_array($status, ['open', 'resolved', 'dismissed'], true)) {
            $status = '';
        }

        $reports = ErrorReport::allWithUser($status);

        $this->view('admin/erros/index', [
            'pageTitle' => 'Relatos de erros de análise',
            'reports' => $reports,
            'statusFilter' => $status,
        ]);
    }

    public function show(): void
    {
        $this->requireAdmin();

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            http_response_code(404);
            echo 'Relato não encontrado';
            return;
        }

        $report = ErrorReport::findById($id);
        if (!$report) {
            http_response_code(404);
            echo 'Relato não encontrado';
            return;
        }

        $user = null;
        if (!empty($report['user_id'])) {
            $user = User::findById((int)$report['user_id']);
        }

        $this->view('admin/erros/show', [
            'pageTitle' => 'Detalhes do relato de erro',
            'report' => $report,
            'user' => $user,
        ]);
    }

    public function refund(): void
    {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Método não permitido';
            return;
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            http_response_code(400);
            echo 'ID inválido';
            return;
        }

        $report = ErrorReport::findById($id);
        if (!$report) {
            http_response_code(404);
            echo 'Relato não encontrado';
            return;
        }

        if (($report['status'] ?? 'open') !== 'open') {
            header('Location: /admin/erros/ver?id=' . $id);
            return;
        }

        $userId = (int)($report['user_id'] ?? 0);
        $tokens = (int)($report['tokens_used'] ?? 0);

        if ($userId <= 0 || $tokens <= 0) {
            header('Location: /admin/erros/ver?id=' . $id);
            return;
        }

        $user = User::findById($userId);
        if (!$user) {
            header('Location: /admin/erros/ver?id=' . $id);
            return;
        }

        User::creditTokens($userId, $tokens, 'error_refund');
        ErrorReport::markRefunded($id, $tokens);
        ErrorReport::updateStatus($id, 'resolved');

        header('Location: /admin/erros/ver?id=' . $id);
    }

    public function resolve(): void
    {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Método não permitido';
            return;
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            http_response_code(400);
            echo 'ID inválido';
            return;
        }

        $report = ErrorReport::findById($id);
        if (!$report) {
            http_response_code(404);
            echo 'Relato não encontrado';
            return;
        }

        if (($report['status'] ?? 'open') === 'open') {
            ErrorReport::updateStatus($id, 'resolved');
        }

        header('Location: /admin/erros/ver?id=' . $id);
    }
}
