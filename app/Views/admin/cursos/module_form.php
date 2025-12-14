<?php
/** @var array $course */
/** @var array|null $module */
$isEdit = !empty($module);
?>
<div style="max-width: 720px; margin: 0 auto;">
    <h1 style="font-size: 20px; margin-bottom: 8px; font-weight: 650;">
        <?= $isEdit ? 'Editar módulo' : 'Novo módulo' ?> - <?= htmlspecialchars($course['title'] ?? '') ?>
    </h1>
    <p style="color:var(--text-secondary); font-size:13px; margin-bottom:10px;">
        Organize as aulas em módulos para facilitar o progresso dos alunos.
    </p>

    <?php if (!empty($_SESSION['admin_course_error'])): ?>
        <div style="background:#311; border:1px solid #a33; color:#ffbaba; padding:8px 10px; border-radius:8px; font-size:13px; margin-bottom:10px;">
            <?= htmlspecialchars($_SESSION['admin_course_error']) ?>
        </div>
        <?php unset($_SESSION['admin_course_error']); ?>
    <?php endif; ?>

    <form action="/admin/cursos/modulos/salvar" method="post" style="display:flex; flex-direction:column; gap:10px;">
        <input type="hidden" name="course_id" value="<?= (int)$course['id'] ?>">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int)$module['id'] ?>">
        <?php endif; ?>

        <div>
            <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Título do módulo</label>
            <input type="text" name="title" required value="<?= htmlspecialchars($module['title'] ?? '') ?>" style="
                width:100%; padding:8px 10px; border-radius:8px; border:1px solid var(--border-subtle);
                background:var(--surface-subtle); color:var(--text-primary); font-size:14px;">
        </div>

        <div>
            <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Descrição (opcional)</label>
            <textarea name="description" rows="4" style="
                width:100%; padding:8px 10px; border-radius:8px; border:1px solid var(--border-subtle);
                background:var(--surface-subtle); color:var(--text-primary); font-size:13px; resize:vertical;">
<?= htmlspecialchars($module['description'] ?? '') ?></textarea>
        </div>

        <div style="display:flex; gap:14px; flex-wrap:wrap;">
            <div style="flex:1 1 160px;">
                <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Ordem</label>
                <input type="number" name="sort_order" value="<?= isset($module['sort_order']) ? (int)$module['sort_order'] : 0 ?>" style="
                    width:120px; padding:8px 10px; border-radius:8px; border:1px solid var(--border-subtle);
                    background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
            </div>
        </div>

        <div style="margin-top:12px; display:flex; gap:8px;">
            <button type="submit" style="
                border:none; border-radius:999px; padding:8px 16px;
                background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509;
                font-weight:600; font-size:13px; cursor:pointer;">
                Salvar módulo
            </button>
            <a href="/admin/cursos/modulos?course_id=<?= (int)$course['id'] ?>" style="
                display:inline-flex; align-items:center; padding:8px 14px;
                border-radius:999px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary);
                font-size:13px; text-decoration:none;">
                Cancelar
            </a>
        </div>
    </form>
</div>
