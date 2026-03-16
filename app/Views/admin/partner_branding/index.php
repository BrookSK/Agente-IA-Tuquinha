<?php
/** @var array $partners */
?>

<div style="max-width: 980px; margin: 0 auto;">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; gap:12px; flex-wrap:wrap; margin-bottom:12px;">
        <div>
            <h1 style="font-size: 22px; font-weight: 700; margin: 0 0 4px 0;">Branding de parceiros</h1>
            <div style="font-size: 13px; color: var(--text-secondary);">Configure nome, cores e logo (upload no servidor de mídia) para cursos externos.</div>
        </div>
    </div>

    <?php if (!empty($_SESSION['admin_partner_branding_success'])): ?>
        <div style="background:var(--surface-subtle); border:1px solid var(--border-subtle); color:var(--text-primary); padding:8px 10px; border-radius:10px; font-size:13px; margin-bottom:12px;">
            <?= htmlspecialchars($_SESSION['admin_partner_branding_success'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php unset($_SESSION['admin_partner_branding_success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['admin_partner_branding_error'])): ?>
        <div style="background:var(--surface-subtle); border:1px solid var(--border-subtle); color:var(--text-primary); padding:8px 10px; border-radius:10px; font-size:13px; margin-bottom:12px;">
            <?= htmlspecialchars($_SESSION['admin_partner_branding_error'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php unset($_SESSION['admin_partner_branding_error']); ?>
    <?php endif; ?>

    <div style="border-radius:14px; border:1px solid var(--border-subtle); overflow:hidden;">
        <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
            <table style="width:100%; min-width:820px; border-collapse:collapse; font-size:13px;">
                <thead style="background:var(--surface-subtle);">
                    <tr>
                        <th style="text-align:left; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Parceiro</th>
                        <th style="text-align:left; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">E-mail</th>
                        <th style="text-align:center; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Ação</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($partners)): ?>
                    <tr>
                        <td colspan="3" style="padding:12px; color:var(--text-secondary);">Nenhum parceiro encontrado.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($partners as $p): ?>
                        <tr style="background:var(--surface-card); border-top:1px solid var(--border-subtle);">
                            <td style="padding:10px 12px;">
                                <?= htmlspecialchars((string)($p['user_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td style="padding:10px 12px; color:var(--text-secondary);">
                                <?= htmlspecialchars((string)($p['user_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td style="padding:10px 12px; text-align:center;">
                                <a href="/admin/branding-parceiros/editar?user_id=<?= (int)($p['user_id'] ?? 0) ?>" style="display:inline-flex; align-items:center; padding:7px 12px; border-radius:999px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); text-decoration:none; font-size:12px;">Editar branding</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
