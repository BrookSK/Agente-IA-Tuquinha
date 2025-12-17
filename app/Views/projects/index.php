<?php /** @var array $projects */ ?>
<div style="max-width: 980px; margin: 0 auto;">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:14px;">
        <h1 style="font-size: 24px; margin: 0;">Projetos</h1>
        <a href="/projetos/novo" style="border:none; border-radius:999px; padding:8px 14px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:600; font-size:13px; text-decoration:none;">Novo projeto</a>
    </div>

    <?php if (empty($projects)): ?>
        <div style="background:#111118; border:1px solid #272727; border-radius:14px; padding:14px; color:#b0b0b0; font-size:14px;">
            Você ainda não tem projetos.
        </div>
    <?php else: ?>
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:12px;">
            <?php foreach ($projects as $p): ?>
                <a href="/projetos/ver?id=<?= (int)($p['id'] ?? 0) ?>" style="display:block; background:#111118; border:1px solid #272727; border-radius:14px; padding:14px; text-decoration:none; color:#f5f5f5;">
                    <div style="font-weight:650; font-size:15px; margin-bottom:6px;">
                        <?= htmlspecialchars((string)($p['name'] ?? '')) ?>
                    </div>
                    <?php if (!empty($p['description'])): ?>
                        <div style="color:#b0b0b0; font-size:13px; line-height:1.35;">
                            <?= nl2br(htmlspecialchars((string)$p['description'])) ?>
                        </div>
                    <?php else: ?>
                        <div style="color:#8d8d8d; font-size:13px;">
                            Sem descrição.
                        </div>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
