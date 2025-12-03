<?php /** @var array $reports */ ?>
<?php /** @var string $statusFilter */ ?>

<div style="max-width: 900px; margin: 0 auto;">
    <h1 style="font-size: 22px; margin-bottom: 10px;">Relatos de erros de análise</h1>

    <a href="/admin" style="font-size:12px; color:#ff6f60; text-decoration:none;">⟵ Voltar para o painel</a>

    <div style="margin-top:12px; margin-bottom:10px; display:flex; gap:8px; align-items:center; font-size:13px; color:#b0b0b0;">
        <span>Filtrar por status:</span>
        <a href="/admin/erros" style="padding:3px 8px; border-radius:999px; border:1px solid <?= $statusFilter === '' ? '#ff6f60' : '#272727' ?>; color:<?= $statusFilter === '' ? '#ffcc80' : '#b0b0b0' ?>; text-decoration:none; font-size:12px;">Todos</a>
        <a href="/admin/erros?status=open" style="padding:3px 8px; border-radius:999px; border:1px solid <?= $statusFilter === 'open' ? '#ff6f60' : '#272727' ?>; color:<?= $statusFilter === 'open' ? '#ffcc80' : '#b0b0b0' ?>; text-decoration:none; font-size:12px;">Abertos</a>
        <a href="/admin/erros?status=resolved" style="padding:3px 8px; border-radius:999px; border:1px solid <?= $statusFilter === 'resolved' ? '#ff6f60' : '#272727' ?>; color:<?= $statusFilter === 'resolved' ? '#ffcc80' : '#b0b0b0' ?>; text-decoration:none; font-size:12px;">Resolvidos</a>
        <a href="/admin/erros?status=dismissed" style="padding:3px 8px; border-radius:999px; border:1px solid <?= $statusFilter === 'dismissed' ? '#ff6f60' : '#272727' ?>; color:<?= $statusFilter === 'dismissed' ? '#ffcc80' : '#b0b0b0' ?>; text-decoration:none; font-size:12px;">Descartados</a>
    </div>

    <?php if (empty($reports)): ?>
        <p style="font-size:13px; color:#b0b0b0; margin-top:12px;">Nenhum relato encontrado para este filtro.</p>
    <?php else: ?>
        <div style="margin-top:10px; border-radius:12px; border:1px solid #272727; overflow:hidden;">
            <table style="width:100%; border-collapse:collapse; font-size:13px;">
                <thead>
                <tr style="background:#111118;">
                    <th style="text-align:left; padding:8px 10px; border-bottom:1px solid #272727;">ID</th>
                    <th style="text-align:left; padding:8px 10px; border-bottom:1px solid #272727;">Usuário</th>
                    <th style="text-align:left; padding:8px 10px; border-bottom:1px solid #272727;">Tokens</th>
                    <th style="text-align:left; padding:8px 10px; border-bottom:1px solid #272727;">Status</th>
                    <th style="text-align:left; padding:8px 10px; border-bottom:1px solid #272727;">Criado em</th>
                    <th style="text-align:left; padding:8px 10px; border-bottom:1px solid #272727;">Ações</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($reports as $r): ?>
                    <tr style="background:#050509; border-top:1px solid #272727;">
                        <td style="padding:6px 10px;">#<?= (int)$r['id'] ?></td>
                        <td style="padding:6px 10px;">
                            <?php if (!empty($r['user_name']) || !empty($r['user_email'])): ?>
                                <div><?= htmlspecialchars($r['user_name'] ?? '') ?></div>
                                <div style="font-size:11px; color:#777;"><?= htmlspecialchars($r['user_email'] ?? '') ?></div>
                            <?php else: ?>
                                <span style="font-size:11px; color:#777;">(usuário não encontrado)</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:6px 10px;">
                            <?= (int)($r['tokens_used'] ?? 0) ?>
                        </td>
                        <td style="padding:6px 10px;">
                            <?php
                            $status = (string)($r['status'] ?? 'open');
                            $label = $status === 'resolved' ? 'Resolvido' : ($status === 'dismissed' ? 'Descartado' : 'Aberto');
                            $colorBg = $status === 'resolved' ? '#14361f' : ($status === 'dismissed' ? '#333333' : '#311');
                            $colorBorder = $status === 'resolved' ? '#2ecc71' : ($status === 'dismissed' ? '#555555' : '#a33');
                            $colorText = $status === 'resolved' ? '#c1ffda' : ($status === 'dismissed' ? '#dddddd' : '#ffbaba');
                            $refunded = (int)($r['refunded_tokens'] ?? 0);
                            ?>
                            <span style="display:inline-block; padding:2px 8px; border-radius:999px; border:1px solid <?= $colorBorder ?>; background:<?= $colorBg ?>; color:<?= $colorText ?>; font-size:11px; margin-right:4px;">
                                <?= $label ?>
                            </span>
                            <?php if ($refunded > 0): ?>
                                <span style="display:inline-block; padding:2px 6px; border-radius:999px; border:1px solid #2ecc71; background:#071b10; color:#9ff1c2; font-size:10px;">
                                    com estorno (<?= $refunded ?>)
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:6px 10px;">
                            <?php if (!empty($r['created_at'])): ?>
                                <?= htmlspecialchars(date('d/m/Y H:i', strtotime((string)$r['created_at']))) ?>
                            <?php endif; ?>
                        </td>
                        <td style="padding:6px 10px;">
                            <a href="/admin/erros/ver?id=<?= (int)$r['id'] ?>" style="font-size:12px; color:#ffcc80; text-decoration:none;">Ver detalhes</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
