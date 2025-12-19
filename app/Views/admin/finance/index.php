<?php
/** @var string $mode */
/** @var int $year */
/** @var int $month */
/** @var int $semester */
/** @var string $start */
/** @var string $end */
/** @var array $summary */
/** @var array $topCourses */
/** @var array $planPayments */

$plan = (int)($summary['plan_revenue_cents'] ?? 0);
$planByType = (array)($summary['plan_revenue_by_type_cents'] ?? []);
$planMensal = (int)($planByType['mensal'] ?? 0);
$planSemestral = (int)($planByType['semestral'] ?? 0);
$planAnual = (int)($planByType['anual'] ?? 0);

$courses = (int)($summary['course_revenue_cents'] ?? 0);
$courseCommission = (int)($summary['course_commission_cents'] ?? 0);
$courseNet = (int)($summary['course_net_cents'] ?? 0);
$total = (int)($summary['total_revenue_cents'] ?? 0);
$totalNet = (int)($summary['total_net_cents'] ?? 0);

function money(int $cents): string {
    return 'R$ ' . number_format($cents / 100, 2, ',', '.');
}

$labelPeriod = '';
if ($mode === 'year') {
    $labelPeriod = 'Ano ' . (int)$year;
} elseif ($mode === 'semester') {
    $labelPeriod = 'Semestre ' . (int)$semester . ' / ' . (int)$year;
} else {
    $labelPeriod = sprintf('%02d/%04d', (int)$month, (int)$year);
}
?>

