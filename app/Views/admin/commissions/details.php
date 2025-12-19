<?php
/** @var int $year */
/** @var int $month */
/** @var array $partner */
/** @var array $monthData */
/** @var int $accruedUpToCents */
/** @var int $paidUpToCents */
/** @var int $owedCents */
/** @var bool $eligible */
/** @var int $minPayoutCents */
/** @var array $payouts */

$minPayout = $minPayoutCents / 100;
?>

<div style="max-width: 1100px; margin: 0 auto;">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; gap:12px; flex-wrap:wrap; margin-bottom:12px;">
        <div>
            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Detalhes de comissões</div>
            <h1 style="font-size: 22px; font-weight: 700; margin: 0;">
                <?= htmlspecialchars((string)($partner['user_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            </h1>
            <div style="font-size: 13px; color: var(--text-secondary);">
                Período: <strong><?= sprintf('%02d/%04d', (int)$month, (int)$year) ?></strong>
                | Mínimo para pagamento: <strong>R$ <?= number_format($minPayout, 2, ',', '.') ?></strong>
            </div>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <a href="/admin/comissoes?year=<?= (int)$year ?>&month=<?= (int)$month ?>" style="display:inline-flex; align-items:center; padding:8px 12px; border-radius:999px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); text-decoration:none; font-size:12px;">Voltar</a>
        </div>
    </div>

    <?php if (!empty($_SESSION['admin_commissions_success'])): ?>
        <div style="background:var(--surface-subtle); border:1px solid var(--border-subtle); color:var(--text-primary); padding:8px 10px; border-radius:10px; font-size:13px; margin-bottom:12px;">
            <?= htmlspecialchars($_SESSION['admin_commissions_success'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php unset($_SESSION['admin_commissions_success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['admin_commissions_error'])): ?>
        <div style="background:var(--surface-subtle); border:1px solid var(--border-subtle); color:var(--text-primary); padding:8px 10px; border-radius:10px; font-size:13px; margin-bottom:12px;">
            <?= htmlspecialchars($_SESSION['admin_commissions_error'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php unset($_SESSION['admin_commissions_error']); ?>
    <?php endif; ?>

    <div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:12px;">
        <div style="flex:1 1 220px; padding:10px 12px; border-radius:12px; border:1px solid var(--border-subtle); background:var(--surface-card);">
            <div style="font-size:12px; color:var(--text-secondary);">Comissão do mês</div>
            <div style="font-size:18px; font-weight:800;">R$ <?= number_format(((int)($monthData['total_commission_cents'] ?? 0))/100, 2, ',', '.') ?></div>
        </div>
        <div style="flex:1 1 220px; padding:10px 12px; border-radius:12px; border:1px solid var(--border-subtle); background:var(--surface-card);">
            <div style="font-size:12px; color:var(--text-secondary);">Acumulado a pagar</div>
            <div style="font-size:18px; font-weight:800; color:var(--text-primary);">R$ <?= number_format(((int)$owedCents)/100, 2, ',', '.') ?></div>
            <div style="font-size:12px; color:var(--text-secondary); margin-top:3px;">(acumulado - pagos)</div>
        </div>
        <div style="flex:1 1 320px; padding:10px 12px; border-radius:12px; border:1px solid var(--border-subtle); background:var(--surface-card);">
            <div style="font-size:12px; color:var(--text-secondary);">Dados para pagamento</div>
            <div style="margin-top:6px; font-size:13px; color:var(--text-primary); line-height:1.45;">
                <div><strong>PIX:</strong> <?= htmlspecialchars((string)($partner['pix_key'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
                <div><strong>Banco:</strong> <?= htmlspecialchars((string)($partner['bank_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
                <div><strong>Agência:</strong> <?= htmlspecialchars((string)($partner['bank_agency'] ?? '—'), ENT_QUOTES, 'UTF-8') ?> <strong>Conta:</strong> <?= htmlspecialchars((string)($partner['bank_account'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
                <div><strong>Titular:</strong> <?= htmlspecialchars((string)($partner['bank_holder_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars((string)($partner['bank_holder_document'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>)</div>
            </div>
        </div>
    </div>

    <div style="margin-bottom:12px;">
        <form method="post" action="/admin/comissoes/marcar-pago" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <input type="hidden" name="partner_id" value="<?= (int)($partner['id'] ?? 0) ?>">
            <input type="hidden" name="year" value="<?= (int)$year ?>">
            <input type="hidden" name="month" value="<?= (int)$month ?>">
            <button type="submit" <?= $eligible ? '' : 'disabled' ?> style="border:none; border-radius:999px; padding:9px 14px; font-weight:800; cursor:pointer; background:<?= $eligible ? 'linear-gradient(135deg,#e53935,#ff6f60)' : 'var(--surface-subtle)' ?>; color:#050509; opacity:<?= $eligible ? '1' : '0.55' ?>;">
                Marcar como pago (<?= sprintf('%02d/%04d', (int)$month, (int)$year) ?>)
            </button>
            <?php if (!$eligible): ?>
                <div style="font-size:12px; color:var(--text-secondary);">Só habilita quando o acumulado atingir pelo menos R$ <?= number_format($minPayout, 2, ',', '.') ?>.</div>
            <?php endif; ?>
        </form>
    </div>

    <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-start;">
        <div style="flex:1 1 560px; min-width:300px;">
            <div style="font-size:14px; font-weight:800; margin-bottom:8px;">Detalhamento por curso (mês)</div>
            <div style="border-radius:14px; border:1px solid var(--border-subtle); overflow:hidden;">
                <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
                    <table style="width:100%; min-width:760px; border-collapse:collapse; font-size:13px;">
                    <thead style="background:var(--surface-subtle);">
                        <tr>
                            <th style="text-align:left; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Curso</th>
                            <th style="text-align:center; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Vendas pagas</th>
                            <th style="text-align:right; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Vendas (R$)</th>
                            <th style="text-align:right; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Comissão</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($monthData['by_course'])): ?>
                        <tr><td colspan="4" style="padding:12px; color:var(--text-secondary);">Nenhuma venda paga neste mês.</td></tr>
                    <?php else: ?>
                        <?php foreach ($monthData['by_course'] as $row): ?>
                            <?php $c = $row['course']; ?>
                            <tr style="background:var(--surface-card); border-top:1px solid var(--border-subtle);">
                                <td style="padding:10px 12px;">
                                    <?= htmlspecialchars((string)($c['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    <div style="font-size:11px; color:var(--text-secondary);"><?= htmlspecialchars((string)($c['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                </td>
                                <td style="padding:10px 12px; text-align:center;">
                                    <?= (int)($row['paid_count'] ?? 0) ?>
                                </td>
                                <td style="padding:10px 12px; text-align:right;">
                                    R$ <?= number_format(((int)($row['sales_cents'] ?? 0))/100, 2, ',', '.') ?>
                                </td>
                                <td style="padding:10px 12px; text-align:right;">
                                    <?= number_format((float)($row['commission_percent'] ?? 0.0), 2, ',', '.') ?>%<br>
                                    <strong>R$ <?= number_format(((int)($row['commission_cents'] ?? 0))/100, 2, ',', '.') ?></strong>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div style="flex:1 1 420px; min-width:280px;">
            <div style="font-size:14px; font-weight:800; margin-bottom:8px;">Histórico de pagamentos</div>
            <div style="border-radius:14px; border:1px solid var(--border-subtle); overflow:hidden;">
                <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
                    <table style="width:100%; min-width:520px; border-collapse:collapse; font-size:13px;">
                    <thead style="background:var(--surface-subtle);">
                        <tr>
                            <th style="text-align:left; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Competência</th>
                            <th style="text-align:right; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Pago (R$)</th>
                            <th style="text-align:left; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Data</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($payouts)): ?>
                        <tr><td colspan="3" style="padding:12px; color:var(--text-secondary);">Nenhum pagamento registrado ainda.</td></tr>
                    <?php else: ?>
                        <?php foreach ($payouts as $p): ?>
                            <tr style="background:var(--surface-card); border-top:1px solid var(--border-subtle);">
                                <td style="padding:10px 12px;"><?= sprintf('%02d/%04d', (int)($p['period_month'] ?? 0), (int)($p['period_year'] ?? 0)) ?></td>
                                <td style="padding:10px 12px; text-align:right; font-weight:700;">R$ <?= number_format(((int)($p['amount_cents'] ?? 0))/100, 2, ',', '.') ?></td>
                                <td style="padding:10px 12px; color:var(--text-secondary);"><?= htmlspecialchars((string)($p['paid_at'] ?? $p['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
