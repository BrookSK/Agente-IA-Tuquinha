<?php

$friendsCount = is_array($friends) ? count($friends) : 0;
$pendingCount = is_array($pending) ? count($pending) : 0;

$q = isset($q) ? trim((string)$q) : '';
$onlyFavorites = !empty($onlyFavorites);

// Branding colors
$primaryColor = !empty($branding['primary_color']) ? $branding['primary_color'] : '#e53935';
$secondaryColor = !empty($branding['secondary_color']) ? $branding['secondary_color'] : '#ff6f60';
$accentColor = !empty($branding['accent_color']) ? $branding['accent_color'] : '#4caf50';

?>
<style>
    .friends-page-gradient {
        background: linear-gradient(135deg, <?= $primaryColor ?>, <?= $secondaryColor ?>);
    }
    .friends-btn-primary {
        background: linear-gradient(135deg, <?= $primaryColor ?>, <?= $secondaryColor ?>);
        border: none;
        color: #fff;
    }
    .friends-btn-primary:hover {
        opacity: 0.9;
        color: #fff;
    }
    .friends-btn-success {
        background: linear-gradient(135deg, <?= $accentColor ?>, #8bc34a);
        border: none;
        color: #fff;
    }
    .friends-btn-success:hover {
        opacity: 0.9;
        color: #fff;
    }
    .friends-accent-badge {
        background: rgba(<?= hexdec(substr($accentColor, 1, 2)) ?>, <?= hexdec(substr($accentColor, 3, 2)) ?>, <?= hexdec(substr($accentColor, 5, 2)) ?>, 0.1);
        color: <?= $accentColor ?>;
        border: 1px solid rgba(<?= hexdec(substr($accentColor, 1, 2)) ?>, <?= hexdec(substr($accentColor, 3, 2)) ?>, <?= hexdec(substr($accentColor, 5, 2)) ?>, 0.3);
    }
</style>
<div class="container-fluid" style="padding: 24px; max-width: 100%; margin: 0 auto;">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-3" style="font-size: 28px; font-weight: 700; color: var(--text-primary);">Meus Amigos</h1>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" style="background: rgba(220, 53, 69, 0.1); border: 1px solid rgba(220, 53, 69, 0.3); color: #dc3545; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px;">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($success)): ?>
                <div class="alert alert-success" style="background: rgba(40, 167, 69, 0.1); border: 1px solid rgba(40, 167, 69, 0.3); color: #28a745; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px;">
                    <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card" style="background: var(--surface-card); border: 1px solid var(--border-subtle); border-radius: 12px; padding: 20px;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 style="font-size: 20px; font-weight: 600; color: var(--text-primary); margin: 0;">Lista de Amigos</h2>
                    <div class="d-flex align-items-center gap-3">
                        <span style="font-size: 14px; color: var(--text-secondary);"><?= (int)$friendsCount ?> amigo(s)</span>
                        <a href="/painel-externo/amigos/adicionar" class="btn friends-btn-primary" style="padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px;">
                            + Adicionar Amigo
                        </a>
                    </div>
                </div>

                <form action="/painel-externo/amigos" method="get" class="mb-3">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <input type="text" name="q" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>" placeholder="Pesquisar amigo..." class="form-control" style="background: var(--surface-subtle); border: 1px solid var(--border-subtle); color: var(--text-primary); padding: 10px 14px; border-radius: 8px;">
                        </div>
                        <div class="col-md-3">
                            <div class="form-check" style="padding: 10px 14px;">
                                <input type="checkbox" name="fav" value="1" <?= $onlyFavorites ? 'checked' : '' ?> onchange="this.form.submit()" class="form-check-input" id="favCheck">
                                <label class="form-check-label" for="favCheck" style="color: var(--text-secondary); font-size: 14px;">
                                    Somente favoritos
                                </label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn friends-btn-primary w-100" style="padding: 10px; border-radius: 8px; font-weight: 600;">
                                Filtrar
                            </button>
                            <?php if ($q !== '' || $onlyFavorites): ?>
                                <a href="/painel-externo/amigos" class="btn btn-link btn-sm w-100 mt-1" style="color: <?= $primaryColor ?>; text-decoration: none; font-size: 13px;">Limpar filtros</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
                <?php if (empty($friends)): ?>
                    <div class="text-center py-5">
                        <p style="font-size: 15px; color: var(--text-secondary);">Você ainda não tem amigos aceitos aqui. Comece visitando perfis na comunidade e enviando pedidos de amizade.</p>
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($friends as $f): ?>
                            <?php
                            $friendId = (int)($f['friend_id'] ?? 0);
                            $friendName = (string)($f['friend_name'] ?? 'Amigo');
                            $initial = mb_strtoupper(mb_substr($friendName, 0, 1, 'UTF-8'), 'UTF-8');
                            $avatarPath = isset($f['friend_avatar_path']) ? trim((string)$f['friend_avatar_path']) : '';
                            $isFavorite = !empty($f['is_favorite']);
                            ?>
                            <div class="col-md-3 col-sm-6">
                                <div class="card h-100" style="background: var(--surface-subtle); border: 1px solid var(--border-subtle); border-radius: 12px; padding: 16px;">
                                    <div class="text-center mb-3">
                                        <a href="/painel-externo/perfil?user_id=<?= $friendId ?>" style="text-decoration: none;">
                                            <div style="width: 80px; height: 80px; border-radius: 50%; background: radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%); display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: 700; color: #050509; margin: 0 auto;">
                                                <?php if ($avatarPath !== ''): ?>
                                                    <img src="<?= htmlspecialchars($avatarPath, ENT_QUOTES, 'UTF-8') ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover; display: block; border-radius: 50%;">
                                                <?php else: ?>
                                                    <?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?>
                                                <?php endif; ?>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="text-center mb-3">
                                        <a href="/painel-externo/perfil?user_id=<?= $friendId ?>" style="text-decoration: none;">
                                            <h5 style="font-size: 16px; font-weight: 600; color: var(--text-primary); margin-bottom: 4px;">
                                                <?= htmlspecialchars($friendName, ENT_QUOTES, 'UTF-8') ?>
                                            </h5>
                                        </a>
                                        <?php if ($isFavorite): ?>
                                            <span class="friends-accent-badge" style="display: inline-block; font-size: 12px; padding: 4px 10px; border-radius: 12px; font-weight: 600;">★ Favorito</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-grid gap-2">
                                        <a href="/painel-externo/chat?user_id=<?= $friendId ?>" class="btn btn-sm friends-btn-primary" style="padding: 8px; border-radius: 8px; font-weight: 600; text-decoration: none;">
                                            💬 Conversar
                                        </a>
                                        <a href="/painel-externo/perfil?user_id=<?= $friendId ?>" class="btn btn-sm btn-outline-secondary" style="border: 1px solid var(--border-subtle); color: var(--text-primary); padding: 8px; border-radius: 8px; text-decoration: none;">
                                            👤 Ver Perfil
                                        </a>
                                        <form action="/painel-externo/amigos/remover" method="post" onsubmit="return confirm('Tem certeza que deseja remover este amigo?');" class="m-0">
                                            <input type="hidden" name="user_id" value="<?= $friendId ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger w-100" style="border: 1px solid rgba(220, 53, 69, 0.3); color: #dc3545; padding: 8px; border-radius: 8px;">
                                                🗑️ Remover
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card" style="background: var(--surface-card); border: 1px solid var(--border-subtle); border-radius: 12px; padding: 20px;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 style="font-size: 20px; font-weight: 600; color: var(--text-primary); margin: 0;">Pedidos Pendentes</h2>
                    <span style="font-size: 14px; color: var(--text-secondary);"><?= (int)$pendingCount ?> pendente(s)</span>
                </div>
                <?php if (empty($pending)): ?>
                    <div class="text-center py-4">
                        <p style="font-size: 15px; color: var(--text-secondary);">Nenhum pedido de amizade aguardando sua resposta.</p>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($pending as $p): ?>
                            <?php
                            $otherId = (int)($p['other_id'] ?? 0);
                            $otherName = (string)($p['other_name'] ?? 'Usuário');
                            $initial = mb_strtoupper(mb_substr($otherName, 0, 1, 'UTF-8'), 'UTF-8');
                            $avatarPath = isset($p['other_avatar_path']) ? trim((string)$p['other_avatar_path']) : '';
                            ?>
                            <div class="card" style="background: var(--surface-subtle); border: 1px solid var(--border-subtle); border-radius: 12px; padding: 16px;">
                                <div class="d-flex align-items-center justify-content-between gap-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div style="width: 50px; height: 50px; border-radius: 50%; background: radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%); display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 700; color: #050509; flex-shrink: 0;">
                                            <?php if ($avatarPath !== ''): ?>
                                                <img src="<?= htmlspecialchars($avatarPath, ENT_QUOTES, 'UTF-8') ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover; display: block; border-radius: 50%;">
                                            <?php else: ?>
                                                <?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <a href="/painel-externo/perfil?user_id=<?= $otherId ?>" style="font-size: 16px; font-weight: 600; color: var(--text-primary); text-decoration: none;">
                                                <?= htmlspecialchars($otherName, ENT_QUOTES, 'UTF-8') ?>
                                            </a>
                                            <div style="font-size: 13px; color: var(--text-secondary);">Quer ser seu amigo</div>
                                        </div>
                                    </div>
                                    <form action="/painel-externo/amigos/decidir" method="post" class="d-flex gap-2">
                                        <input type="hidden" name="user_id" value="<?= $otherId ?>">
                                        <button type="submit" name="decision" value="accepted" class="btn friends-btn-success" style="padding: 8px 16px; border-radius: 8px; font-weight: 600; white-space: nowrap;">
                                            ✓ Aceitar
                                        </button>
                                        <button type="submit" name="decision" value="rejected" class="btn btn-outline-danger" style="border: 1px solid rgba(220, 53, 69, 0.3); color: #dc3545; padding: 8px 16px; border-radius: 8px; white-space: nowrap;">
                                            ✗ Recusar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
