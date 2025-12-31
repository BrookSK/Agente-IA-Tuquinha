<?php
/** @var array $conversations */
/** @var string $term */
/** @var int $retentionDays */
/** @var bool $favoritesOnly */
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
        <p style="color:var(--text-secondary); font-size:14px;">Nenhum histórico encontrado para esta sessão.</p>
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
                ?>
                <div style="background:var(--surface-card); border-radius:12px; padding:10px 12px; border:1px solid var(--border-subtle);" class="tuqChatListItem">
                    <div class="tuqChatListItemMain">
                        <div class="tuqChatTitleRow">
                            <div class="tuqChatTitleRowTitle" style="font-size:14px; font-weight:500;">
                                <?= htmlspecialchars($title) ?>
                            </div>
                            <?php if ($personaName !== ''): ?>
                                <span class="tuqPersonaBadge" title="<?= htmlspecialchars($personaName . ($personaArea !== '' ? ' · ' . $personaArea : '')) ?>">
                                    <span class="tuqPersonaBadgeAvatar">
                                        <?php if ($personaImg !== ''): ?>
                                            <img src="<?= htmlspecialchars($personaImg) ?>" alt="">
                                        <?php else: ?>
                                            <span style="font-size:11px; color:var(--text-secondary); font-weight:800; line-height:1;">T</span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="tuqPersonaBadgeText">
                                        <span class="tuqPersonaBadgeName"><?= htmlspecialchars($personaName) ?></span>
                                        <?php if ($personaArea !== ''): ?>
                                            <span class="tuqPersonaBadgeArea"><?= htmlspecialchars($personaArea) ?></span>
                                        <?php endif; ?>
                                    </span>
                                </span>
                            <?php else: ?>
                                <span class="tuqPersonaBadge" title="Padrão do Tuquinha / da conta">
                                    <span class="tuqPersonaBadgeAvatar">
                                        <img src="/public/favicon.png" alt="">
                                    </span>
                                    <span class="tuqPersonaBadgeText">
                                        <span class="tuqPersonaBadgeName">Padrão do Tuquinha</span>
                                        <span class="tuqPersonaBadgeArea">Padrão da conta</span>
                                    </span>
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php if ($created): ?>
                            <div style="font-size:11px; color:var(--text-secondary); margin-bottom:4px;">
                                Iniciado em <?= htmlspecialchars(date('d/m/Y H:i', strtotime($created))) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="tuqChatListItemActions">
                        <a href="/chat?c=<?= (int)$conv['id'] ?>" style="
                            display:inline-flex; align-items:center; gap:6px;
                            border-radius:999px; padding:6px 12px;
                            border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary);
                            font-size:12px; text-decoration:none;
                        ">
                            <span>Abrir chat</span>
                            <span>➜</span>
                        </a>

                        <form method="post" action="/chat/excluir" style="display:inline; margin-left:6px;">
                            <input type="hidden" name="conversation_id" value="<?= (int)$conv['id'] ?>">
                            <input type="hidden" name="redirect" value="/historico">
                            <button type="submit" title="Excluir chat" onclick="return confirm('Excluir este chat do histórico? Essa ação não pode ser desfeita.');" style="
                                border:1px solid var(--border-subtle);
                                background:var(--surface-subtle);
                                color:#ff6b6b;
                                width:34px; height:34px;
                                border-radius:999px;
                                cursor:pointer;
                                font-size:14px;
                                line-height:1;
                            ">🗑</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
