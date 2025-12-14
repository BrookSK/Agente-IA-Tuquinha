<?php

$communityName = (string)($community['name'] ?? 'Comunidade');
$slug = (string)($community['slug'] ?? '');

?>
<div style="max-width: 980px; margin: 0 auto; display:flex; flex-direction:column; gap:14px;">
    <?php if (!empty($error)): ?>
        <div style="background:#311; border:1px solid #a33; color:#ffbaba; padding:8px 10px; border-radius:10px; font-size:13px;">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div style="background:#10330f; border:1px solid #3aa857; color:#c8ffd4; padding:8px 10px; border-radius:10px; font-size:13px;">
            <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <section style="background:var(--surface-card); border-radius:16px; border:1px solid var(--border-subtle); padding:10px 12px;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:8px; flex-wrap:wrap;">
            <div style="font-size:13px; color:var(--text-secondary);">
                <a href="/comunidades" style="color:#ff6f60; text-decoration:none;">Comunidades</a>
                <span> / </span>
                <a href="/comunidades/ver?slug=<?= urlencode($slug) ?>" style="color:#ff6f60; text-decoration:none;">
                    <?= htmlspecialchars($communityName, ENT_QUOTES, 'UTF-8') ?>
                </a>
                <span> / Convites</span>
            </div>
            <a href="/comunidades/membros?slug=<?= urlencode($slug) ?>" style="font-size:12px; color:#ff6f60; text-decoration:none;">Voltar para membros</a>
        </div>
    </section>

    <section style="background:var(--surface-card); border-radius:16px; border:1px solid var(--border-subtle); padding:12px 14px; display:flex; flex-direction:column; gap:8px;">
        <h1 style="font-size:16px;">Convidar pessoas para <?= htmlspecialchars($communityName, ENT_QUOTES, 'UTF-8') ?></h1>
        <p style="font-size:13px; color:var(--text-secondary);">Envie convites por e-mail para que pessoas entrem nesta comunidade. Ideal para comunidades privadas.</p>

        <form action="/comunidades/convites/enviar" method="post" style="display:flex; flex-direction:column; gap:6px; max-width:420px;">
            <input type="hidden" name="community_id" value="<?= (int)($community['id'] ?? 0) ?>">
            <label style="font-size:12px; color:var(--text-secondary);">
                E-mail da pessoa convidada
                <input type="email" name="email" required placeholder="pessoa@exemplo.com" style="margin-top:2px; width:100%; padding:6px 8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--input-bg); color:var(--text-primary); font-size:13px;">
            </label>
            <label style="font-size:12px; color:var(--text-secondary);">
                Nome (opcional)
                <input type="text" name="name" placeholder="Nome para personalizar o convite" style="margin-top:2px; width:100%; padding:6px 8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--input-bg); color:var(--text-primary); font-size:13px;">
            </label>
            <div style="display:flex; gap:8px; margin-top:4px;">
                <button type="submit" style="border:none; border-radius:999px; padding:6px 12px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-size:12px; font-weight:600; cursor:pointer;">Enviar convite</button>
                <a href="/comunidades/ver?slug=<?= urlencode($slug) ?>" style="font-size:12px; color:var(--text-secondary); text-decoration:none; align-self:center;">Cancelar</a>
            </div>
        </form>
    </section>
</div>
