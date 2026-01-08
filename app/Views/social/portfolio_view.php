<?php
/** @var array $user */
/** @var array $profileUser */
/** @var array $profile */
/** @var array $item */
/** @var array $media */
/** @var array|null $blocks */
/** @var int $likesCount */
/** @var bool $isLiked */
/** @var bool $isOwner */
/** @var bool|null $canEdit */
/** @var array|null $collaboratorsForItem */

$ownerId = (int)($profileUser['id'] ?? 0);
$title = (string)($item['title'] ?? 'Portfólio');
$desc = trim((string)($item['description'] ?? ''));
$externalUrl = trim((string)($item['external_url'] ?? ''));
$projectId = (int)($item['project_id'] ?? 0);
$avatarPath = isset($profile['avatar_path']) ? trim((string)$profile['avatar_path']) : '';
$displayName = trim((string)($profileUser['preferred_name'] ?? $profileUser['name'] ?? ''));
if ($displayName === '') { $displayName = 'Perfil'; }

$blocks = is_array($blocks ?? null) ? $blocks : [];
$collaboratorsForItem = is_array($collaboratorsForItem ?? null) ? $collaboratorsForItem : [];
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
    #behanceHero {
        width: 100%;
        border-radius: 18px;
        overflow: hidden;
        border: 1px solid var(--border-subtle);
        background: linear-gradient(135deg,#1a1a1f,#0b0b10);
    }
    #behanceHeroImg {
        width: 100%;
        height: 420px;
        object-fit: cover;
        display: block;
    }
    #behanceHeader {
        background: transparent;
        border: 0;
        padding: 0;
    }
    #behanceHeaderInner {
        background: var(--surface-card);
        border-radius: 18px;
        border: 1px solid var(--border-subtle);
        padding: 12px 14px;
    }
    #behanceContent {
        max-width: 920px;
        margin: 0 auto;
        width: 100%;
    }
    .behanceBlock {
        margin: 0;
    }
    .behanceBlockText {
        font-size: 15px;
        line-height: 1.75;
        color: var(--text-primary);
        white-space: pre-wrap;
    }
    .behanceBlockMedia {
        border-radius: 16px;
        overflow: hidden;
        background: var(--surface-subtle);
        border: 1px solid var(--border-subtle);
    }
    .behanceGallery {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 12px;
    }
    @media (max-width: 900px) {
        #portfolioViewTop {
            flex-direction: column !important;
            align-items: flex-start !important;
        }
        #portfolioGallery {
            grid-template-columns: 1fr !important;
        }
        #behanceHeroImg {
            height: 240px;
        }
        #behanceContent {
            max-width: 100%;
        }
    }
</style>

