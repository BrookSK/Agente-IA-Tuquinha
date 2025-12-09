<?php

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

    <section style="background:#111118; border-radius:16px; border:1px solid #272727; padding:12px 14px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
            <h1 style="font-size:18px;">Comunidades do Tuquinha</h1>
            <span style="font-size:12px; color:#b0b0b0;">Escolha onde quer se conectar</span>
        </div>

        <div style="margin-bottom:10px; padding:8px 10px; border-radius:12px; border:1px solid #272727; background:#050509;">
            <h2 style="font-size:14px; margin-bottom:4px;">Criar nova comunidade</h2>
            <p style="font-size:12px; color:#b0b0b0; margin-bottom:6px;">Crie um espaço temático para seus projetos, turmas ou interesses. Você será o dono da comunidade.</p>
            <form action="/comunidades/criar" method="post" style="display:flex; flex-direction:column; gap:6px;">
                <div>
                    <label for="community-name" style="display:block; font-size:12px; color:#b0b0b0; margin-bottom:2px;">Nome da comunidade</label>
                    <input id="community-name" name="name" type="text" maxlength="255" required style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid #272727; background:#111118; color:#f5f5f5; font-size:13px;">
                </div>
                <div>
                    <label for="community-description" style="display:block; font-size:12px; color:#b0b0b0; margin-bottom:2px;">Descrição (opcional)</label>
                    <textarea id="community-description" name="description" rows="2" maxlength="4000" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid #272727; background:#111118; color:#f5f5f5; font-size:12px; resize:vertical;"></textarea>
                </div>
                <div style="display:flex; justify-content:flex-end;">
                    <button type="submit" style="border:none; border-radius:999px; padding:6px 12px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-size:12px; font-weight:600; cursor:pointer;">Criar comunidade</button>
                </div>
            </form>
        </div>

        <?php if (empty($communities)): ?>
            <p style="font-size:13px; color:#b0b0b0;">Nenhuma comunidade cadastrada ainda.</p>
        <?php else: ?>
            <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr)); gap:10px;">
                <?php foreach ($communities as $c): ?>
                    <?php
                    $cid = (int)($c['id'] ?? 0);
                    $isMember = !empty($memberships[$cid] ?? false);
                    $name = (string)($c['name'] ?? 'Comunidade');
                    $slug = (string)($c['slug'] ?? '');
                    $initial = mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8');
                    ?>
                    <div style="background:#050509; border-radius:14px; border:1px solid #272727; padding:10px 12px; display:flex; flex-direction:column; gap:6px;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <div style="width:36px; height:36px; border-radius:8px; background:radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%); display:flex; align-items:center; justify-content:center; font-size:18px; font-weight:700; color:#050509;">
                                <?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <div>
                                <a href="/comunidades/ver?slug=<?= urlencode($slug) ?>" style="font-size:14px; font-weight:600; color:#f5f5f5; text-decoration:none;">
                                    <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </div>
                        </div>
                        <?php if (!empty($c['description'])): ?>
                            <div style="font-size:12px; color:#b0b0b0;">
                                <?= nl2br(htmlspecialchars((string)$c['description'], ENT_QUOTES, 'UTF-8')) ?>
                            </div>
                        <?php endif; ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:4px;">
                            <a href="/comunidades/ver?slug=<?= urlencode($slug) ?>" style="font-size:12px; color:#ff6f60; text-decoration:none;">Ver tópicos</a>
                            <form action="<?= $isMember ? '/comunidades/sair' : '/comunidades/entrar' ?>" method="post" style="margin:0;">
                                <input type="hidden" name="community_id" value="<?= $cid ?>">
                                <?php if ($isMember): ?>
                                    <button type="submit" style="border:none; border-radius:999px; padding:4px 8px; background:#111118; border:1px solid #272727; color:#f5f5f5; font-size:11px; cursor:pointer;">Sair</button>
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
