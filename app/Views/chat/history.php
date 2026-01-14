<?php
/** @var array $conversations */
/** @var string $term */
/** @var int $retentionDays */
/** @var bool $favoritesOnly */
/** @var array $userProjects */
?>
<style>
    .tuqPersonaBadge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 10px;
        border-radius: 999px;
        border: 1px solid var(--border-subtle);
        background: var(--surface-subtle);
        color: var(--text-primary);
        width: 180px;
        max-width: 180px;
        min-width: 180px;
    }
    .tuqPersonaBadgeInline {
        width: auto;
        max-width: 240px;
        min-width: 0;
    }
    .tuqChatTitleRow {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }
    .tuqChatTitleRowTitle {
        flex: 0 1 auto;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .tuqPersonaBadgeAvatar {
        width: 24px;
        height: 24px;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid var(--border-subtle);
        background: var(--surface-card);
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .tuqPersonaBadgeAvatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .tuqPersonaBadgeText {
        display: flex;
        flex-direction: column;
        line-height: 1.15;
        min-width: 0;
    }
    .tuqPersonaBadgeName {
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .tuqPersonaBadgeArea {
        font-size: 11px;
        color: var(--text-secondary);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .tuqChatListItem {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        min-width: 0;
    }
    .tuqChatListItemMain {
        min-width: 0;
        flex: 1;
    }
    .tuqChatListItemActions {
        flex: 0 0 auto;
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }
    .tuqDotsBtn {
        border: 1px solid var(--border-subtle);
        background: var(--surface-subtle);
        color: var(--text-primary);
        width: 34px;
        height: 34px;
        border-radius: 999px;
        cursor: pointer;
        font-size: 18px;
        line-height: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .tuqDotsMenuWrap {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: flex-end;
    }
    .tuqDotsMenu {
        position: absolute;
        right: 0;
        top: calc(100% + 8px);
        min-width: 220px;
        background: #0b0b10;
        border: 1px solid var(--border-subtle);
        border-radius: 12px;
        padding: 6px;
        box-shadow: 0 12px 34px rgba(0,0,0,0.55);
        display: none;
        z-index: 50;
    }
    .tuqDotsMenuItemBtn {
        width: 100%;
        text-align: left;
        border: none;
        background: transparent;
        color: var(--text-primary);
        padding: 10px 10px;
        border-radius: 10px;
        cursor: pointer;
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .tuqDotsMenuItemBtn:hover {
        background: rgba(255,255,255,0.06);
    }
    .tuqDotsMenuItemDanger {
        color: #ff6b6b;
    }
    .tuqDotsMenuDivider {
        height: 1px;
        background: rgba(255,255,255,0.08);
        margin: 6px 6px;
        border-radius: 999px;
    }
    .tuqDotsMenuSelect {
        width: 100%;
        padding: 8px 10px;
        border-radius: 10px;
        border: 1px solid var(--border-subtle);
        background: var(--surface-subtle);
        color: var(--text-primary);
        font-size: 12px;
    }
    .tuqChatCardLink {
        display: block;
        color: inherit;
        text-decoration: none;
        min-width: 0;
    }
    .tuqChatCardLink:hover {
        text-decoration: none;
    }
    @media (max-width: 640px) {
        .tuqPersonaBadge {
            padding: 5px 8px;
            width: 100%;
            max-width: 100%;
            min-width: 0;
        }
        .tuqChatTitleRow {
            flex-direction: column;
            align-items: flex-start;
            gap: 6px;
        }
        .tuqChatTitleRowTitle {
            width: 100%;
        }
        .tuqPersonaBadgeAvatar {
            width: 22px;
            height: 22px;
            border-radius: 7px;
        }
        .tuqChatListItem {
            flex-direction: column;
            align-items: stretch;
        }
        .tuqChatListItemActions {
            width: 100%;
            justify-content: flex-end;
            gap: 8px;
        }
    }
</style>
<div style="max-width: 880px; margin: 0 auto;">
    <h1 style="font-size: 24px; margin-bottom: 10px; font-weight: 650;">Histórico de conversas</h1>
    <p style="color:var(--text-secondary); font-size: 14px; margin-bottom: 4px;">
        Aqui você encontra os chats recentes com o Tuquinha nesta sessão. Use a busca para localizar pelo título.
    </p>
    <?php $days = (int)($retentionDays ?? 90); if ($days <= 0) { $days = 90; } ?>
    <p style="color:#777; font-size: 12px; margin-bottom: 14px;">
        Os históricos são mantidos por <strong><?= htmlspecialchars((string)$days) ?> dias</strong>. Conversas mais antigas que isso são apagadas automaticamente.
    </p>

    <form method="get" action="/historico" style="margin-bottom: 14px; display:flex; gap:8px; flex-wrap:wrap;">
        <input type="text" name="q" value="<?= htmlspecialchars($term) ?>" placeholder="Buscar pelo título do chat" style="
            flex:1; min-width:220px; padding:8px 10px; border-radius:999px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">

        <select name="fav" style="
            padding:8px 10px; border-radius:999px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
            <option value="0" <?= empty($favoritesOnly) ? 'selected' : '' ?>>Todos</option>
            <option value="1" <?= !empty($favoritesOnly) ? 'selected' : '' ?>>Favoritos</option>
        </select>

        <button type="submit" style="border:none; border-radius:999px; padding:8px 14px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:600; font-size:13px; cursor:pointer;">
            Buscar
        </button>
    </form>

    <?php if (empty($conversations)): ?>
        <div style="background:var(--surface-card); border-radius:12px; padding:12px 14px; border:1px solid var(--border-subtle); color:var(--text-secondary); font-size:13px;">
            Nenhuma conversa encontrada.
        </div>
    <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:8px;">
            <?php foreach ($conversations as $conv): ?>
                <?php
                    $title = trim((string)($conv['title'] ?? ''));
                    if ($title === '') {
                        $title = 'Chat sem título';
                    }
                    $created = $conv['created_at'] ?? null;
                    $personaName = !empty($planAllowsPersonalities) ? trim((string)($conv['persona_name'] ?? '')) : '';
                    $personaArea = !empty($planAllowsPersonalities) ? trim((string)($conv['persona_area'] ?? '')) : '';
                    $personaImg = !empty($planAllowsPersonalities) ? trim((string)($conv['persona_image_path'] ?? '')) : '';
                    $convId = (int)($conv['id'] ?? 0);
                    $isFav = !empty($conv['is_favorite']);
                    $currentProjectId = isset($conv['project_id']) ? (int)$conv['project_id'] : 0;
                    $qs = [];
                    if (!empty($term)) { $qs['q'] = $term; }
                    if (!empty($favoritesOnly)) { $qs['fav'] = '1'; }
                    $querySuffix = !empty($qs) ? ('?' . http_build_query($qs)) : '';
                ?>
                <div style="background:var(--surface-card); border-radius:12px; padding:10px 12px; border:1px solid var(--border-subtle);" class="tuqChatListItem">
                    <div class="tuqChatListItemMain">
                        <a class="tuqChatCardLink" href="/chat?c=<?= (int)$conv['id'] ?>">
                            <div class="tuqChatTitleRow">
                                <?php
                                    $showPersona = !empty($planAllowsPersonalities);
                                    $personaLabelName = $personaName !== '' ? $personaName : 'Tuquinha';
                                    $personaLabelArea = $personaArea !== '' ? $personaArea : 'Chat';
                                    $personaHasImg = $personaImg !== '';
                                ?>
                                <?php if ($showPersona): ?>
                                    <div class="tuqPersonaBadge tuqPersonaBadgeInline" title="<?= htmlspecialchars($personaLabelName . ($personaArea !== '' ? (' • ' . $personaArea) : ''), ENT_QUOTES, 'UTF-8') ?>">
                                        <span class="tuqPersonaBadgeAvatar" aria-hidden="true">
                                            <?php if ($personaHasImg): ?>
                                                <img src="<?= htmlspecialchars($personaImg, ENT_QUOTES, 'UTF-8') ?>" alt="">
                                            <?php else: ?>
                                                <span style="font-size:14px;">🐦</span>
                                            <?php endif; ?>
                                        </span>
                                        <span class="tuqPersonaBadgeText">
                                            <span class="tuqPersonaBadgeName"><?= htmlspecialchars($personaLabelName) ?></span>
                                            <span class="tuqPersonaBadgeArea"><?= htmlspecialchars($personaLabelArea) ?></span>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                <div class="tuqChatTitleRowTitle" style="font-size:14px; font-weight:500;">
                                    <?= htmlspecialchars($title) ?>
                                </div>
                            </div>
                            <?php if ($created): ?>
                                <div style="font-size:11px; color:var(--text-secondary); margin-bottom:4px;">
                                    Iniciado em <?= htmlspecialchars(date('d/m/Y H:i', strtotime($created))) ?>
                                </div>
                            <?php endif; ?>
                        </a>
                    </div>
                    <div class="tuqChatListItemActions">
                        <div class="tuqDotsMenuWrap">
                            <button type="button" class="tuqDotsBtn" aria-label="Abrir menu" data-dots="<?= (int)$convId ?>">⋯</button>
                            <div class="tuqDotsMenu" id="tuqDotsMenu<?= (int)$convId ?>">
                                <a class="tuqDotsMenuItemBtn" href="/chat?c=<?= (int)$convId ?>" style="text-decoration:none;">
                                    <span style="opacity:0.9;">➜</span>
                                    <span>Abrir chat</span>
                                </a>

                                <button type="button" class="tuqDotsMenuItemBtn" data-rename-conv-id="<?= (int)$convId ?>" data-rename-title="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>">
                                    <span style="opacity:0.9;">✏️</span>
                                    <span>Mudar o nome</span>
                                </button>

                                <form method="post" action="/historico/favoritar<?= htmlspecialchars($querySuffix) ?>" style="margin:0;">
                                    <input type="hidden" name="id" value="<?= (int)$convId ?>">
                                    <input type="hidden" name="is_favorite" value="<?= $isFav ? '0' : '1' ?>">
                                    <button type="submit" class="tuqDotsMenuItemBtn">
                                        <span style="opacity:0.9;"><?= $isFav ? '★' : '☆' ?></span>
                                        <span><?= $isFav ? 'Remover dos favoritos' : 'Favoritar' ?></span>
                                    </button>
                                </form>

                                <?php if (!empty($userProjects) && is_array($userProjects)): ?>
                                    <div class="tuqDotsMenuDivider"></div>
                                    <div style="padding:6px 8px; font-size:12px; color:var(--text-secondary);">Mudar projeto</div>
                                    <form method="post" action="/historico/projeto<?= htmlspecialchars($querySuffix) ?>" style="margin:0; padding:0 8px 8px 8px;">
                                        <input type="hidden" name="id" value="<?= (int)$convId ?>">
                                        <select name="project_id" onchange="this.form.submit()" class="tuqDotsMenuSelect">
                                            <option value="0" <?= $currentProjectId <= 0 ? 'selected' : '' ?>>Sem projeto</option>
                                            <?php foreach ($userProjects as $p): ?>
                                                <?php $pid = (int)($p['id'] ?? 0); if ($pid <= 0) { continue; } ?>
                                                <option value="<?= (int)$pid ?>" <?= $currentProjectId === $pid ? 'selected' : '' ?>><?= htmlspecialchars((string)($p['name'] ?? ('Projeto #' . $pid))) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                <?php endif; ?>

                                <div class="tuqDotsMenuDivider"></div>
                                <form method="post" action="/chat/excluir" style="margin:0;">
                                    <input type="hidden" name="conversation_id" value="<?= (int)$convId ?>">
                                    <input type="hidden" name="redirect" value="/historico">
                                    <button type="submit" class="tuqDotsMenuItemBtn tuqDotsMenuItemDanger" onclick="return confirm('Excluir este chat do histórico? Essa ação não pode ser desfeita.');">
                                        <span style="opacity:0.9;">🗑</span>
                                        <span>Apagar</span>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <form method="post" action="/historico/renomear<?= htmlspecialchars($querySuffix) ?>" id="tuqRenameForm<?= (int)$convId ?>" style="display:none;">
                            <input type="hidden" name="id" value="<?= (int)$convId ?>">
                            <input type="hidden" name="title" value="">
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    function tuqRenameConversation(id, currentTitle) {
        try {
            var title = prompt('Novo nome do chat:', (currentTitle || '').toString());
            if (title === null) {
                return false;
            }
            title = (title || '').trim();
            if (title === '') {
                title = 'Chat com o Tuquinha';
            }
            var form = document.getElementById('tuqRenameForm' + id);
            if (!form) {
                return false;
            }
            var input = form.querySelector('input[name="title"]');
            if (!input) {
                return false;
            }
            input.value = title;
            form.submit();
            return false;
        } catch (e) {
            return false;
        }
    }

    (function () {
        function closeAllMenus() {
            var menus = document.querySelectorAll('.tuqDotsMenu');
            menus.forEach(function (m) {
                m.style.display = 'none';
            });
        }

        function handleRenameClick(btn) {
            try {
                var id = Number(btn.getAttribute('data-rename-conv-id') || '0') || 0;
                var title = (btn.getAttribute('data-rename-title') || '').toString();
                closeAllMenus();
                return tuqRenameConversation(id, title);
            } catch (e) {
                return false;
            }
        }

        document.addEventListener('click', function (ev) {
            try {
                var t = ev.target;
                var renameBtn = t && t.closest ? t.closest('[data-rename-conv-id]') : null;
                if (renameBtn) {
                    ev.preventDefault();
                    ev.stopPropagation();
                    handleRenameClick(renameBtn);
                    return;
                }
                var btn = t && t.closest ? t.closest('[data-dots]') : null;

                if (btn) {
                    ev.preventDefault();
                    var id = btn.getAttribute('data-dots');
                    var menu = document.getElementById('tuqDotsMenu' + id);
                    if (!menu) {
                        return;
                    }

                    var isOpen = menu.style.display === 'block';
                    closeAllMenus();
                    menu.style.display = isOpen ? 'none' : 'block';
                    return;
                }

                var inside = t && t.closest ? t.closest('.tuqDotsMenu') : null;
                if (!inside) {
                    closeAllMenus();
                }
            } catch (e) {
                // noop
            }
        });

        window.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape') {
                closeAllMenus();
            }
        });
    })();
</script>
