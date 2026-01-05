<?php
/** @var array|null $persona */
$isEdit = !empty($persona);
?>
<div style="max-width: 720px; margin: 0 auto;">
    <h1 style="font-size: 22px; margin-bottom: 10px; font-weight: 650;">
        <?= $isEdit ? 'Editar personalidade' : 'Nova personalidade' ?>
    </h1>
    <p style="color:var(--text-secondary); font-size:13px; margin-bottom:10px;">
        Dê um nome, uma área de atuação, um texto de prompt detalhado e uma imagem para essa personalidade do Tuquinha.
    </p>

    <?php if (!empty($_SESSION['admin_persona_error'])): ?>
        <div style="background:#311; border:1px solid #a33; color:#ffbaba; padding:8px 10px; border-radius:8px; font-size:13px; margin-bottom:10px;">
            <?= htmlspecialchars($_SESSION['admin_persona_error']) ?>
        </div>
        <?php unset($_SESSION['admin_persona_error']); ?>
    <?php endif; ?>

    <form action="/admin/personalidades/salvar" method="post" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:10px;">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int)$persona['id'] ?>">
        <?php endif; ?>

        <div style="display:flex; gap:14px; align-items:flex-start; flex-wrap:wrap;">
            <div style="flex:1 1 260px;">
                <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Nome</label>
                <input type="text" name="name" required value="<?= htmlspecialchars($persona['name'] ?? '') ?>" style="
                    width:100%; padding:8px 10px; border-radius:8px; border:1px solid var(--border-subtle);
                    background:var(--surface-subtle); color:var(--text-primary); font-size:14px;">
            </div>
            <div style="flex:1 1 220px;">
                <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Área</label>
                <input type="text" name="area" required value="<?= htmlspecialchars($persona['area'] ?? '') ?>" placeholder="Ex: Marcas, Redes sociais" style="
                    width:100%; padding:8px 10px; border-radius:8px; border:1px solid var(--border-subtle);
                    background:var(--surface-subtle); color:var(--text-primary); font-size:14px;">
            </div>
        </div>

        <div>
            <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Slug (técnico)</label>
            <input type="text" name="slug" required value="<?= htmlspecialchars($persona['slug'] ?? '') ?>" placeholder="ex: especialista-marcas" style="
                width:100%; padding:8px 10px; border-radius:8px; border:1px solid var(--border-subtle);
                background:var(--surface-subtle); color:var(--text-primary); font-size:14px;">
            <div style="font-size:11px; color:#777; margin-top:3px;">Usado em URLs e integrações. Use apenas letras minúsculas, números e hífens.</div>
        </div>

        <div>
            <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">URL da imagem</label>
            <input type="text" name="image_path" value="<?= htmlspecialchars($persona['image_path'] ?? '') ?>" placeholder="Opcional. Ex: /public/img/tuquinha-marcas.png" style="
                width:100%; padding:8px 10px; border-radius:8px; border:1px solid var(--border-subtle);
                background:var(--surface-subtle); color:var(--text-primary); font-size:14px;">
            <div style="font-size:11px; color:#777; margin-top:3px;">Pode ser um caminho interno do site ou uma URL completa de CDN/imagens.</div>
        </div>

        <div>
            <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Upload de nova imagem</label>
            <input type="file" name="image_file" accept="image/*" style="
                width:100%; padding:6px 0; border-radius:8px; border:1px dashed var(--border-subtle);
                background:transparent; color:var(--text-primary); font-size:13px;">
            <div style="font-size:11px; color:#777; margin-top:3px;">
                Opcional. Se você enviar um arquivo de imagem, ele será salvo no servidor e substituirá a URL acima.
            </div>
        </div>

        <div>
            <label style="font-size:13px; color:var(--text-primary); display:block; margin-bottom:4px;">Prompt da personalidade</label>
            <textarea name="prompt" rows="10" required style="
                width:100%; padding:8px 10px; border-radius:8px; border:1px solid var(--border-subtle);
                background:var(--surface-subtle); color:var(--text-primary); font-size:13px; resize:vertical; min-height:180px;">
<?= htmlspecialchars($persona['prompt'] ?? '') ?></textarea>
            <div style="font-size:11px; color:#777; margin-top:3px;">
                Explique detalhadamente como essa personalidade deve se comportar: tom de voz, foco, o que priorizar, o que evitar, exemplos de tarefas.
            </div>
        </div>

        <div style="display:flex; flex-wrap:wrap; gap:10px; font-size:13px; color:var(--text-secondary); margin-top:4px;">
            <label style="display:flex; align-items:center; gap:5px;">
                <input type="checkbox" name="active" value="1" <?= !isset($persona['active']) || !empty($persona['active']) ? 'checked' : '' ?>>
                <span>Personalidade ativa</span>
            </label>
            <label style="display:flex; align-items:center; gap:5px;">
                <input type="checkbox" name="is_default" value="1" <?= !empty($persona['is_default']) ? 'checked' : '' ?>>
                <span>Definir como personalidade padrão global</span>
            </label>
            <label style="display:flex; align-items:center; gap:5px;">
                <input type="checkbox" name="coming_soon" value="1" <?= !empty($persona['coming_soon']) ? 'checked' : '' ?>>
                <span>Em breve (preview, sem permitir uso)</span>
            </label>
        </div>
        <div style="font-size:11px; color:#777; margin-top:3px;">
            Apenas uma personalidade será considerada padrão global; se marcar mais de uma, o sistema considera a primeira pelo ID.
        </div>

        <div style="margin-top:12px; display:flex; gap:8px;">
            <button type="submit" style="
                border:none; border-radius:999px; padding:8px 16px;
                background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509;
                font-weight:600; font-size:13px; cursor:pointer;">
                Salvar personalidade
            </button>
            <a href="/admin/personalidades" style="
                display:inline-flex; align-items:center; padding:8px 14px;
                border-radius:999px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary);
                font-size:13px; text-decoration:none;">
                Cancelar
            </a>
        </div>
    </form>
</div>
