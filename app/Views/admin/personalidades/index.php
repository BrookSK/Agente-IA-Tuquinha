<?php
/** @var array $personalities */
?>
<div style="max-width: 900px; margin: 0 auto;">
    <h1 style="font-size: 22px; margin-bottom: 10px; font-weight: 650;">Personalidades do Tuquinha</h1>
    <p style="color:#b0b0b0; font-size:13px; margin-bottom:14px;">
        Aqui você cria e gerencia as personalidades que os usuários podem escolher antes de começar um chat.
    </p>

    <div style="margin-bottom:12px; display:flex; justify-content:space-between; align-items:center; gap:8px;">
        <a href="/admin/personalidades/novo" style="
            display:inline-flex; align-items:center; gap:6px;
            border-radius:999px; padding:7px 12px;
            background:linear-gradient(135deg,#e53935,#ff6f60);
            color:#050509; font-size:13px; font-weight:600; text-decoration:none;">
            <span>+ Nova personalidade</span>
        </a>
    </div>

    <div style="border-radius:12px; border:1px solid #272727; overflow:hidden;">
        <table style="width:100%; border-collapse:collapse; font-size:13px;">
            <thead style="background:#0b0b10;">
                <tr>
                    <th style="text-align:left; padding:8px 10px; border-bottom:1px solid #272727;">Nome</th>
                    <th style="text-align:left; padding:8px 10px; border-bottom:1px solid #272727;">Área</th>
                    <th style="text-align:left; padding:8px 10px; border-bottom:1px solid #272727;">Slug</th>
                    <th style="text-align:left; padding:8px 10px; border-bottom:1px solid #272727;">Padrão</th>
                    <th style="text-align:left; padding:8px 10px; border-bottom:1px solid #272727;">Status</th>
                    <th style="text-align:right; padding:8px 10px; border-bottom:1px solid #272727;">Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($personalities)): ?>
                <tr>
                    <td colspan="6" style="padding:10px; color:#b0b0b0;">Nenhuma personalidade cadastrada.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($personalities as $p): ?>
                    <?php
                        $active = !empty($p['active']);
                        $isDefault = !empty($p['is_default']);
                    ?>
                    <tr style="background:#111118; border-top:1px solid #272727;">
                        <td style="padding:8px 10px;">
                            <?= htmlspecialchars($p['name'] ?? '') ?>
                        </td>
                        <td style="padding:8px 10px; color:#b0b0b0;">
                            <?= htmlspecialchars($p['area'] ?? '') ?>
                        </td>
                        <td style="padding:8px 10px; color:#b0b0b0;">
                            <?= htmlspecialchars($p['slug'] ?? '') ?>
                        </td>
                        <td style="padding:8px 10px;">
                            <?php if ($isDefault): ?>
                                <span style="display:inline-flex; align-items:center; gap:4px; border-radius:999px; padding:2px 8px; font-size:11px; background:#201216; color:#ffcc80; border:1px solid #ff6f60;">Padrão</span>
                            <?php else: ?>
                                <a href="/admin/personalidades/padrao?id=<?= (int)$p['id'] ?>" style="font-size:11px; color:#ff6f60; text-decoration:none;">Definir como padrão</a>
                            <?php endif; ?>
                        </td>
                        <td style="padding:8px 10px;">
                            <span style="
                                display:inline-flex; align-items:center; gap:4px;
                                border-radius:999px; padding:2px 8px; font-size:11px;
                                background:<?= $active ? '#16351f' : '#332020' ?>;
                                color:<?= $active ? '#6be28d' : '#ff8a80' ?>;">
                                <?= $active ? 'Ativa' : 'Inativa' ?>
                            </span>
                        </td>
                        <td style="padding:8px 10px; text-align:right; white-space:nowrap;">
                            <a href="/admin/personalidades/editar?id=<?= (int)$p['id'] ?>" style="margin-right:6px; color:#ff6f60; text-decoration:none;">Editar</a>
                            <a href="/admin/personalidades/ativar?id=<?= (int)$p['id'] ?>&v=<?= $active ? '0' : '1' ?>" style="color:#b0b0b0; text-decoration:none; font-size:12px;">
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
