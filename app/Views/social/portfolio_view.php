<?php
/** @var array $user */
/** @var array $profileUser */
/** @var array $profile */
/** @var array $item */
/** @var array $media */
/** @var int $likesCount */
/** @var bool $isLiked */
/** @var bool $isOwner */
/** @var bool|null $canEdit */

$ownerId = (int)($profileUser['id'] ?? 0);
$title = (string)($item['title'] ?? 'Portfólio');
$desc = trim((string)($item['description'] ?? ''));
$externalUrl = trim((string)($item['external_url'] ?? ''));
$projectId = (int)($item['project_id'] ?? 0);
$avatarPath = isset($profile['avatar_path']) ? trim((string)$profile['avatar_path']) : '';
$displayName = trim((string)($profileUser['preferred_name'] ?? $profileUser['name'] ?? ''));
if ($displayName === '') { $displayName = 'Perfil'; }
$initial = mb_strtoupper(mb_substr((string)$displayName, 0, 1, 'UTF-8'), 'UTF-8');

$success = $_SESSION['portfolio_success'] ?? null;
$error = $_SESSION['portfolio_error'] ?? null;
unset($_SESSION['portfolio_success'], $_SESSION['portfolio_error']);

$images = [];
$files = [];
foreach ($media as $m) {
    $kind = (string)($m['kind'] ?? 'image');
    if ($kind === 'image') {
        $images[] = $m;
    } else {
        $files[] = $m;
    }
}

?>
<style>
    @media (max-width: 900px) {
        #portfolioViewTop {
            flex-direction: column !important;
            align-items: flex-start !important;
        }
        #portfolioGallery {
            grid-template-columns: 1fr !important;
        }
    }
</style>

