<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\SubscriptionPayment;
use App\Services\FinanceService;

class AdminFinanceController extends Controller
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

        $mode = isset($_GET['mode']) ? (string)$_GET['mode'] : 'month';
        if (!in_array($mode, ['month', 'year', 'semester'], true)) {
            $mode = 'month';
        }

        $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
        $semester = isset($_GET['semester']) ? (int)$_GET['semester'] : ((int)date('n') <= 6 ? 1 : 2);

        [$start, $end] = FinanceService::periodRange($mode, $year, $month, $semester);

        $summary = FinanceService::summary($start, $end);
        $topCourses = FinanceService::courseRevenueByCourse($start, $end, 30);
        $planPayments = SubscriptionPayment::listByPeriod($start, $end, 120);

        $this->view('admin/finance/index', [
            'pageTitle' => 'Finanças',
            'mode' => $mode,
            'year' => $year,
            'month' => $month,
            'semester' => $semester,
            'start' => $start,
            'end' => $end,
            'summary' => $summary,
            'topCourses' => $topCourses,
            'planPayments' => $planPayments,
        ]);
    }
}
