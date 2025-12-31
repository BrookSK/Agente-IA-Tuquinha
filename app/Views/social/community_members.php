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
                <span> / Membros</span>
            </div>
            <a href="/comunidades/ver?slug=<?= urlencode($slug) ?>" style="font-size:12px; color:#ff6f60; text-decoration:none;">Voltar à comunidade</a>
        </div>
    </section>

    <section style="background:var(--surface-card); border-radius:16px; border:1px solid var(--border-subtle); padding:12px 14px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; flex-wrap:wrap; gap:8px;">
            <h1 style="font-size:16px;">Membros de <?= htmlspecialchars($communityName, ENT_QUOTES, 'UTF-8') ?></h1>
            <?php if (!empty($canModerate)): ?>
                <a href="/comunidades/convites?slug=<?= urlencode($slug) ?>" style="font-size:12px; padding:4px 9px; border-radius:999px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); text-decoration:none;">Convidar por e-mail</a>
            <?php endif; ?>
        </div>

        <?php if (!empty($canModerate) && !empty($reports)): ?>
            <div style="margin-bottom:10px; padding:8px 10px; border-radius:10px; border:1px dashed var(--border-subtle); background:var(--surface-subtle);">
                <div style="font-size:13px; font-weight:600; color:var(--text-primary); margin-bottom:4px;">Denúncias abertas</div>
                <div style="display:flex; flex-direction:column; gap:4px;">
                    <?php foreach ($reports as $r): ?>
                        <?php
                        $rid = (int)($r['id'] ?? 0);
                        $reporter = (string)($r['reporter_name'] ?? '');
                        $reported = (string)($r['reported_name'] ?? '');
                        $reason = (string)($r['reason'] ?? '');
                        ?>
                        <div style="display:flex; flex-wrap:wrap; gap:6px; align-items:center; font-size:12px; color:var(--text-secondary);">
                            <span><strong><?= htmlspecialchars($reporter, ENT_QUOTES, 'UTF-8') ?></strong> denunciou <strong><?= htmlspecialchars($reported, ENT_QUOTES, 'UTF-8') ?></strong></span>
                            <?php if ($reason !== ''): ?>
                                <span style="opacity:0.85;">· "<?= htmlspecialchars($reason, ENT_QUOTES, 'UTF-8') ?>"</span>
                            <?php endif; ?>
                            <form action="/comunidades/membros/denuncias/resolver" method="post" style="margin-left:auto;">
                                <input type="hidden" name="report_id" value="<?= $rid ?>">
                                <button type="submit" style="border:none; border-radius:999px; padding:3px 7px; background:var(--surface-card); border:1px solid var(--border-subtle); color:var(--text-secondary); font-size:11px; cursor:pointer;">Marcar como resolvida</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($members)): ?>
            <p style="font-size:13px; color:var(--text-secondary);">Nenhum membro listado ainda.</p>
        <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:6px;">
                <?php foreach ($members as $m): ?>
                    <?php
                    $memberId = (int)($m['user_id'] ?? 0);
                    $name = (string)($m['user_name'] ?? 'Usuário');
                    $role = (string)($m['role'] ?? 'member');
                    $initial = mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8');
                    $avatar = trim((string)($m['user_avatar_path'] ?? ''));
                    $isBlocked = !empty($m['is_blocked'] ?? 0);
                    ?>
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; padding:6px 8px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-subtle);">
                        <a href="/perfil?user_id=<?= $memberId ?>" style="flex:1 1 auto; display:flex; align-items:center; gap:8px; text-decoration:none;">
                            <div style="width:28px; height:28px; border-radius:50%; overflow:hidden; background:radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%); display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; color:#050509; flex:0 0 28px;">
                                <?php if ($avatar !== ''): ?>
                                    <img src="<?= htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8') ?>" alt="" style="width:100%; height:100%; object-fit:cover; display:block;">
                                <?php else: ?>
                                    <?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?>
                                <?php endif; ?>
                            </div>
                            <div style="display:flex; flex-direction:column;">
                                <span style="font-size:13px; color:var(--text-primary);">
                                    <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <span style="font-size:11px; color:var(--text-secondary);">
                                    <?php if ($role === 'owner'): ?>
                                        Dono
                                    <?php elseif ($role === 'moderator'): ?>
                                        Moderador
                                    <?php else: ?>
                                        Membro
                                    <?php endif; ?>
                                    <?php if ($isBlocked): ?> · bloqueado
                                    <?php endif; ?>
                                </span>
                            </div>
                        </a>

                        <?php if (!empty($canModerate) && $memberId !== (int)($community['owner_user_id'] ?? 0)): ?>
                            <div style="display:flex; flex-wrap:wrap; gap:4px; justify-content:flex-end;">
                                <form action="/comunidades/membros/denunciar" method="post" style="margin:0;">
                                    <input type="hidden" name="community_id" value="<?= (int)($community['id'] ?? 0) ?>">
                                    <input type="hidden" name="reported_user_id" value="<?= $memberId ?>">
                                    <button type="submit" style="border:none; border-radius:999px; padding:3px 7px; background:var(--surface-card); border:1px solid var(--border-subtle); color:var(--text-secondary); font-size:11px; cursor:pointer;">Denunciar</button>
                                </form>

                                <?php if ($isBlocked): ?>
                                    <form action="/comunidades/membros/desbloquear" method="post" style="margin:0;">
                                        <input type="hidden" name="community_id" value="<?= (int)($community['id'] ?? 0) ?>">
                                        <input type="hidden" name="user_id" value="<?= $memberId ?>">
                                        <button type="submit" style="border:none; border-radius:999px; padding:3px 7px; background:var(--surface-card); border:1px solid var(--border-subtle); color:var(--text-secondary); font-size:11px; cursor:pointer;">Desbloquear</button>
                                    </form>
                                <?php else: ?>
                                    <form action="/comunidades/membros/bloquear" method="post" style="margin:0;">
                                        <input type="hidden" name="community_id" value="<?= (int)($community['id'] ?? 0) ?>">
                                        <input type="hidden" name="user_id" value="<?= $memberId ?>">
                                        <button type="submit" style="border:none; border-radius:999px; padding:3px 7px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-size:11px; cursor:pointer;">Bloquear</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
