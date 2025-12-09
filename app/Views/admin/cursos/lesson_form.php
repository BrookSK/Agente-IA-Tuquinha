<?php
/** @var array $course */
/** @var array|null $lesson */
$isEdit = !empty($lesson);
?>
<div style="max-width: 720px; margin: 0 auto;">
    <h1 style="font-size: 20px; margin-bottom: 8px; font-weight: 650;">
        <?= $isEdit ? 'Editar aula' : 'Nova aula' ?> - <?= htmlspecialchars($course['title'] ?? '') ?>
    </h1>
    <p style="color:#b0b0b0; font-size:13px; margin-bottom:10px;">
        Informe título, link do vídeo (YouTube ou outro player) e, opcionalmente, uma descrição.
    </p>

    <?php if (!empty($_SESSION['admin_course_error'])): ?>
        <div style="background:#311; border:1px solid #a33; color:#ffbaba; padding:8px 10px; border-radius:8px; font-size:13px; margin-bottom:10px;">
            <?= htmlspecialchars($_SESSION['admin_course_error']) ?>
        </div>
        <?php unset($_SESSION['admin_course_error']); ?>
    <?php endif; ?>

    <form action="/admin/cursos/aulas/salvar" method="post" style="display:flex; flex-direction:column; gap:10px;">
        <input type="hidden" name="course_id" value="<?= (int)$course['id'] ?>">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int)$lesson['id'] ?>">
        <?php endif; ?>

        <div>
            <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Título da aula</label>
            <input type="text" name="title" required value="<?= htmlspecialchars($lesson['title'] ?? '') ?>" style="
                width:100%; padding:8px 10px; border-radius:8px; border:1px solid #272727;
                background:#050509; color:#f5f5f5; font-size:14px;">
        </div>

        <div>
            <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Link do vídeo</label>
            <input type="text" name="video_url" required value="<?= htmlspecialchars($lesson['video_url'] ?? '') ?>" placeholder="Ex: https://www.youtube.com/watch?v=..." style="
                width:100%; padding:8px 10px; border-radius:8px; border:1px solid #272727;
                background:#050509; color:#f5f5f5; font-size:13px;">
        </div>

        <div>
            <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Descrição (opcional)</label>
            <textarea name="description" rows="4" style="
                width:100%; padding:8px 10px; border-radius:8px; border:1px solid #272727;
                background:#050509; color:#f5f5f5; font-size:13px; resize:vertical;">
<?= htmlspecialchars($lesson['description'] ?? '') ?></textarea>
        </div>

        <div style="display:flex; gap:14px; flex-wrap:wrap;">
            <div style="flex:1 1 200px;">
                <?php $modules = $modules ?? []; $currentModuleId = isset($lesson['module_id']) ? (int)$lesson['module_id'] : 0; ?>
                <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Módulo (opcional)</label>
                <select name="module_id" style="
                    width:100%; max-width:260px; padding:8px 10px; border-radius:8px; border:1px solid #272727;
                    background:#050509; color:#f5f5f5; font-size:13px;">
                    <option value="">Sem módulo</option>
                    <?php foreach ($modules as $m): ?>
                        <?php $mid = (int)($m['id'] ?? 0); ?>
                        <option value="<?= $mid ?>" <?= $mid > 0 && $mid === $currentModuleId ? 'selected' : '' ?>>
                            <?= htmlspecialchars($m['title'] ?? '') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="flex:1 1 120px;">
                <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Ordem</label>
                <input type="number" name="sort_order" value="<?= isset($lesson['sort_order']) ? (int)$lesson['sort_order'] : 0 ?>" style="
                    width:120px; padding:8px 10px; border-radius:8px; border:1px solid #272727;
                    background:#050509; color:#f5f5f5; font-size:13px;">
            </div>
            <div style="flex:1 1 160px; display:flex; align-items:flex-end;">
                <label style="display:flex; align-items:center; gap:5px; font-size:13px; color:#ddd;">
                    <input type="checkbox" name="is_published" value="1" <?= !isset($lesson['is_published']) || !empty($lesson['is_published']) ? 'checked' : '' ?>>
                    <span>Aula publicada</span>
                </label>
            </div>
        </div>

        <div style="margin-top:12px; display:flex; gap:8px;">
            <button type="submit" style="
                border:none; border-radius:999px; padding:8px 16px;
                background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509;
                font-weight:600; font-size:13px; cursor:pointer;">
                Salvar aula
            </button>
            <a href="/admin/cursos/aulas?course_id=<?= (int)$course['id'] ?>" style="
                display:inline-flex; align-items:center; padding:8px 14px;
                border-radius:999px; border:1px solid #272727; color:#f5f5f5;
                font-size:13px; text-decoration:none;">
                Cancelar
            </a>
        </div>
    </form>
</div>
