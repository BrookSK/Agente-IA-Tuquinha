<?php
/** @var array $user */
/** @var array $profileUser */
/** @var array $profile */
/** @var array $items */
/** @var array $likesCountById */
/** @var bool $isOwn */
/** @var bool|null $canManage */

$displayName = trim((string)($profileUser['preferred_name'] ?? $profileUser['name'] ?? ''));
if ($displayName === '') {
    $displayName = 'Perfil';
}

$targetId = (int)($profileUser['id'] ?? 0);
$avatarPath = isset($profile['avatar_path']) ? trim((string)$profile['avatar_path']) : '';
$initial = mb_strtoupper(mb_substr((string)$displayName, 0, 1, 'UTF-8'), 'UTF-8');
?>
<style>
    .behanceCard {
        border: 1px solid var(--border-subtle);
        background: var(--surface-card);
        border-radius: 16px;
        overflow: hidden;
        transition: transform 120ms ease, box-shadow 120ms ease;
    }
    .behanceCard:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 26px rgba(0,0,0,0.22);
    }
    @media (max-width: 900px) {
        #portfolioHeaderRow {
            flex-direction: column !important;
            align-items: flex-start !important;
        }
        #portfolioCardsGrid {
            grid-template-columns: 1fr !important;
        }
    }
</style>

<div style="max-width: 980px; margin: 0 auto; display:flex; flex-direction:column; gap:14px;">
    <section style="background:var(--surface-card); border-radius:16px; border:1px solid var(--border-subtle); padding:12px 14px;">
        <div id="portfolioHeaderRow" style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">
            <div style="display:flex; align-items:center; gap:10px; min-width:0;">
                <div style="width:44px; height:44px; border-radius:12px; overflow:hidden; background:radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%); display:flex; align-items:center; justify-content:center; font-size:18px; font-weight:700; color:#050509;">
                    <?php if ($avatarPath !== ''): ?>
                        <img src="<?= htmlspecialchars($avatarPath, ENT_QUOTES, 'UTF-8') ?>" alt="Avatar" style="width:100%; height:100%; object-fit:cover; display:block;">
                    <?php else: ?>
                        <?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?>
                    <?php endif; ?>
                </div>
                <div style="min-width:0;">
                    <div style="font-size:16px; font-weight:650;">Portfólio</div>
                    <div style="font-size:12px; color:var(--text-secondary); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width: 520px;">
                        de <?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>
            </div>

            <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                <a href="/perfil?user_id=<?= (int)$targetId ?>" style="font-size:12px; color:#ff6f60; text-decoration:none;">Voltar ao perfil</a>
                <?php if ($isOwn): ?>
                    <a href="/perfil/portfolio/gerenciar?owner_user_id=<?= (int)$targetId ?>" style="border-radius:999px; padding:6px 12px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:12px; font-weight:650; text-decoration:none; white-space:nowrap;">Criar novo projeto</a>
                <?php endif; ?>
                <?php if (!empty($canManage) || $isOwn): ?>
                    <a href="/perfil/portfolio/gerenciar?owner_user_id=<?= (int)$targetId ?>" style="border-radius:999px; padding:6px 12px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-size:12px; font-weight:650; text-decoration:none; white-space:nowrap;">Gerenciar</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php if (empty($items)): ?>
        <?php if ($isOwn): ?>
            <a href="/perfil/portfolio/gerenciar?owner_user_id=<?= (int)$targetId ?>" style="text-decoration:none;">
                <div style="border-radius:16px; border:2px dashed rgba(255,255,255,0.18); padding:18px; background:rgba(255,255,255,0.03); color:var(--text-primary);">
                    <div style="font-weight:750; font-size:13px;">Criar novo projeto</div>
                    <div style="margin-top:4px; font-size:12px; color:var(--text-secondary);">Clique para criar e depois montar os blocos (texto, imagem, grade, vídeo...).</div>
                </div>
            </a>
        <?php else: ?>
            <div style="background:var(--surface-card); border-radius:16px; border:1px solid var(--border-subtle); padding:14px; color:var(--text-secondary); font-size:13px;">
                Nenhum portfólio publicado ainda.
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div id="portfolioCardsGrid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(240px, 1fr)); gap:10px;">
            <?php foreach ($items as $it): ?>
                <?php
                    $iid = (int)($it['id'] ?? 0);
                    $title = (string)($it['title'] ?? 'Portfólio');
                    $desc = trim((string)($it['description'] ?? ''));
                    $likes = (int)($likesCountById[$iid] ?? 0);
                    $cover = trim((string)($it['cover_url'] ?? ''));
                ?>
                <a href="/perfil/portfolio/ver?id=<?= $iid ?>" style="text-decoration:none;">
                    <div class="behanceCard" style="height:100%; display:flex; flex-direction:column;">
                        <div style="height:160px; background:linear-gradient(135deg,#1a1a1f,#0b0b10); position:relative;">
                            <?php if ($cover !== ''): ?>
                                <img src="<?= htmlspecialchars($cover, ENT_QUOTES, 'UTF-8') ?>" alt="Capa" style="width:100%; height:100%; object-fit:cover; display:block;">
                            <?php else: ?>
                                <div style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center; color:rgba(255,255,255,0.55); font-size:12px;">Sem capa</div>
                            <?php endif; ?>
                        </div>
                        <div style="padding:10px 12px; display:flex; flex-direction:column; gap:6px;">
                            <div style="font-weight:750; font-size:13px; color:var(--text-primary); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <?php if ($desc !== ''): ?>
                                <div style="font-size:12px; color:var(--text-secondary); line-height:1.35; overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;">
                                    <?= htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            <?php endif; ?>
                            <div style="margin-top:auto; display:flex; align-items:center; justify-content:space-between; gap:10px;">
                                <div style="font-size:11px; color:var(--text-secondary);"><?= !empty($it['created_at']) ? htmlspecialchars(date('d/m/Y', strtotime((string)$it['created_at'])), ENT_QUOTES, 'UTF-8') : '' ?></div>
                                <div style="font-size:11px; color:var(--text-secondary);">❤ <?= $likes ?></div>
                            </div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
