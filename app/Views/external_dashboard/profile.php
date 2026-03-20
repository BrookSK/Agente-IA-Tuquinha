<?php

$isOwnProfile = (int)($user['id'] ?? 0) === (int)($profileUser['id'] ?? 0);
$displayName = trim((string)($profileUser['preferred_name'] ?? $profileUser['name'] ?? ''));
if ($displayName === '') {
    $displayName = 'Perfil';
}

$baseName = (string)($profileUser['preferred_name'] ?? $profileUser['name'] ?? 'U');
$initial = mb_strtoupper(mb_substr($baseName, 0, 1, 'UTF-8'), 'UTF-8');
$avatarPath = isset($profile['avatar_path']) ? trim((string)$profile['avatar_path']) : '';

$coverPath = isset($profile['cover_path']) ? trim((string)$profile['cover_path']) : '';

$nickname = trim((string)($profileUser['nickname'] ?? ''));

$courseBadges = $courseBadges ?? [];

$friendsCount = is_array($friends) ? count($friends) : 0;
$scrapsCount = is_array($scraps) ? count($scraps) : 0;
$communitiesCount = is_array($communities) ? count($communities) : 0;

$friendStatus = null;
$requestedById = null;
if (is_array($friendship)) {
    $friendStatus = $friendship['status'] ?? null;
    $requestedById = isset($friendship['requested_by_user_id']) ? (int)$friendship['requested_by_user_id'] : null;
}

$isFavoriteFriend = !empty($isFavoriteFriend);

$profileId = (int)($profileUser['id'] ?? 0);
$currentId = (int)($user['id'] ?? 0);
$isOwnProfile = $profileId === $currentId;

// Branding colors
$primaryColor = !empty($branding['primary_color']) ? $branding['primary_color'] : '#e53935';
$secondaryColor = !empty($branding['secondary_color']) ? $branding['secondary_color'] : '#ff6f60';
$accentColor = !empty($branding['accent_color']) ? $branding['accent_color'] : '#4caf50';

