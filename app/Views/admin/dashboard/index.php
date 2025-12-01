<?php /** @var int $totalUsers */ ?>
<?php /** @var int $totalAdmins */ ?>
<?php /** @var int $totalPlans */ ?>
<?php /** @var array $subsByStatus */ ?>
<?php /** @var int $activeRevenueCents */ ?>

<div style="max-width: 900px; margin: 0 auto;">
    <h1 style="font-size: 22px; margin-bottom: 16px;">Visão geral</h1>
    <div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:20px;">
        <div style="flex:1; min-width:180px; padding:12px 14px; border-radius:12px; background:#111118; border:1px solid #272727;">
            <div style="font-size:12px; color:#b0b0b0;">Usuários cadastrados</div>
            <div style="font-size:22px; font-weight:600; margin-top:4px;"><?= (int)$totalUsers ?></div>
            <div style="font-size:11px; color:#b0b0b0; margin-top:4px;">Inclui admins e clientes</div>
        </div>
        <div style="flex:1; min-width:180px; padding:12px 14px; border-radius:12px; background:#111118; border:1px solid #272727;">
            <div style="font-size:12px; color:#b0b0b0;">Admins</div>
            <div style="font-size:22px; font-weight:600; margin-top:4px;"><?= (int)$totalAdmins ?></div>
        </div>
        <div style="flex:1; min-width:180px; padding:12px 14px; border-radius:12px; background:#111118; border:1px solid #272727;">
            <div style="font-size:12px; color:#b0b0b0;">Planos</div>
            <div style="font-size:22px; font-weight:600; margin-top:4px;"><?= (int)$totalPlans ?></div>
        </div>
        <div style="flex:1; min-width:220px; padding:12px 14px; border-radius:12px; background:#111118; border:1px solid #272727;">
            <div style="font-size:12px; color:#b0b0b0;">Receita recorrente ativa (estimada)</div>
            <div style="font-size:22px; font-weight:600; margin-top:4px;">
                R$ <?= number_format($activeRevenueCents / 100, 2, ',', '.') ?>
            </div>
            <div style="font-size:11px; color:#b0b0b0; margin-top:4px;">Soma dos planos com status ativo</div>
        </div>
    </div>

    <h2 style="font-size:16px; margin-bottom:8px;">Assinaturas por status</h2>
    <table style="width:100%; border-collapse:collapse; font-size:13px; background:#111118; border-radius:12px; overflow:hidden; border:1px solid #272727;">
        <thead>
            <tr style="background:#15151f;">
                <th style="text-align:left; padding:8px 10px;">Status</th>
                <th style="text-align:right; padding:8px 10px;">Quantidade</th>
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
