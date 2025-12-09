<?php
/** @var array $course */
/** @var array $modules */
?>
<div style="max-width: 900px; margin: 0 auto;">
    <h1 style="font-size: 20px; margin-bottom: 8px; font-weight: 650;">
        Módulos do curso: <?= htmlspecialchars($course['title'] ?? '') ?>
    </h1>
    <p style="color:#b0b0b0; font-size:13px; margin-bottom:12px;">
        Crie módulos para organizar as aulas em blocos e, se desejar, ative uma prova ao final de cada módulo.
    </p>

    <div style="margin-bottom:12px; display:flex; justify-content:space-between; gap:8px; align-items:center;">
        <a href="/admin/cursos" style="font-size:12px; color:#b0b0b0; text-decoration:none;">&larr; Voltar para cursos</a>
        <div style="display:flex; gap:8px;">
            <a href="/admin/cursos/aulas?course_id=<?= (int)$course['id'] ?>" style="
                font-size:12px; color:#b0b0b0; text-decoration:none; border-radius:999px; padding:6px 10px;
                border:1px solid #272727;">Ver aulas</a>
            <a href="/admin/cursos/modulos/novo?course_id=<?= (int)$course['id'] ?>" style="
                display:inline-flex; align-items:center; gap:6px;
                border-radius:999px; padding:7px 12px;
                background:linear-gradient(135deg,#e53935,#ff6f60);
                color:#050509; font-size:13px; font-weight:600; text-decoration:none;">
                <span>+ Novo módulo</span>
            </a>
        </div>
    </div>

    <?php if (!empty($_SESSION['admin_course_error'])): ?>
        <div style="background:#311; border:1px solid #a33; color:#ffbaba; padding:8px 10px; border-radius:8px; font-size:13px; margin-bottom:10px;">
            <?= htmlspecialchars($_SESSION['admin_course_error']) ?>
        </div>
        <?php unset($_SESSION['admin_course_error']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['admin_course_success'])): ?>
        <div style="background:#10330f; border:1px solid #3aa857; color:#c8ffd4; padding:8px 10px; border-radius:8px; font-size:13px; margin-bottom:10px;">
            <?= htmlspecialchars($_SESSION['admin_course_success']) ?>
        </div>
        <?php unset($_SESSION['admin_course_success']); ?>
    <?php endif; ?>

    <div style="border-radius:12px; border:1px solid #272727; overflow:hidden;">
        <table style="width:100%; border-collapse:collapse; font-size:13px;">
            <thead style="background:#0b0b10;">
                <tr>
                    <th style="text-align:left; padding:8px 10px; border-bottom:1px solid #272727;">Módulo</th>
                    <th style="text-align:left; padding:8px 10px; border-bottom:1px solid #272727;">Descrição</th>
                    <th style="text-align:center; padding:8px 10px; border-bottom:1px solid #272727;">Ordem</th>
                    <th style="text-align:left; padding:8px 10px; border-bottom:1px solid #272727;">Prova</th>
                    <th style="text-align:right; padding:8px 10px; border-bottom:1px solid #272727;">Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($modules)): ?>
                <tr>
                    <td colspan="5" style="padding:10px; color:#b0b0b0;">Nenhum módulo cadastrado.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($modules as $m): ?>
                    <?php
                        $mid = (int)($m['id'] ?? 0);
                        $hasExam = !empty($m['exam'] ?? null);
                        $exam = $m['exam'] ?? null;
                    ?>
                    <tr style="background:#111118; border-top:1px solid #272727;">
                        <td style="padding:8px 10px; font-weight:600;">
                            <?= htmlspecialchars($m['title'] ?? '') ?>
                        </td>
                        <td style="padding:8px 10px; color:#b0b0b0; max-width:280px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                            <?= htmlspecialchars($m['description'] ?? '') ?>
                        </td>
                        <td style="padding:8px 10px; text-align:center;">
                            <?= (int)($m['sort_order'] ?? 0) ?>
                        </td>
                        <td style="padding:8px 10px;">
                            <?php if ($exam): ?>
                                <span style="font-size:11px; color:#c8ffd4;">Ativa (mín. <?= (int)($exam['pass_score_percent'] ?? 70) ?>%, máx. <?= (int)($exam['max_attempts'] ?? 3) ?> tentativas)</span>
                            <?php else: ?>
                                <span style="font-size:11px; color:#b0b0b0;">Nenhuma prova configurada</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:8px 10px; text-align:right; white-space:nowrap;">
                            <a href="/admin/cursos/modulos/editar?course_id=<?= (int)$course['id'] ?>&id=<?= $mid ?>" style="margin-right:6px; color:#ff6f60; text-decoration:none;">Editar</a>
                            <a href="/admin/cursos/modulos/prova?course_id=<?= (int)$course['id'] ?>&module_id=<?= $mid ?>" style="margin-right:6px; color:#ffcc80; text-decoration:none;">Prova</a>
                            <form action="/admin/cursos/modulos/excluir" method="post" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja excluir este módulo? As aulas continuarão existindo, apenas sem vínculo com este módulo.');">
                                <input type="hidden" name="course_id" value="<?= (int)$course['id'] ?>">
                                <input type="hidden" name="id" value="<?= $mid ?>">
                                <button type="submit" style="border:none; background:none; color:#b0b0b0; font-size:12px; cursor:pointer;">Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
