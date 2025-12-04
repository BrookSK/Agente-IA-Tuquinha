<?php
/** @var array|null $course */
/** @var float|null $partnerCommissionPercent */
/** @var float|null $partnerDefaultPercent */
$isEdit = !empty($course);
$partnerCommissionPercent = $partnerCommissionPercent ?? null;
$partnerDefaultPercent = $partnerDefaultPercent ?? null;
?>
<div style="max-width: 720px; margin: 0 auto;">
    <h1 style="font-size: 22px; margin-bottom: 10px; font-weight: 650;">
        <?= $isEdit ? 'Editar curso' : 'Novo curso' ?>
    </h1>
    <p style="color:#b0b0b0; font-size:13px; margin-bottom:10px;">
        Defina título, descrição, acesso por plano ou compra avulsa e, se desejar, o parceiro responsável pelo curso.
    </p>

    <?php if (!empty($_SESSION['admin_course_error'])): ?>
        <div style="background:#311; border:1px solid #a33; color:#ffbaba; padding:8px 10px; border-radius:8px; font-size:13px; margin-bottom:10px;">
            <?= htmlspecialchars($_SESSION['admin_course_error']) ?>
        </div>
        <?php unset($_SESSION['admin_course_error']); ?>
    <?php endif; ?>

    <form action="/admin/cursos/salvar" method="post" style="display:flex; flex-direction:column; gap:10px;">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int)$course['id'] ?>">
        <?php endif; ?>

        <div style="display:flex; gap:14px; align-items:flex-start; flex-wrap:wrap;">
            <div style="flex:1 1 260px;">
                <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Título do curso</label>
                <input type="text" name="title" required value="<?= htmlspecialchars($course['title'] ?? '') ?>" style="
                    width:100%; padding:8px 10px; border-radius:8px; border:1px solid #272727;
                    background:#050509; color:#f5f5f5; font-size:14px;">
            </div>
            <div style="flex:1 1 220px;">
                <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Slug (técnico)</label>
                <input type="text" name="slug" required value="<?= htmlspecialchars($course['slug'] ?? '') ?>" placeholder="ex: branding-para-designers" style="
                    width:100%; padding:8px 10px; border-radius:8px; border:1px solid #272727;
                    background:#050509; color:#f5f5f5; font-size:14px;">
                <div style="font-size:11px; color:#777; margin-top:3px;">Usado nas URLs do curso.</div>
            </div>
        </div>

        <div>
            <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Descrição curta</label>
            <input type="text" name="short_description" value="<?= htmlspecialchars($course['short_description'] ?? '') ?>" style="
                width:100%; padding:8px 10px; border-radius:8px; border:1px solid #272727;
                background:#050509; color:#f5f5f5; font-size:13px;">
        </div>

        <div>
            <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Descrição completa</label>
            <textarea name="description" rows="6" style="
                width:100%; padding:8px 10px; border-radius:8px; border:1px solid #272727;
                background:#050509; color:#f5f5f5; font-size:13px; resize:vertical;">
<?= htmlspecialchars($course['description'] ?? '') ?></textarea>
        </div>

        <div>
            <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Imagem (URL)</label>
            <input type="text" name="image_path" value="<?= htmlspecialchars($course['image_path'] ?? '') ?>" placeholder="Opcional. Ex: /public/img/curso-branding.png" style="
                width:100%; padding:8px 10px; border-radius:8px; border:1px solid #272727;
                background:#050509; color:#f5f5f5; font-size:14px;">
        </div>

        <div style="display:flex; gap:14px; flex-wrap:wrap;">
            <div style="flex:1 1 200px;">
                <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">ID do parceiro (opcional)</label>
                <input type="number" name="owner_user_id" value="<?= isset($course['owner_user_id']) ? (int)$course['owner_user_id'] : '' ?>" style="
                    width:140px; padding:8px 10px; border-radius:8px; border:1px solid #272727;
                    background:#050509; color:#f5f5f5; font-size:13px;">
                <div style="font-size:11px; color:#777; margin-top:3px;">Use o ID do usuário parceiro (se houver).</div>
            </div>
            <div style="flex:1 1 200px;">
                <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Preço (R$)</label>
                <input type="text" name="price" value="<?= isset($course['price_cents']) ? number_format($course['price_cents']/100, 2, ',', '.') : '0,00' ?>" style="
                    width:140px; padding:8px 10px; border-radius:8px; border:1px solid #272727;
                    background:#050509; color:#f5f5f5; font-size:13px;">
                <div style="font-size:11px; color:#777; margin-top:3px;">Usado apenas se o curso estiver marcado como pago.</div>
            </div>
            <div style="flex:1 1 220px;">
                <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Comissão deste curso para o parceiro (%)</label>
                <input type="text" name="partner_commission_percent" value="<?= $partnerCommissionPercent !== null ? number_format($partnerCommissionPercent, 2, ',', '.') : '' ?>" style="
                    width:140px; padding:8px 10px; border-radius:8px; border:1px solid #272727;
                    background:#050509; color:#f5f5f5; font-size:13px;">
                <?php if ($partnerDefaultPercent !== null): ?>
                    <div style="font-size:11px; color:#777; margin-top:3px;">Comissão padrão do parceiro: <?= number_format($partnerDefaultPercent, 2, ',', '.') ?>%</div>
                <?php else: ?>
                    <div style="font-size:11px; color:#777; margin-top:3px;">Se houver um parceiro configurado, a comissão padrão dele será usada caso este campo fique em branco.</div>
                <?php endif; ?>
            </div>
        </div>

        <div style="display:flex; flex-wrap:wrap; gap:10px; font-size:13px; color:#ddd; margin-top:4px;">
            <label style="display:flex; align-items:center; gap:5px;">
                <input type="checkbox" name="is_paid" value="1" <?= !empty($course['is_paid']) ? 'checked' : '' ?>>
                <span>Curso pago (pode ser vendido avulso)</span>
            </label>
            <label style="display:flex; align-items:center; gap:5px;">
                <input type="checkbox" name="allow_plan_access_only" value="1" <?= !empty($course['allow_plan_access_only']) ? 'checked' : '' ?>>
                <span>Somente usuários com planos que liberam cursos</span>
            </label>
            <label style="display:flex; align-items:center; gap:5px;">
                <input type="checkbox" name="allow_public_purchase" value="1" <?= !empty($course['allow_public_purchase']) ? 'checked' : '' ?>>
                <span>Mostrar para quem não tem plano (compra avulsa)</span>
            </label>
            <label style="display:flex; align-items:center; gap:5px;">
                <input type="checkbox" name="is_active" value="1" <?= !isset($course['is_active']) || !empty($course['is_active']) ? 'checked' : '' ?>>
                <span>Curso ativo</span>
            </label>
        </div>

        <div style="margin-top:12px; display:flex; gap:8px;">
            <button type="submit" style="
                border:none; border-radius:999px; padding:8px 16px;
                background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509;
                font-weight:600; font-size:13px; cursor:pointer;">
                Salvar curso
            </button>
            <a href="/admin/cursos" style="
                display:inline-flex; align-items:center; padding:8px 14px;
                border-radius:999px; border:1px solid #272727; color:#f5f5f5;
                font-size:13px; text-decoration:none;">
                Cancelar
            </a>
        </div>
    </form>
</div>
