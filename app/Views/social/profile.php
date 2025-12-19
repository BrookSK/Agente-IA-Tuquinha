<?php

$isOwnProfile = (int)($user['id'] ?? 0) === (int)($profileUser['id'] ?? 0);
$displayName = trim((string)($profileUser['preferred_name'] ?? $profileUser['name'] ?? ''));
if ($displayName === '') {
    $displayName = 'Perfil';
}

$baseName = (string)($profileUser['preferred_name'] ?? $profileUser['name'] ?? 'U');
$initial = mb_strtoupper(mb_substr($baseName, 0, 1, 'UTF-8'), 'UTF-8');
$avatarPath = isset($profile['avatar_path']) ? trim((string)$profile['avatar_path']) : '';

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

$currentId = (int)($user['id'] ?? 0);
$profileId = (int)($profileUser['id'] ?? 0);

?>
<style>
    #socialProfileMain {
        min-width: 0;
    }

    @media (max-width: 900px) {
        #socialProfileLayout {
            flex-direction: column !important;
            gap: 12px !important;
            padding: 0 4px;
            flex-wrap: nowrap !important;
        }
        #socialProfileAside {
            flex: 0 0 auto !important;
            width: 100% !important;
            order: 1;
        }
        #socialProfileMain {
            width: 100% !important;
            order: 2;
        }
        #socialProfileWidgets {
            flex: 0 0 auto !important;
            width: 100% !important;
            order: 3;
        }
    }
