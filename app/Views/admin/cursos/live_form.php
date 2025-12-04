<?php
/** @var array $course */
/** @var array|null $live */
$isEdit = !empty($live);
?>
<div style="max-width: 720px; margin: 0 auto;">
    <h1 style="font-size: 20px; margin-bottom: 8px; font-weight: 650;">
        <?= $isEdit ? 'Editar live' : 'Nova live' ?> - <?= htmlspecialchars($course['title'] ?? '') ?>
    </h1>
    <p style="color:#b0b0b0; font-size:13px; margin-bottom:10px;">
        Defina título, data/horário e link da live. Se você não informar um link, o sistema gera um link de Meet genérico.
    </p>

    <?php if (!empty($_SESSION['admin_course_error'])): ?>
        <div style="background:#311; border:1px solid #a33; color:#ffbaba; padding:8px 10px; border-radius:8px; font-size:13px; margin-bottom:10px;">
            <?= htmlspecialchars($_SESSION['admin_course_error']) ?>
        </div>
        <?php unset($_SESSION['admin_course_error']); ?>
    <?php endif; ?>

    <form action="/admin/cursos/lives/salvar" method="post" style="display:flex; flex-direction:column; gap:10px;">
        <input type="hidden" name="course_id" value="<?= (int)$course['id'] ?>">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int)$live['id'] ?>">
        <?php endif; ?>

        <div>
            <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Título da live</label>
            <input type="text" name="title" required value="<?= htmlspecialchars($live['title'] ?? '') ?>" style="
                width:100%; padding:8px 10px; border-radius:8px; border:1px solid #272727;
                background:#050509; color:#f5f5f5; font-size:14px;">
        </div>

        <div>
            <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Data e horário</label>
            <input type="datetime-local" name="scheduled_at" required value="<?= !empty($live['scheduled_at']) ? date('Y-m-d\TH:i', strtotime($live['scheduled_at'])) : '' ?>" style="
                width:220px; padding:8px 10px; border-radius:8px; border:1px solid #272727;
                background:#050509; color:#f5f5f5; font-size:13px;">
        </div>

        <div>
            <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Link da reunião (Meet ou outro)</label>
            <input type="text" name="meet_link" value="<?= htmlspecialchars($live['meet_link'] ?? '') ?>" placeholder="Opcional. Ex: https://meet.google.com/..." style="
                width:100%; padding:8px 10px; border-radius:8px; border:1px solid #272727;
                background:#050509; color:#f5f5f5; font-size:13px;">
            <div style="font-size:11px; color:#777; margin-top:3px;">Se deixar em branco, o sistema gera um link de Meet aleatório.</div>
        </div>

        <div>
            <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Descrição (opcional)</label>
            <textarea name="description" rows="4" style="
                width:100%; padding:8px 10px; border-radius:8px; border:1px solid #272727;
                background:#050509; color:#f5f5f5; font-size:13px; resize:vertical;">
<?= htmlspecialchars($live['description'] ?? '') ?></textarea>
        </div>

        <div style="display:flex; flex-wrap:wrap; gap:10px; font-size:13px; color:#ddd; margin-top:4px;">
            <label style="display:flex; align-items:center; gap:5px;">
                <input type="checkbox" name="is_published" value="1" <?= !isset($live['is_published']) || !empty($live['is_published']) ? 'checked' : '' ?>>
                <span>Live publicada (visível para os alunos)</span>
            </label>
        </div>

        <div style="margin-top:12px; display:flex; gap:8px;">
            <button type="submit" style="
                border:none; border-radius:999px; padding:8px 16px;
                background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509;
                font-weight:600; font-size:13px; cursor:pointer;">
                Salvar live
            </button>
            <a href="/admin/cursos/lives?course_id=<?= (int)$course['id'] ?>" style="
                display:inline-flex; align-items:center; padding:8px 14px;
                border-radius:999px; border:1px solid #272727; color:#f5f5f5;
                font-size:13px; text-decoration:none;">
                Cancelar
            </a>
        </div>
    </form>
</div>
