<?php
/** @var array $categories */
/** @var string|null $success */
/** @var string|null $error */

$categories = is_array($categories ?? null) ? $categories : [];
?>
<div style="max-width: 900px; margin: 0 auto;">
    <h1 style="font-size: 20px; margin-bottom: 10px; font-weight: 650;">Categorias de comunidades</h1>
    <p style="font-size:13px; color:#b0b0b0; margin-bottom:10px;">Gerencie as categorias usadas para organizar as comunidades.</p>

    <?php if (!empty($success)): ?>
        <div style="background:#10330f; border:1px solid #3aa857; color:#c8ffd4; padding:8px 10px; border-radius:8px; font-size:13px; margin-bottom:10px;">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div style="background:#311; border:1px solid #a33; color:#ffbaba; padding:8px 10px; border-radius:8px; font-size:13px; margin-bottom:10px;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div style="border-radius:12px; border:1px solid #272727; background:#111118; padding:12px 12px; margin-bottom:12px;">
        <form action="/admin/comunidade/categorias/criar" method="post" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
            <div style="flex:1; min-width:220px;">
                <label style="font-size:12px; color:#b0b0b0;">Nova categoria</label>
                <input name="name" placeholder="Ex: Programação" style="width:100%; padding:8px 10px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:13px;">
            </div>
            <div style="align-self:flex-end;">
                <button type="submit" style="border:none; border-radius:999px; padding:8px 14px; background:linear-gradient(135deg,#ffcc80,#ff8a65); color:#050509; font-weight:700; font-size:12px; cursor:pointer;">
                    Criar
                </button>
            </div>
        </form>
    </div>

    <?php if (empty($categories)): ?>
        <div style="font-size:13px; color:#b0b0b0;">Nenhuma categoria cadastrada.</div>
    <?php else: ?>
        <div style="border-radius:12px; border:1px solid #272727; background:#111118; overflow:hidden;">
            <table style="width:100%; border-collapse:collapse; font-size:12px;">
                <thead>
                    <tr style="background:#050509;">
                        <th style="text-align:left; padding:8px 10px; border-bottom:1px solid #272727;">Categoria</th>
                        <th style="text-align:left; padding:8px 10px; border-bottom:1px solid #272727;">Ordem</th>
                        <th style="text-align:left; padding:8px 10px; border-bottom:1px solid #272727;">Status</th>
                        <th style="text-align:right; padding:8px 10px; border-bottom:1px solid #272727;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                        <?php
                            $id = (int)($cat['id'] ?? 0);
                            $name = (string)($cat['name'] ?? '');
                            $sortOrder = (int)($cat['sort_order'] ?? 0);
                            $isActive = !empty($cat['is_active']);
                        ?>
                        <tr>
                            <td style="padding:8px 10px; border-bottom:1px solid #272727; vertical-align:top;">
                                <div style="font-weight:600;"><?= htmlspecialchars($name) ?></div>
                            </td>
                            <td style="padding:8px 10px; border-bottom:1px solid #272727; vertical-align:top;">
                                <?= (int)$sortOrder ?>
                            </td>
                            <td style="padding:8px 10px; border-bottom:1px solid #272727; vertical-align:top;">
                                <?php if ($isActive): ?>
                                    <span style="display:inline-flex; align-items:center; padding:3px 8px; border-radius:999px; background:#10330f; color:#c8ffd4; border:1px solid #2ecc71; font-size:11px;">Ativa</span>
                                <?php else: ?>
                                    <span style="display:inline-flex; align-items:center; padding:3px 8px; border-radius:999px; background:#111; color:#bbb; border:1px solid #272727; font-size:11px;">Inativa</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding:8px 10px; border-bottom:1px solid #272727; vertical-align:top; text-align:right;">
                                <a href="/admin/comunidade/categorias/toggle?id=<?= $id ?>" style="display:inline-flex; align-items:center; padding:5px 10px; border-radius:999px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:11px; text-decoration:none;">
                                    <?= $isActive ? 'Desativar' : 'Ativar' ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<style>
@media (max-width: 520px) {
    table {
        font-size: 11px !important;
    }
}
</style>
