<?php
/** @var array $user */
/** @var array $items */

$items = is_array($items ?? null) ? $items : [];
?>

<div style="max-width: 860px; margin: 0 auto;">
    <h1 style="font-size:22px; font-weight:650; margin-bottom:10px;">Cursos concluídos</h1>

    <?php if (empty($items)): ?>
        <div style="color:var(--text-secondary); font-size:13px;">
            Você ainda não concluiu nenhum curso.
        </div>
    <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:10px;">
            <?php foreach ($items as $it): ?>
                <?php
                    $courseId = (int)($it['course_id'] ?? 0);
                    $title = trim((string)($it['course_title'] ?? ''));
                    $badgeUrl = trim((string)($it['badge_image_path'] ?? ''));
                    $hasCertificate = !empty($it['certificate_code']);
                ?>
                <div class="cert-item" style="border-radius:14px; border:1px solid var(--border-subtle); background:var(--surface-card); padding:12px 12px; display:flex; gap:12px; align-items:center;">
                    <div style="width:44px; height:44px; border-radius:12px; overflow:hidden; border:1px solid var(--border-subtle); background:var(--surface-subtle); display:flex; align-items:center; justify-content:center; flex:0 0 auto;">
                        <?php if ($badgeUrl !== ''): ?>
                            <img src="<?= htmlspecialchars($badgeUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Insígnia" style="width:100%; height:100%; object-fit:cover; display:block;">
                        <?php else: ?>
                            <span style="font-size:18px;">🏅</span>
                        <?php endif; ?>
                    </div>
                    <div style="flex:1; min-width:0;">
                        <div style="font-size:14px; font-weight:650;">
                            <?= htmlspecialchars($title !== '' ? $title : 'Curso') ?>
                        </div>
                        <div style="font-size:12px; color:var(--text-secondary);">
                            Concluído em <?= !empty($it['earned_at']) ? htmlspecialchars((string)$it['earned_at']) : '-' ?>
                        </div>
                    </div>
                    <div class="cert-actions" style="display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end;">
                        <a href="/cursos/ver?id=<?= $courseId ?>" style="display:inline-flex; align-items:center; padding:7px 12px; border-radius:999px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:12px; text-decoration:none;">Ver curso</a>
                        <?php if ($hasCertificate): ?>
                            <a href="/certificados/ver?course_id=<?= $courseId ?>" style="display:inline-flex; align-items:center; padding:7px 12px; border-radius:999px; border:1px solid #ffcc80; background:linear-gradient(135deg,#ffcc80,#ff8a65); color:#050509; font-size:12px; font-weight:700; text-decoration:none;">Abrir certificado</a>
                            <a href="/certificados/ver?course_id=<?= $courseId ?>&print=1" target="_blank" rel="noopener" style="display:inline-flex; align-items:center; padding:7px 12px; border-radius:999px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:12px; text-decoration:none;">Baixar/Imprimir</a>
                        <?php else: ?>
                            <span style="display:inline-flex; align-items:center; padding:7px 12px; border-radius:999px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-secondary); font-size:12px;">Certificado indisponível</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
@media (max-width: 520px) {
    .cert-item {
        flex-direction: column !important;
        align-items: flex-start !important;
    }
    .cert-actions {
        width: 100% !important;
        justify-content: stretch !important;
    }
    .cert-actions a,
    .cert-actions span {
        width: 100% !important;
        justify-content: center !important;
        text-align: center !important;
    }
}
</style>
