<?php
/** @var array $user */
/** @var array $profileUser */
/** @var array $profile */
/** @var array $items */
/** @var array $likesCountById */
/** @var bool $isOwn */

$displayName = trim((string)($profileUser['preferred_name'] ?? $profileUser['name'] ?? ''));
if ($displayName === '') {
    $displayName = 'Perfil';
}

$targetId = (int)($profileUser['id'] ?? 0);
$avatarPath = isset($profile['avatar_path']) ? trim((string)$profile['avatar_path']) : '';
$initial = mb_strtoupper(mb_substr((string)$displayName, 0, 1, 'UTF-8'), 'UTF-8');
?>
<style>
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
                    <a href="/perfil/portfolio/gerenciar" style="border-radius:999px; padding:6px 12px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-size:12px; font-weight:650; text-decoration:none; white-space:nowrap;">Gerenciar</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php if (empty($items)): ?>
        <div style="background:var(--surface-card); border-radius:16px; border:1px solid var(--border-subtle); padding:14px; color:var(--text-secondary); font-size:13px;">
            Nenhum portfólio publicado ainda.
        </div>
    <?php else: ?>
        <div id="portfolioCardsGrid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(240px, 1fr)); gap:10px;">
            <?php foreach ($items as $it): ?>
                <?php
                    $iid = (int)($it['id'] ?? 0);
                    $title = (string)($it['title'] ?? 'Portfólio');
                    $desc = trim((string)($it['description'] ?? ''));
                    $likes = (int)($likesCountById[$iid] ?? 0);
                ?>
                <a href="/perfil/portfolio/ver?id=<?= $iid ?>" style="text-decoration:none;">
                    <div style="background:var(--surface-card); border-radius:16px; border:1px solid var(--border-subtle); padding:12px 14px; display:flex; flex-direction:column; gap:8px; height:100%;">
                        <div style="font-weight:650; font-size:14px; color:var(--text-primary);">
                            <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <?php if ($desc !== ''): ?>
                            <div style="font-size:12px; color:var(--text-secondary); line-height:1.35;">
                                <?= nl2br(htmlspecialchars(mb_strlen($desc,'UTF-8') > 180 ? mb_substr($desc,0,180,'UTF-8').'…' : $desc, ENT_QUOTES, 'UTF-8')) ?>
                            </div>
                        <?php else: ?>
                            <div style="font-size:12px; color:var(--text-secondary);">Sem descrição.</div>
                        <?php endif; ?>
                        <div style="margin-top:auto; display:flex; align-items:center; justify-content:space-between; gap:10px;">
                            <div style="font-size:11px; color:var(--text-secondary);"><?= !empty($it['created_at']) ? htmlspecialchars(date('d/m/Y', strtotime((string)$it['created_at'])), ENT_QUOTES, 'UTF-8') : '' ?></div>
                            <div style="font-size:11px; color:var(--text-secondary);">❤ <?= $likes ?></div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
