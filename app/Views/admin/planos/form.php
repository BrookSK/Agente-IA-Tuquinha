<?php
/** @var array|null $plan */
$isEdit = !empty($plan);
?>
<div style="max-width: 640px; margin: 0 auto;">
    <h1 style="font-size: 22px; margin-bottom: 10px; font-weight: 650;">
        <?= $isEdit ? 'Editar plano' : 'Novo plano' ?>
    </h1>
    <p style="color:#b0b0b0; font-size:13px; margin-bottom:14px;">
        Defina nome, slug, preço e quais recursos esse plano libera no Tuquinha.
    </p>

    <form action="/admin/planos/salvar" method="post" style="display:flex; flex-direction:column; gap:10px;">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int)$plan['id'] ?>">
        <?php endif; ?>

        <div>
            <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Nome do plano</label>
            <input type="text" name="name" required value="<?= htmlspecialchars($plan['name'] ?? '') ?>" style="
                width:100%; padding:8px 10px; border-radius:8px; border:1px solid #272727;
                background:#050509; color:#f5f5f5; font-size:14px;">
        </div>

        <div>
            <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Slug</label>
            <input type="text" name="slug" required value="<?= htmlspecialchars($plan['slug'] ?? '') ?>" style="
                width:100%; padding:8px 10px; border-radius:8px; border:1px solid #272727;
                background:#050509; color:#f5f5f5; font-size:14px;">
            <div style="font-size:11px; color:#777; margin-top:3px;">Usado nas URLs e integrações (ex: free, pro, expert).</div>
        </div>

        <div>
            <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Preço mensal (R$)</label>
            <input type="text" name="price" required value="<?= isset($plan['price_cents']) ? number_format($plan['price_cents']/100, 2, ',', '.') : '0,00' ?>" style="
                width:120px; padding:8px 10px; border-radius:8px; border:1px solid #272727;
                background:#050509; color:#f5f5f5; font-size:14px;">
        </div>

        <div>
            <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Descrição curta</label>
            <textarea name="description" rows="2" style="
                width:100%; padding:8px 10px; border-radius:8px; border:1px solid #272727;
                background:#050509; color:#f5f5f5; font-size:13px; resize:vertical;">
<?= htmlspecialchars($plan['description'] ?? '') ?></textarea>
        </div>

        <div>
            <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Benefícios (um por linha)</label>
            <textarea name="benefits" rows="4" style="
                width:100%; padding:8px 10px; border-radius:8px; border:1px solid #272727;
                background:#050509; color:#f5f5f5; font-size:13px; resize:vertical;">
<?= htmlspecialchars($plan['benefits'] ?? '') ?></textarea>
        </div>

        <div style="display:flex; flex-wrap:wrap; gap:10px; font-size:13px; color:#ddd;">
            <label style="display:flex; align-items:center; gap:5px;">
                <input type="checkbox" name="allow_audio" value="1" <?= !empty($plan['allow_audio']) ? 'checked' : '' ?>>
                <span>Permitir áudios</span>
            </label>
            <label style="display:flex; align-items:center; gap:5px;">
                <input type="checkbox" name="allow_images" value="1" <?= !empty($plan['allow_images']) ? 'checked' : '' ?>>
                <span>Permitir imagens</span>
            </label>
            <label style="display:flex; align-items:center; gap:5px;">
                <input type="checkbox" name="allow_files" value="1" <?= !empty($plan['allow_files']) ? 'checked' : '' ?>>
                <span>Permitir arquivos</span>
            </label>
            <label style="display:flex; align-items:center; gap:5px;">
                <input type="checkbox" name="is_active" value="1" <?= !isset($plan['is_active']) || !empty($plan['is_active']) ? 'checked' : '' ?>>
                <span>Plano ativo</span>
            </label>
        </div>

        <div style="margin-top:12px; display:flex; gap:8px;">
            <button type="submit" style="
                border:none; border-radius:999px; padding:8px 16px;
                background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509;
                font-weight:600; font-size:13px; cursor:pointer;">
                Salvar plano
            </button>
            <a href="/admin/planos" style="
                display:inline-flex; align-items:center; padding:8px 14px;
                border-radius:999px; border:1px solid #272727; color:#f5f5f5;
                font-size:13px; text-decoration:none;">
                Cancelar
            </a>
        </div>
    </form>
</div>