<div style="max-width: 1100px; margin: 0 auto; display:flex; flex-direction:column; gap:14px;">
    <section id="behanceHeader">
        <div id="behanceHeaderInner">
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
                    <div style="font-size:18px; font-weight:800; color:var(--text-primary); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></div>
                    <div style="font-size:12px; color:var(--text-secondary);">por <a href="/perfil?user_id=<?= $ownerId ?>" style="color:#ff6f60; text-decoration:none;"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></a></div>
                </div>
            </div>

            <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                <a href="/perfil/portfolio?user_id=<?= $ownerId ?>" style="font-size:12px; color:#ff6f60; text-decoration:none;">Voltar ao portfólio</a>
                <?php if (!empty($canEdit) || $isOwner): ?>
                    <a href="/perfil/portfolio/gerenciar?owner_user_id=<?= (int)$ownerId ?>&edit_id=<?= (int)($item['id'] ?? 0) ?>" style="border-radius:999px; padding:6px 10px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:12px; text-decoration:none;">Editar</a>
                <?php endif; ?>
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

        <?php if (!empty($collaboratorsForItem)): ?>
            <div style="margin-top:10px; font-size:12px; color:var(--text-secondary); line-height:1.35;">
                Colaboradores:
                <?php
                    $labels = [];
                    foreach ($collaboratorsForItem as $c) {
                        $label = trim((string)($c['user_preferred_name'] ?? ''));
                        if ($label === '') { $label = trim((string)($c['user_name'] ?? '')); }
                        if ($label === '') { $label = trim((string)($c['user_nickname'] ?? '')); }
                        if ($label === '') { $label = trim((string)($c['user_email'] ?? '')); }
                        if ($label !== '') { $labels[] = $label; }
                    }
                    $labels = array_values(array_unique($labels));
                ?>
                <?= htmlspecialchars(implode(', ', $labels), ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>
        </div>
    </section>

    <?php if (!empty($blocks)): ?>
        <section id="behanceContent" style="padding: 6px 0;">
            <div style="display:flex; flex-direction:column; gap:18px;">
                <?php foreach ($blocks as $b): ?>
                    <?php
                        $t = (string)($b['type'] ?? 'text');
                        $text = (string)($b['text_content'] ?? '');
                        $url = (string)($b['media_url'] ?? '');
                        $mime = (string)($b['media_mime'] ?? '');
                        $gallery = is_array($b['media'] ?? null) ? $b['media'] : [];
                    ?>
                    <?php if ($t === 'text'): ?>
                        <div class="behanceBlock behanceBlockText"><?= htmlspecialchars($text, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php elseif ($t === 'image'): ?>
                        <div class="behanceBlock behanceBlockMedia">
                            <img src="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" alt="Imagem" style="width:100%; height:auto; display:block;">
                        </div>
                    <?php elseif ($t === 'gallery'): ?>
                        <div class="behanceBlock behanceGallery">
                            <?php foreach ($gallery as $g): ?>
                                <?php $gurl = (string)($g['url'] ?? ''); ?>
                                <?php if ($gurl !== ''): ?>
                                    <div class="behanceBlockMedia">
                                        <img src="<?= htmlspecialchars($gurl, ENT_QUOTES, 'UTF-8') ?>" alt="Imagem" style="width:100%; height:auto; display:block;">
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif ($t === 'video'): ?>
                        <?php if ($url !== ''): ?>
                            <?php if ($mime !== '' && str_starts_with($mime, 'video/')): ?>
                                <video controls style="width:100%; border-radius:16px; border:1px solid var(--border-subtle); background:#000;">
                                    <source src="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" type="<?= htmlspecialchars($mime, ENT_QUOTES, 'UTF-8') ?>">
                                </video>
                            <?php else: ?>
                                <div class="behanceBlockMedia" style="padding:12px;">
                                    <a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" style="color:#ff6f60; text-decoration:none;">Abrir vídeo</a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php elseif ($t === 'audio'): ?>
                        <?php if ($url !== ''): ?>
                            <?php if ($mime !== '' && str_starts_with($mime, 'audio/')): ?>
                                <div class="behanceBlockMedia" style="padding:12px;">
                                    <audio controls style="width:100%;">
                                        <source src="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" type="<?= htmlspecialchars($mime, ENT_QUOTES, 'UTF-8') ?>">
                                    </audio>
                                </div>
                            <?php else: ?>
                                <div class="behanceBlockMedia" style="padding:12px;">
                                    <a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" style="color:#ff6f60; text-decoration:none;">Ouvir áudio</a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php elseif ($t === 'embed'): ?>
                        <?php if ($text !== ''): ?>
                            <div class="behanceBlockMedia" style="padding:12px;">
                                <?= $text ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

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
            <h2 style="font-size:16px; margin-bottom:8px;">Edição</h2>
            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:10px;">Edite os detalhes no gerenciar ou abra o editor para mexer nos blocos.</div>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <a href="/perfil/portfolio/gerenciar?owner_user_id=<?= (int)$ownerId ?>&edit_id=<?= (int)($item['id'] ?? 0) ?>" style="border-radius:999px; padding:7px 12px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:12px; text-decoration:none;">Editar detalhes</a>
                <a href="/perfil/portfolio/editor?id=<?= (int)($item['id'] ?? 0) ?>" style="border-radius:999px; padding:7px 12px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-size:12px; font-weight:800; text-decoration:none;">Editar blocos</a>
            </div>
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

(function(){
    var canEdit = <?= (!empty($canEdit) || $isOwner) ? 'true' : 'false' ?>;
    if (!canEdit) return;

    var initialBlocks = <?= json_encode($blocks ?? [], JSON_UNESCAPED_UNICODE) ?>;
    function normalizeBlocks(serverBlocks){
        if (!Array.isArray(serverBlocks)) return [];
        return serverBlocks.map(function(b){
            var type = (b && b.type) ? String(b.type) : String(b.type || b['type'] || 'text');
            type = String(type || 'text');
            var out = { type: type };
            if (b.text_content !== undefined) out.text = String(b.text_content || '');
            if (b.media_url !== undefined) out.media_url = String(b.media_url || '');
            if (b.media_mime !== undefined) out.media_mime = String(b.media_mime || '');
            if (Array.isArray(b.media)) out.media = b.media.map(function(m){
                return {
                    url: String((m && m.url) || ''),
                    mime_type: m && m.mime_type ? String(m.mime_type) : null,
                    title: m && m.title ? String(m.title) : null,
                    size_bytes: m && m.size_bytes ? parseInt(m.size_bytes,10) : null,
                };
            });
            return out;
        });
    }

    var state = normalizeBlocks(initialBlocks);
    var list = document.getElementById('behanceBuilderList');
    var toolbar = document.getElementById('behanceBuilderToolbar');
    var saveBtn = document.getElementById('behanceSaveBtn');
    var fb = document.getElementById('behanceSaveFeedback');
    if (!list || !toolbar || !saveBtn) return;

    function el(tag, attrs){
        var e = document.createElement(tag);
        if (attrs) {
            Object.keys(attrs).forEach(function(k){
                if (k === 'text') { e.textContent = attrs[k]; return; }
                if (k === 'html') { e.innerHTML = attrs[k]; return; }
                e.setAttribute(k, attrs[k]);
            });
        }
        return e;
    }

    function render(){
        list.innerHTML = '';
        state.forEach(function(b, idx){
            var card = el('div');
            card.style.border = '1px solid var(--border-subtle)';
            card.style.background = 'var(--surface-subtle)';
            card.style.borderRadius = '14px';
            card.style.padding = '10px 12px';

            var top = el('div');
            top.style.display = 'flex';
            top.style.justifyContent = 'space-between';
            top.style.alignItems = 'center';
            top.style.gap = '10px';

            var label = el('div', { text: (b.type || 'text').toUpperCase() });
            label.style.fontSize = '11px';
            label.style.color = 'var(--text-secondary)';
            label.style.fontWeight = '700';

            var actions = el('div');
            actions.style.display = 'flex';
            actions.style.gap = '8px';
            actions.style.flexWrap = 'wrap';

            function actBtn(txt){
                var btn = el('button', { type: 'button', text: txt });
                btn.style.border = '1px solid var(--border-subtle)';
                btn.style.background = 'var(--surface-card)';
                btn.style.color = 'var(--text-primary)';
                btn.style.borderRadius = '999px';
                btn.style.padding = '5px 10px';
                btn.style.fontSize = '12px';
                btn.style.cursor = 'pointer';
                return btn;
            }

            var up = actBtn('↑');
            var down = actBtn('↓');
            var del = actBtn('Excluir');
            del.style.color = '#ffbaba';

            up.addEventListener('click', function(){
                if (idx <= 0) return;
                var tmp = state[idx-1];
                state[idx-1] = state[idx];
                state[idx] = tmp;
                render();
            });
            down.addEventListener('click', function(){
                if (idx >= state.length-1) return;
                var tmp = state[idx+1];
                state[idx+1] = state[idx];
                state[idx] = tmp;
                render();
            });
            del.addEventListener('click', function(){
                state.splice(idx, 1);
                render();
            });

            actions.appendChild(up);
            actions.appendChild(down);
            actions.appendChild(del);
            top.appendChild(label);
            top.appendChild(actions);
            card.appendChild(top);

            var body = el('div');
            body.style.marginTop = '10px';

            if (b.type === 'text') {
                var ta = el('textarea');
                ta.rows = 5;
                ta.value = b.text || '';
                ta.style.width = '100%';
                ta.style.padding = '8px 10px';
                ta.style.borderRadius = '12px';
                ta.style.border = '1px solid var(--border-subtle)';
                ta.style.background = 'var(--surface-card)';
                ta.style.color = 'var(--text-primary)';
                ta.style.resize = 'vertical';
                ta.addEventListener('input', function(){ b.text = ta.value; });
                body.appendChild(ta);
            }

            if (b.type === 'image') {
                var row = el('div');
                row.style.display = 'flex';
                row.style.gap = '10px';
                row.style.alignItems = 'center';
                row.style.flexWrap = 'wrap';

                var input = el('input', { type: 'file', accept: 'image/*' });
                input.style.fontSize = '12px';

                var preview = el('div');
                preview.style.flex = '1 1 240px';
                preview.style.minWidth = '200px';
                preview.style.border = '1px solid var(--border-subtle)';
                preview.style.borderRadius = '12px';
                preview.style.overflow = 'hidden';
                preview.style.background = 'var(--surface-card)';
                preview.style.minHeight = '120px';
                preview.style.display = 'flex';
                preview.style.alignItems = 'center';
                preview.style.justifyContent = 'center';
                preview.style.color = 'var(--text-secondary)';
                preview.style.fontSize = '12px';
                if (b.media_url) {
                    preview.innerHTML = '<img src="' + String(b.media_url).replace(/"/g,'&quot;') + '" style="width:100%; height:auto; display:block;" />';
                } else {
                    preview.textContent = 'Sem imagem';
                }

                input.addEventListener('change', async function(){
                    if (!input.files || !input.files[0]) return;
                    var f = input.files[0];
                    var fd = new FormData();
                    fd.append('item_id', '<?= (int)($item['id'] ?? 0) ?>');
                    fd.append('file', f);
                    input.disabled = true;
                    try {
                        var res = await fetch('/perfil/portfolio/blocos/upload', { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                        var json = await res.json().catch(function(){ return null; });
                        if (json && json.ok && json.url) {
                            b.media_url = json.url;
                            b.media_mime = json.mime_type || null;
                            preview.innerHTML = '<img src="' + String(b.media_url).replace(/"/g,'&quot;') + '" style="width:100%; height:auto; display:block;" />';
                        }
                    } catch(e) {
                    } finally {
                        input.disabled = false;
                    }
                });

                row.appendChild(input);
                row.appendChild(preview);
                body.appendChild(row);
            }

            if (b.type === 'gallery') {
                if (!Array.isArray(b.media)) b.media = [];
                var inputG = el('input', { type: 'file', accept: 'image/*', multiple: 'multiple' });
                inputG.style.fontSize = '12px';

                var grid = el('div');
                grid.style.marginTop = '10px';
                grid.style.display = 'grid';
                grid.style.gridTemplateColumns = 'repeat(auto-fill, minmax(140px, 1fr))';
                grid.style.gap = '8px';

                function renderGrid(){
                    grid.innerHTML = '';
                    b.media.forEach(function(m, midx){
                        var box = el('div');
                        box.style.border = '1px solid var(--border-subtle)';
                        box.style.borderRadius = '12px';
                        box.style.overflow = 'hidden';
                        box.style.background = 'var(--surface-card)';

                        var img = el('img');
                        img.src = m.url;
                        img.style.width = '100%';
                        img.style.height = '110px';
                        img.style.objectFit = 'cover';
                        img.style.display = 'block';

                        var rm = el('button', { type: 'button', text: 'Excluir' });
                        rm.style.width = '100%';
                        rm.style.border = 'none';
                        rm.style.borderTop = '1px solid var(--border-subtle)';
                        rm.style.background = 'var(--surface-subtle)';
                        rm.style.color = '#ffbaba';
                        rm.style.padding = '6px 8px';
                        rm.style.cursor = 'pointer';
                        rm.style.fontSize = '12px';
                        rm.addEventListener('click', function(){
                            b.media.splice(midx, 1);
                            renderGrid();
                        });

                        box.appendChild(img);
                        box.appendChild(rm);
                        grid.appendChild(box);
                    });
                }
                renderGrid();

                inputG.addEventListener('change', async function(){
                    if (!inputG.files || inputG.files.length === 0) return;
                    inputG.disabled = true;
                    try {
                        for (var i=0;i<inputG.files.length;i++) {
                            var f = inputG.files[i];
                            var fd = new FormData();
                            fd.append('item_id', '<?= (int)($item['id'] ?? 0) ?>');
                            fd.append('file', f);
                            var res = await fetch('/perfil/portfolio/blocos/upload', { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                            var json = await res.json().catch(function(){ return null; });
                            if (json && json.ok && json.url) {
                                b.media.push({ url: json.url, mime_type: json.mime_type || null, title: json.title || null, size_bytes: json.size_bytes || null });
                                renderGrid();
                            }
                        }
                        inputG.value = '';
                    } catch(e) {
                    } finally {
                        inputG.disabled = false;
                    }
                });

                body.appendChild(inputG);
                body.appendChild(grid);
            }

            if (b.type === 'video') {
                var urlIn = el('input', { type: 'url', placeholder: 'Link do vídeo (YouTube/drive/etc) ou faça upload abaixo' });
                urlIn.value = b.media_url || '';
                urlIn.style.width = '100%';
                urlIn.style.padding = '8px 10px';
                urlIn.style.borderRadius = '12px';
                urlIn.style.border = '1px solid var(--border-subtle)';
                urlIn.style.background = 'var(--surface-card)';
                urlIn.style.color = 'var(--text-primary)';
                urlIn.addEventListener('input', function(){ b.media_url = urlIn.value; b.media_mime = null; });

                var upV = el('input', { type: 'file', accept: 'video/*' });
                upV.style.fontSize = '12px';
                upV.style.marginTop = '8px';
                upV.addEventListener('change', async function(){
                    if (!upV.files || !upV.files[0]) return;
                    var fd = new FormData();
                    fd.append('item_id', '<?= (int)($item['id'] ?? 0) ?>');
                    fd.append('file', upV.files[0]);
                    upV.disabled = true;
                    try {
                        var res = await fetch('/perfil/portfolio/blocos/upload', { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                        var json = await res.json().catch(function(){ return null; });
                        if (json && json.ok && json.url) {
                            b.media_url = json.url;
                            b.media_mime = json.mime_type || null;
                            urlIn.value = b.media_url;
                        }
                    } catch(e) {
                    } finally {
                        upV.disabled = false;
                    }
                });

                body.appendChild(urlIn);
                body.appendChild(upV);
            }

            card.appendChild(body);
            list.appendChild(card);
        });
    }

    toolbar.querySelectorAll('button[data-add]').forEach(function(btn){
        btn.addEventListener('click', function(){
            var type = btn.getAttribute('data-add') || 'text';
            var b = { type: type };
            if (type === 'text') b.text = '';
            if (type === 'gallery') b.media = [];
            state.push(b);
            render();
        });
    });

    saveBtn.addEventListener('click', async function(){
        saveBtn.disabled = true;
        if (fb) { fb.style.display = 'none'; }
        try {
            var payload = state.map(function(b){
                var out = { type: b.type };
                if (b.type === 'text') out.text = b.text || '';
                if (b.type === 'image') { out.media_url = b.media_url || ''; out.media_mime = b.media_mime || null; }
                if (b.type === 'gallery') { out.media = Array.isArray(b.media) ? b.media : []; }
                if (b.type === 'video') { out.media_url = b.media_url || ''; out.media_mime = b.media_mime || null; }
                return out;
            });
            var fd = new FormData();
            fd.append('item_id', '<?= (int)($item['id'] ?? 0) ?>');
            fd.append('blocks_json', JSON.stringify(payload));
            var res = await fetch('/perfil/portfolio/blocos/salvar', { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            var json = await res.json().catch(function(){ return null; });
            if (fb) {
                fb.style.display = 'block';
                fb.style.color = (json && json.ok) ? '#c8ffd4' : '#ffbaba';
                fb.textContent = (json && json.ok) ? 'Conteúdo salvo.' : ((json && json.error) ? json.error : 'Não foi possível salvar.');
            }
            if (json && json.ok) {
                setTimeout(function(){ window.location.reload(); }, 650);
            }
        } catch(e) {
            if (fb) {
                fb.style.display = 'block';
                fb.style.color = '#ffbaba';
                fb.textContent = 'Não foi possível salvar.';
            }
        } finally {
            saveBtn.disabled = false;
        }
    });

    render();
})();
</script>
