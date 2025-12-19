<?php
/** @var int $year */
/** @var int $month */
/** @var array $rows */
/** @var int $minPayoutCents */

$minPayout = $minPayoutCents / 100;
?>

<div style="max-width: 1100px; margin: 0 auto;">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; gap:12px; flex-wrap:wrap; margin-bottom:12px;">
        <div>
            <h1 style="font-size: 22px; font-weight: 700; margin: 0 0 4px 0;">Comissões (professores/parceiros)</h1>
            <div style="font-size: 13px; color: var(--text-secondary);">Pagamento liberado somente quando o acumulado atingir pelo menos <strong>R$ <?= number_format($minPayout, 2, ',', '.') ?></strong>.</div>
        </div>

        <form method="get" action="/admin/comissoes" style="display:flex; gap:8px; align-items:flex-end; flex-wrap:wrap;">
            <div>
                <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Ano</div>
                <input type="number" name="year" value="<?= (int)$year ?>" style="width:110px; padding:8px 10px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-card); color:var(--text-primary);">
            </div>
            <div>
                <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Mês</div>
                <input type="number" min="1" max="12" name="month" value="<?= (int)$month ?>" style="width:110px; padding:8px 10px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-card); color:var(--text-primary);">
            </div>
            <button type="submit" style="border:none; border-radius:999px; padding:8px 14px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:700; cursor:pointer;">Filtrar</button>
        </form>
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

    <div style="border-radius:14px; border:1px solid var(--border-subtle); overflow:hidden;">
        <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
            <table style="width:100%; min-width:780px; border-collapse:collapse; font-size:13px;">
            <thead style="background:var(--surface-subtle);">
                <tr>
                    <th style="text-align:left; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Parceiro</th>
                    <th style="text-align:left; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">E-mail</th>
                    <th style="text-align:right; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Comissão do mês</th>
                    <th style="text-align:right; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Acumulado a pagar</th>
                    <th style="text-align:center; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Ação</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="5" style="padding:12px; color:var(--text-secondary);">Nenhum parceiro encontrado.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <?php $p = $r['partner']; ?>
                    <tr style="background:var(--surface-card); border-top:1px solid var(--border-subtle);">
                        <td style="padding:10px 12px;">
                            <?= htmlspecialchars((string)($p['user_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td style="padding:10px 12px; color:var(--text-secondary);">
                            <?= htmlspecialchars((string)($p['user_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td style="padding:10px 12px; text-align:right;">
                            R$ <?= number_format(((int)$r['total_month_cents'])/100, 2, ',', '.') ?>
                        </td>
                        <td style="padding:10px 12px; text-align:right; font-weight:700; color:var(--text-primary);">
                            R$ <?= number_format(((int)$r['owed_cents'])/100, 2, ',', '.') ?>
                        </td>
                        <td style="padding:10px 12px; text-align:center;">
                            <a href="/admin/comissoes/detalhes?partner_id=<?= (int)($p['id'] ?? 0) ?>&year=<?= (int)$year ?>&month=<?= (int)$month ?>" style="display:inline-flex; align-items:center; padding:7px 12px; border-radius:999px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); text-decoration:none; font-size:12px;">Detalhes</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
            </table>
        </div>
    </div>
</div>
