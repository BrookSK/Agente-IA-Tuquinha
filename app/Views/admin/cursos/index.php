<?php
/** @var array $courses */
?>
<div style="max-width: 900px; margin: 0 auto;">
    <h1 style="font-size: 22px; margin-bottom: 10px; font-weight: 650;">Cursos do Tuquinha</h1>
    <p style="color:#b0b0b0; font-size:13px; margin-bottom:14px;">
        Gerencie os cursos disponíveis para os usuários: defina título, acesso por plano ou compra avulsa e status.
    </p>

    <div style="margin-bottom:12px; display:flex; justify-content:space-between; align-items:center; gap:8px;">
        <a href="/admin/cursos/novo" style="
            display:inline-flex; align-items:center; gap:6px;
            border-radius:999px; padding:7px 12px;
            background:linear-gradient(135deg,#e53935,#ff6f60);
            color:#050509; font-size:13px; font-weight:600; text-decoration:none;">
            <span>+ Novo curso</span>
        </a>
    </div>

    <div style="border-radius:12px; border:1px solid #272727; overflow:hidden;">
        <table style="width:100%; border-collapse:collapse; font-size:13px;">
            <thead style="background:#0b0b10;">
                <tr>
                    <th style="text-align:left; padding:8px 10px; border-bottom:1px solid #272727;">Título</th>
                    <th style="text-align:left; padding:8px 10px; border-bottom:1px solid #272727;">Slug</th>
                    <th style="text-align:left; padding:8px 10px; border-bottom:1px solid #272727;">Acesso</th>
                    <th style="text-align:left; padding:8px 10px; border-bottom:1px solid #272727;">Preço</th>
                    <th style="text-align:left; padding:8px 10px; border-bottom:1px solid #272727;">Status</th>
                    <th style="text-align:right; padding:8px 10px; border-bottom:1px solid #272727;">Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($courses)): ?>
                <tr>
                    <td colspan="6" style="padding:10px; color:#b0b0b0;">Nenhum curso cadastrado.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($courses as $c): ?>
                    <?php
                        $isActive = !empty($c['is_active']);
                        $isPaid = !empty($c['is_paid']);
                        $allowPlanOnly = !empty($c['allow_plan_access_only']);
                        $allowPublicPurchase = !empty($c['allow_public_purchase']);
                    ?>
                    <tr style="background:#111118; border-top:1px solid #272727;">
                        <td style="padding:8px 10px;">
                            <?= htmlspecialchars($c['title'] ?? '') ?>
                        </td>
                        <td style="padding:8px 10px; color:#b0b0b0;">
                            <?= htmlspecialchars($c['slug'] ?? '') ?>
                        </td>
                        <td style="padding:8px 10px; color:#b0b0b0; font-size:11px;">
                            <?php if ($allowPlanOnly): ?>
                                <div>Planos com cursos</div>
                            <?php endif; ?>
                            <?php if ($allowPublicPurchase): ?>
                                <div>Visível/pago avulso</div>
                            <?php endif; ?>
                            <?php if (!$allowPlanOnly && !$allowPublicPurchase): ?>
                                <div style="color:#777;">Sem regra definida</div>
                            <?php endif; ?>
                        </td>
                        <td style="padding:8px 10px; color:#b0b0b0;">
                            <?php if ($isPaid && isset($c['price_cents'])): ?>
                                R$ <?= number_format(((int)$c['price_cents'])/100, 2, ',', '.') ?>
                            <?php else: ?>
                                <span style="font-size:11px; color:#a5d6a7;">Gratuito</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:8px 10px;">
                            <span style="
                                display:inline-flex; align-items:center; gap:4px;
                                border-radius:999px; padding:2px 8px; font-size:11px;
                                background:<?= $isActive ? '#16351f' : '#332020' ?>;
                                color:<?= $isActive ? '#6be28d' : '#ff8a80' ?>;">
                                <?= $isActive ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </td>
                        <td style="padding:8px 10px; text-align:right; white-space:nowrap;">
                            <a href="/admin/cursos/editar?id=<?= (int)$c['id'] ?>" style="margin-right:6px; color:#ff6f60; text-decoration:none;">Editar</a>
                            <a href="/admin/cursos/modulos?course_id=<?= (int)$c['id'] ?>" style="margin-right:6px; color:#b0b0b0; text-decoration:none; font-size:12px;">Módulos</a>
                            <a href="/admin/cursos/aulas?course_id=<?= (int)$c['id'] ?>" style="margin-right:6px; color:#b0b0b0; text-decoration:none; font-size:12px;">Aulas</a>
                            <a href="/admin/cursos/lives?course_id=<?= (int)$c['id'] ?>" style="color:#b0b0b0; text-decoration:none; font-size:12px;">Lives</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