<div style="max-width: 980px; margin: 0 auto; display:flex; flex-direction:column; gap:14px;">
    <section style="background:var(--surface-card); border-radius:16px; border:1px solid var(--border-subtle); padding:12px 14px;">
        <div id="portfolioViewTop" style="display:flex; justify-content:space-between; gap:10px; align-items:center; flex-wrap:wrap;">
            <div style="display:flex; align-items:center; gap:10px; min-width:0;">
                <div style="width:44px; height:44px; border-radius:12px; overflow:hidden; background:radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%); display:flex; align-items:center; justify-content:center; font-size:18px; font-weight:700; color:#050509;">
                    <?php if ($avatarPath !== ''): ?>
                        <img src="<?= htmlspecialchars($avatarPath, ENT_QUOTES, 'UTF-8') ?>" alt="Avatar" style="width:100%; height:100%; object-fit:cover; display:block;">
                    <?php else: ?>
                        <?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?>
                    <?php endif; ?>
                </div>
                <div style="min-width:0;">
                    <div style="font-size:16px; font-weight:700; color:var(--text-primary); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></div>
                    <div style="font-size:12px; color:var(--text-secondary);">de <a href="/perfil?user_id=<?= $ownerId ?>" style="color:#ff6f60; text-decoration:none;"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></a></div>
                </div>
            </div>

            <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                <a href="/perfil/portfolio?user_id=<?= $ownerId ?>" style="font-size:12px; color:#ff6f60; text-decoration:none;">Voltar ao portfólio</a>
                <button type="button" id="portfolioLikeBtn" aria-pressed="<?= $isLiked ? 'true' : 'false' ?>" style="border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); border-radius:999px; padding:6px 10px; font-size:12px; cursor:pointer;">
                    <span id="portfolioLikeIcon"><?= $isLiked ? '❤' : '♡' ?></span>
                    <span id="portfolioLikeCount" style="margin-left:4px;"><?= (int)$likesCount ?></span>
                </button>
                <?php if ($externalUrl !== ''): ?>
                    <a href="<?= htmlspecialchars($externalUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" style="border-radius:999px; padding:6px 10px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-size:12px; font-weight:650; text-decoration:none;">Abrir link</a>
                <?php endif; ?>
                <?php if ($projectId > 0): ?>
                    <a href="/projetos/ver?id=<?= $projectId ?>" style="border-radius:999px; padding:6px 10px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:12px; text-decoration:none;">Ver projeto</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($desc !== ''): ?>
            <div style="margin-top:10px; font-size:13px; color:var(--text-secondary); line-height:1.35;">
                <?= nl2br(htmlspecialchars($desc, ENT_QUOTES, 'UTF-8')) ?>
            </div>
        <?php endif; ?>
    </section>

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

    <?php if (!empty($canEdit) || $isOwner): ?>
        <section style="background:var(--surface-card); border-radius:16px; border:1px solid var(--border-subtle); padding:12px 14px;">
            <h2 style="font-size:16px; margin-bottom:8px;">Adicionar mídia</h2>
            <form action="/perfil/portfolio/upload" method="post" enctype="multipart/form-data" style="display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap;">
                <input type="hidden" name="item_id" value="<?= (int)($item['id'] ?? 0) ?>">
                <input type="hidden" name="owner_user_id" value="<?= (int)$ownerId ?>">
                <div style="flex:1 1 260px; min-width:0;">
                    <label style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Arquivo (imagem ou documento)</label>
                    <input type="file" name="file" required style="font-size:12px;">
                </div>
                <button type="submit" style="border:none; border-radius:999px; padding:7px 12px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-size:12px; font-weight:650; cursor:pointer;">Enviar</button>
            </form>
            <div style="margin-top:6px; font-size:11px; color:var(--text-secondary);">Dica: imagens viram galeria; outros arquivos viram anexos.</div>
        </section>
    <?php endif; ?>

    <?php if (!empty($images)): ?>
        <section style="background:var(--surface-card); border-radius:16px; border:1px solid var(--border-subtle); padding:12px 14px;">
            <h2 style="font-size:16px; margin-bottom:8px;">Imagens</h2>
            <div id="portfolioGallery" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(180px, 1fr)); gap:10px;">
                <?php foreach ($images as $img): ?>
                    <?php $mid = (int)($img['id'] ?? 0); $url = (string)($img['url'] ?? ''); ?>
                    <div style="border:1px solid var(--border-subtle); background:var(--surface-subtle); border-radius:14px; overflow:hidden;">
                        <a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" style="display:block;">
                            <img src="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" alt="Imagem" style="width:100%; height:160px; object-fit:cover; display:block;">
                        </a>
                        <?php if (!empty($canEdit) || $isOwner): ?>
                            <form action="/perfil/portfolio/midia/excluir" method="post" style="margin:0; padding:8px; display:flex; justify-content:flex-end;" onsubmit="return confirm('Excluir esta mídia?');">
                                <input type="hidden" name="item_id" value="<?= (int)($item['id'] ?? 0) ?>">
                                <input type="hidden" name="owner_user_id" value="<?= (int)$ownerId ?>">
                                <input type="hidden" name="media_id" value="<?= $mid ?>">
                                <button type="submit" style="border:1px solid var(--border-subtle); background:var(--surface-card); color:#ffbaba; border-radius:999px; padding:5px 10px; font-size:12px; cursor:pointer;">Excluir</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!empty($files)): ?>
        <section style="background:var(--surface-card); border-radius:16px; border:1px solid var(--border-subtle); padding:12px 14px;">
            <h2 style="font-size:16px; margin-bottom:8px;">Arquivos</h2>
            <div style="display:flex; flex-direction:column; gap:8px;">
                <?php foreach ($files as $f): ?>
                    <?php $mid = (int)($f['id'] ?? 0); $url = (string)($f['url'] ?? ''); $name = (string)($f['title'] ?? 'arquivo'); ?>
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; border:1px solid var(--border-subtle); background:var(--surface-subtle); border-radius:14px; padding:10px 12px; flex-wrap:wrap;">
                        <div style="min-width:0;">
                            <div style="font-size:13px; font-weight:650; color:var(--text-primary); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width: 620px;"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></div>
                            <div style="font-size:11px; color:var(--text-secondary);"><?= htmlspecialchars((string)($f['mime_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                            <a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" style="border-radius:999px; padding:5px 10px; border:1px solid var(--border-subtle); background:var(--surface-card); color:var(--text-primary); font-size:12px; text-decoration:none;">Baixar</a>
                            <?php if (!empty($canEdit) || $isOwner): ?>
                                <form action="/perfil/portfolio/midia/excluir" method="post" style="margin:0;" onsubmit="return confirm('Excluir este arquivo?');">
                                    <input type="hidden" name="item_id" value="<?= (int)($item['id'] ?? 0) ?>">
                                    <input type="hidden" name="owner_user_id" value="<?= (int)$ownerId ?>">
                                    <input type="hidden" name="media_id" value="<?= $mid ?>">
                                    <button type="submit" style="border:1px solid var(--border-subtle); background:var(--surface-card); color:#ffbaba; border-radius:999px; padding:5px 10px; font-size:12px; cursor:pointer;">Excluir</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<script>
(function(){
    var btn = document.getElementById('portfolioLikeBtn');
    if (!btn) return;
    btn.addEventListener('click', async function(){
        btn.disabled = true;
        try {
            var fd = new FormData();
            fd.append('item_id', '<?= (int)($item['id'] ?? 0) ?>');
            var res = await fetch('/perfil/portfolio/curtir', { method: 'POST', body: fd, credentials: 'same-origin' });
            var json = await res.json().catch(function(){ return null; });
            if (json && json.ok) {
                var icon = document.getElementById('portfolioLikeIcon');
                var count = document.getElementById('portfolioLikeCount');
                if (icon) icon.textContent = json.liked ? '❤' : '♡';
                if (count) count.textContent = json.count;
                btn.setAttribute('aria-pressed', json.liked ? 'true' : 'false');
            }
        } catch (e) {
        } finally {
            btn.disabled = false;
        }
    });
})();
</script>
