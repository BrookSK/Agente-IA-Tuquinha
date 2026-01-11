<?php
/** @var array $user */
/** @var array $pages */
/** @var array|null $current */
/** @var array $shares */

$currentId = $current ? (int)($current['id'] ?? 0) : 0;
$currentTitle = $current ? (string)($current['title'] ?? 'Sem título') : 'Sem título';
$currentIcon = $current ? (string)($current['icon'] ?? '') : '';
$contentJson = $current ? (string)($current['content_json'] ?? '') : '';
$accessRole = $current ? strtolower((string)($current['access_role'] ?? 'owner')) : 'owner';
$canEdit = $current && ($accessRole === 'owner' || $accessRole === 'edit');
$isOwner = $current && (int)($current['owner_user_id'] ?? 0) === (int)($user['id'] ?? 0);
$isPublished = $current && !empty($current['is_published']);
$publicToken = $current ? (string)($current['public_token'] ?? '') : '';
$publicUrl = ($isPublished && $publicToken !== '') ? ('/caderno/publico?token=' . urlencode($publicToken)) : '';
?>

<div style="display:flex; gap:12px; min-height: calc(100vh - 64px);">
    <div style="width: 280px; flex: 0 0 280px; border:1px solid var(--border-subtle); border-radius:12px; background:var(--surface-card); overflow:hidden;">
        <div style="padding:12px; border-bottom:1px solid var(--border-subtle); display:flex; align-items:center; justify-content:space-between; gap:8px;">
            <div style="font-weight:700; font-size:14px;">Caderno</div>
            <button type="button" id="btn-new-page" style="border:none; border-radius:10px; padding:6px 10px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:700; font-size:12px; cursor:pointer;">+ Nova</button>
        </div>
        <div style="padding:8px; display:flex; flex-direction:column; gap:6px;">
            <?php if (empty($pages)): ?>
                <div style="padding:10px; color:var(--text-secondary); font-size:12px;">Você ainda não tem páginas.</div>
            <?php else: ?>
                <?php foreach ($pages as $p): ?>
                    <?php
                        $pid = (int)($p['id'] ?? 0);
                        $active = $pid === $currentId;
                        $ptitle = (string)($p['title'] ?? 'Sem título');
                        $picon = trim((string)($p['icon'] ?? ''));
                    ?>
                    <a href="/caderno?id=<?= $pid ?>" style="
                        display:flex; align-items:center; gap:8px;
                        padding:8px 10px; border-radius:10px;
                        text-decoration:none;
                        background:<?= $active ? 'rgba(229,57,53,0.14)' : 'transparent' ?>;
                        border:1px solid <?= $active ? 'rgba(229,57,53,0.25)' : 'transparent' ?>;
                        color:var(--text-primary);
                        font-size:13px;">
                        <span style="width:20px; text-align:center; opacity:0.9;"><?= $picon !== '' ? htmlspecialchars($picon) : '📄' ?></span>
                        <span style="flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars($ptitle) ?></span>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div style="flex: 1; min-width: 0; border:1px solid var(--border-subtle); border-radius:12px; background:var(--surface-card); overflow:hidden;">
        <div style="padding:12px; border-bottom:1px solid var(--border-subtle); display:flex; align-items:center; justify-content:space-between; gap:10px;">
            <div style="display:flex; align-items:center; gap:8px; min-width:0;">
                <input type="text" id="page-icon" value="<?= htmlspecialchars($currentIcon) ?>" placeholder="📄" style="
                    width:42px; padding:6px 8px; border-radius:10px; border:1px solid var(--border-subtle);
                    background:var(--surface-subtle); color:var(--text-primary); font-size:16px; text-align:center;">
                <input type="text" id="page-title" value="<?= htmlspecialchars($currentTitle) ?>" placeholder="Sem título" style="
                    flex:1; min-width:0; padding:8px 10px; border-radius:10px; border:1px solid var(--border-subtle);
                    background:var(--surface-subtle); color:var(--text-primary); font-size:14px; font-weight:700;">
                <?php if (!$current): ?>
                    <span style="font-size:12px; color:var(--text-secondary);">Crie uma página para começar.</span>
                <?php else: ?>
                    <span style="font-size:11px; color:var(--text-secondary);"><?= $canEdit ? 'Editável' : 'Somente leitura' ?></span>
                <?php endif; ?>
            </div>
            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; justify-content:flex-end;">
                <?php if ($current && $isOwner): ?>
                    <button type="button" id="btn-publish" style="
                        border:1px solid var(--border-subtle); border-radius:999px; padding:7px 12px;
                        background:var(--surface-subtle); color:var(--text-primary); font-size:12px; cursor:pointer;">
                        <?= $isPublished ? 'Despublicar' : 'Publicar' ?>
                    </button>
                    <button type="button" id="btn-share" style="
                        border:1px solid var(--border-subtle); border-radius:999px; padding:7px 12px;
                        background:var(--surface-subtle); color:var(--text-primary); font-size:12px; cursor:pointer;">Compartilhar</button>
                    <button type="button" id="btn-delete" style="
                        border:1px solid rgba(229,57,53,0.35); border-radius:999px; padding:7px 12px;
                        background:rgba(229,57,53,0.10); color:var(--accent); font-size:12px; cursor:pointer;">Excluir</button>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($current && $isOwner): ?>
            <div id="share-panel" style="display:none; padding:12px; border-bottom:1px solid var(--border-subtle);">
                <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end;">
                    <div style="flex: 1 1 220px;">
                        <label style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Compartilhar com (e-mail)</label>
                        <input type="email" id="share-email" placeholder="email@exemplo.com" style="
                            width:100%; padding:8px 10px; border-radius:10px; border:1px solid var(--border-subtle);
                            background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                    </div>
                    <div style="flex: 0 0 140px;">
                        <label style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Permissão</label>
                        <select id="share-role" style="
                            width:100%; padding:8px 10px; border-radius:10px; border:1px solid var(--border-subtle);
                            background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                            <option value="view">Somente ver</option>
                            <option value="edit">Pode editar</option>
                        </select>
                    </div>
                    <button type="button" id="btn-share-add" style="
                        border:none; border-radius:10px; padding:9px 14px;
                        background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:700; font-size:12px; cursor:pointer;">Adicionar</button>
                </div>

                <div style="margin-top:10px;">
                    <div style="font-size:12px; color:var(--text-secondary); margin-bottom:6px;">Pessoas com acesso</div>
                    <div id="share-list" style="display:flex; flex-direction:column; gap:6px;">
                        <?php if (empty($shares)): ?>
                            <div style="font-size:12px; color:var(--text-secondary);">Ninguém ainda.</div>
                        <?php else: ?>
                            <?php foreach ($shares as $s): ?>
                                <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; border:1px solid var(--border-subtle); background:var(--surface-subtle); padding:8px 10px; border-radius:10px;">
                                    <div style="min-width:0;">
                                        <div style="font-size:13px; color:var(--text-primary); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars((string)($s['email'] ?? '')) ?></div>
                                        <div style="font-size:11px; color:var(--text-secondary);"><?= htmlspecialchars((string)($s['role'] ?? 'view')) ?></div>
                                    </div>
                                    <button type="button" class="btn-share-remove" data-user-id="<?= (int)($s['user_id'] ?? 0) ?>" style="
                                        border:1px solid var(--border-subtle); border-radius:999px; padding:6px 10px;
                                        background:transparent; color:var(--text-primary); font-size:12px; cursor:pointer;">Remover</button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($current && $isPublished && $publicUrl !== ''): ?>
                    <div style="margin-top:10px; border-top:1px dashed var(--border-subtle); padding-top:10px;">
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Link público (somente leitura)</div>
                        <input type="text" readonly value="<?= htmlspecialchars($publicUrl) ?>" style="
                            width:100%; padding:8px 10px; border-radius:10px; border:1px solid var(--border-subtle);
                            background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div style="padding:14px;">
            <div id="editorjs" style="background:transparent;"></div>
            <div id="editor-hint" style="margin-top:10px; font-size:12px; color:var(--text-secondary);"></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@editorjs/editorjs@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/header@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/list@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/checklist@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/quote@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/code@latest"></script>
