<?php

$friendsCount = is_array($friends) ? count($friends) : 0;
$pendingCount = is_array($pending) ? count($pending) : 0;

$q = isset($q) ? trim((string)$q) : '';
$onlyFavorites = !empty($onlyFavorites);

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
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
            <h1 style="font-size:18px;">Meus amigos</h1>
            <div style="display:flex; align-items:center; gap:10px;">
                <a href="/amigos/adicionar" style="display:inline-block; font-size:12px; padding:6px 10px; border-radius:999px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; text-decoration:none; font-weight:700;">Adicionar amigo</a>
                <span style="font-size:12px; color:#b0b0b0;"><?= (int)$friendsCount ?> amigo(s)</span>
            </div>
        </div>

        <form action="/amigos" method="get" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin-top:8px;">
            <input type="text" name="q" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>" placeholder="Pesquisar amigo..." style="flex:1 1 220px; min-width:200px; padding:8px 10px; border-radius:999px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:12px;">
            <label style="display:flex; align-items:center; gap:6px; font-size:12px; color:#b0b0b0; padding:6px 10px; border-radius:999px; border:1px solid #272727; background:#0b0b10;">
                <input type="checkbox" name="fav" value="1" <?= $onlyFavorites ? 'checked' : '' ?> onchange="this.form.submit()">
                Somente favoritos
            </label>
            <button type="submit" style="border:none; border-radius:999px; padding:8px 14px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-size:12px; font-weight:650; cursor:pointer;">Filtrar</button>
            <?php if ($q !== '' || $onlyFavorites): ?>
                <a href="/amigos" style="font-size:12px; color:#ff6f60; text-decoration:none;">Limpar</a>
            <?php endif; ?>
        </form>
        <?php if (empty($friends)): ?>
            <p style="font-size:13px; color:#b0b0b0;">Você ainda não tem amigos aceitos aqui. Comece visitando perfis na comunidade e enviando pedidos de amizade.</p>
        <?php else: ?>
            <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(160px, 1fr)); gap:10px; margin-top:8px;">
                <?php foreach ($friends as $f): ?>
                    <?php
                    $friendId = (int)($f['friend_id'] ?? 0);
                    $friendName = (string)($f['friend_name'] ?? 'Amigo');
                    $initial = mb_strtoupper(mb_substr($friendName, 0, 1, 'UTF-8'), 'UTF-8');
                    $avatarPath = isset($f['friend_avatar_path']) ? trim((string)$f['friend_avatar_path']) : '';
                    $isFavorite = !empty($f['is_favorite']);
                    ?>
                    <div style="background:#050509; border-radius:12px; border:1px solid #272727; padding:8px 10px; display:flex; flex-direction:column; gap:6px;">
                        <a href="/perfil?user_id=<?= $friendId ?>" style="text-decoration:none; display:flex; align-items:center; gap:8px;">
                            <div style="width:32px; height:32px; border-radius:50%; background:radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%); display:flex; align-items:center; justify-content:center; font-size:16px; font-weight:700; color:#050509;">
                                <?php if ($avatarPath !== ''): ?>
                                    <img src="<?= htmlspecialchars($avatarPath, ENT_QUOTES, 'UTF-8') ?>" alt="Avatar" style="width:100%; height:100%; object-fit:cover; display:block; border-radius:50%;">
                                <?php else: ?>
                                    <?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div style="font-size:13px; font-weight:600; color:#f5f5f5;">
                                    <?= htmlspecialchars($friendName, ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </div>
                        </a>
                        <div style="display:flex; gap:6px; flex-wrap:wrap;">
                            <a href="/social/chat?user_id=<?= $friendId ?>" style="display:inline-block; font-size:11px; padding:4px 8px; border-radius:999px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; text-decoration:none; font-weight:600;">
                                Conversar
                            </a>
                            <a href="/perfil?user_id=<?= $friendId ?>" style="display:inline-block; font-size:11px; padding:4px 8px; border-radius:999px; border:1px solid #272727; background:#111118; color:#f5f5f5; text-decoration:none; font-weight:600;">
                                Ver perfil
                            </a>
                            <?php if ($isFavorite): ?>
                                <span style="display:inline-block; font-size:11px; padding:4px 8px; border-radius:999px; border:1px solid #272727; background:#0b0b10; color:#ffcc80; font-weight:650;">★ Favorito</span>
                            <?php endif; ?>
                            <form action="/amigos/remover" method="post" onsubmit="return confirm('Tem certeza que deseja remover este amigo?');" style="display:inline; margin:0;">
                                <input type="hidden" name="user_id" value="<?= $friendId ?>">
                                <button type="submit" style="border:none; border-radius:999px; padding:4px 8px; background:#311; color:#ffbaba; border:1px solid #a33; font-size:11px; cursor:pointer;">Remover</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section style="background:#111118; border-radius:16px; border:1px solid #272727; padding:12px 14px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
            <h2 style="font-size:16px;">Pedidos de amizade pendentes</h2>
            <span style="font-size:12px; color:#b0b0b0;"><?= (int)$pendingCount ?> pendente(s)</span>
        </div>
        <?php if (empty($pending)): ?>
            <p style="font-size:13px; color:#b0b0b0;">Nenhum pedido de amizade aguardando sua resposta.</p>
        <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:8px; margin-top:6px;">
                <?php foreach ($pending as $p): ?>
                    <?php
                    $otherId = (int)($p['other_id'] ?? 0);
                    $otherName = (string)($p['other_name'] ?? 'Usuário');
                    $initial = mb_strtoupper(mb_substr($otherName, 0, 1, 'UTF-8'), 'UTF-8');
                    $avatarPath = isset($p['other_avatar_path']) ? trim((string)$p['other_avatar_path']) : '';
                    ?>
                    <div style="background:#050509; border-radius:12px; border:1px solid #272727; padding:8px 10px; display:flex; align-items:center; justify-content:space-between; gap:10px;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <div style="width:32px; height:32px; border-radius:50%; background:radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%); display:flex; align-items:center; justify-content:center; font-size:16px; font-weight:700; color:#050509;">
                                <?php if ($avatarPath !== ''): ?>
                                    <img src="<?= htmlspecialchars($avatarPath, ENT_QUOTES, 'UTF-8') ?>" alt="Avatar" style="width:100%; height:100%; object-fit:cover; display:block; border-radius:50%;">
                                <?php else: ?>
                                    <?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <a href="/perfil?user_id=<?= $otherId ?>" style="font-size:13px; font-weight:600; color:#f5f5f5; text-decoration:none;">
                                    <?= htmlspecialchars($otherName, ENT_QUOTES, 'UTF-8') ?>
                                </a>
                                <div style="font-size:11px; color:#b0b0b0;">Quer ser seu amigo</div>
                            </div>
                        </div>
                        <form action="/amigos/decidir" method="post" style="display:flex; gap:6px;">
                            <input type="hidden" name="user_id" value="<?= $otherId ?>">
                            <button type="submit" name="decision" value="accepted" style="border:none; border-radius:999px; padding:4px 8px; background:linear-gradient(135deg,#4caf50,#8bc34a); color:#050509; font-size:11px; cursor:pointer;">Aceitar</button>
                            <button type="submit" name="decision" value="rejected" style="border:none; border-radius:999px; padding:4px 8px; background:#311; color:#ffbaba; border:1px solid #a33; font-size:11px; cursor:pointer;">Recusar</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
