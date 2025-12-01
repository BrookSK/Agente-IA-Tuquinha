<?php
/** @var array $plans */
?>
<div style="max-width: 880px; margin: 0 auto;">
    <h1 style="font-size: 22px; margin-bottom: 10px; font-weight: 650;">Gerenciar planos</h1>
    <p style="color:#b0b0b0; font-size:13px; margin-bottom:14px;">
        Aqui você controla os planos disponíveis na tela pública de planos e no fluxo de assinatura.
    </p>

    <div style="margin-bottom:12px;">
        <a href="/admin/planos/novo" style="
            display:inline-flex; align-items:center; gap:6px;
            border-radius:999px; padding:7px 12px;
            background:linear-gradient(135deg,#e53935,#ff6f60);
            color:#050509; font-size:13px; font-weight:600; text-decoration:none;">
            <span>+ Novo plano</span>
        </a>
    </div>

    <div style="border-radius:12px; border:1px solid #272727; overflow:hidden;">
        <table style="width:100%; border-collapse:collapse; font-size:13px;">
            <thead style="background:#0b0b10;">
                <tr>
                    <th style="text-align:left; padding:8px 10px; border-bottom:1px solid #272727;">Nome</th>
                    <th style="text-align:left; padding:8px 10px; border-bottom:1px solid #272727;">Slug</th>
                    <th style="text-align:left; padding:8px 10px; border-bottom:1px solid #272727;">Preço</th>
                    <th style="text-align:left; padding:8px 10px; border-bottom:1px solid #272727;">Recursos</th>
                    <th style="text-align:left; padding:8px 10px; border-bottom:1px solid #272727;">Status</th>
                    <th style="text-align:right; padding:8px 10px; border-bottom:1px solid #272727;">Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($plans)): ?>
                <tr>
                    <td colspan="6" style="padding:10px; color:#b0b0b0;">Nenhum plano cadastrado.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($plans as $plan): ?>
                    <?php
                        $price = number_format(($plan['price_cents'] ?? 0) / 100, 2, ',', '.');
                        $flags = [];
                        if (!empty($plan['allow_audio'])) $flags[] = 'Áudio';
                        if (!empty($plan['allow_images'])) $flags[] = 'Imagens';
                        if (!empty($plan['allow_files'])) $flags[] = 'Arquivos';
                        $flagsText = $flags ? implode(' · ', $flags) : '-';
                        $active = !empty($plan['is_active']);
                    ?>
                    <tr style="background:#111118; border-top:1px solid #272727;">
                        <td style="padding:8px 10px;">
                            <?= htmlspecialchars($plan['name'] ?? '') ?>
                        </td>
                        <td style="padding:8px 10px; color:#b0b0b0;">
                            <?= htmlspecialchars($plan['slug'] ?? '') ?>
                        </td>
                        <td style="padding:8px 10px;">
                            R$ <?= $price ?> / mês
                        </td>
                        <td style="padding:8px 10px; color:#b0b0b0;">
                            <?= htmlspecialchars($flagsText) ?>
                        </td>
                        <td style="padding:8px 10px;">
                            <span style="
                                display:inline-flex; align-items:center; gap:4px;
                                border-radius:999px; padding:2px 8px; font-size:11px;
                                background:<?= $active ? '#16351f' : '#332020' ?>;
                                color:<?= $active ? '#6be28d' : '#ff8a80' ?>;">
                                <?= $active ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </td>
                        <td style="padding:8px 10px; text-align:right; white-space:nowrap;">
                            <a href="/admin/planos/editar?id=<?= (int)$plan['id'] ?>" style="
                                margin-right:6px; color:#ff6f60; text-decoration:none;">Editar</a>
                            <a href="/admin/planos/ativar?id=<?= (int)$plan['id'] ?>&v=<?= $active ? '0' : '1' ?>" style="
                                color:#b0b0b0; text-decoration:none; font-size:12px;">
                                <?= $active ? 'Desativar' : 'Ativar' ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
