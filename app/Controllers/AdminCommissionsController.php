<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CoursePartner;
use App\Models\CoursePartnerPayout;
use App\Services\CourseCommissionService;

class AdminCommissionsController extends Controller
{
    private const MIN_PAYOUT_CENTS = 5000;

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

        $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
        if ($month < 1) $month = 1;
        if ($month > 12) $month = 12;

        $partners = CoursePartner::allWithUser();
        $rows = [];

        foreach ($partners as $p) {
            $partnerId = (int)($p['id'] ?? 0);
            if ($partnerId <= 0) {
                continue;
            }

            $monthData = CourseCommissionService::computePartnerMonthByPartnerId($partnerId, $year, $month);
            $totalMonthCents = (int)($monthData['total_commission_cents'] ?? 0);

            $accruedUpTo = CourseCommissionService::sumPartnerCommissionUpToByPartnerId($partnerId, $year, $month);
            $paidUpTo = CoursePartnerPayout::sumPaidUpTo($partnerId, $year, $month);
            $owedCents = max(0, $accruedUpTo - $paidUpTo);

            $eligible = $owedCents >= self::MIN_PAYOUT_CENTS;

            $rows[] = [
                'partner' => $p,
                'total_month_cents' => $totalMonthCents,
                'accrued_up_to_cents' => $accruedUpTo,
                'paid_up_to_cents' => $paidUpTo,
                'owed_cents' => $owedCents,
                'eligible' => $eligible,
            ];
        }

        $this->view('admin/commissions/index', [
            'pageTitle' => 'Comissões (professores/parceiros)',
            'year' => $year,
            'month' => $month,
            'rows' => $rows,
            'minPayoutCents' => self::MIN_PAYOUT_CENTS,
        ]);
    }

    public function details(): void
    {
        $this->ensureAdmin();

        $partnerId = isset($_GET['partner_id']) ? (int)$_GET['partner_id'] : 0;
        $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
        if ($month < 1) $month = 1;
        if ($month > 12) $month = 12;

        if ($partnerId <= 0) {
            header('Location: /admin/comissoes');
            exit;
        }

        $monthData = CourseCommissionService::computePartnerMonthByPartnerId($partnerId, $year, $month);
        $partner = $monthData['partner'] ?? null;
        if (!$partner) {
            header('Location: /admin/comissoes');
            exit;
        }

        $accruedUpTo = CourseCommissionService::sumPartnerCommissionUpToByPartnerId($partnerId, $year, $month);
        $paidUpTo = CoursePartnerPayout::sumPaidUpTo($partnerId, $year, $month);
        $owedCents = max(0, $accruedUpTo - $paidUpTo);
        $eligible = $owedCents >= self::MIN_PAYOUT_CENTS;

        $payouts = CoursePartnerPayout::listByPartner($partnerId, 24);

        $this->view('admin/commissions/details', [
            'pageTitle' => 'Detalhes de comissões',
            'year' => $year,
            'month' => $month,
            'partner' => $partner,
            'monthData' => $monthData,
            'accruedUpToCents' => $accruedUpTo,
            'paidUpToCents' => $paidUpTo,
            'owedCents' => $owedCents,
            'eligible' => $eligible,
            'minPayoutCents' => self::MIN_PAYOUT_CENTS,
            'payouts' => $payouts,
        ]);
    }

    public function markPaid(): void
    {
        $this->ensureAdmin();

        $partnerId = isset($_POST['partner_id']) ? (int)$_POST['partner_id'] : 0;
        $year = isset($_POST['year']) ? (int)$_POST['year'] : (int)date('Y');
        $month = isset($_POST['month']) ? (int)$_POST['month'] : (int)date('n');
        $amountPaidRaw = trim((string)($_POST['amount_paid'] ?? ''));
        if ($month < 1) $month = 1;
        if ($month > 12) $month = 12;

        if ($partnerId <= 0) {
            header('Location: /admin/comissoes');
            exit;
        }

        $accruedUpTo = CourseCommissionService::sumPartnerCommissionUpToByPartnerId($partnerId, $year, $month);
        $paidUpTo = CoursePartnerPayout::sumPaidUpTo($partnerId, $year, $month);
        $owedCents = max(0, $accruedUpTo - $paidUpTo);

        if ($owedCents < self::MIN_PAYOUT_CENTS) {
            $_SESSION['admin_commissions_error'] = 'Ainda não atingiu o mínimo de R$ 50,00 para pagamento. O valor fica acumulado para o próximo mês.';
            header('Location: /admin/comissoes/detalhes?partner_id=' . $partnerId . '&year=' . $year . '&month=' . $month);
            exit;
        }

        $existing = CoursePartnerPayout::findByPartnerAndPeriod($partnerId, $year, $month);
        if ($existing) {
            $_SESSION['admin_commissions_error'] = 'Este mês já foi marcado como pago.';
            header('Location: /admin/comissoes/detalhes?partner_id=' . $partnerId . '&year=' . $year . '&month=' . $month);
            exit;
        }

        if ($amountPaidRaw === '') {
            $_SESSION['admin_commissions_error'] = 'Informe o valor pago.';
            header('Location: /admin/comissoes/detalhes?partner_id=' . $partnerId . '&year=' . $year . '&month=' . $month);
            exit;
        }

        $normalized = str_replace(['.', ' '], ['', ''], $amountPaidRaw);
        $normalized = str_replace([','], ['.'], $normalized);
        $amountPaid = is_numeric($normalized) ? (float)$normalized : -1.0;
        $amountPaidCents = (int)round($amountPaid * 100);

        if ($amountPaidCents <= 0) {
            $_SESSION['admin_commissions_error'] = 'O valor pago deve ser maior que zero.';
            header('Location: /admin/comissoes/detalhes?partner_id=' . $partnerId . '&year=' . $year . '&month=' . $month);
            exit;
        }

        if ($amountPaidCents > $owedCents) {
            $_SESSION['admin_commissions_error'] = 'O valor pago não pode ser maior que o valor disponível para pagamento.';
            header('Location: /admin/comissoes/detalhes?partner_id=' . $partnerId . '&year=' . $year . '&month=' . $month);
            exit;
        }

        CoursePartnerPayout::createPaid($partnerId, $year, $month, $amountPaidCents);

        $_SESSION['admin_commissions_success'] = 'Pagamento registrado com sucesso.';
        header('Location: /admin/comissoes/detalhes?partner_id=' . $partnerId . '&year=' . $year . '&month=' . $month);
        exit;
    }
}
