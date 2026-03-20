<?php
/** @var array|null $course */
/** @var string|null $error */

$course = is_array($course ?? null) ? $course : null;
$id = $course ? (int)($course['id'] ?? 0) : 0;
$title = $course ? (string)($course['title'] ?? '') : '';
$slug = $course ? (string)($course['slug'] ?? '') : '';
$shortDescription = $course ? (string)($course['short_description'] ?? '') : '';
$description = $course ? (string)($course['description'] ?? '') : '';
$isExternal = $course ? !empty($course['is_external']) : true;
$isActive = $course ? !empty($course['is_active']) : true;
$isPaid = $course ? !empty($course['is_paid']) : false;
$priceCents = $course && isset($course['price_cents']) ? (int)$course['price_cents'] : 0;
$price = $isPaid ? number_format(max(0, $priceCents) / 100, 2, ',', '.') : '';
?>
<div style="max-width: 900px; margin: 0 auto;">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; gap:10px; flex-wrap:wrap; margin-bottom:12px;">
        <div>
            <h1 style="font-size: 22px; font-weight: 800; margin: 0 0 4px 0;">
                <?= $id > 0 ? 'Editar curso' : 'Novo curso' ?>
            </h1>
            <p style="margin:0; font-size:13px; color:var(--text-secondary);">Configure as informações básicas do curso.</p>
        </div>
        <a href="/profissional/cursos" style="border-radius:999px; padding:9px 14px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-weight:700; text-decoration:none; font-size:13px;">Voltar</a>
    </div>

    <?php if (!empty($error)): ?>
        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; border-radius: 12px; padding: 10px 12px; margin-bottom: 12px; color: #ef4444; font-size:13px;">
            <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="post" action="/profissional/cursos/salvar" style="background:var(--surface-card); border:1px solid var(--border-subtle); border-radius:14px; padding:14px 16px;">
        <input type="hidden" name="id" value="<?= (int)$id ?>">

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:10px;">
            <div>
                <label style="display:block; font-size:13px; font-weight:700; margin-bottom:4px;">Título</label>
                <input name="title" type="text" value="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>" required style="width:100%; padding:10px 12px; border-radius:12px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary);">
            </div>
            <div>
                <label style="display:block; font-size:13px; font-weight:700; margin-bottom:4px;">Slug</label>
                <input name="slug" type="text" value="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>" required placeholder="meu-curso" style="width:100%; padding:10px 12px; border-radius:12px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary);">
            </div>
        </div>

        <div style="margin-top:10px;">
            <label style="display:block; font-size:13px; font-weight:700; margin-bottom:4px;">Descrição curta</label>
            <input name="short_description" type="text" value="<?= htmlspecialchars($shortDescription, ENT_QUOTES, 'UTF-8') ?>" style="width:100%; padding:10px 12px; border-radius:12px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary);">
        </div>

        <div style="margin-top:10px;">
            <label style="display:block; font-size:13px; font-weight:700; margin-bottom:4px;">Descrição</label>
            <textarea name="description" rows="6" style="width:100%; padding:10px 12px; border-radius:12px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); resize:vertical;"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <div style="margin-top:10px; display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:10px;">
            <div style="border:1px solid var(--border-subtle); border-radius:12px; padding:10px 12px; background:var(--surface-subtle);">
                <label style="display:flex; align-items:center; gap:8px; font-size:13px; color:var(--text-primary);">
                    <input type="checkbox" name="is_external" value="1" <?= $isExternal ? 'checked' : '' ?>>
                    <span>Curso externo (catálogo por subdomínio)</span>
                </label>
            </div>
            <div style="border:1px solid var(--border-subtle); border-radius:12px; padding:10px 12px; background:var(--surface-subtle);">
                <label style="display:flex; align-items:center; gap:8px; font-size:13px; color:var(--text-primary);">
                    <input type="checkbox" name="is_active" value="1" <?= $isActive ? 'checked' : '' ?>>
                    <span>Curso ativo</span>
                </label>
            </div>
        </div>

        <div style="margin-top:10px; display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:10px;">
            <div style="border:1px solid var(--border-subtle); border-radius:12px; padding:10px 12px; background:var(--surface-subtle);">
                <label style="display:flex; align-items:center; gap:8px; font-size:13px; color:var(--text-primary);">
                    <input type="checkbox" name="is_paid" value="1" <?= $isPaid ? 'checked' : '' ?> onchange="document.getElementById('course-price').disabled = !this.checked; if(!this.checked){document.getElementById('course-price').value='';}">
                    <span>Curso pago</span>
                </label>
            </div>
            <div>
                <label style="display:block; font-size:13px; font-weight:700; margin-bottom:4px;">Preço (R$)</label>
                <input id="course-price" name="price" type="text" value="<?= htmlspecialchars($price, ENT_QUOTES, 'UTF-8') ?>" <?= $isPaid ? '' : 'disabled' ?> placeholder="49,90" style="width:100%; padding:10px 12px; border-radius:12px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary);">
            </div>
        </div>

        <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;">
            <button type="submit" style="border:none; border-radius:999px; padding:10px 16px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:900; cursor:pointer;">Salvar</button>
            <a href="/profissional/cursos" style="border-radius:999px; padding:10px 16px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-weight:800; text-decoration:none;">Cancelar</a>
        </div>
    </form>
</div>
