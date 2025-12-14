<?php /** @var int $totalUsers */ ?>
<?php /** @var int $totalClients */ ?>
<?php /** @var int $totalPlans */ ?>
<?php /** @var array $subsByStatus */ ?>
<?php /** @var int $activeRevenueCents */ ?>

<div style="max-width: 900px; margin: 0 auto;">
    <h1 style="font-size: 22px; margin-bottom: 16px;">Visão geral</h1>
    <div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:20px;">
        <div style="flex:1; min-width:180px; padding:12px 14px; border-radius:12px; background:var(--surface-card); border:1px solid var(--border-subtle); box-shadow:0 8px 20px rgba(15,23,42,0.12);">
            <div style="font-size:12px; color:var(--text-secondary);">Usuários cadastrados</div>
            <div style="font-size:22px; font-weight:600; margin-top:4px;"><?php echo (int)$totalUsers; ?></div>
            <div style="font-size:11px; color:#b0b0b0; margin-top:4px;">Inclui admins e clientes</div>
        </div>
        <div style="flex:1; min-width:180px; padding:12px 14px; border-radius:12px; background:var(--surface-card); border:1px solid var(--border-subtle); box-shadow:0 8px 20px rgba(15,23,42,0.12);">
            <div style="font-size:12px; color:var(--text-secondary);">Clientes (não admin)</div>
            <div style="font-size:22px; font-weight:600; margin-top:4px;"><?= (int)$totalClients ?></div>
            <div style="font-size:11px; color:#b0b0b0; margin-top:4px;">Usuários comuns do sistema</div>
        </div>
        <div style="flex:1; min-width:180px; padding:12px 14px; border-radius:12px; background:var(--surface-card); border:1px solid var(--border-subtle); box-shadow:0 8px 20px rgba(15,23,42,0.12);">
            <div style="font-size:12px; color:var(--text-secondary);">Planos</div>
            <div style="font-size:22px; font-weight:600; margin-top:4px;"><?= (int)$totalPlans ?></div>
        </div>
        <div style="flex:1; min-width:220px; padding:12px 14px; border-radius:12px; background:var(--surface-card); border:1px solid var(--border-subtle); box-shadow:0 8px 20px rgba(15,23,42,0.12);">
            <div style="font-size:12px; color:var(--text-secondary);">Receita recorrente ativa mensal (estimada)</div>
            <div style="font-size:22px; font-weight:600; margin-top:4px;">
                R$ <?= number_format($activeRevenueCents / 100, 2, ',', '.') ?>
            </div>
            <div style="font-size:11px; color:#b0b0b0; margin-top:4px;">Estimativa mensal normalizada (planos mensais, semestrais e anuais ativos)</div>
        </div>
        <div style="flex:1; min-width:220px; padding:12px 14px; border-radius:12px; background:var(--surface-card); border:1px solid var(--border-subtle); display:flex; flex-direction:column; justify-content:space-between; box-shadow:0 8px 20px rgba(15,23,42,0.12);">
            <div>
                <div style="font-size:12px; color:var(--text-secondary);">Relatos de erros de análise e anexos do chat</div>
                <div style="font-size:13px; color:var(--text-secondary); margin-top:6px;">
                    Acompanhe problemas reportados e limpe anexos antigos (imagens, arquivos e áudios) enviados no chat.
                </div>
            </div>
            <div style="margin-top:10px; display:flex; flex-wrap:wrap; gap:6px;">
                <a href="/admin/erros" style="display:inline-block; padding:7px 12px; border-radius:999px; border:1px solid #ff6f60; color:#050509; background:linear-gradient(135deg,#e53935,#ff6f60); font-size:12px; text-decoration:none; font-weight:600;">
                    Ver relatos de erro
                </a>
                <a href="/admin/anexos" style="display:inline-block; padding:7px 12px; border-radius:999px; border:1px solid var(--border-subtle); color:var(--text-primary); font-size:12px; text-decoration:none; background:var(--surface-subtle);">
                    Gerenciar anexos do chat
                </a>
            </div>
        </div>
    </div>

    <h2 style="font-size:16px; margin-bottom:8px;">Assinaturas por status</h2>
    <table style="width:100%; border-collapse:collapse; font-size:13px; background:var(--surface-card); border-radius:12px; overflow:hidden; border:1px solid var(--border-subtle);">
        <thead>
            <tr style="background:var(--surface-subtle);">
                <th style="text-align:left; padding:8px 10px; color:var(--text-secondary);">Status</th>
                <th style="text-align:right; padding:8px 10px; color:var(--text-secondary);">Quantidade</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($subsByStatus)): ?>
                <?php foreach ($subsByStatus as $row): ?>
                    <tr>
                        <td style="padding:7px 10px; text-transform:capitalize;"><?= htmlspecialchars($row['status']) ?></td>
                        <td style="padding:7px 10px; text-align:right;"><?= (int)$row['total'] ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="2" style="padding:10px; color:#b0b0b0;">Nenhuma assinatura registrada ainda.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
