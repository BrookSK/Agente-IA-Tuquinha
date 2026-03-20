<?php
/** @var array $courses */
/** @var string|null $success */
/** @var string|null $error */

$courses = is_array($courses ?? null) ? $courses : [];
?>
<div style="max-width: 1100px; margin: 0 auto;">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; gap:10px; flex-wrap:wrap; margin-bottom:12px;">
        <div>
            <h1 style="font-size: 22px; font-weight: 800; margin: 0 0 4px 0;">Meus Cursos</h1>
            <p style="margin:0; font-size:13px; color:var(--text-secondary);">Cadastre e gerencie os seus cursos.</p>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <a href="/profissional/cursos/novo" style="border-radius:999px; padding:9px 14px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:800; text-decoration:none; font-size:13px;">Novo curso</a>
            <a href="/profissional" style="border-radius:999px; padding:9px 14px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-weight:700; text-decoration:none; font-size:13px;">Voltar</a>
        </div>
    </div>

    <?php if (!empty($success)): ?>
        <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; border-radius: 12px; padding: 10px 12px; margin-bottom: 12px; color: #10b981; font-size:13px;">
            <?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; border-radius: 12px; padding: 10px 12px; margin-bottom: 12px; color: #ef4444; font-size:13px;">
            <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if (empty($courses)): ?>
        <div style="background: var(--surface-card); border: 1px solid var(--border-subtle); border-radius: 14px; padding: 14px 16px; color: var(--text-secondary); font-size:13px;">
            Você ainda não cadastrou nenhum curso.
        </div>
    <?php else: ?>
        <div style="border:1px solid var(--border-subtle); border-radius:14px; overflow:hidden; background:var(--surface-card);">
            <table style="width:100%; border-collapse:collapse; font-size:13px;">
                <thead style="background:var(--surface-subtle);">
                    <tr>
                        <th style="text-align:left; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Título</th>
                        <th style="text-align:left; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Slug</th>
                        <th style="text-align:center; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Externo</th>
                        <th style="text-align:center; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Ativo</th>
                        <th style="text-align:left; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Preço</th>
                        <th style="text-align:right; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($courses as $c): ?>
                    <?php
                        $id = (int)($c['id'] ?? 0);
                        $title = (string)($c['title'] ?? '');
                        $slug = (string)($c['slug'] ?? '');
                        $isExternal = !empty($c['is_external']);
                        $isActive = !empty($c['is_active']);
                        $isPaid = !empty($c['is_paid']);
                        $priceCents = isset($c['price_cents']) ? (int)$c['price_cents'] : 0;
                        $priceLabel = $isPaid ? ('R$ ' . number_format(max(0, $priceCents) / 100, 2, ',', '.')) : 'Grátis';
                        $publicUrl = $isExternal ? ('/curso/' . urlencode($slug)) : ('/cursos/ver?slug=' . urlencode($slug));
                    ?>
                    <tr style="border-top:1px solid var(--border-subtle);">
                        <td style="padding:10px 12px;">
                            <a href="<?= htmlspecialchars($publicUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" style="color:var(--text-primary); text-decoration:none; border-bottom:1px dashed var(--border-subtle);">
                                <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </td>
                        <td style="padding:10px 12px; color:var(--text-secondary);">
                            <?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td style="padding:10px 12px; text-align:center;">
                            <?= $isExternal ? 'Sim' : 'Não' ?>
                        </td>
                        <td style="padding:10px 12px; text-align:center;">
                            <?= $isActive ? 'Sim' : 'Não' ?>
                        </td>
                        <td style="padding:10px 12px; color:var(--text-secondary);">
                            <?= htmlspecialchars($priceLabel, ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td style="padding:10px 12px; text-align:right;">
                            <a href="/profissional/cursos/editar?id=<?= (int)$id ?>" style="border-radius:999px; padding:6px 10px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); text-decoration:none; font-weight:700; font-size:12px;">Editar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
