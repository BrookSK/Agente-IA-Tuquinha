<?php
/** @var array $user */
/** @var array|null $partner */
/** @var array $rows */
?>
<div style="max-width: 960px; margin: 0 auto;">
    <h1 style="font-size: 22px; margin-bottom: 8px; font-weight: 650;">Meus cursos como parceiro</h1>
    <p style="color:#b0b0b0; font-size:13px; margin-bottom:12px;">
        Aqui você acompanha os cursos em que é dono/parceiro, a porcentagem de comissão definida e quantos alunos já estão inscritos.
    </p>

    <?php if (!$partner): ?>
        <div style="background:#111118; border-radius:12px; border:1px solid #272727; padding:10px 12px; font-size:13px; color:#b0b0b0; margin-bottom:12px;">
            Nenhum cadastro de parceiro foi encontrado para seu usuário ainda. Combine com o admin para configurar seu perfil de parceiro e vincular cursos ao seu usuário.
        </div>
    <?php else: ?>
        <div style="background:#111118; border-radius:12px; border:1px solid #272727; padding:8px 12px; font-size:12px; color:#b0b0b0; margin-bottom:12px;">
            Comissão padrão configurada: <strong><?= number_format((float)($partner['default_commission_percent'] ?? 0.0), 2, ',', '.') ?>%</strong>
        </div>
    <?php endif; ?>

    <?php if (empty($rows)): ?>
        <div style="margin-top:8px; color:#b0b0b0; font-size:13px;">
            Você ainda não é dono de nenhum curso cadastrado.
        </div>
    <?php else: ?>
        <div style="border-radius:12px; border:1px solid #272727; overflow:hidden;">
            <table style="width:100%; border-collapse:collapse; font-size:13px;">
                <thead style="background:#0b0b10;">
                    <tr>
                        <th style="text-align:left; padding:8px 10px; border-bottom:1px solid #272727;">Curso</th>
                        <th style="text-align:left; padding:8px 10px; border-bottom:1px solid #272727;">Slug</th>
                        <th style="text-align:center; padding:8px 10px; border-bottom:1px solid #272727;">Alunos</th>
                        <th style="text-align:left; padding:8px 10px; border-bottom:1px solid #272727;">Comissão</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <?php $course = $row['course']; ?>
                    <tr style="background:#111118; border-top:1px solid #272727;">
                        <td style="padding:8px 10px;">
                            <?= htmlspecialchars($course['title'] ?? '') ?>
                        </td>
                        <td style="padding:8px 10px; color:#b0b0b0;">
                            <?= htmlspecialchars($course['slug'] ?? '') ?>
                        </td>
                        <td style="padding:8px 10px; text-align:center;">
                            <?= (int)($row['enrollment_count'] ?? 0) ?>
                        </td>
                        <td style="padding:8px 10px; color:#b0b0b0;">
                            <?php if ($row['commission_percent'] !== null): ?>
                                <?= number_format((float)$row['commission_percent'], 2, ',', '.') ?>%
                            <?php else: ?>
                                <span style="font-size:11px; color:#777;">Não configurada</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
