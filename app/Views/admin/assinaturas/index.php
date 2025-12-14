<?php /** @var array $subscriptions */ ?>
<?php /** @var string $status */ ?>

<div style="max-width: 1000px; margin: 0 auto;">
    <h1 style="font-size: 22px; margin-bottom: 16px;">Assinaturas</h1>

    <form method="get" action="/admin/assinaturas" style="margin-bottom: 14px; display:flex; gap:8px; align-items:center;">
        <label style="font-size:13px; color:var(--text-secondary);">Filtrar por status:</label>
        <select name="status" style="min-width:140px; padding:6px 10px; border-radius:999px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
            <option value="" <?= $status === '' ? 'selected' : '' ?>>Todos</option>
            <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pendente</option>
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Ativa</option>
            <option value="canceled" <?= $status === 'canceled' ? 'selected' : '' ?>>Cancelada</option>
            <option value="expired" <?= $status === 'expired' ? 'selected' : '' ?>>Expirada</option>
            <option value="error" <?= $status === 'error' ? 'selected' : '' ?>>Erro</option>
        </select>
        <button type="submit" style="border:none; border-radius:999px; padding:6px 12px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-size:13px; font-weight:600; cursor:pointer;">Aplicar</button>
    </form>

    <table style="width:100%; border-collapse:collapse; font-size:13px; background:var(--surface-card); border-radius:12px; overflow:hidden; border:1px solid var(--border-subtle);">
        <thead>
            <tr style="background:var(--surface-subtle);">
                <th style="text-align:left; padding:8px 10px;">Cliente</th>
                <th style="text-align:left; padding:8px 10px;">E-mail</th>
                <th style="text-align:left; padding:8px 10px;">Plano</th>
                <th style="text-align:center; padding:8px 10px;">Status</th>
                <th style="text-align:left; padding:8px 10px;">Início</th>
                <th style="text-align:left; padding:8px 10px;">Último pagamento</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($subscriptions)): ?>
                <?php foreach ($subscriptions as $s): ?>
                    <tr>
                        <td style="padding:7px 10px;"><?= htmlspecialchars($s['customer_name']) ?></td>
                        <td style="padding:7px 10px;"><?= htmlspecialchars($s['customer_email']) ?></td>
                        <td style="padding:7px 10px;">
                            <?= htmlspecialchars($s['plan_name'] ?? '') ?>
                            <?php if (!empty($s['plan_slug'])): ?>
                                <span style="font-size:11px; color:var(--text-secondary);">(<?= htmlspecialchars($s['plan_slug']) ?>)</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:7px 10px; text-align:center; text-transform:capitalize;">
                            <?= htmlspecialchars($s['status']) ?>
                        </td>
                        <td style="padding:7px 10px; font-size:12px; color:var(--text-secondary);">
                            <?= htmlspecialchars($s['started_at'] ?? $s['created_at'] ?? '') ?>
                        </td>
                        <td style="padding:7px 10px; font-size:12px; color:var(--text-secondary);">
                            <?= htmlspecialchars($s['started_at'] ?? $s['created_at'] ?? '') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="padding:10px; color:var(--text-secondary);">Nenhuma assinatura encontrada.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
