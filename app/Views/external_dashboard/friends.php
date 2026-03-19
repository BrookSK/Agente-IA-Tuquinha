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
    .friends-page-container {
        max-width: 100%;
        margin: 0 auto;
        padding: 48px 24px;
    }
    
    .friends-page-header {
        display: flex;
        align-items: baseline;
        gap: 14px;
        margin-bottom: 40px;
    }
    
    .friends-page-header h1 {
        font-size: clamp(2rem, 5vw, 3rem);
        font-weight: 800;
        letter-spacing: -.03em;
        line-height: 1;
        color: var(--text-primary);
        margin: 0;
    }
    
    .friends-pill-count {
        background: linear-gradient(135deg, <?= $primaryColor ?>, <?= $secondaryColor ?>);
        color: #fff;
        font-size: .75rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        padding: 4px 10px;
        border-radius: 999px;
        align-self: center;
    }
    
    .friends-card {
        background: var(--surface-card);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 16px;
        box-shadow: 0 2px 12px rgba(0,0,0,.06), 0 1px 3px rgba(0,0,0,.04);
        overflow: hidden;
        margin-bottom: 20px;
    }
    
    .friends-card-header {
        padding: 24px 28px 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }
    
    .friends-card-title {
        font-size: 1rem;
        font-weight: 700;
        letter-spacing: .02em;
        text-transform: uppercase;
        color: var(--text-secondary);
        margin: 0;
    }
    
    .friends-toolbar {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        padding: 16px 28px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        background: var(--surface-subtle);
    }
    
    .friends-search-wrap {
        position: relative;
        flex: 1;
        min-width: 160px;
    }
    
    .friends-search-wrap svg {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-secondary);
        pointer-events: none;
    }
    
    .friends-search-input {
        width: 100%;
        padding: 9px 12px 9px 36px;
        border: 1.5px solid rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        background: var(--surface-card);
        font-size: .875rem;
        color: var(--text-primary);
        outline: none;
        transition: border-color .2s;
    }
    
    .friends-search-input:focus {
        border-color: <?= $primaryColor ?>;
    }
    
    .friends-search-input::placeholder {
        color: var(--text-secondary);
    }
    
    .friends-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 10px;
        border: none;
        font-size: .8rem;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        transition: transform .15s, box-shadow .15s, opacity .15s;
        white-space: nowrap;
    }
    
    .friends-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0,0,0,.12);
    }
    
    .friends-btn:active {
        transform: translateY(0);
        box-shadow: none;
    }
    
    .friends-btn-primary {
        background: linear-gradient(135deg, <?= $primaryColor ?>, <?= $secondaryColor ?>);
        color: #fff;
    }
    
    .friends-btn-success {
        background: linear-gradient(135deg, <?= $accentColor ?>, #8bc34a);
        color: #fff;
    }
    
    .friends-btn-ghost {
        background: var(--surface-subtle);
        color: var(--text-primary);
        border: 1.5px solid rgba(255, 255, 255, 0.1);
    }
    
    .friends-btn-danger {
        background: transparent;
        color: #c0392b;
        border: 1.5px solid rgba(192, 57, 43, 0.3);
    }
    
    .friends-btn-danger:hover {
        background: rgba(192, 57, 43, 0.1);
    }
    
    .friends-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 14px;
        padding: 20px 28px 28px;
    }
    
    .friend-card-item {
        background: var(--surface-subtle);
        border: 1.5px solid rgba(255, 255, 255, 0.08);
        border-radius: 14px;
        padding: 20px 18px 16px;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 10px;
        transition: box-shadow .2s, transform .2s;
        position: relative;
    }
    
    .friend-card-item:hover {
        box-shadow: 0 8px 32px rgba(0,0,0,.10), 0 2px 8px rgba(0,0,0,.06);
        transform: translateY(-2px);
    }
    
    .friend-avatar-wrap {
        width: 68px;
        height: 68px;
        position: relative;
    }
    
    .friend-avatar-wrap img {
        width: 68px;
        height: 68px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid var(--surface-card);
        box-shadow: 0 2px 8px rgba(0,0,0,.10);
    }
    
    .friend-name {
        font-weight: 700;
        font-size: .95rem;
        letter-spacing: -.01em;
        color: var(--text-primary);
        margin: 0;
    }
    
    .friend-badge {
        font-size: .75rem;
        color: var(--text-secondary);
        margin-top: -6px;
    }
    
    .friend-actions {
        display: flex;
        flex-direction: column;
        gap: 7px;
        width: 100%;
        margin-top: 4px;
    }
    
    .friend-actions-row {
        display: flex;
        gap: 7px;
    }
    
    .friend-actions-row .friends-btn {
        flex: 1;
        justify-content: center;
        font-size: .75rem;
        padding: 7px 10px;
    }
    
    .friend-actions .friends-btn-danger {
        width: 100%;
        justify-content: center;
        font-size: .75rem;
        padding: 7px 10px;
    }
    
    .friends-empty-state {
        text-align: center;
        padding: 48px 24px;
        color: var(--text-secondary);
    }
    
    .friends-empty-state .icon {
        font-size: 2.5rem;
        margin-bottom: 12px;
    }
    
    .friends-empty-state p {
        font-size: .9rem;
        line-height: 1.6;
    }
    
    .pending-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
        padding: 20px 28px 28px;
    }
    
    .pending-item {
        display: flex;
        align-items: center;
        gap: 14px;
        background: var(--surface-subtle);
        border: 1.5px solid rgba(255, 255, 255, 0.08);
        border-radius: 12px;
        padding: 14px 16px;
        transition: box-shadow .2s;
    }
    
    .pending-item:hover {
        box-shadow: 0 2px 12px rgba(0,0,0,.06);
    }
    
    .pending-item img {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        object-fit: cover;
        flex-shrink: 0;
        border: 2.5px solid var(--surface-card);
    }
    
    .pending-info {
        flex: 1;
        min-width: 0;
    }
    
    .pending-info strong {
        display: block;
        font-size: .9rem;
        font-weight: 600;
        color: var(--text-primary);
    }
    
    .pending-info span {
        font-size: .78rem;
        color: var(--text-secondary);
    }
    
    .pending-actions {
        display: flex;
        gap: 8px;
        flex-shrink: 0;
    }
    
    .pending-actions .friends-btn {
        padding: 7px 14px;
        font-size: .75rem;
    }
    
    .fav-star {
        position: absolute;
        top: 12px;
        right: 14px;
        background: none;
        border: none;
        cursor: default;
        font-size: 1rem;
        opacity: 1;
        line-height: 1;
        color: <?= $accentColor ?>;
    }
    
    .toggle-fav {
        display: flex;
        align-items: center;
        gap: 7px;
        font-size: .8rem;
        font-weight: 500;
        color: var(--text-secondary);
        cursor: pointer;
        user-select: none;
        white-space: nowrap;
    }
    
    .toggle-fav input[type=checkbox] {
        accent-color: <?= $primaryColor ?>;
        width: 15px;
        height: 15px;
        cursor: pointer;
    }
    
    @media (max-width: 540px) {
        .friends-page-container {
            padding: 28px 14px;
        }
        .friends-card-header,
        .friends-grid,
        .pending-list {
            padding-left: 18px;
            padding-right: 18px;
        }
        .friends-toolbar {
            padding: 14px 18px;
        }
        .friends-grid {
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
    }
</style>

<div class="friends-page-container">
    <header class="friends-page-header">
        <h1>Meus Amigos</h1>
        <span class="friends-pill-count"><?= $friendsCount ?> <?= $friendsCount === 1 ? 'amigo' : 'amigos' ?></span>
    </header>
            
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" style="background: rgba(220, 53, 69, 0.1); border: 1px solid rgba(220, 53, 69, 0.3); color: #dc3545; padding: 12px 16px; border-radius: 10px; margin-bottom: 20px;">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="alert alert-success" style="background: rgba(40, 167, 69, 0.1); border: 1px solid rgba(40, 167, 69, 0.3); color: #28a745; padding: 12px 16px; border-radius: 10px; margin-bottom: 20px;">
            <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <section class="friends-card">
        <div class="friends-card-header">
            <span class="friends-card-title">Lista de Amigos</span>
            <a href="/painel-externo/amigos/adicionar" class="friends-btn friends-btn-primary">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                Adicionar Amigo
            </a>
        </div>

        <form action="/painel-externo/amigos" method="get" class="friends-toolbar">
            <div class="friends-search-wrap">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                </svg>
                <input class="friends-search-input" name="q" type="search" placeholder="Pesquisar amigo…" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>"/>
            </div>
            <label class="toggle-fav">
                <input type="checkbox" name="fav" value="1" <?= $onlyFavorites ? 'checked' : '' ?> onchange="this.form.submit()"/> Somente favoritos
            </label>
            <button type="submit" class="friends-btn friends-btn-ghost">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 6h18M7 12h10m-7 6h4"/></svg>
                Filtrar
            </button>
        </form>
        <?php if (empty($friends)): ?>
            <div class="friends-empty-state">
                <div class="icon">🔍</div>
                <p>Você ainda não tem amigos aceitos aqui.<br>Comece visitando perfis na comunidade e enviando pedidos de amizade.</p>
            </div>
        <?php else: ?>
            <div class="friends-grid">
                <?php foreach ($friends as $f): ?>
                    <?php
                    $friendId = (int)($f['friend_id'] ?? 0);
                    $friendName = (string)($f['friend_name'] ?? 'Amigo');
                    $initial = mb_strtoupper(mb_substr($friendName, 0, 1, 'UTF-8'), 'UTF-8');
                    $avatarPath = isset($f['friend_avatar_path']) ? trim((string)$f['friend_avatar_path']) : '';
                    $isFavorite = !empty($f['is_favorite']);
                    ?>
                    <div class="friend-card-item">
                        <?php if ($isFavorite): ?>
                            <span class="fav-star">★</span>
                        <?php endif; ?>
                        <div class="friend-avatar-wrap">
                            <?php if ($avatarPath !== ''): ?>
                                <img src="<?= htmlspecialchars($avatarPath, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($friendName, ENT_QUOTES, 'UTF-8') ?>"/>
                            <?php else: ?>
                                <div style="width: 68px; height: 68px; border-radius: 50%; background: radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%); display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 700; color: #050509; border: 3px solid var(--surface-card); box-shadow: 0 2px 8px rgba(0,0,0,.10);">
                                    <?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <p class="friend-name"><?= htmlspecialchars($friendName, ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="friend-badge">Amigo</p>
                        </div>
                        <div class="friend-actions">
                            <div class="friend-actions-row">
                                <a href="/painel-externo/chat?user_id=<?= $friendId ?>" class="friends-btn friends-btn-success">
                                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                    Conversar
                                </a>
                                <a href="/painel-externo/perfil?user_id=<?= $friendId ?>" class="friends-btn friends-btn-primary">
                                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                                    Perfil
                                </a>
                            </div>
                            <form action="/painel-externo/amigos/remover" method="post" onsubmit="return confirm('Tem certeza que deseja remover este amigo?');" style="margin: 0;">
                                <input type="hidden" name="user_id" value="<?= $friendId ?>">
                                <button type="submit" class="friends-btn friends-btn-danger">
                                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                    Remover
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="friends-card">
        <div class="friends-card-header">
            <span class="friends-card-title">Pedidos Pendentes</span>
            <span class="friends-pill-count" style="background: var(--text-secondary);"><?= $pendingCount ?> <?= $pendingCount === 1 ? 'pendente' : 'pendentes' ?></span>
        </div>
        <?php if (empty($pending)): ?>
            <div class="friends-empty-state">
                <div class="icon">✅</div>
                <p>Nenhum pedido de amizade aguardando sua resposta.</p>
            </div>
        <?php else: ?>
            <div class="pending-list">
                <?php foreach ($pending as $p): ?>
                    <?php
                    $otherId = (int)($p['other_id'] ?? 0);
                    $otherName = (string)($p['other_name'] ?? 'Usuário');
                    $initial = mb_strtoupper(mb_substr($otherName, 0, 1, 'UTF-8'), 'UTF-8');
                    $avatarPath = isset($p['other_avatar_path']) ? trim((string)$p['other_avatar_path']) : '';
                    ?>
                    <div class="pending-item">
                        <?php if ($avatarPath !== ''): ?>
                            <img src="<?= htmlspecialchars($avatarPath, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($otherName, ENT_QUOTES, 'UTF-8') ?>"/>
                        <?php else: ?>
                            <div style="width: 44px; height: 44px; border-radius: 50%; background: radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%); display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 700; color: #050509; flex-shrink: 0; border: 2.5px solid var(--surface-card);">
                                <?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php endif; ?>
                        <div class="pending-info">
                            <strong><?= htmlspecialchars($otherName, ENT_QUOTES, 'UTF-8') ?></strong>
                            <span>Enviou um pedido de amizade</span>
                        </div>
                        <form action="/painel-externo/amigos/decidir" method="post" class="pending-actions">
                            <input type="hidden" name="user_id" value="<?= $otherId ?>">
                            <button type="submit" name="decision" value="accepted" class="friends-btn friends-btn-success" title="Aceitar">
                                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg>
                                Aceitar
                            </button>
                            <button type="submit" name="decision" value="rejected" class="friends-btn friends-btn-danger" title="Recusar">
                                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