</style>
<div id="socialProfileLayout" style="max-width: 980px; margin: 0 auto; display: flex; gap: 18px; align-items: flex-start; flex-wrap: wrap;">
    <aside id="socialProfileAside" style="flex: 0 0 260px; background:var(--surface-card); border-radius:18px; border:1px solid var(--border-subtle); padding:14px; max-width:100%;">
        <div style="display:flex; flex-direction:column; align-items:center; gap:8px; margin-bottom:10px;">
            <div style="width:96px; height:96px; border-radius:50%; overflow:hidden; background:radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%); display:flex; align-items:center; justify-content:center; font-size:40px; font-weight:700; color:#050509;">
                <?php if ($avatarPath !== ''): ?>
                    <img src="<?= htmlspecialchars($avatarPath, ENT_QUOTES, 'UTF-8') ?>" alt="Avatar" style="width:100%; height:100%; object-fit:cover; display:block;">
                <?php else: ?>
                    <?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
            </div>
            <div style="text-align:center;">
                <div style="font-size:18px; font-weight:650;">
                    <?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>
                </div>
                <?php if ($nickname !== ''): ?>
                    <div style="font-size:12px; color:var(--text-secondary);">
                        @<?= htmlspecialchars($nickname, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($courseBadges)): ?>
                    <div style="margin-top:8px; display:flex; gap:8px; align-items:center; justify-content:center; flex-wrap:wrap;">
                        <?php foreach ($courseBadges as $b): ?>
                            <?php
                                $badgeUrl = trim((string)($b['badge_image_path'] ?? ''));
                                $courseTitle = trim((string)($b['course_title'] ?? ''));
                                $courseSlug = trim((string)($b['course_slug'] ?? ''));
                                $badgeLabel = $courseTitle !== '' ? $courseTitle : 'Curso concluído';
                                $courseLink = $courseSlug !== '' ? '/cursos/ver?slug=' . urlencode($courseSlug) : '/cursos';
                            ?>
                            <a href="<?= htmlspecialchars($courseLink, ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($badgeLabel, ENT_QUOTES, 'UTF-8') ?>" style="
                                width:34px; height:34px; border-radius:10px; overflow:hidden;
                                border:1px solid var(--border-subtle);
                                background:var(--surface-subtle);
                                display:inline-flex; align-items:center; justify-content:center;
                                text-decoration:none;">
                                <?php if ($badgeUrl !== ''): ?>
                                    <img src="<?= htmlspecialchars($badgeUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($badgeLabel, ENT_QUOTES, 'UTF-8') ?>" style="width:100%; height:100%; object-fit:cover; display:block;">
                                <?php else: ?>
                                    <span style="font-size:16px;">🏅</span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <div style="margin-top:4px; font-size:11px; color:var(--text-secondary);">Insígnias de cursos concluídos</div>
                <?php endif; ?>
                <?php if (!empty($profileUser['name']) && $displayName !== $profileUser['name']): ?>
                    <div style="font-size:12px; color:var(--text-secondary);">
                        <?= htmlspecialchars($profileUser['name'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div style="display:flex; justify-content:space-between; gap:6px; margin-bottom:10px;">
            <div style="flex:1; background:var(--surface-subtle); border-radius:12px; padding:6px 8px; border:1px solid var(--border-subtle); text-align:center;">
                <div style="font-size:11px; color:var(--text-secondary); text-transform:uppercase; letter-spacing:0.08em;">Amigos</div>
                <div style="font-size:16px; font-weight:650;">
                    <?= (int)$friendsCount ?>
                </div>
            </div>
            <div style="flex:1; background:var(--surface-subtle); border-radius:12px; padding:6px 8px; border:1px solid var(--border-subtle); text-align:center;">
                <div style="font-size:11px; color:var(--text-secondary); text-transform:uppercase; letter-spacing:0.08em;">Scraps</div>
                <div style="font-size:16px; font-weight:650;">
                    <?= (int)$scrapsCount ?>
                </div>
            </div>
            <div style="flex:1; background:var(--surface-subtle); border-radius:12px; padding:6px 8px; border:1px solid var(--border-subtle); text-align:center;">
                <div style="font-size:11px; color:var(--text-secondary); text-transform:uppercase; letter-spacing:0.08em;">Comun.</div>
                <div style="font-size:16px; font-weight:650;">
                    <?= (int)$communitiesCount ?>
                </div>
            </div>
        </div>

        <?php if (!$isOwnProfile): ?>
            <div style="display:flex; flex-direction:column; gap:6px; margin-bottom:8px;">
                <?php if ($friendStatus === 'accepted'): ?>
                    <div style="font-size:12px; color:#8bc34a; background:var(--surface-subtle); border-radius:10px; border:1px solid var(--border-subtle); padding:6px 8px; text-align:center;">
                        Vocês são amigos na rede social do Tuquinha.
                    </div>
                    <form action="/amigos/favorito" method="post" style="margin:0;">
                        <input type="hidden" name="user_id" value="<?= (int)$profileId ?>">
                        <input type="hidden" name="is_favorite" value="<?= $isFavoriteFriend ? '0' : '1' ?>">
                        <button type="submit" style="width:100%; border:none; border-radius:999px; padding:7px 10px; font-size:13px; font-weight:650; cursor:pointer; background:<?= $isFavoriteFriend ? '#0b0b10' : 'var(--surface-subtle)' ?>; border:1px solid var(--border-subtle); color:<?= $isFavoriteFriend ? '#ffcc80' : 'var(--text-primary)' ?>;">
                            <?= $isFavoriteFriend ? '★ Remover dos favoritos' : '☆ Favoritar amigo' ?>
                        </button>
                    </form>
                    <a href="/social/chat?user_id=<?= (int)$profileId ?>" style="display:block; text-align:center; font-size:12px; color:#050509; text-decoration:none; margin-top:4px;">
                        <span style="display:inline-block; padding:6px 12px; border-radius:999px; background:linear-gradient(135deg,#e53935,#ff6f60); font-weight:600;">Abrir chat privado</span>
                    </a>
                <?php elseif ($friendStatus === 'pending' && $requestedById === $currentId): ?>
                    <div style="font-size:12px; color:#ffb74d; background:var(--surface-subtle); border-radius:10px; border:1px solid var(--border-subtle); padding:6px 8px; text-align:center;">
                        Pedido de amizade enviado. Aguardando resposta.
                    </div>
                    <form action="/amigos/cancelar" method="post" style="margin:0;">
                        <input type="hidden" name="user_id" value="<?= (int)$profileId ?>">
                        <button type="submit" style="width:100%; border:none; border-radius:999px; padding:7px 10px; font-size:13px; font-weight:650; cursor:pointer; background:#311; color:#ffbaba; border:1px solid #a33;">
                            Cancelar solicitação
                        </button>
                    </form>
                <?php elseif ($friendStatus === 'pending' && $requestedById !== $currentId): ?>
                    <form action="/amigos/decidir" method="post" style="display:flex; flex-direction:column; gap:6px;">
                        <div style="font-size:12px; color:var(--text-secondary); text-align:center;">Esta pessoa quer ser sua amiga.</div>
                        <input type="hidden" name="user_id" value="<?= (int)$profileId ?>">
                        <div style="display:flex; gap:6px;">
                            <button type="submit" name="decision" value="accepted" style="flex:1; border:none; border-radius:999px; padding:6px 10px; font-size:12px; font-weight:600; cursor:pointer; background:linear-gradient(135deg,#4caf50,#8bc34a); color:#050509;">Aceitar</button>
                            <button type="submit" name="decision" value="rejected" style="flex:1; border:none; border-radius:999px; padding:6px 10px; font-size:12px; cursor:pointer; background:var(--surface-subtle); border:1px solid var(--border-subtle); color:var(--text-secondary);">Recusar</button>
                        </div>
                    </form>
                <?php else: ?>
                    <form action="/amigos/solicitar" method="post">
                        <input type="hidden" name="user_id" value="<?= (int)$profileId ?>">
                        <button type="submit" style="width:100%; border:none; border-radius:999px; padding:7px 10px; font-size:13px; font-weight:600; cursor:pointer; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; margin-bottom:4px;">
                            Adicionar como amigo
                        </button>
                    </form>
                <?php endif; ?>

                <a href="#scraps" style="display:block; text-align:center; font-size:12px; color:#ff6f60; text-decoration:none;">Ir para os scraps</a>
            </div>
        <?php else: ?>
            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:8px; text-align:center;">
                Este é o seu perfil social dentro da comunidade do Tuquinha.
            </div>

            <div style="display:flex; flex-direction:column; gap:8px; margin-bottom:8px;">
                <button
                    type="button"
                    id="copyProfileLinkBtn"
                    data-profile-id="<?= (int)$profileId ?>"
                    style="width:100%; border:none; border-radius:999px; padding:7px 10px; font-size:13px; font-weight:600; cursor:pointer; background:var(--surface-subtle); border:1px solid var(--border-subtle); color:var(--text-primary);"
                >
                    Copiar link do meu perfil
                </button>
                <div id="copyProfileLinkStatus" style="display:none; font-size:12px; color:var(--text-secondary); text-align:center;"></div>
            </div>
        <?php endif; ?>

        <div style="display:flex; flex-direction:column; gap:6px; margin-top:6px;">
            <a href="/perfil/portfolio?user_id=<?= (int)$profileId ?>" style="display:block; text-align:center; font-size:12px; color:#050509; text-decoration:none;">
                <span style="display:inline-block; width:100%; padding:7px 12px; border-radius:999px; background:linear-gradient(135deg,#e53935,#ff6f60); font-weight:650;">Ver portfólio</span>
            </a>
            <?php if ($isOwnProfile): ?>
                <a href="/perfil/portfolio/gerenciar" style="display:block; text-align:center; font-size:12px; color:var(--text-primary); text-decoration:none;">
                    <span style="display:inline-block; width:100%; padding:7px 12px; border-radius:999px; background:var(--surface-subtle); border:1px solid var(--border-subtle);">Gerenciar meu portfólio</span>
                </a>
            <?php endif; ?>
        </div>

        <?php if (!empty($profile['visits_count'])): ?>
            <div style="font-size:11px; color:var(--text-secondary); margin-top:4px; text-align:center;">
                <?= (int)$profile['visits_count'] ?> visita(s) neste perfil.
            </div>
        <?php endif; ?>
    </aside>

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
            var url = base + '/perfil?user_id=' + encodeURIComponent(profileId);

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

    <main id="socialProfileMain" style="flex: 1 1 480px; min-width: 300px; display:flex; flex-direction:column; gap:14px;">
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
            <h2 style="font-size:16px; margin-bottom:6px; color:var(--text-primary);">Sobre</h2>
            <div style="font-size:13px; color:var(--text-secondary);">
                <?php if (!empty($profile['about_me'])): ?>
                    <p style="margin-bottom:6px;"><?= nl2br(htmlspecialchars((string)$profile['about_me'], ENT_QUOTES, 'UTF-8')) ?></p>
                <?php else: ?>
                    <p style="margin-bottom:6px;">Nenhuma descrição adicionada ainda.</p>
                <?php endif; ?>
                <?php if (!empty($profileUser['global_memory'])): ?>
                    <p style="margin-bottom:4px; font-size:12px; color:var(--text-secondary);">Memórias globais que o Tuquinha usa sobre esta pessoa:</p>
                    <p style="font-size:12px;"><?= nl2br(htmlspecialchars((string)$profileUser['global_memory'], ENT_QUOTES, 'UTF-8')) ?></p>
                <?php endif; ?>
            </div>
        </section>

        <section style="background:var(--surface-card); border-radius:16px; border:1px solid var(--border-subtle); padding:12px 14px;">
            <h2 style="font-size:16px; margin-bottom:6px; color:var(--text-primary);">Detalhes sociais</h2>
            <div style="font-size:13px; color:var(--text-secondary);">
                <div style="display:grid; grid-template-columns: minmax(0, 180px) minmax(0, 1fr); row-gap:4px; column-gap:8px;">
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
                    foreach ($details as $label => $value):
                        if ($value === null || $value === '') {
                            continue;
                        }
                        ?>
                        <div style="font-size:12px; color:var(--text-secondary); text-transform:lowercase;">
                            <?= htmlspecialchars(mb_strtolower($label, 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>:
                        </div>
                        <div>
                            <?= nl2br(htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8')) ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!array_filter($details, static fn($v) => $v !== null && $v !== '')): ?>
                        <div style="grid-column:1 / -1; font-size:13px;">Nenhum detalhe social preenchido ainda.</div>
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
                <?php if (empty($profile['interests']) && empty($profile['favorite_music']) && empty($profile['favorite_movies']) && empty($profile['favorite_books']) && empty($profile['website']) && empty($profile['instagram']) && empty($profile['facebook']) && empty($profile['youtube'])): ?>
                    <span>Nenhum interesse cadastrado ainda.</span>
                <?php endif; ?>
            </div>
        </section>

        <?php if ($isOwnProfile): ?>
            <section style="background:var(--surface-card); border-radius:16px; border:1px solid var(--border-subtle); padding:12px 14px;">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:6px; flex-wrap:wrap;">
                    <h2 style="font-size:16px; margin:0; color:var(--text-primary);">Editar meu perfil social</h2>
                    <button type="button" id="toggleSocialProfileEdit" style="border:none; border-radius:999px; padding:6px 12px; background:var(--surface-subtle); border:1px solid var(--border-subtle); color:var(--text-primary); font-size:12px; font-weight:650; cursor:pointer;">Editar</button>
                </div>
                <div id="socialProfileEditPanel" style="display:none;">
                <form action="/perfil/salvar" method="post" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:10px; font-size:13px; color:var(--text-primary);">
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
                        <button type="submit" style="border:none; border-radius:999px; padding:6px 12px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-size:12px; font-weight:600; cursor:pointer;">Salvar perfil</button>
                    </div>
                </form>
                </div>

                <script>
                (function () {
                    var btn = document.getElementById('toggleSocialProfileEdit');
                    var panel = document.getElementById('socialProfileEditPanel');
                    if (!btn || !panel) return;

                    function setOpen(open) {
                        panel.style.display = open ? 'block' : 'none';
                        btn.textContent = open ? 'Fechar' : 'Editar';
                    }

                    btn.addEventListener('click', function () {
                        setOpen(panel.style.display === 'none');
                    });

                    if (window.location && window.location.hash === '#editar-perfil') {
                        setOpen(true);
                    }
                })();
                </script>
            </section>
        <?php endif; ?>

        <section id="scraps" style="background:var(--surface-card); border-radius:16px; border:1px solid var(--border-subtle); padding:12px 14px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                <h2 style="font-size:16px; color:var(--text-primary);">Scraps</h2>
                <span style="font-size:12px; color:var(--text-secondary);">Recados públicos no mural</span>
            </div>

            <?php if (!$isOwnProfile): ?>
                <form action="/perfil/scrap" method="post" style="margin-bottom:10px; display:flex; flex-direction:column; gap:6px;">
                    <input type="hidden" name="to_user_id" value="<?= (int)$profileId ?>">
                    <textarea name="body" rows="3" placeholder="Escreva um scrap carinhoso, uma dúvida ou um oi nostálgico..." style="width:100%; padding:8px 10px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px; resize:vertical;"></textarea>
                    <button type="submit" style="align-self:flex-end; border:none; border-radius:999px; padding:6px 12px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:600; font-size:12px; cursor:pointer;">Enviar scrap</button>
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
                            $isHidden = !empty($s['is_hidden']);
                            $canEdit = $scrapFromId === $currentId;
                            $canModerate = $isOwnProfile && $scrapToId === $currentId;
                        ?>
                        <div style="background:var(--surface-subtle); border-radius:12px; border:1px solid var(--border-subtle); padding:8px 10px; <?= $isHidden ? 'opacity:0.72;' : '' ?>">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px; font-size:12px; color:var(--text-secondary);">
                                <div>
                                    <strong>
                                        <a href="/perfil?user_id=<?= (int)($s['from_user_id'] ?? 0) ?>" style="color:#ff6f60; text-decoration:none;">
                                            <?= htmlspecialchars((string)($s['from_user_name'] ?? 'Usuário'), ENT_QUOTES, 'UTF-8') ?>
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
                                <form action="/perfil/scrap/editar" method="post" style="display:flex; flex-direction:column; gap:6px;">
                                    <input type="hidden" name="scrap_id" value="<?= (int)$scrapId ?>">
                                    <textarea name="body" rows="3" style="width:100%; padding:8px 10px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-card); color:var(--text-primary); font-size:13px; resize:vertical;" maxlength="4000"><?= htmlspecialchars((string)($s['body'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                                    <div style="display:flex; gap:8px; justify-content:flex-end; flex-wrap:wrap;">
                                        <a href="/perfil?user_id=<?= (int)$profileId ?>#scraps" style="text-decoration:none; display:inline-block; border-radius:999px; padding:6px 12px; border:1px solid var(--border-subtle); background:var(--surface-card); color:var(--text-primary); font-size:12px;">Cancelar</a>
                                        <button type="submit" style="border:none; border-radius:999px; padding:6px 12px; background:linear-gradient(135deg,#4caf50,#8bc34a); color:#050509; font-weight:650; font-size:12px; cursor:pointer;">Salvar</button>
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
                                        <form action="/perfil/scrap/visibilidade" method="post">
                                            <input type="hidden" name="scrap_id" value="<?= (int)$scrapId ?>">
                                            <button type="submit" name="action" value="<?= $isHidden ? 'show' : 'hide' ?>" style="border:none; border-radius:999px; padding:5px 10px; background:var(--surface-card); border:1px solid var(--border-subtle); color:var(--text-primary); font-size:12px; cursor:pointer;">
                                                <?= $isHidden ? 'Mostrar' : 'Ocultar' ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($canEdit): ?>
                                        <a href="/perfil?user_id=<?= (int)$profileId ?>&edit_scrap=<?= (int)$scrapId ?>#scraps" style="text-decoration:none; display:inline-block; border-radius:999px; padding:5px 10px; border:1px solid var(--border-subtle); background:var(--surface-card); color:var(--text-primary); font-size:12px;">Editar</a>
                                        <form action="/perfil/scrap/excluir" method="post" onsubmit="return confirm('Excluir este scrap?');">
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
                        <div style="border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-subtle); padding:6px 8px;">
                            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:3px;">
                                <strong><?= htmlspecialchars((string)($t['from_user_name'] ?? 'Usuário'), ENT_QUOTES, 'UTF-8') ?></strong>
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
                <form action="/perfil/depoimento" method="post" style="display:flex; flex-direction:column; gap:6px;">
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
                        <div style="border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-subtle); padding:6px 8px; font-size:12px; color:var(--text-secondary);">
                            <div style="margin-bottom:3px;">
                                <strong><?= htmlspecialchars((string)($t['from_user_name'] ?? 'Usuário'), ENT_QUOTES, 'UTF-8') ?></strong>
                            </div>
                            <div style="font-size:12px; color:var(--text-primary); margin-bottom:4px;">
                                <?= nl2br(htmlspecialchars((string)($t['body'] ?? ''), ENT_QUOTES, 'UTF-8')) ?>
                            </div>
                            <form action="/perfil/depoimento/decidir" method="post" style="display:flex; gap:6px;">
                                <input type="hidden" name="testimonial_id" value="<?= (int)($t['id'] ?? 0) ?>">
                                <button type="submit" name="decision" value="accepted" style="flex:1; border:none; border-radius:999px; padding:4px 8px; background:linear-gradient(135deg,#4caf50,#8bc34a); color:#050509; font-size:11px; cursor:pointer;">Aceitar</button>
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
