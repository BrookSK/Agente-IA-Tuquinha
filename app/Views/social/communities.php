<?php
/** @var array $categories */
/** @var string $selectedCategory */
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

    <section style="background:var(--surface-card); border-radius:16px; border:1px solid var(--border-subtle); padding:12px 14px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; gap:10px; flex-wrap:wrap;">
            <div>
                <h1 style="font-size:18px; margin-bottom:2px; color:var(--text-primary);">Comunidades do Tuquinha</h1>
                <span style="font-size:12px; color:var(--text-secondary);">Escolha onde quer se conectar</span>
            </div>
            <a href="/comunidades/nova" style="border-radius:999px; padding:6px 12px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-size:12px; font-weight:600; text-decoration:none; white-space:nowrap;">Criar nova comunidade</a>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom:10px; flex-wrap:wrap;">
            <form action="/comunidades" method="get" style="display:flex; align-items:center; gap:6px; font-size:12px;">
                <label for="category" style="color:var(--text-secondary);">Filtrar por categoria:</label>
                <select id="category" name="category" onchange="this.form.submit()" style="min-width:160px; padding:4px 8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:12px;">
                    <option value="">Todas as categorias</option>
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $cat): ?>
                            <?php $catStr = (string)$cat; ?>
                            <option value="<?= htmlspecialchars($catStr, ENT_QUOTES, 'UTF-8') ?>" <?= $selectedCategory === $catStr ? 'selected' : '' ?>>
                                <?= htmlspecialchars($catStr, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </form>
        </div>

        <?php if (empty($communities)): ?>
            <p style="font-size:13px; color:var(--text-secondary);">Nenhuma comunidade cadastrada ainda.</p>
        <?php else: ?>
            <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr)); gap:10px;">
                <?php foreach ($communities as $c): ?>
                    <?php
                    $cid = (int)($c['id'] ?? 0);
                    $isMember = !empty($memberships[$cid] ?? false);
                    $name = (string)($c['name'] ?? 'Comunidade');
                    $slug = (string)($c['slug'] ?? '');
                    $category = (string)($c['category'] ?? '');
                    $initial = mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8');
                    $imagePath = trim((string)($c['cover_image_path'] ?? $c['image_path'] ?? ''));
                    ?>
                    <div style="background:var(--surface-subtle); border-radius:14px; border:1px solid var(--border-subtle); padding:10px 12px; display:flex; flex-direction:column; gap:6px;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <div style="width:36px; height:36px; border-radius:8px; background:radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%); display:flex; align-items:center; justify-content:center; font-size:18px; font-weight:700; color:#050509;">
                                <?php if ($imagePath !== ''): ?>
                                    <img src="<?= htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8') ?>" alt="Imagem da comunidade" style="width:100%; height:100%; object-fit:cover; display:block;">
                                <?php else: ?>
                                    <?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <a href="/comunidades/ver?slug=<?= urlencode($slug) ?>" style="font-size:14px; font-weight:600; color:var(--text-primary); text-decoration:none;">
                                    <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>
                                </a>
                                <?php if ($category !== ''): ?>
                                    <div style="font-size:11px; color:var(--text-secondary);">Categoria: <?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!empty($c['description'])): ?>
                            <div style="font-size:12px; color:var(--text-secondary);">
                                <?= nl2br(htmlspecialchars((string)$c['description'], ENT_QUOTES, 'UTF-8')) ?>
                            </div>
                        <?php endif; ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:4px;">
                            <a href="/comunidades/ver?slug=<?= urlencode($slug) ?>" style="font-size:12px; color:#ff6f60; text-decoration:none;">Ver tópicos</a>
                            <form action="<?= $isMember ? '/comunidades/sair' : '/comunidades/entrar' ?>" method="post" style="margin:0;">
                                <input type="hidden" name="community_id" value="<?= $cid ?>">
                                <?php if ($isMember): ?>
                                    <button type="submit" style="border:none; border-radius:999px; padding:4px 8px; background:var(--surface-card); border:1px solid var(--border-subtle); color:var(--text-primary); font-size:11px; cursor:pointer;">Sair</button>
                                <?php else: ?>
                                    <button type="submit" style="border:none; border-radius:999px; padding:4px 8px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-size:11px; font-weight:600; cursor:pointer;">Participar</button>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
