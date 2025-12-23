<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CoursePartner;
use App\Models\CoursePartnerPayout;
use App\Models\Setting;
use App\Models\User;
use App\Services\CourseCommissionService;

class PartnerCommissionsController extends Controller
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

    public function index(): void
    {
        $user = $this->requireLogin();

        $minPayoutCents = (int)Setting::get('course_partner_min_payout_cents', '5000');
        if ($minPayoutCents < 0) {
            $minPayoutCents = 5000;
        }

        $partner = CoursePartner::findByUserId((int)$user['id']);
        if (!$partner) {
            $this->view('partner/commissions', [
                'pageTitle' => 'Minhas comissões',
                'user' => $user,
                'partner' => null,
                'year' => (int)date('Y'),
                'month' => (int)date('n'),
                'monthData' => ['total_sales_cents' => 0, 'total_commission_cents' => 0, 'by_course' => []],
                'owedCents' => 0,
                'eligible' => false,
                'minPayoutCents' => $minPayoutCents,
                'payouts' => [],
            ]);
            return;
        }

        $partnerId = (int)($partner['id'] ?? 0);
        $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
        if ($month < 1) $month = 1;
        if ($month > 12) $month = 12;

        $monthData = CourseCommissionService::computePartnerMonthByPartnerId($partnerId, $year, $month);

        $accruedUpTo = CourseCommissionService::sumPartnerCommissionUpToByPartnerId($partnerId, $year, $month);
        $paidUpTo = CoursePartnerPayout::sumPaidUpTo($partnerId, $year, $month);
        $owedCents = max(0, $accruedUpTo - $paidUpTo);
        $eligible = $owedCents >= $minPayoutCents;

        $payouts = CoursePartnerPayout::listByPartner($partnerId, 24);

        $this->view('partner/commissions', [
            'pageTitle' => 'Minhas comissões',
            'user' => $user,
            'partner' => $partner,
            'year' => $year,
            'month' => $month,
            'monthData' => $monthData,
            'owedCents' => $owedCents,
            'eligible' => $eligible,
            'minPayoutCents' => $minPayoutCents,
            'payouts' => $payouts,
        ]);
    }

    public function savePayoutDetails(): void
    {
        $user = $this->requireLogin();

        $partner = CoursePartner::findByUserId((int)$user['id']);
        if (!$partner || empty($partner['id'])) {
            header('Location: /parceiro/comissoes');
            exit;
        }

        $data = [
            'pix_key' => trim((string)($_POST['pix_key'] ?? '')),
            'bank_name' => trim((string)($_POST['bank_name'] ?? '')),
            'bank_agency' => trim((string)($_POST['bank_agency'] ?? '')),
            'bank_account' => trim((string)($_POST['bank_account'] ?? '')),
            'bank_account_type' => trim((string)($_POST['bank_account_type'] ?? '')),
            'bank_holder_name' => trim((string)($_POST['bank_holder_name'] ?? '')),
            'bank_holder_document' => trim((string)($_POST['bank_holder_document'] ?? '')),
        ];

        CoursePartner::updatePayoutDetails((int)$partner['id'], $data);
        $_SESSION['partner_commissions_success'] = 'Dados de pagamento atualizados.';

        header('Location: /parceiro/comissoes');
        exit;
    }
}
