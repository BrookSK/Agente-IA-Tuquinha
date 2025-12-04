<?php
/** @var array $course */
/** @var array $lessons */
?>
<div style="max-width: 900px; margin: 0 auto;">
    <h1 style="font-size: 20px; margin-bottom: 8px; font-weight: 650;">
        Aulas do curso: <?= htmlspecialchars($course['title'] ?? '') ?>
    </h1>
    <p style="color:#b0b0b0; font-size:13px; margin-bottom:12px;">
        Cadastre e organize as aulas deste curso. As novas aulas podem disparar e-mail para os alunos inscritos.
    </p>

    <div style="margin-bottom:12px; display:flex; justify-content:space-between; gap:8px; align-items:center;">
        <a href="/admin/cursos" style="font-size:12px; color:#b0b0b0; text-decoration:none;">&larr; Voltar para cursos</a>
        <a href="/admin/cursos/aulas/nova?course_id=<?= (int)$course['id'] ?>" style="
            display:inline-flex; align-items:center; gap:6px;
            border-radius:999px; padding:7px 12px;
            background:linear-gradient(135deg,#e53935,#ff6f60);
            color:#050509; font-size:13px; font-weight:600; text-decoration:none;">
            <span>+ Nova aula</span>
        </a>
    </div>

    <?php if (!empty($_SESSION['admin_course_error'])): ?>
        <div style="background:#311; border:1px solid #a33; color:#ffbaba; padding:8px 10px; border-radius:8px; font-size:13px; margin-bottom:10px;">
            <?= htmlspecialchars($_SESSION['admin_course_error']) ?>
        </div>
        <?php unset($_SESSION['admin_course_error']); ?>
    <?php endif; ?>

    <div style="border-radius:12px; border:1px solid #272727; overflow:hidden;">
        <table style="width:100%; border-collapse:collapse; font-size:13px;">
            <thead style="background:#0b0b10;">
                <tr>
                    <th style="text-align:left; padding:8px 10px; border-bottom:1px solid #272727;">Título</th>
                    <th style="text-align:left; padding:8px 10px; border-bottom:1px solid #272727;">Link do vídeo</th>
                    <th style="text-align:center; padding:8px 10px; border-bottom:1px solid #272727;">Ordem</th>
                    <th style="text-align:left; padding:8px 10px; border-bottom:1px solid #272727;">Status</th>
                    <th style="text-align:right; padding:8px 10px; border-bottom:1px solid #272727;">Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($lessons)): ?>
                <tr>
                    <td colspan="5" style="padding:10px; color:#b0b0b0;">Nenhuma aula cadastrada.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($lessons as $l): ?>
                    <?php $published = !empty($l['is_published']); ?>
                    <tr style="background:#111118; border-top:1px solid #272727;">
                        <td style="padding:8px 10px;">
                            <?= htmlspecialchars($l['title'] ?? '') ?>
                        </td>
                        <td style="padding:8px 10px; color:#b0b0b0; max-width:280px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                            <?= htmlspecialchars($l['video_url'] ?? '') ?>
                        </td>
                        <td style="padding:8px 10px; text-align:center;">
                            <?= (int)($l['sort_order'] ?? 0) ?>
                        </td>
                        <td style="padding:8px 10px;">
                            <span style="
                                display:inline-flex; align-items:center; gap:4px;
                                border-radius:999px; padding:2px 8px; font-size:11px;
                                background:<?= $published ? '#16351f' : '#332020' ?>;
                                color:<?= $published ? '#6be28d' : '#ff8a80' ?>;">
                                <?= $published ? 'Publicado' : 'Rascunho' ?>
                            </span>
                        </td>
                        <td style="padding:8px 10px; text-align:right; white-space:nowrap;">
                            <a href="/admin/cursos/aulas/editar?course_id=<?= (int)$course['id'] ?>&id=<?= (int)$l['id'] ?>" style="margin-right:6px; color:#ff6f60; text-decoration:none;">Editar</a>
                            <form action="/admin/cursos/aulas/excluir" method="post" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja excluir esta aula?');">
                                <input type="hidden" name="course_id" value="<?= (int)$course['id'] ?>">
                                <input type="hidden" name="id" value="<?= (int)$l['id'] ?>">
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
