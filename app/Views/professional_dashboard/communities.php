<?php
/** @var array $communities */

$communities = is_array($communities ?? null) ? $communities : [];
?>
<div style="max-width: 1100px; margin: 0 auto;">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; gap:10px; flex-wrap:wrap; margin-bottom:12px;">
        <div>
            <h1 style="font-size: 22px; font-weight: 800; margin: 0 0 4px 0;">Comunidades</h1>
            <p style="margin:0; font-size:13px; color:var(--text-secondary);">Comunidades vinculadas à sua conta.</p>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <a href="/comunidades" style="border-radius:999px; padding:9px 14px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:800; text-decoration:none; font-size:13px;">Abrir comunidades</a>
            <a href="/profissional" style="border-radius:999px; padding:9px 14px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-weight:700; text-decoration:none; font-size:13px;">Voltar</a>
        </div>
    </div>

    <?php if (empty($communities)): ?>
        <div style="background: var(--surface-card); border: 1px solid var(--border-subtle); border-radius: 14px; padding: 14px 16px; color: var(--text-secondary); font-size:13px;">
            Nenhuma comunidade encontrada.
        </div>
    <?php else: ?>
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:10px;">
            <?php foreach ($communities as $c): ?>
                <?php
                    $name = (string)($c['name'] ?? 'Comunidade');
                    $slug = (string)($c['slug'] ?? '');
                    $desc = (string)($c['description'] ?? '');
                ?>
                <a href="/comunidades/ver?slug=<?= urlencode($slug) ?>" style="display:block; text-decoration:none; background:var(--surface-card); border:1px solid var(--border-subtle); border-radius:14px; padding:12px 14px;">
                    <div style="font-weight:800; color:var(--text-primary); margin-bottom:6px;">
                        <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <?php if ($desc !== ''): ?>
                        <div style="font-size:12px; color:var(--text-secondary); line-height:1.55;">
                            <?= nl2br(htmlspecialchars($desc, ENT_QUOTES, 'UTF-8')) ?>
                        </div>
                    <?php else: ?>
                        <div style="font-size:12px; color:var(--text-secondary);">Sem descrição.</div>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
