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

$profileId = (int)($profile['id'] ?? 0);
$currentId = (int)($user['id'] ?? 0);
$isOwnProfile = $profileId === $currentId;

// Branding colors
$primaryColor = !empty($branding['primary_color']) ? $branding['primary_color'] : '#e53935';
$secondaryColor = !empty($branding['secondary_color']) ? $branding['secondary_color'] : '#ff6f60';
$accentColor = !empty($branding['accent_color']) ? $branding['accent_color'] : '#4caf50';

?>
<style>
    .profile-cover {
        height: 220px;
        background: linear-gradient(135deg, #1a1916 0%, #2d2a25 40%, #3a3530 100%);
        position: relative;
        overflow: hidden;
    }
    .profile-cover::after {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(ellipse at 30% 60%, rgba(<?= hexdec(substr($primaryColor, 1, 2)) ?>, <?= hexdec(substr($primaryColor, 3, 2)) ?>, <?= hexdec(substr($primaryColor, 5, 2)) ?>, .18) 0%, transparent 70%),
                    radial-gradient(ellipse at 80% 20%, rgba(<?= hexdec(substr($secondaryColor, 1, 2)) ?>, <?= hexdec(substr($secondaryColor, 3, 2)) ?>, <?= hexdec(substr($secondaryColor, 5, 2)) ?>, .12) 0%, transparent 60%);
    }
    .profile-cover::before {
        content: '';
        position: absolute;
        inset: 0;
        background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.04'/%3E%3C/svg%3E");
        opacity: .4;
    }
    
    .profile-page-wrap {
        max-width: 1100px;
        margin: 0 auto;
        padding: 0 24px 80px;
    }
    
    .profile-hero {
        display: flex;
        align-items: flex-end;
        gap: 20px;
        margin-top: -56px;
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

<!-- COVER -->
<div class="profile-cover">
    <?php if ($coverPath !== ''): ?>
        <img src="<?= htmlspecialchars($coverPath, ENT_QUOTES, 'UTF-8') ?>" alt="Capa" style="position:absolute; inset:0; width:100%; height:100%; object-fit:cover; z-index:1;"/>
    <?php endif; ?>
</div>

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
                        <div style="background: linear-gradient(135deg, #e8f8ef, #d4f0e2); border: 1.5px solid #b8e8cc; border-radius: 10px; padding: 10px 14px; font-size: .78rem; color: <?= $accentColor ?>; font-weight: 500; text-align: center; margin-bottom: 14px; line-height: 1.4;">
                            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" style="vertical-align:middle;margin-right:4px;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            Vocês são amigos na rede social do Tuquinha.
                        </div>

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

                        <p style="text-align: center; font-size: .72rem; color: var(--text-secondary); margin-top: 14px;">0 visita(s) neste perfil.</p>
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

            <!-- Portfolio -->
            <div class="profile-card">
                <div class="profile-card-header">
                    <span class="profile-card-title">Portfólio</span>
                    <span style="font-size:.72rem;color:var(--text-secondary);">último publicado</span>
                </div>
                <div class="profile-card-body">
                    <?php
                        $portfolioCover = '';
                        $portfolioTitle = '';
                        if (!empty($lastPublishedPortfolioItem) && is_array($lastPublishedPortfolioItem)) {
                            $portfolioCover = trim((string)($lastPublishedPortfolioItem['cover_url'] ?? ''));
                            $portfolioTitle = trim((string)($lastPublishedPortfolioItem['title'] ?? ''));
                        }
                    ?>
                    <div style="width:100%; aspect-ratio:16/9; border-radius:10px; background:linear-gradient(135deg, #2d2a25, #1a1916); overflow:hidden; position:relative; margin-bottom:10px;">
                        <?php if ($portfolioCover !== ''): ?>
                            <img src="<?= htmlspecialchars($portfolioCover, ENT_QUOTES, 'UTF-8') ?>" alt="" style="width:100%; height:100%; object-fit:cover;"/>
                        <?php else: ?>
                            <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;opacity:.3;">
                                <svg width="32" height="32" fill="none" stroke="#fff" stroke-width="1.5" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                            </div>
                        <?php endif; ?>
                    </div>
                    <p style="font-size:.75rem; font-weight:700; color:var(--text-secondary); margin-bottom:8px;">2025</p>
                    <a href="/painel-externo/perfil?user_id=<?= $profileId ?>#portfolio" class="profile-btn profile-btn-primary" style="width:100%; justify-content:center; text-decoration:none;">
                        Ver portfólio
                    </a>
                </div>
            </div>
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
                                    $commIcon = trim((string)($c['icon_path'] ?? ''));
                                ?>
                                <a href="/painel-externo/comunidade/topicos?community_id=<?= $commId ?>" style="display:flex; align-items:center; gap:10px; padding:10px 12px; background:var(--surface-subtle); border:1.5px solid rgba(255, 255, 255, 0.08); border-radius:10px; text-decoration:none; color:var(--text-primary); font-size:.84rem; font-weight:500; transition:all .2s;">
                                    <div style="width:36px; height:36px; border-radius:8px; overflow:hidden; background:rgba(255, 255, 255, 0.05); flex-shrink:0;">
                                        <?php if ($commIcon !== ''): ?>
                                            <img src="<?= htmlspecialchars($commIcon, ENT_QUOTES, 'UTF-8') ?>" alt="" style="width:100%; height:100%; object-fit:cover;"/>
                                        <?php else: ?>
                                            <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:18px;">🏘️</div>
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
            <div style="font-size:13px; color:var(--text-secondary);">
                <div style="display:flex; flex-direction:column; gap:4px;">
                    <?php
                    $details = [
                        'Idioma' => $profile['language'] ?? null,
                        'Categoria' => $profile['profile_category'] ?? null,
                        'Perfil' => ($profile['profile_privacy'] ?? '') === 'private' ? 'Privado' : 'Público',
                        'Visível para' => match ($profile['visibility_scope'] ?? 'everyone') {
                            'friends' => 'Apenas amigos',
                            'community' => 'Pessoas das mesmas comunidades',
                            default => 'Todos na comunidade',
                        },
                        'Relacionamento' => $profile['relationship_status'] ?? null,
                        'Aniversário' => $profile['birthday'] ?? null,
                        'Idade' => isset($profile['age']) && (int)$profile['age'] > 0 ? (int)$profile['age'] : null,
                        'Filhos' => $profile['children'] ?? null,
                        'Etnia' => $profile['ethnicity'] ?? null,
                        'Humor' => $profile['mood'] ?? null,
                        'Orientação sexual' => $profile['sexual_orientation'] ?? null,
                        'Estilo' => $profile['style'] ?? null,
                        'Fuma' => $profile['smokes'] ?? null,
                        'Bebe' => $profile['drinks'] ?? null,
                        'Animais de estimação' => $profile['pets'] ?? null,
                        'Cidade natal' => $profile['hometown'] ?? null,
                        'Onde mora' => $profile['location'] ?? null,
                        'Esportes' => $profile['sports'] ?? null,
                        'Paixões' => $profile['passions'] ?? null,
                        'Atividades' => $profile['activities'] ?? null,
                    ];
                    $hasAnyDetail = false;
                    foreach ($details as $label => $value):
                        if ($value === null || $value === '') {
                            continue;
                        }
                        $hasAnyDetail = true;
                        ?>
                        <div style="display:flex; align-items:baseline; gap:6px;">
                            <div style="font-size:12px; color:var(--text-secondary); text-transform:lowercase; white-space:nowrap;">
                                <?= htmlspecialchars(mb_strtolower($label, 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>:
                            </div>
                            <div style="min-width:0;">
                                <?= nl2br(htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8')) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$hasAnyDetail): ?>
                        <div style="font-size:13px;">Nenhum detalhe social preenchido ainda.</div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section style="background:var(--surface-card); border-radius:16px; border:1px solid var(--border-subtle); padding:12px 14px;">
            <h2 style="font-size:16px; margin-bottom:6px; color:var(--text-primary);">Interesses</h2>
            <div style="display:flex; flex-wrap:wrap; gap:6px; font-size:12px; color:var(--text-secondary);">
                <?php if (!empty($profile['interests'])): ?>
                    <span style="background:var(--surface-subtle); border-radius:999px; padding:4px 8px; border:1px solid var(--border-subtle);">Interesses: <?= htmlspecialchars((string)$profile['interests'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
                <?php if (!empty($profile['favorite_music'])): ?>
                    <span style="background:var(--surface-subtle); border-radius:999px; padding:4px 8px; border:1px solid var(--border-subtle);">Músicas: <?= htmlspecialchars((string)$profile['favorite_music'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
                <?php if (!empty($profile['favorite_movies'])): ?>
                    <span style="background:var(--surface-subtle); border-radius:999px; padding:4px 8px; border:1px solid var(--border-subtle);">Filmes: <?= htmlspecialchars((string)$profile['favorite_movies'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
                <?php if (!empty($profile['favorite_books'])): ?>
                    <span style="background:var(--surface-subtle); border-radius:999px; padding:4px 8px; border:1px solid var(--border-subtle);">Livros: <?= htmlspecialchars((string)$profile['favorite_books'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
                <?php if (empty($profile['interests']) && empty($profile['favorite_music']) && empty($profile['favorite_movies']) && empty($profile['favorite_books'])): ?>
                    <span>Nenhum interesse cadastrado ainda.</span>
                <?php endif; ?>
            </div>
        </section>

        <section style="background:var(--surface-card); border-radius:16px; border:1px solid var(--border-subtle); padding:12px 14px;">
            <h2 style="font-size:16px; margin-bottom:6px; color:var(--text-primary);">Redes sociais</h2>
            <div style="display:flex; flex-wrap:wrap; gap:6px; font-size:12px; color:var(--text-secondary);">
                <?php if (!empty($profile['website'])): ?>
                    <a href="<?= htmlspecialchars((string)$profile['website'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" style="background:var(--surface-subtle); border-radius:999px; padding:4px 8px; border:1px solid var(--border-subtle); color:#ff6f60;">Site pessoal</a>
                <?php endif; ?>
                <?php if (!empty($profile['instagram'])): ?>
                    <a href="<?= htmlspecialchars((string)$profile['instagram'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" style="background:var(--surface-subtle); border-radius:999px; padding:4px 8px; border:1px solid var(--border-subtle); color:#ff6f60;">Instagram</a>
                <?php endif; ?>
                <?php if (!empty($profile['facebook'])): ?>
                    <a href="<?= htmlspecialchars((string)$profile['facebook'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" style="background:var(--surface-subtle); border-radius:999px; padding:4px 8px; border:1px solid var(--border-subtle); color:#ff6f60;">Facebook</a>
                <?php endif; ?>
                <?php if (!empty($profile['youtube'])): ?>
                    <a href="<?= htmlspecialchars((string)$profile['youtube'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" style="background:var(--surface-subtle); border-radius:999px; padding:4px 8px; border:1px solid var(--border-subtle); color:#ff6f60;">YouTube</a>
                <?php endif; ?>
                <?php if (empty($profile['website']) && empty($profile['instagram']) && empty($profile['facebook']) && empty($profile['youtube'])): ?>
                    <span>Nenhuma rede social cadastrada ainda.</span>
                <?php endif; ?>
            </div>
        </section>

        <?php if ($isOwnProfile): ?>
            <section id="socialProfileEditSection" style="display:none; background:var(--surface-card); border-radius:16px; border:1px solid var(--border-subtle); padding:16px 18px; margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <h2 style="font-size: 20px; font-weight: 700; color: var(--text-primary); margin: 0;">Editar Meu Perfil</h2>
                    <button type="button" id="closeSocialProfileEditBtn" style="border: none; background: transparent; color: var(--text-secondary); font-size: 24px; cursor: pointer; padding: 0; line-height: 1;">×</button>
                </div>
                <form action="/painel-externo/perfil/salvar" method="post" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:10px; font-size:13px; color:var(--text-primary);">
                    <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:center;">
                        <div style="width:72px; height:72px; border-radius:50%; overflow:hidden; background:radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%); display:flex; align-items:center; justify-content:center; font-size:28px; font-weight:700; color:#050509;">
                            <?php if ($avatarPath !== ''): ?>
                                <img src="<?= htmlspecialchars($avatarPath, ENT_QUOTES, 'UTF-8') ?>" alt="Avatar" style="width:100%; height:100%; object-fit:cover; display:block;">
                            <?php else: ?>
                                <?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                        </div>
                        <div style="flex:1 1 0; min-width:0;">
                            <label for="avatar_file" style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Foto de perfil</label>
                            <input id="avatar_file" type="file" name="avatar_file" accept="image/*" style="font-size:12px;">
                            <div style="font-size:11px; color:var(--text-secondary); margin-top:2px;">Formatos comuns (JPG, PNG) até 2 MB.</div>
                        </div>
                    </div>

                    <div>
                        <label for="cover_file" style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Capa do perfil (estilo Behance)</label>
                        <input id="cover_file" type="file" name="cover_file" accept="image/*" style="font-size:12px;">
                        <div style="font-size:11px; color:var(--text-secondary); margin-top:2px;">Recomendado: imagem larga. Até 4 MB.</div>
                    </div>

                    <div>
                        <label for="nickname" style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Nickname (vai aparecer como @nickname)</label>
                        <input id="nickname" name="nickname" type="text" value="<?= htmlspecialchars((string)($profileUser['nickname'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="ex: joao_silva" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                        <div style="font-size:11px; color:var(--text-secondary); margin-top:2px;">Apenas letras minúsculas, números, _ e -. Sem espaço.</div>
                    </div>

                    <div>
                        <label for="about_me" style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Sobre mim</label>
                        <textarea id="about_me" name="about_me" rows="3" style="width:100%; padding:8px 10px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px; resize:vertical;"><?= htmlspecialchars((string)($profile['about_me'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <div style="display:flex; flex-wrap:wrap; gap:8px;">
                        <div style="flex:1 1 160px; min-width:0;">
                            <label for="language" style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Idioma principal</label>
                            <select id="language" name="language" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                                <?php $lang = (string)($profile['language'] ?? ''); ?>
                                <option value="">Selecione</option>
                                <option value="pt-BR" <?= $lang === 'pt-BR' ? 'selected' : '' ?>>Português (Brasil)</option>
                                <option value="en" <?= $lang === 'en' ? 'selected' : '' ?>>Inglês</option>
                                <option value="es" <?= $lang === 'es' ? 'selected' : '' ?>>Espanhol</option>
                            </select>
                        </div>
                        <div style="flex:1 1 180px; min-width:0;">
                            <label for="profile_category" style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Categoria</label>
                            <input id="profile_category" name="profile_category" type="text" value="<?= htmlspecialchars((string)($profile['profile_category'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex: Designer, Empreendedor, Estudante" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                        </div>
                    </div>

                    <div style="display:flex; flex-wrap:wrap; gap:12px;">
                        <div style="flex:1 1 180px; min-width:0;">
                            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Privacidade do perfil</div>
                            <?php $privacy = (string)($profile['profile_privacy'] ?? 'public'); ?>
                            <label style="font-size:12px; display:flex; align-items:center; gap:4px; color:var(--text-secondary); margin-bottom:2px;">
                                <input type="radio" name="profile_privacy" value="public" <?= $privacy !== 'private' ? 'checked' : '' ?> style="accent-color:#e53935;">
                                <span>Público</span>
                            </label>
                            <label style="font-size:12px; display:flex; align-items:center; gap:4px; color:var(--text-secondary);">
                                <input type="radio" name="profile_privacy" value="private" <?= $privacy === 'private' ? 'checked' : '' ?> style="accent-color:#e53935;">
                                <span>Privado (só você vê)</span>
                            </label>
                        </div>
                        <div style="flex:1 1 200px; min-width:0;">
                            <label for="visibility_scope" style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Visível para</label>
                            <?php $vis = (string)($profile['visibility_scope'] ?? 'everyone'); ?>
                            <select id="visibility_scope" name="visibility_scope" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                                <option value="everyone" <?= $vis === 'everyone' ? 'selected' : '' ?>>Todos na comunidade</option>
                                <option value="community" <?= $vis === 'community' ? 'selected' : '' ?>>Pessoas das mesmas comunidades</option>
                                <option value="friends" <?= $vis === 'friends' ? 'selected' : '' ?>>Apenas amigos</option>
                            </select>
                        </div>
                    </div>

                    <div style="display:flex; flex-wrap:wrap; gap:8px;">
                        <div style="flex:1 1 160px; min-width:0;">
                            <label for="relationship_status" style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Relacionamento</label>
                            <input id="relationship_status" name="relationship_status" type="text" value="<?= htmlspecialchars((string)($profile['relationship_status'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                        </div>
                        <div style="flex:0 0 120px;">
                            <label for="age" style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Idade</label>
                            <input id="age" name="age" type="number" min="0" max="120" value="<?= isset($profile['age']) ? (int)$profile['age'] : '' ?>" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                        </div>
                        <div style="flex:1 1 160px; min-width:0;">
                            <label for="birthday" style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Aniversário</label>
                            <input id="birthday" name="birthday" type="date" value="<?= htmlspecialchars((string)($profile['birthday'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                        </div>
                    </div>

                    <div style="display:flex; flex-wrap:wrap; gap:8px;">
                        <div style="flex:1 1 160px; min-width:0;">
                            <label for="children" style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Filhos</label>
                            <input id="children" name="children" type="text" value="<?= htmlspecialchars((string)($profile['children'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                        </div>
                        <div style="flex:1 1 160px; min-width:0;">
                            <label for="ethnicity" style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Etnia</label>
                            <input id="ethnicity" name="ethnicity" type="text" value="<?= htmlspecialchars((string)($profile['ethnicity'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                        </div>
                        <div style="flex:1 1 160px; min-width:0;">
                            <label for="mood" style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Humor</label>
                            <input id="mood" name="mood" type="text" value="<?= htmlspecialchars((string)($profile['mood'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                        </div>
                    </div>

                    <div style="display:flex; flex-wrap:wrap; gap:8px;">
                        <div style="flex:1 1 160px; min-width:0;">
                            <label for="sexual_orientation" style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Orientação sexual</label>
                            <input id="sexual_orientation" name="sexual_orientation" type="text" value="<?= htmlspecialchars((string)($profile['sexual_orientation'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                        </div>
                        <div style="flex:1 1 160px; min-width:0;">
                            <label for="style" style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Estilo</label>
                            <input id="style" name="style" type="text" value="<?= htmlspecialchars((string)($profile['style'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                        </div>
                    </div>

                    <div style="display:flex; flex-wrap:wrap; gap:8px;">
                        <div style="flex:1 1 120px; min-width:0;">
                            <label for="smokes" style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Fuma</label>
                            <input id="smokes" name="smokes" type="text" value="<?= htmlspecialchars((string)($profile['smokes'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex: não, às vezes" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                        </div>
                        <div style="flex:1 1 120px; min-width:0;">
                            <label for="drinks" style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Bebe</label>
                            <input id="drinks" name="drinks" type="text" value="<?= htmlspecialchars((string)($profile['drinks'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex: socialmente" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                        </div>
                        <div style="flex:1 1 160px; min-width:0;">
                            <label for="pets" style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Animais de estimação</label>
                            <input id="pets" name="pets" type="text" value="<?= htmlspecialchars((string)($profile['pets'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                        </div>
                    </div>

                    <div style="display:flex; flex-wrap:wrap; gap:8px;">
                        <div style="flex:1 1 160px; min-width:0;">
                            <label for="hometown" style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Cidade natal</label>
                            <input id="hometown" name="hometown" type="text" value="<?= htmlspecialchars((string)($profile['hometown'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                        </div>
                        <div style="flex:1 1 160px; min-width:0;">
                            <label for="location" style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Onde mora</label>
                            <input id="location" name="location" type="text" value="<?= htmlspecialchars((string)($profile['location'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                        </div>
                    </div>

                    <div style="display:flex; flex-wrap:wrap; gap:8px;">
                        <div style="flex:1 1 160px; min-width:0;">
                            <label for="interests" style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Interesses</label>
                            <input id="interests" name="interests" type="text" value="<?= htmlspecialchars((string)($profile['interests'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                        </div>
                        <div style="flex:1 1 160px; min-width:0;">
                            <label for="sports" style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Esportes</label>
                            <input id="sports" name="sports" type="text" value="<?= htmlspecialchars((string)($profile['sports'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                        </div>
                    </div>

                    <div style="display:flex; flex-wrap:wrap; gap:8px;">
                        <div style="flex:1 1 160px; min-width:0;">
                            <label for="passions" style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Paixões</label>
                            <input id="passions" name="passions" type="text" value="<?= htmlspecialchars((string)($profile['passions'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                        </div>
                        <div style="flex:1 1 160px; min-width:0;">
                            <label for="activities" style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Atividades</label>
                            <input id="activities" name="activities" type="text" value="<?= htmlspecialchars((string)($profile['activities'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                        </div>
                    </div>

                    <div>
                        <label for="website" style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Site pessoal</label>
                        <input id="website" name="website" type="text" value="<?= htmlspecialchars((string)($profile['website'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="https://seusite.com" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                    </div>

                    <div style="display:flex; flex-wrap:wrap; gap:8px;">
                        <div style="flex:1 1 160px; min-width:0;">
                            <label for="instagram" style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Instagram</label>
                            <input id="instagram" name="instagram" type="text" value="<?= htmlspecialchars((string)($profile['instagram'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="@usuario ou link" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                        </div>
                        <div style="flex:1 1 160px; min-width:0;">
                            <label for="facebook" style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Facebook</label>
                            <input id="facebook" name="facebook" type="text" value="<?= htmlspecialchars((string)($profile['facebook'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="@usuario ou link" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                        </div>
                        <div style="flex:1 1 160px; min-width:0;">
                            <label for="youtube" style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">YouTube</label>
                            <input id="youtube" name="youtube" type="text" value="<?= htmlspecialchars((string)($profile['youtube'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="canal ou link" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                        </div>
                    </div>

                    <div style="display:flex; justify-content:flex-end;">
                        <button type="submit" class="profile-btn-primary" style="border-radius:999px; padding:6px 12px; font-size:12px; font-weight:600; cursor:pointer;">Salvar perfil</button>
                    </div>
                </form>
            </section>
        <?php endif; ?>

        <script>
        (function () {
            var btn = document.getElementById('openSocialProfileEditBtn');
            var btnCover = document.getElementById('openSocialProfileEditBtnCover');
            var closeBtn = document.getElementById('closeSocialProfileEditBtn');
            var section = document.getElementById('socialProfileEditSection');
            if (!section) return;

            function openEdit() {
                section.style.display = 'block';
                try { section.scrollIntoView({ behavior: 'smooth', block: 'start' }); } catch (e) { section.scrollIntoView(true); }
                var first = section.querySelector('input, textarea, select');
                if (first && first.focus) {
                    try { first.focus(); } catch (e) {}
                }
            }

            function closeEdit() {
                section.style.display = 'none';
            }

            if (btn) {
                btn.addEventListener('click', function () {
                    openEdit();
                });
            }

            if (btnCover) {
                btnCover.addEventListener('click', function () {
                    openEdit();
                });
            }

            if (closeBtn) {
                closeBtn.addEventListener('click', function () {
                    closeEdit();
                });
            }

            if (window.location && window.location.hash === '#editar-perfil') {
                openEdit();
            }
        })();
        </script>

        <section id="scraps" style="background:var(--surface-card); border-radius:16px; border:1px solid var(--border-subtle); padding:12px 14px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                <h2 style="font-size:16px; color:var(--text-primary);">Scraps</h2>
                <span style="font-size:12px; color:var(--text-secondary);">Recados públicos no mural</span>
            </div>

            <?php if (!$isOwnProfile): ?>
                <form action="/painel-externo/perfil/scrap" method="post" style="margin-bottom:10px; display:flex; flex-direction:column; gap:6px;">
                    <input type="hidden" name="to_user_id" value="<?= (int)$profileId ?>">
                    <textarea name="body" rows="3" placeholder="Escreva um scrap carinhoso, uma dúvida ou um oi nostálgico..." style="width:100%; padding:8px 10px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px; resize:vertical;"></textarea>
                    <button type="submit" class="profile-btn-primary" style="align-self:flex-end; border-radius:999px; padding:6px 12px; font-weight:600; font-size:12px; cursor:pointer;">Enviar scrap</button>
                </form>
            <?php endif; ?>

            <?php if (empty($scraps)): ?>
                <div style="font-size:13px; color:var(--text-secondary);">Nenhum scrap ainda. Seja o primeiro a deixar um recado aqui.</div>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:8px;">
                    <?php foreach ($scraps as $s): ?>
                        <?php
                            $scrapId = (int)($s['id'] ?? 0);
                            $scrapFromId = (int)($s['from_user_id'] ?? 0);
                            $scrapToId = (int)($s['to_user_id'] ?? 0);
                            $scrapFromAvatar = trim((string)($s['from_user_avatar_path'] ?? ''));
                            $scrapFromName = (string)($s['from_user_name'] ?? 'Usuário');
                            $scrapFromInitial = 'U';
                            $tmpName = trim($scrapFromName);
                            if ($tmpName !== '') {
                                $scrapFromInitial = mb_strtoupper(mb_substr($tmpName, 0, 1, 'UTF-8'), 'UTF-8');
                            }
                            $isHidden = !empty($s['is_hidden']);
                            $canEdit = $scrapFromId === $currentId;
                            $canModerate = $isOwnProfile && $scrapToId === $currentId;
                        ?>
                        <div style="background:var(--surface-subtle); border-radius:12px; border:1px solid var(--border-subtle); padding:8px 10px; <?= $isHidden ? 'opacity:0.72;' : '' ?>">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px; font-size:12px; color:var(--text-secondary);">
                                <div>
                                    <strong>
                                        <a href="/painel-externo/perfil?user_id=<?= (int)($s['from_user_id'] ?? 0) ?>" style="color:#ff6f60; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                                            <span style="width:18px; height:18px; border-radius:50%; overflow:hidden; background:var(--surface-card); border:1px solid var(--border-subtle); display:inline-flex; align-items:center; justify-content:center; flex:0 0 18px;">
                                                <?php if ($scrapFromAvatar !== ''): ?>
                                                    <img src="<?= htmlspecialchars($scrapFromAvatar, ENT_QUOTES, 'UTF-8') ?>" alt="" style="width:100%; height:100%; object-fit:cover; display:block;">
                                                <?php else: ?>
                                                    <span style="font-size:11px; color:var(--text-secondary); font-weight:800; line-height:1;"><?= htmlspecialchars($scrapFromInitial, ENT_QUOTES, 'UTF-8') ?></span>
                                                <?php endif; ?>
                                            </span>
                                            <span><?= htmlspecialchars($scrapFromName, ENT_QUOTES, 'UTF-8') ?></span>
                                        </a>
                                    </strong>
                                    <?php if ($isHidden): ?>
                                        <span style="margin-left:6px; font-size:11px; padding:2px 6px; border-radius:999px; border:1px solid var(--border-subtle); background:var(--surface-card); color:var(--text-secondary);">oculto</span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($s['created_at'])): ?>
                                    <span><?= htmlspecialchars(date('d/m/Y H:i', strtotime((string)$s['created_at'])), ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>

                            <?php if ($canEdit && isset($_GET['edit_scrap']) && (int)$_GET['edit_scrap'] === $scrapId): ?>
                                <form action="/painel-externo/perfil/scrap/editar" method="post" style="display:flex; flex-direction:column; gap:6px;">
                                    <input type="hidden" name="scrap_id" value="<?= (int)$scrapId ?>">
                                    <textarea name="body" rows="3" style="width:100%; padding:8px 10px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-card); color:var(--text-primary); font-size:13px; resize:vertical;" maxlength="4000"><?= htmlspecialchars((string)($s['body'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                                    <div style="display:flex; gap:8px; justify-content:flex-end; flex-wrap:wrap;">
                                        <a href="/painel-externo/perfil?user_id=<?= (int)$profileId ?>#scraps" style="text-decoration:none; display:inline-block; border-radius:999px; padding:6px 12px; border:1px solid var(--border-subtle); background:var(--surface-card); color:var(--text-primary); font-size:12px;">Cancelar</a>
                                        <button type="submit" class="profile-btn-success" style="border-radius:999px; padding:6px 12px; font-weight:650; font-size:12px; cursor:pointer;">Salvar</button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <div style="font-size:13px; color:var(--text-primary);">
                                    <?= nl2br(htmlspecialchars((string)($s['body'] ?? ''), ENT_QUOTES, 'UTF-8')) ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($canEdit || $canModerate): ?>
                                <div style="display:flex; gap:8px; justify-content:flex-end; flex-wrap:wrap; margin-top:6px;">
                                    <?php if ($canModerate): ?>
                                        <form action="/painel-externo/perfil/scrap/visibilidade" method="post">
                                            <input type="hidden" name="scrap_id" value="<?= (int)$scrapId ?>">
                                            <button type="submit" name="action" value="<?= $isHidden ? 'show' : 'hide' ?>" style="border:none; border-radius:999px; padding:5px 10px; background:var(--surface-card); border:1px solid var(--border-subtle); color:var(--text-primary); font-size:12px; cursor:pointer;">
                                                <?= $isHidden ? 'Mostrar' : 'Ocultar' ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($canEdit): ?>
                                        <a href="/painel-externo/perfil?user_id=<?= (int)$profileId ?>&edit_scrap=<?= (int)$scrapId ?>#scraps" style="text-decoration:none; display:inline-block; border-radius:999px; padding:5px 10px; border:1px solid var(--border-subtle); background:var(--surface-card); color:var(--text-primary); font-size:12px;">Editar</a>
                                        <form action="/painel-externo/perfil/scrap/excluir" method="post" onsubmit="return confirm('Excluir este scrap?');">
                                            <input type="hidden" name="scrap_id" value="<?= (int)$scrapId ?>">
                                            <button type="submit" style="border:none; border-radius:999px; padding:5px 10px; background:#311; color:#ffbaba; border:1px solid #a33; font-size:12px; cursor:pointer;">Excluir</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <aside id="socialProfileWidgets" style="flex: 0 0 260px; background:var(--surface-card); border-radius:18px; border:1px solid var(--border-subtle); padding:12px; display:flex; flex-direction:column; gap:10px; min-height:0; max-width:100%;">
        <section style="background:var(--surface-card); border-radius:12px; border:1px solid var(--border-subtle); padding:8px 10px;">
            <h3 style="font-size:14px; margin-bottom:6px; color:var(--text-primary);">Depoimentos</h3>
            <?php if (empty($publicTestimonials)): ?>
                <div style="font-size:12px; color:var(--text-secondary);">Nenhum depoimento público ainda.</div>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:6px;">
                    <?php foreach ($publicTestimonials as $t): ?>
                        <?php
                            $tAvatar = trim((string)($t['from_user_avatar_path'] ?? ''));
                            $tName = (string)($t['from_user_name'] ?? 'Usuário');
                            $tInitial = 'U';
                            $tNameTmp = trim($tName);
                            if ($tNameTmp !== '') {
                                $tInitial = mb_strtoupper(mb_substr($tNameTmp, 0, 1, 'UTF-8'), 'UTF-8');
                            }
                        ?>
                        <div style="border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-subtle); padding:6px 8px;">
                            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:3px;">
                                <strong style="display:inline-flex; align-items:center; gap:6px;">
                                    <span style="width:18px; height:18px; border-radius:50%; overflow:hidden; background:var(--surface-card); border:1px solid var(--border-subtle); display:inline-flex; align-items:center; justify-content:center; flex:0 0 18px;">
                                        <?php if ($tAvatar !== ''): ?>
                                            <img src="<?= htmlspecialchars($tAvatar, ENT_QUOTES, 'UTF-8') ?>" alt="" style="width:100%; height:100%; object-fit:cover; display:block;">
                                        <?php else: ?>
                                            <span style="font-size:11px; color:var(--text-secondary); font-weight:800; line-height:1;"><?= htmlspecialchars($tInitial, ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endif; ?>
                                    </span>
                                    <span><?= htmlspecialchars($tName, ENT_QUOTES, 'UTF-8') ?></span>
                                </strong>
                                <?php if (!empty($t['created_at'])): ?>
                                    <span style="margin-left:4px;">· <?= htmlspecialchars(date('d/m/Y', strtotime((string)$t['created_at'])), ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>
                            <div style="font-size:13px; color:var(--text-primary);">
                                <?= nl2br(htmlspecialchars((string)($t['body'] ?? ''), ENT_QUOTES, 'UTF-8')) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <?php if (!$isOwnProfile): ?>
            <section style="background:var(--surface-card); border-radius:12px; border:1px solid var(--border-subtle); padding:8px 10px;">
                <h3 style="font-size:14px; margin-bottom:6px; color:var(--text-primary);">Escrever depoimento</h3>
                <form action="/painel-externo/perfil/depoimento" method="post" style="display:flex; flex-direction:column; gap:6px;">
                    <input type="hidden" name="to_user_id" value="<?= (int)$profileId ?>">
                    <textarea name="body" rows="3" placeholder="Conte algo legal sobre essa pessoa, do jeitinho que só você sabe." style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:12px; resize:vertical;"></textarea>
                    <label style="font-size:11px; color:var(--text-secondary); display:flex; align-items:center; gap:4px;">
                        <input type="checkbox" name="is_public" value="1" checked style="accent-color:#e53935;">
                        Tornar depoimento público se a pessoa aceitar
                    </label>
                    <button type="submit" style="align-self:flex-end; border:none; border-radius:999px; padding:5px 10px; background:var(--surface-subtle); border:1px solid var(--border-subtle); color:var(--text-primary); font-size:12px; cursor:pointer;">Enviar depoimento</button>
                </form>
            </section>
        <?php endif; ?>

        <?php if ($isOwnProfile && !empty($pendingTestimonials)): ?>
            <section style="background:var(--surface-card); border-radius:12px; border:1px solid var(--border-subtle); padding:8px 10px;">
                <h3 style="font-size:14px; margin-bottom:6px; color:var(--text-primary);">Depoimentos pendentes</h3>
                <div style="display:flex; flex-direction:column; gap:6px;">
                    <?php foreach ($pendingTestimonials as $t): ?>
                        <?php
                            $ptAvatar = trim((string)($t['from_user_avatar_path'] ?? ''));
                            $ptName = (string)($t['from_user_name'] ?? 'Usuário');
                            $ptInitial = 'U';
                            $ptNameTmp = trim($ptName);
                            if ($ptNameTmp !== '') {
                                $ptInitial = mb_strtoupper(mb_substr($ptNameTmp, 0, 1, 'UTF-8'), 'UTF-8');
                            }
                        ?>
                        <div style="border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-subtle); padding:6px 8px; font-size:12px; color:var(--text-secondary);">
                            <div style="margin-bottom:3px;">
                                <strong style="display:inline-flex; align-items:center; gap:6px;">
                                    <span style="width:18px; height:18px; border-radius:50%; overflow:hidden; background:var(--surface-card); border:1px solid var(--border-subtle); display:inline-flex; align-items:center; justify-content:center; flex:0 0 18px;">
                                        <?php if ($ptAvatar !== ''): ?>
                                            <img src="<?= htmlspecialchars($ptAvatar, ENT_QUOTES, 'UTF-8') ?>" alt="" style="width:100%; height:100%; object-fit:cover; display:block;">
                                        <?php else: ?>
                                            <span style="font-size:11px; color:var(--text-secondary); font-weight:800; line-height:1;"><?= htmlspecialchars($ptInitial, ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endif; ?>
                                    </span>
                                    <span><?= htmlspecialchars($ptName, ENT_QUOTES, 'UTF-8') ?></span>
                                </strong>
                            </div>
                            <div style="font-size:12px; color:var(--text-primary); margin-bottom:4px;">
                                <?= nl2br(htmlspecialchars((string)($t['body'] ?? ''), ENT_QUOTES, 'UTF-8')) ?>
                            </div>
                            <form action="/painel-externo/perfil/depoimento/decidir" method="post" style="display:flex; gap:6px;">
                                <input type="hidden" name="testimonial_id" value="<?= (int)($t['id'] ?? 0) ?>">
                                <button type="submit" name="decision" value="accepted" class="profile-btn-success" style="flex:1; border-radius:999px; padding:4px 8px; font-size:11px; cursor:pointer;">Aceitar</button>
                                <button type="submit" name="decision" value="rejected" style="flex:1; border:none; border-radius:999px; padding:4px 8px; background:#311; color:#ffbaba; border:1px solid #a33; font-size:11px; cursor:pointer;">Recusar</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <section style="background:var(--surface-card); border-radius:12px; border:1px solid var(--border-subtle); padding:8px 10px;">
            <h3 style="font-size:14px; margin-bottom:6px; color:var(--text-primary);">Comunidades</h3>
            <?php if (empty($communities)): ?>
                <div style="font-size:12px; color:var(--text-secondary);">Nenhuma comunidade listada ainda.</div>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:4px; font-size:12px;">
                    <?php foreach ($communities as $c): ?>
                        <?php $communityImage = trim((string)($c['cover_image_path'] ?? $c['image_path'] ?? '')); ?>
                        <a href="/comunidades/ver?slug=<?= urlencode((string)($c['slug'] ?? '')) ?>" style="display:flex; align-items:center; gap:6px; padding:4px 6px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--surface-subtle); text-decoration:none;">
                            <div style="width:18px; height:18px; border-radius:50%; overflow:hidden; background:#e53935; flex:0 0 18px;">
                                <?php if ($communityImage !== ''): ?>
                                    <img src="<?= htmlspecialchars($communityImage, ENT_QUOTES, 'UTF-8') ?>" alt="Imagem da comunidade" style="width:100%; height:100%; object-fit:cover; display:block;">
                                <?php endif; ?>
                            </div>
                            <span style="color:var(--text-primary);"><?= htmlspecialchars((string)($c['name'] ?? 'Comunidade'), ENT_QUOTES, 'UTF-8') ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </aside>
</div>