?>
<style>
    .profile-page-wrap {
        max-width: 1100px;
        margin: 0 auto;
        padding: 32px 24px 80px;
    }
    
    .profile-hero {
        display: flex;
        align-items: flex-end;
        gap: 20px;
        margin-bottom: 32px;
        position: relative;
        z-index: 2;
        flex-wrap: wrap;
    }
    
    .profile-avatar-ring {
        width: 108px;
        height: 108px;
        border-radius: 50%;
        border: 4px solid var(--bg);
        box-shadow: 0 4px 16px rgba(0,0,0,.15);
        flex-shrink: 0;
        background: var(--surface-subtle);
        overflow: hidden;
    }
    .profile-avatar-ring img { width: 100%; height: 100%; object-fit: cover; }
    
    .profile-hero-info { flex: 1; padding-bottom: 6px; }
    .profile-hero-name {
        font-size: 1.6rem;
        font-weight: 800;
        letter-spacing: -.03em;
        line-height: 1.1;
        color: var(--text-primary);
    }
    .profile-hero-handle { font-size: .82rem; color: var(--text-secondary); margin-top: 2px; }
    
    .profile-hero-actions {
        display: flex;
        gap: 8px;
        align-items: center;
        padding-bottom: 6px;
        flex-wrap: wrap;
    }
    
    .profile-stats-bar {
        display: flex;
        gap: 0;
        background: var(--surface-card);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 16px;
        box-shadow: 0 2px 12px rgba(0,0,0,.06), 0 1px 3px rgba(0,0,0,.04);
        overflow: hidden;
        margin-bottom: 24px;
        animation: fadeUp .35s ease both;
    }
    .profile-stat-item {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 18px 12px;
        border-right: 1px solid rgba(255, 255, 255, 0.08);
        transition: background .2s;
        cursor: default;
    }
    .profile-stat-item:last-child { border-right: none; }
    .profile-stat-item:hover { background: var(--surface-subtle); }
    .profile-stat-num {
        font-size: 1.5rem;
        font-weight: 800;
        letter-spacing: -.04em;
        color: var(--text-primary);
    }
    .profile-stat-lbl { font-size: .72rem; color: var(--text-secondary); font-weight: 500; text-transform: uppercase; letter-spacing: .06em; margin-top: 2px; }
    
    .profile-layout {
        display: grid;
        grid-template-columns: 240px 1fr 260px;
        gap: 20px;
        align-items: start;
    }
    
    .profile-card {
        background: var(--surface-card);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 16px;
        box-shadow: 0 2px 12px rgba(0,0,0,.06), 0 1px 3px rgba(0,0,0,.04);
        overflow: hidden;
    }
    .profile-card + .profile-card { margin-top: 16px; }
    
    .profile-card-header {
        padding: 18px 20px 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .profile-card-title {
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .12em;
        text-transform: uppercase;
        color: var(--text-secondary);
    }
    .profile-card-body { padding: 14px 20px 20px; }
    
    .profile-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 9px 18px;
        border-radius: 10px;
        border: none;
        font-size: .8rem;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        transition: transform .15s, box-shadow .15s;
        white-space: nowrap;
    }
    .profile-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,.12); }
    .profile-btn:active { transform: none; }
    
    .profile-btn-primary { background: linear-gradient(135deg, <?= $primaryColor ?>, <?= $secondaryColor ?>); color: #fff; }
    .profile-btn-ghost { background: var(--surface-subtle); border: 1.5px solid rgba(255, 255, 255, 0.08); color: var(--text-primary); }
    .profile-btn-chat { background: linear-gradient(135deg, <?= $accentColor ?>, #1e7a47); color: #fff; }
    .profile-btn-sm { padding: 6px 13px; font-size: .75rem; }
    
    @keyframes fadeUp {
        from { opacity:0; transform:translateY(14px); }
        to { opacity:1; transform:translateY(0); }
    }
    .profile-card { animation: fadeUp .4s ease both; }
    
    @media (max-width: 900px) {
        .profile-layout { grid-template-columns: 220px 1fr; }
        .profile-layout > *:last-child { grid-column: 1 / -1; }
    }
    @media (max-width: 600px) {
        .profile-layout { grid-template-columns: 1fr; }
        .profile-cover { height: 140px; }
        .profile-hero { margin-top: -40px; }
        .profile-avatar-ring { width: 80px; height: 80px; }
        .profile-stats-bar { flex-wrap: wrap; }
        .profile-stat-item { min-width: 50%; border-right: none; border-bottom: 1px solid rgba(255, 255, 255, 0.08); }
        .profile-page-wrap { padding: 0 14px 60px; }
    }
</style>

<div class="profile-page-wrap">
    <!-- HERO -->
    <div class="profile-hero">
        <div class="profile-avatar-ring">
            <?php if ($avatarPath !== ''): ?>
                <img src="<?= htmlspecialchars($avatarPath, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>"/>
            <?php else: ?>
                <div style="width:100%; height:100%; background:radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%); display:flex; align-items:center; justify-content:center; font-size:40px; font-weight:700; color:#050509;">
                    <?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="profile-hero-info">
            <h1 class="profile-hero-name"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="profile-hero-handle">
                <?php if ($nickname !== ''): ?>@<?= htmlspecialchars($nickname, ENT_QUOTES, 'UTF-8') ?> · <?php endif; ?>
                <span style="color:<?= $accentColor ?>;font-weight:500;">● Online</span>
            </p>
        </div>
        <div class="profile-hero-actions">
            <?php if (!$isOwnProfile && $friendStatus === 'accepted'): ?>
                <a href="/painel-externo/chat?user_id=<?= $profileId ?>" class="profile-btn profile-btn-chat">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    Chat privado
                </a>
            <?php endif; ?>
            <a href="#scraps" class="profile-btn profile-btn-ghost">Scraps</a>
            <?php if ($isOwnProfile): ?>
                <a href="/painel-externo/perfil/editar" class="profile-btn profile-btn-ghost profile-btn-sm">
                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    Editar
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- STATS BAR -->
    <div class="profile-stats-bar">
        <div class="profile-stat-item">
            <span class="profile-stat-num"><?= $friendsCount ?></span>
            <span class="profile-stat-lbl">Amigos</span>
        </div>
        <div class="profile-stat-item">
            <span class="profile-stat-num"><?= $scrapsCount ?></span>
            <span class="profile-stat-lbl">Scraps</span>
        </div>
        <div class="profile-stat-item">
            <span class="profile-stat-num"><?= $communitiesCount ?></span>
            <span class="profile-stat-lbl">Comunid.</span>
        </div>
        <div class="profile-stat-item">
            <span class="profile-stat-num">0</span>
            <span class="profile-stat-lbl">Visitas</span>
        </div>
    </div>

    <!-- 3-COL LAYOUT -->
    <div class="profile-layout">
        <!-- LEFT COLUMN -->
        <div>
            <?php if (!$isOwnProfile && $friendStatus === 'accepted'): ?>
                <!-- Friendship Badge -->
                <div class="profile-card">
                    <div class="profile-card-body">
                        <form action="/painel-externo/amigos/favorito" method="post" style="margin:0 0 10px 0;">
                            <input type="hidden" name="user_id" value="<?= $profileId ?>">
                            <input type="hidden" name="is_favorite" value="<?= $isFavoriteFriend ? '0' : '1' ?>">
                            <button type="submit" class="<?= $isFavoriteFriend ? 'active' : '' ?>" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 6px; padding: 8px; border-radius: 9px; background: none; border: 1.5px solid rgba(255, 255, 255, 0.08); font-size: .8rem; font-weight: 500; color: <?= $isFavoriteFriend ? '#d4a017' : 'var(--text-secondary)' ?>; cursor: pointer; transition: all .2s;">
                                <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                                <?= $isFavoriteFriend ? 'Remover dos favoritos' : 'Favoritar amigo' ?>
                            </button>
                        </form>

                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <a href="/painel-externo/chat?user_id=<?= $profileId ?>" class="profile-btn profile-btn-primary" style="width: 100%; justify-content: center; text-decoration: none;">
                                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                Abrir chat privado
                            </a>
                            <a href="#scraps" class="profile-btn profile-btn-ghost" style="width: 100%; justify-content: center; text-decoration: none;">
                                Ir para os scraps
                            </a>
                        </div>

                        <p style="text-align: center; font-size: .72rem; color: var(--text-secondary); margin-top: 14px;">
                            <?= (int)($profile['visits_count'] ?? 0) ?> visita(s) neste perfil.
                        </p>
                    </div>
                </div>
            <?php elseif (!$isOwnProfile && $friendStatus === 'pending' && $requestedById === $currentId): ?>
                <div class="profile-card">
                    <div class="profile-card-body">
                        <div style="font-size:12px; color:#ffb74d; background:var(--surface-subtle); border-radius:10px; border:1px solid rgba(255, 255, 255, 0.08); padding:10px; text-align:center; margin-bottom:10px;">
                            Pedido de amizade enviado. Aguardando resposta.
                        </div>
                        <form action="/painel-externo/amigos/cancelar" method="post" style="margin:0;">
                            <input type="hidden" name="user_id" value="<?= $profileId ?>">
                            <button type="submit" class="profile-btn profile-btn-ghost" style="width:100%; justify-content:center; color:#c0392b;">
                                Cancelar solicitação
                            </button>
                        </form>
                    </div>
                </div>
            <?php elseif (!$isOwnProfile && $friendStatus === 'pending' && $requestedById !== $currentId): ?>
                <div class="profile-card">
                    <div class="profile-card-body">
                        <form action="/painel-externo/amigos/decidir" method="post" style="display:flex; flex-direction:column; gap:8px;">
                            <div style="font-size:.82rem; color:var(--text-secondary); text-align:center; margin-bottom:4px;">Esta pessoa quer ser sua amiga.</div>
                            <input type="hidden" name="user_id" value="<?= $profileId ?>">
                            <button type="submit" name="decision" value="accepted" class="profile-btn profile-btn-chat" style="width:100%; justify-content:center;">
                                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg>
                                Aceitar
                            </button>
                            <button type="submit" name="decision" value="rejected" class="profile-btn profile-btn-ghost" style="width:100%; justify-content:center; color:#c0392b;">
                                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                Recusar
                            </button>
                        </form>
                    </div>
                </div>
            <?php elseif (!$isOwnProfile): ?>
                <div class="profile-card">
                    <div class="profile-card-body">
                        <form action="/painel-externo/amigos/solicitar" method="post" style="margin:0;">
                            <input type="hidden" name="user_id" value="<?= $profileId ?>">
                            <button type="submit" class="profile-btn profile-btn-primary" style="width:100%; justify-content:center; margin-bottom:10px;">
                                Adicionar como amigo
                            </button>
                        </form>
                        <a href="#scraps" class="profile-btn profile-btn-ghost" style="width:100%; justify-content:center; text-decoration:none;">Ir para os scraps</a>
                    </div>
                </div>
            <?php endif; ?>

        </div>

        <!-- MIDDLE COLUMN -->
        <div>
            <!-- Sobre -->
            <div class="profile-card">
                <div class="profile-card-header"><span class="profile-card-title">Sobre</span></div>
                <div class="profile-card-body">
                    <?php if (!empty($profile['about_me'])): ?>
                        <p style="font-size:.875rem; color:var(--text-primary); line-height:1.6;"><?= nl2br(htmlspecialchars((string)$profile['about_me'], ENT_QUOTES, 'UTF-8')) ?></p>
                    <?php else: ?>
                        <p style="font-size:.875rem; color:var(--text-secondary); font-style:italic;">Nenhuma descrição adicionada ainda.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Detalhes Sociais -->
            <div class="profile-card">
                <div class="profile-card-header"><span class="profile-card-title">Detalhes Sociais</span></div>
                <div class="profile-card-body">
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        <?php
                        $socialDetails = [
                            'Idioma' => $profile['language'] ?? '',
                            'Categoria' => $profile['profile_category'] ?? '',
                            'Relacionamento' => $profile['relationship_status'] ?? '',
                            'Aniversário' => $profile['birthday'] ?? '',
                            'Idade' => isset($profile['age']) ? (int)$profile['age'] : '',
                            'Cidade natal' => $profile['hometown'] ?? '',
                            'Onde mora' => $profile['location'] ?? '',
                        ];
                        $hasAnyDetail = false;
                        foreach ($socialDetails as $key => $value):
                            if ($value !== '' && $value !== 0):
                                $hasAnyDetail = true;
                        ?>
                            <div style="display:flex; align-items:baseline; gap:8px; font-size:.84rem; line-height:1.4;">
                                <span style="color:var(--text-secondary); font-weight:500; min-width:130px; flex-shrink:0; font-size:.78rem;"><?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?></span>
                                <span style="color:var(--text-primary); font-weight:400;"><?= htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        <?php endif; endforeach; ?>
                        <?php if (!$hasAnyDetail): ?>
                            <p style="font-size:.875rem; color:var(--text-secondary);">Nenhum detalhe social preenchido ainda.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Interesses -->
            <div class="profile-card">
                <div class="profile-card-header"><span class="profile-card-title">Interesses</span></div>
                <div class="profile-card-body">
                    <div style="display:flex; gap:7px; flex-wrap:wrap;">
                        <?php
                        $hasInterests = false;
                        $interestsList = [];
                        if (!empty($profile['interests'])) {
                            $interestsList = array_merge($interestsList, array_map('trim', explode(',', (string)$profile['interests'])));
                        }
                        if (!empty($profile['favorite_music'])) {
                            $interestsList[] = 'Música';
                        }
                        if (!empty($profile['favorite_movies'])) {
                            $interestsList[] = 'Filmes';
                        }
                        if (!empty($profile['favorite_books'])) {
                            $interestsList[] = 'Livros';
                        }
                        foreach ($interestsList as $interest):
                            if (trim($interest) !== ''):
                                $hasInterests = true;
                        ?>
                            <span style="padding:4px 11px; background:var(--surface-subtle); border:1.5px solid rgba(255, 255, 255, 0.08); border-radius:999px; font-size:.75rem; font-weight:500; color:var(--text-primary);">
                                <?= htmlspecialchars(trim($interest), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endif; endforeach; ?>
                        <?php if (!$hasInterests): ?>
                            <p style="font-size:.875rem; color:var(--text-secondary);">Nenhum interesse cadastrado ainda.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Redes Sociais -->
            <div class="profile-card">
                <div class="profile-card-header"><span class="profile-card-title">Redes Sociais</span></div>
                <div class="profile-card-body">
                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                        <?php
                        $hasSocial = false;
                        if (!empty($profile['website'])):
                            $hasSocial = true;
                        ?>
                            <a href="<?= htmlspecialchars((string)$profile['website'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" style="display:inline-flex; align-items:center; gap:6px; padding:6px 13px; background:var(--surface-subtle); border:1.5px solid rgba(255, 255, 255, 0.08); border-radius:8px; font-size:.78rem; font-weight:600; color:var(--text-primary); text-decoration:none; transition:all .2s;">
                                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                                Site pessoal
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($profile['instagram'])):
                            $hasSocial = true;
                        ?>
                            <a href="<?= htmlspecialchars((string)$profile['instagram'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" style="display:inline-flex; align-items:center; gap:6px; padding:6px 13px; background:var(--surface-subtle); border:1.5px solid rgba(255, 255, 255, 0.08); border-radius:8px; font-size:.78rem; font-weight:600; color:var(--text-primary); text-decoration:none; transition:all .2s;">
                                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg>
                                Instagram
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($profile['facebook'])):
                            $hasSocial = true;
                        ?>
                            <a href="<?= htmlspecialchars((string)$profile['facebook'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" style="display:inline-flex; align-items:center; gap:6px; padding:6px 13px; background:var(--surface-subtle); border:1.5px solid rgba(255, 255, 255, 0.08); border-radius:8px; font-size:.78rem; font-weight:600; color:var(--text-primary); text-decoration:none; transition:all .2s;">
                                <svg width="13" height="13" fill="currentColor" viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                                Facebook
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($profile['youtube'])):
                            $hasSocial = true;
                        ?>
                            <a href="<?= htmlspecialchars((string)$profile['youtube'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" style="display:inline-flex; align-items:center; gap:6px; padding:6px 13px; background:var(--surface-subtle); border:1.5px solid rgba(255, 255, 255, 0.08); border-radius:8px; font-size:.78rem; font-weight:600; color:var(--text-primary); text-decoration:none; transition:all .2s;">
                                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46A2.78 2.78 0 0 0 1.46 6.42 29 29 0 0 0 1 12a29 29 0 0 0 .46 5.58 2.78 2.78 0 0 0 1.95 1.96C5.12 20 12 20 12 20s6.88 0 8.59-.46a2.78 2.78 0 0 0 1.96-1.96A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58z"/><polygon fill="currentColor" points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02"/></svg>
                                YouTube
                            </a>
                        <?php endif; ?>
                        <?php if (!$hasSocial): ?>
                            <p style="font-size:.875rem; color:var(--text-secondary);">Nenhuma rede social cadastrada ainda.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Scraps -->
            <div class="profile-card" id="scraps">
                <div class="profile-card-header">
                    <span class="profile-card-title">Scraps</span>
                    <span style="font-size:.72rem; color:var(--text-secondary);">Recados públicos no mural</span>
                </div>
                <div class="profile-card-body">
                    <?php if (!$isOwnProfile): ?>
                        <form action="/painel-externo/perfil/scrap" method="post" style="margin-bottom:14px;">
                            <input type="hidden" name="to_user_id" value="<?= $profileId ?>">
                            <textarea name="body" rows="3" placeholder="Escreva um scrap carinhoso, uma dúvida ou um oi nostálgico…" style="width:100%; min-height:72px; padding:10px 14px; border:1.5px solid rgba(255, 255, 255, 0.1); border-radius:10px; background:var(--surface-subtle); font-size:.875rem; color:var(--text-primary); resize:vertical; outline:none; transition:border-color .2s; line-height:1.6;"></textarea>
                            <div style="display:flex; align-items:center; justify-content:flex-end; margin-top:10px;">
                                <button type="submit" class="profile-btn profile-btn-primary profile-btn-sm">
                                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                                    Enviar scrap
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>

                    <?php if (empty($scraps)): ?>
                        <p style="font-size:.875rem; color:var(--text-secondary);">Nenhum scrap ainda. Seja o primeiro a deixar um recado aqui.</p>
                    <?php else: ?>
                        <div style="display:flex; flex-direction:column;">
                            <?php foreach ($scraps as $s): ?>
                                <?php
                                    $scrapFromAvatar = trim((string)($s['from_user_avatar_path'] ?? ''));
                                    $scrapFromName = (string)($s['from_user_name'] ?? 'Usuário');
                                    $scrapFromInitial = mb_strtoupper(mb_substr(trim($scrapFromName), 0, 1, 'UTF-8'), 'UTF-8');
                                    $scrapBody = (string)($s['body'] ?? '');
                                    $scrapDate = (string)($s['created_at'] ?? '');
                                ?>
                                <div style="padding:14px 0; border-bottom:1px solid rgba(255, 255, 255, 0.08); display:flex; gap:12px; align-items:flex-start;">
                                    <div style="width:36px; height:36px; border-radius:50%; overflow:hidden; flex-shrink:0; background:var(--surface-subtle); border:2px solid rgba(255, 255, 255, 0.08);">
                                        <?php if ($scrapFromAvatar !== ''): ?>
                                            <img src="<?= htmlspecialchars($scrapFromAvatar, ENT_QUOTES, 'UTF-8') ?>" alt="" style="width:100%; height:100%; object-fit:cover;"/>
                                        <?php else: ?>
                                            <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:16px; font-weight:700; color:var(--text-secondary);">
                                                <?= htmlspecialchars($scrapFromInitial, ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div style="flex:1; min-width:0;">
                                        <div style="font-weight:600; font-size:.82rem; color:var(--text-primary);">
                                            <?= htmlspecialchars($scrapFromName, ENT_QUOTES, 'UTF-8') ?>
                                            <span style="font-size:.72rem; color:var(--text-secondary); margin-left:6px; font-weight:400;"><?= htmlspecialchars($scrapDate, ENT_QUOTES, 'UTF-8') ?></span>
                                        </div>
                                        <p style="font-size:.84rem; margin-top:3px; line-height:1.5; color:var(--text-primary);">
                                            <?= nl2br(htmlspecialchars($scrapBody, ENT_QUOTES, 'UTF-8')) ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN -->
        <div>
            <!-- Depoimentos -->
            <div class="profile-card">
                <div class="profile-card-header"><span class="profile-card-title">Depoimentos</span></div>
                <div class="profile-card-body">
                    <?php if (empty($publicTestimonials)): ?>
                        <p style="font-size:.8rem; color:var(--text-secondary);">Nenhum depoimento público ainda.</p>
                    <?php else: ?>
                        <div style="display:flex; flex-direction:column; gap:10px;">
                            <?php foreach ($publicTestimonials as $t): ?>
                                <div style="padding:10px; background:var(--surface-subtle); border:1.5px solid rgba(255, 255, 255, 0.08); border-radius:10px;">
                                    <p style="font-size:.82rem; font-weight:600; color:var(--text-primary); margin-bottom:4px;">
                                        <?= htmlspecialchars((string)($t['from_user_name'] ?? 'Usuário'), ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                    <p style="font-size:.78rem; color:var(--text-secondary); line-height:1.5;">
                                        <?= nl2br(htmlspecialchars((string)($t['body'] ?? ''), ENT_QUOTES, 'UTF-8')) ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Comunidades -->
            <div class="profile-card">
                <div class="profile-card-header"><span class="profile-card-title">Comunidades</span></div>
                <div class="profile-card-body">
                    <?php if (empty($communities)): ?>
                        <p style="font-size:.8rem; color:var(--text-secondary);">Não participa de nenhuma comunidade ainda.</p>
                    <?php else: ?>
                        <div style="display:flex; flex-direction:column; gap:10px;">
                            <?php foreach ($communities as $c): ?>
                                <?php
                                    $commId = (int)($c['id'] ?? 0);
                                    $commName = (string)($c['name'] ?? 'Comunidade');
                                    $commImage = trim((string)($c['image_path'] ?? ''));
                                    $commInitial = mb_strtoupper(mb_substr($commName, 0, 1, 'UTF-8'), 'UTF-8');
                                ?>
                                <a href="/painel-externo/comunidade/topicos?community_id=<?= $commId ?>" style="display:flex; align-items:center; gap:10px; padding:10px 12px; background:var(--surface-subtle); border:1.5px solid rgba(255, 255, 255, 0.08); border-radius:10px; text-decoration:none; color:var(--text-primary); font-size:.84rem; font-weight:500; transition:all .2s;">
                                    <div style="width:36px; height:36px; border-radius:8px; overflow:hidden; background: linear-gradient(135deg, var(--accent), var(--accent2)); flex-shrink:0; display:flex; align-items:center; justify-content:center;">
                                        <?php if ($commImage !== ''): ?>
                                            <img src="<?= htmlspecialchars($commImage, ENT_QUOTES, 'UTF-8') ?>" alt="" style="width:100%; height:100%; object-fit:cover;"/>
                                        <?php else: ?>
                                            <span style="font-size:16px; font-weight:700; color:var(--button-text);"><?= htmlspecialchars($commInitial, ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <span><?= htmlspecialchars($commName, ENT_QUOTES, 'UTF-8') ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

    <script>
    (function () {
        var btn = document.getElementById('copyProfileLinkBtn');
        var statusEl = document.getElementById('copyProfileLinkStatus');
        if (!btn) return;

        function setStatus(text, ok) {
            if (!statusEl) return;
            statusEl.style.display = 'block';
            statusEl.style.color = ok ? '#8bc34a' : 'var(--text-secondary)';
            statusEl.textContent = text;
            window.clearTimeout(setStatus._t);
            setStatus._t = window.setTimeout(function () {
                statusEl.style.display = 'none';
            }, 2200);
        }

        btn.addEventListener('click', function () {
            var profileId = btn.getAttribute('data-profile-id') || '';
            var base = (window.location && window.location.origin) ? window.location.origin : '';
            var url = base + '/painel-externo/perfil?user_id=' + encodeURIComponent(profileId);

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(function () {
                    setStatus('Link copiado!', true);
                }).catch(function () {
                    window.prompt('Copie o link do seu perfil:', url);
                });
                return;
            }

            window.prompt('Copie o link do seu perfil:', url);
        });
    })();
    </script>
