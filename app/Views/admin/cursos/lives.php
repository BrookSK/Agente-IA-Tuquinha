<?php
/** @var array $course */
/** @var array $lives */
?>
<div style="max-width: 900px; margin: 0 auto;">
    <h1 style="font-size: 20px; margin-bottom: 8px; font-weight: 650;">
        Lives do curso: <?= htmlspecialchars($course['title'] ?? '') ?>
    </h1>
    <p style="color:#b0b0b0; font-size:13px; margin-bottom:12px;">
        Agende lives para este curso. Os alunos inscritos poderão confirmar participação e receber o link.
    </p>

    <div style="margin-bottom:12px; display:flex; justify-content:space-between; gap:8px; align-items:center;">
        <a href="/admin/cursos" style="font-size:12px; color:#b0b0b0; text-decoration:none;">&larr; Voltar para cursos</a>
        <a href="/admin/cursos/lives/nova?course_id=<?= (int)$course['id'] ?>" style="
            display:inline-flex; align-items:center; gap:6px;
            border-radius:999px; padding:7px 12px;
            background:linear-gradient(135deg,#e53935,#ff6f60);
            color:#050509; font-size:13px; font-weight:600; text-decoration:none;">
            <span>+ Nova live</span>
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
                    <th style="text-align:left; padding:8px 10px; border-bottom:1px solid #272727;">Data / horário</th>
                    <th style="text-align:left; padding:8px 10px; border-bottom:1px solid #272727;">Link</th>
                    <th style="text-align:left; padding:8px 10px; border-bottom:1px solid #272727;">Status</th>
                    <th style="text-align:right; padding:8px 10px; border-bottom:1px solid #272727;">Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($lives)): ?>
                <tr>
                    <td colspan="5" style="padding:10px; color:#b0b0b0;">Nenhuma live cadastrada.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($lives as $live): ?>
                    <?php
                        $published = !empty($live['is_published']);
                        $scheduled = $live['scheduled_at'] ?? '';
                        $formatted = $scheduled ? date('d/m/Y H:i', strtotime($scheduled)) : '';
                    ?>
                    <tr style="background:#111118; border-top:1px solid #272727;">
                        <td style="padding:8px 10px;">
                            <?= htmlspecialchars($live['title'] ?? '') ?>
                        </td>
                        <td style="padding:8px 10px; color:#b0b0b0;">
                            <?= htmlspecialchars($formatted) ?>
                        </td>
                        <td style="padding:8px 10px; color:#b0b0b0; max-width:260px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                            <?= htmlspecialchars($live['meet_link'] ?? '') ?>
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
                            <a href="/admin/cursos/lives/editar?course_id=<?= (int)$course['id'] ?>&id=<?= (int)$live['id'] ?>" style="margin-right:6px; color:#ff6f60; text-decoration:none;">Editar</a>
                            <form action="/admin/cursos/lives/enviar-lembretes" method="post" style="display:inline;" onsubmit="return confirm('Enviar lembretes desta live para todos os participantes confirmados que ainda não receberam lembrete?');">
                                <input type="hidden" name="course_id" value="<?= (int)$course['id'] ?>">
                                <input type="hidden" name="live_id" value="<?= (int)$live['id'] ?>">
                                <button type="submit" style="border:none; background:none; color:#b0b0b0; font-size:12px; cursor:pointer;">Enviar lembretes</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