<div style="max-width: 1200px; margin: 0 auto;">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; gap:12px; flex-wrap:wrap; margin-bottom:12px;">
        <div>
            <h1 style="font-size:22px; font-weight:900; margin:0 0 4px 0;">Finanças</h1>
            <div style="font-size:13px; color:var(--text-secondary);">Resumo do período: <strong><?= htmlspecialchars($labelPeriod, ENT_QUOTES, 'UTF-8') ?></strong></div>
            <div style="font-size:12px; color:var(--text-secondary);">Intervalo: <?= htmlspecialchars($start, ENT_QUOTES, 'UTF-8') ?> até <?= htmlspecialchars($end, ENT_QUOTES, 'UTF-8') ?></div>
        </div>

        <form method="get" action="/admin/financas" style="display:flex; gap:8px; align-items:flex-end; flex-wrap:wrap;">
            <div>
                <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Modo</div>
                <select name="mode" style="min-width:150px; padding:8px 10px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-card); color:var(--text-primary);">
                    <option value="month" <?= $mode === 'month' ? 'selected' : '' ?>>Mês</option>
                    <option value="semester" <?= $mode === 'semester' ? 'selected' : '' ?>>Semestre</option>
                    <option value="year" <?= $mode === 'year' ? 'selected' : '' ?>>Ano</option>
                </select>
            </div>
            <div>
                <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Ano</div>
                <input type="number" name="year" value="<?= (int)$year ?>" style="width:110px; padding:8px 10px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-card); color:var(--text-primary);">
            </div>
            <div>
                <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Mês</div>
                <input type="number" min="1" max="12" name="month" value="<?= (int)$month ?>" style="width:110px; padding:8px 10px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-card); color:var(--text-primary);">
            </div>
            <div>
                <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Semestre</div>
                <select name="semester" style="width:140px; padding:8px 10px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-card); color:var(--text-primary);">
                    <option value="1" <?= (int)$semester === 1 ? 'selected' : '' ?>>1º semestre</option>
                    <option value="2" <?= (int)$semester === 2 ? 'selected' : '' ?>>2º semestre</option>
                </select>
            </div>
            <button type="submit" style="border:none; border-radius:999px; padding:8px 14px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:900; cursor:pointer;">Aplicar</button>
        </form>
    </div>

    <div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:12px;">
        <div style="flex:1 1 260px; padding:10px 12px; border-radius:12px; border:1px solid var(--border-subtle); background:var(--surface-card);">
            <div style="font-size:12px; color:var(--text-secondary);">Receita total (planos + cursos)</div>
            <div style="font-size:20px; font-weight:950;"><?= money($total) ?></div>
        </div>
        <div style="flex:1 1 260px; padding:10px 12px; border-radius:12px; border:1px solid var(--border-subtle); background:var(--surface-card);">
            <div style="font-size:12px; color:var(--text-secondary);">Lucro líquido (após comissões de cursos)</div>
            <div style="font-size:20px; font-weight:950;"><?= money($totalNet) ?></div>
        </div>
        <div style="flex:1 1 260px; padding:10px 12px; border-radius:12px; border:1px solid var(--border-subtle); background:var(--surface-card);">
            <div style="font-size:12px; color:var(--text-secondary);">Receita de planos</div>
            <div style="font-size:20px; font-weight:950;"><?= money($plan) ?></div>
        </div>
        <div style="flex:1 1 260px; padding:10px 12px; border-radius:12px; border:1px solid var(--border-subtle); background:var(--surface-card);">
            <div style="font-size:12px; color:var(--text-secondary);">Receita de cursos (bruto)</div>
            <div style="font-size:20px; font-weight:950;"><?= money($courses) ?></div>
        </div>
        <div style="flex:1 1 260px; padding:10px 12px; border-radius:12px; border:1px solid var(--border-subtle); background:var(--surface-card);">
            <div style="font-size:12px; color:var(--text-secondary);">Comissões de cursos (custo)</div>
            <div style="font-size:20px; font-weight:950;"><?= money($courseCommission) ?></div>
        </div>
        <div style="flex:1 1 260px; padding:10px 12px; border-radius:12px; border:1px solid var(--border-subtle); background:var(--surface-card);">
            <div style="font-size:12px; color:var(--text-secondary);">Lucro em cursos (líquido)</div>
            <div style="font-size:20px; font-weight:950;"><?= money($courseNet) ?></div>
        </div>
    </div>

    <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-start;">
        <div style="flex:1 1 420px; min-width:280px;">
            <div style="font-size:14px; font-weight:900; margin-bottom:8px;">Planos por tipo</div>
            <div style="border-radius:14px; border:1px solid var(--border-subtle); overflow:hidden; background:var(--surface-card);">
                <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
                    <table style="width:100%; min-width:420px; border-collapse:collapse; font-size:13px;">
                        <thead style="background:var(--surface-subtle);">
                            <tr>
                                <th style="text-align:left; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Tipo</th>
                                <th style="text-align:right; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Receita</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="border-top:1px solid var(--border-subtle);">
                                <td style="padding:10px 12px;">Mensal</td>
                                <td style="padding:10px 12px; text-align:right; font-weight:800;"><?= money($planMensal) ?></td>
                            </tr>
                            <tr style="border-top:1px solid var(--border-subtle);">
                                <td style="padding:10px 12px;">Semestral</td>
                                <td style="padding:10px 12px; text-align:right; font-weight:800;"><?= money($planSemestral) ?></td>
                            </tr>
                            <tr style="border-top:1px solid var(--border-subtle);">
                                <td style="padding:10px 12px;">Anual</td>
                                <td style="padding:10px 12px; text-align:right; font-weight:800;"><?= money($planAnual) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div style="flex:1 1 740px; min-width:320px;">
            <div style="font-size:14px; font-weight:900; margin-bottom:8px;">Top cursos (receita bruta no período)</div>
            <div style="border-radius:14px; border:1px solid var(--border-subtle); overflow:hidden; background:var(--surface-card);">
                <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
                    <table style="width:100%; min-width:760px; border-collapse:collapse; font-size:13px;">
                        <thead style="background:var(--surface-subtle);">
                            <tr>
                                <th style="text-align:left; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Curso</th>
                                <th style="text-align:center; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Vendas pagas</th>
                                <th style="text-align:right; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Receita</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topCourses)): ?>
                                <tr><td colspan="3" style="padding:12px; color:var(--text-secondary);">Nenhuma venda de curso paga no período.</td></tr>
                            <?php else: ?>
                                <?php foreach ($topCourses as $r): ?>
                                    <tr style="border-top:1px solid var(--border-subtle);">
                                        <td style="padding:10px 12px;">
                                            <?= htmlspecialchars((string)($r['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                            <div style="font-size:11px; color:var(--text-secondary);"><?= htmlspecialchars((string)($r['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                        </td>
                                        <td style="padding:10px 12px; text-align:center;"><?= (int)($r['paid_count'] ?? 0) ?></td>
                                        <td style="padding:10px 12px; text-align:right; font-weight:900;"><?= money((int)($r['sales_cents'] ?? 0)) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div style="margin-top:14px;">
        <div style="font-size:14px; font-weight:900; margin-bottom:8px;">Pagamentos de plano (registrados via webhook)</div>
        <div style="border-radius:14px; border:1px solid var(--border-subtle); overflow:hidden; background:var(--surface-card);">
            <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
                <table style="width:100%; min-width:980px; border-collapse:collapse; font-size:13px;">
                    <thead style="background:var(--surface-subtle);">
                        <tr>
                            <th style="text-align:left; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Plano</th>
                            <th style="text-align:right; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Valor</th>
                            <th style="text-align:left; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Tipo</th>
                            <th style="text-align:left; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Pago em</th>
                            <th style="text-align:left; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Asaas payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($planPayments)): ?>
                            <tr><td colspan="5" style="padding:12px; color:var(--text-secondary);">Nenhum pagamento de plano registrado no período. (Se você acabou de criar a tabela, ela começa a preencher a partir de agora.)</td></tr>
                        <?php else: ?>
                            <?php foreach ($planPayments as $p): ?>
                                <tr style="border-top:1px solid var(--border-subtle);">
                                    <td style="padding:10px 12px;">
                                        <?= htmlspecialchars((string)($p['plan_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                        <div style="font-size:11px; color:var(--text-secondary);"><?= htmlspecialchars((string)($p['plan_slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                    </td>
                                    <td style="padding:10px 12px; text-align:right; font-weight:900;"><?= money((int)($p['amount_cents'] ?? 0)) ?></td>
                                    <td style="padding:10px 12px; color:var(--text-secondary);"><?= htmlspecialchars((string)($p['billing_type'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td style="padding:10px 12px; color:var(--text-secondary);"><?= htmlspecialchars((string)($p['paid_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td style="padding:10px 12px; color:var(--text-secondary); word-break:break-all;"><?= htmlspecialchars((string)($p['asaas_payment_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