<script>
(function () {
    var pageId = <?= (int)$currentId ?>;
    var canEdit = <?= $canEdit ? 'true' : 'false' ?>;
    var initialJson = <?= json_encode($contentJson !== '' ? $contentJson : '') ?>;

    var $ = function (id) { return document.getElementById(id); };

    function postForm(url, data) {
        var fd = new FormData();
        Object.keys(data || {}).forEach(function (k) { fd.append(k, data[k]); });
        return fetch(url, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: fd
        }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, status: r.status, json: j }; }); });
    }

    function safeJsonParse(text) {
        try { return JSON.parse(text); } catch (e) { return null; }
    }

    var editorData = null;
    if (initialJson && typeof initialJson === 'string') {
        editorData = safeJsonParse(initialJson);
    }

    if (!editorData) {
        editorData = { time: Date.now(), blocks: [] };
    }

    var editor = new EditorJS({
        holder: 'editorjs',
        readOnly: !canEdit,
        data: editorData,
        autofocus: true,
        tools: {
            header: { class: Header, inlineToolbar: true, config: { levels: [1,2,3], defaultLevel: 2 } },
            list: { class: List, inlineToolbar: true },
            checklist: { class: Checklist, inlineToolbar: true },
            quote: { class: Quote, inlineToolbar: true },
            code: { class: CodeTool }
        }
    });

    var saving = false;
    var pending = false;
    var lastSaved = 0;

    function setHint(text) {
        var el = $('editor-hint');
        if (el) el.textContent = text || '';
    }

    function scheduleSave() {
        if (!pageId || !canEdit) return;
        if (saving) { pending = true; return; }
        saving = true;
        setHint('Salvando...');
        editor.save().then(function (data) {
            return postForm('/caderno/salvar', {
                page_id: String(pageId),
                content_json: JSON.stringify(data)
            });
        }).then(function (res) {
            saving = false;
            lastSaved = Date.now();
            if (!res.json || res.json.ok !== true) {
                setHint((res.json && res.json.error) ? res.json.error : 'Falha ao salvar.');
                return;
            }
            setHint('Salvo agora');
            if (pending) { pending = false; setTimeout(scheduleSave, 250); }
        }).catch(function () {
            saving = false;
            setHint('Falha ao salvar.');
        });
    }

    var debounceTimer = null;
    function debounceSave() {
        if (!pageId || !canEdit) return;
        if (debounceTimer) clearTimeout(debounceTimer);
        debounceTimer = setTimeout(scheduleSave, 650);
    }

    if (canEdit) {
        document.addEventListener('keyup', function (e) {
            if (!e) return;
            debounceSave();
        }, true);
        document.addEventListener('mouseup', function () { debounceSave(); }, true);
    } else {
        setHint('Somente leitura');
    }

    var btnNew = $('btn-new-page');
    if (btnNew) {
        btnNew.addEventListener('click', function () {
            postForm('/caderno/criar', { title: 'Sem título' }).then(function (res) {
                if (res.json && res.json.ok && res.json.id) {
                    window.location.href = '/caderno?id=' + encodeURIComponent(res.json.id);
                }
            });
        });
    }

    function attachRename() {
        var title = $('page-title');
        var icon = $('page-icon');
        if (!title || !icon) return;
        if (!pageId) return;
        if (!<?= $isOwner ? 'true' : 'false' ?>) return;

        var timer = null;
        function doRename() {
            postForm('/caderno/renomear', {
                page_id: String(pageId),
                title: title.value || 'Sem título',
                icon: icon.value || ''
            });
        }
        function schedule() {
            if (timer) clearTimeout(timer);
            timer = setTimeout(doRename, 500);
        }
        title.addEventListener('input', schedule);
        icon.addEventListener('input', schedule);
    }
    attachRename();

    var btnDelete = $('btn-delete');
    if (btnDelete && pageId) {
        btnDelete.addEventListener('click', function () {
            if (!confirm('Excluir esta página?')) return;
            postForm('/caderno/excluir', { page_id: String(pageId) }).then(function (res) {
                if (res.json && res.json.ok) {
                    window.location.href = '/caderno';
                }
            });
        });
    }

    var btnShare = $('btn-share');
    if (btnShare) {
        btnShare.addEventListener('click', function () {
            var p = $('share-panel');
            if (!p) return;
            p.style.display = (p.style.display === 'none' || p.style.display === '') ? 'block' : 'none';
        });
    }

    function renderShares(shares) {
        var list = $('share-list');
        if (!list) return;
        list.innerHTML = '';
        if (!shares || !shares.length) {
            var div = document.createElement('div');
            div.style.fontSize = '12px';
            div.style.color = 'var(--text-secondary)';
            div.textContent = 'Ninguém ainda.';
            list.appendChild(div);
            return;
        }
        shares.forEach(function (s) {
            var row = document.createElement('div');
            row.style.display = 'flex';
            row.style.alignItems = 'center';
            row.style.justifyContent = 'space-between';
            row.style.gap = '10px';
            row.style.border = '1px solid var(--border-subtle)';
            row.style.background = 'var(--surface-subtle)';
            row.style.padding = '8px 10px';
            row.style.borderRadius = '10px';

            var left = document.createElement('div');
            left.style.minWidth = '0';

            var email = document.createElement('div');
            email.style.fontSize = '13px';
            email.style.color = 'var(--text-primary)';
            email.style.overflow = 'hidden';
            email.style.textOverflow = 'ellipsis';
            email.style.whiteSpace = 'nowrap';
            email.textContent = s.email || '';

            var role = document.createElement('div');
            role.style.fontSize = '11px';
            role.style.color = 'var(--text-secondary)';
            role.textContent = s.role || 'view';

            left.appendChild(email);
            left.appendChild(role);

            var btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = 'Remover';
            btn.style.border = '1px solid var(--border-subtle)';
            btn.style.borderRadius = '999px';
            btn.style.padding = '6px 10px';
            btn.style.background = 'transparent';
            btn.style.color = 'var(--text-primary)';
            btn.style.fontSize = '12px';
            btn.style.cursor = 'pointer';
            btn.addEventListener('click', function () {
                postForm('/caderno/compartilhar/remover', { page_id: String(pageId), user_id: String(s.user_id || 0) }).then(function (res) {
                    if (res.json && res.json.ok) {
                        renderShares(res.json.shares || []);
                    }
                });
            });

            row.appendChild(left);
            row.appendChild(btn);
            list.appendChild(row);
        });
    }

    var btnShareAdd = $('btn-share-add');
    if (btnShareAdd && pageId) {
        btnShareAdd.addEventListener('click', function () {
            var email = $('share-email');
            var role = $('share-role');
            if (!email || !role) return;
            postForm('/caderno/compartilhar/adicionar', {
                page_id: String(pageId),
                email: email.value || '',
                role: role.value || 'view'
            }).then(function (res) {
                if (res.json && res.json.ok) {
                    email.value = '';
                    renderShares(res.json.shares || []);
                } else if (res.json && res.json.error) {
                    alert(res.json.error);
                }
            });
        });
    }

    var btnPublish = $('btn-publish');
    if (btnPublish && pageId) {
        btnPublish.addEventListener('click', function () {
            var willPublish = <?= $isPublished ? 'false' : 'true' ?>;
            postForm('/caderno/publicar', { page_id: String(pageId), publish: willPublish ? '1' : '' }).then(function (res) {
                if (res.json && res.json.ok) {
                    window.location.reload();
                }
            });
        });
    }
})();
</script>
