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

<style>
    .notion-shell {
        display: flex;
        gap: 12px;
        min-height: calc(100vh - 64px);
    }
    .notion-sidebar {
        width: 280px;
        flex: 0 0 280px;
        border: 1px solid var(--border-subtle);
        border-radius: 12px;
        background: var(--surface-card);
        overflow: hidden;
    }
    .notion-sidebar-head {
        padding: 12px;
        border-bottom: 1px solid var(--border-subtle);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }
    .notion-sidebar-title {
        font-weight: 750;
        font-size: 13px;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        color: var(--text-secondary);
    }
    .notion-page {
        flex: 1;
        min-width: 0;
        border: 1px solid var(--border-subtle);
        border-radius: 12px;
        background: var(--surface-card);
        overflow: hidden;
    }
    .notion-page-header {
        padding: 18px 20px 10px 20px;
        border-bottom: 1px solid var(--border-subtle);
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
    }
    .notion-title-wrap {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
        flex: 1;
    }
    .notion-emoji {
        width: 44px;
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        border: 1px solid var(--border-subtle);
        background: var(--surface-subtle);
        font-size: 18px;
        flex: 0 0 44px;
    }
    .notion-title {
        width: 100%;
        border: none;
        outline: none;
        background: transparent;
        color: var(--text-primary);
        font-size: 28px;
        line-height: 1.15;
        font-weight: 800;
        padding: 6px 2px;
    }
    .notion-title::placeholder {
        color: rgba(255,255,255,0.45);
    }
    body[data-theme="light"] .notion-title::placeholder {
        color: rgba(15, 23, 42, 0.42);
    }
    .notion-page-body {
        padding: 14px 20px 26px 20px;
    }
    .notion-editor-wrap {
        max-width: 900px;
        margin: 0 auto;
    }

    /* Editor.js look (aproxima Notion) */
    .notion-editor-wrap .ce-block__content,
    .notion-editor-wrap .ce-toolbar__content {
        max-width: 900px;
    }
    .notion-editor-wrap .ce-paragraph {
        font-size: 15px;
        line-height: 1.75;
        color: var(--text-primary);
    }
    .notion-editor-wrap .ce-header {
        padding: 0.6em 0 0.2em;
    }
    .notion-editor-wrap h1.ce-header { font-size: 28px; }
    .notion-editor-wrap h2.ce-header { font-size: 22px; }
    .notion-editor-wrap h3.ce-header { font-size: 18px; }
    .notion-editor-wrap .cdx-list__item {
        padding: 2px 0;
        line-height: 1.7;
        font-size: 15px;
    }
    .notion-editor-wrap .cdx-checklist__item-text {
        font-size: 15px;
        line-height: 1.7;
    }
    .notion-editor-wrap .ce-code__textarea {
        background: rgba(0,0,0,0.25);
        border: 1px solid var(--border-subtle);
        color: var(--text-primary);
        border-radius: 10px;
        font-size: 13px;
        line-height: 1.6;
    }
    body[data-theme="light"] .notion-editor-wrap .ce-code__textarea {
        background: rgba(15, 23, 42, 0.04);
    }
    .notion-editor-hint {
        margin-top: 10px;
        font-size: 12px;
        color: var(--text-secondary);
        max-width: 900px;
        margin-left: auto;
        margin-right: auto;
    }

    .notion-sidebar a:hover {
        background: rgba(255,255,255,0.04) !important;
        border-color: rgba(255,255,255,0.07) !important;
    }
    body[data-theme="light"] .notion-sidebar a:hover {
        background: rgba(15,23,42,0.04) !important;
        border-color: rgba(15,23,42,0.08) !important;
    }

    /* Visual colors for blocks (MVP; persistência depois) */
    .notion-editor-wrap .tuq-block--c-gray { color: rgba(255,255,255,0.70) !important; }
    .notion-editor-wrap .tuq-block--c-red { color: #ff8a80 !important; }
    .notion-editor-wrap .tuq-block--c-yellow { color: #ffe082 !important; }
    .notion-editor-wrap .tuq-block--c-green { color: #a5d6a7 !important; }
    .notion-editor-wrap .tuq-block--c-blue { color: #90caf9 !important; }
    body[data-theme="light"] .notion-editor-wrap .tuq-block--c-gray { color: rgba(15,23,42,0.70) !important; }

    .notion-editor-wrap .tuq-block--bg-gray { background: rgba(255,255,255,0.04); border-radius: 10px; }
    .notion-editor-wrap .tuq-block--bg-brown { background: rgba(141,110,99,0.16); border-radius: 10px; }
    .notion-editor-wrap .tuq-block--bg-yellow { background: rgba(255,238,88,0.12); border-radius: 10px; }
    .notion-editor-wrap .tuq-block--bg-blue { background: rgba(66,165,245,0.12); border-radius: 10px; }
    body[data-theme="light"] .notion-editor-wrap .tuq-block--bg-gray { background: rgba(15,23,42,0.04); }

    /* Context menu */
    .tuq-ctx {
        position: fixed;
        z-index: 9999;
        width: 280px;
        border-radius: 12px;
        border: 1px solid var(--border-subtle);
        background: rgba(17,17,24,0.92);
        backdrop-filter: blur(10px);
        box-shadow: 0 18px 46px rgba(0,0,0,0.55);
        padding: 8px;
        display: none;
    }
    body[data-theme="light"] .tuq-ctx {
        background: rgba(255,255,255,0.96);
        box-shadow: 0 18px 46px rgba(15,23,42,0.15);
    }
    .tuq-ctx .tuq-ctx-search {
        width: 100%;
        padding: 8px 10px;
        border-radius: 10px;
        border: 1px solid var(--border-subtle);
        background: var(--surface-subtle);
        color: var(--text-primary);
        font-size: 13px;
        outline: none;
        margin-bottom: 8px;
    }
    .tuq-ctx .tuq-ctx-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 8px 10px;
        border-radius: 10px;
        cursor: pointer;
        color: var(--text-primary);
        font-size: 13px;
    }
    .tuq-ctx .tuq-ctx-item:hover {
        background: rgba(255,255,255,0.06);
    }
    body[data-theme="light"] .tuq-ctx .tuq-ctx-item:hover {
        background: rgba(15,23,42,0.06);
    }
    .tuq-ctx .tuq-ctx-item small {
        color: var(--text-secondary);
        font-size: 11px;
    }
    .tuq-ctx .tuq-ctx-sub {
        display: none;
        margin-top: 6px;
        padding-top: 6px;
        border-top: 1px solid var(--border-subtle);
    }
</style>

<div class="notion-shell">
    <div class="notion-sidebar">
        <div class="notion-sidebar-head">
            <div class="notion-sidebar-title">Caderno</div>
            <button type="button" id="btn-new-page" style="border:none; border-radius:10px; padding:6px 10px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:700; font-size:12px; cursor:pointer;">+ Nova</button>
        </div>
        <div style="padding:8px; display:flex; flex-direction:column; gap:6px;">
            <?php if (empty($pages)): ?>
                <div style="padding:10px; color:var(--text-secondary); font-size:12px;">Você ainda não tem páginas. Clique em <b>+ Nova</b>.</div>
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

    <div class="notion-page">
        <div class="notion-page-header">
            <div class="notion-title-wrap">
                <div class="notion-emoji">
                    <input type="text" id="page-icon" value="<?= htmlspecialchars($currentIcon) ?>" placeholder="📄" style="
                        width:100%; height:100%; border:none; outline:none;
                        background:transparent; color:var(--text-primary); font-size:18px; text-align:center;">
                </div>
                <div style="min-width:0; flex:1;">
                    <input type="text" id="page-title" value="<?= htmlspecialchars($currentTitle) ?>" placeholder="Sem título" class="notion-title">
                    <div style="font-size:12px; color:var(--text-secondary);">
                        <?php if (!$current): ?>
                            Clique em <b>+ Nova</b> para criar sua primeira página.
                        <?php else: ?>
                            <?= $canEdit ? 'Você pode editar.' : 'Somente leitura.' ?> Digite <b>/</b> para inserir blocos.
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; justify-content:flex-end;">
                <?php if ($current && $canEdit): ?>
                    <button type="button" id="btn-save" style="
                        border:1px solid var(--border-subtle); border-radius:999px; padding:7px 12px;
                        background:var(--surface-subtle); color:var(--text-primary); font-size:12px; cursor:pointer;">
                        Salvar
                    </button>
                <?php endif; ?>
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

        <div class="notion-page-body">
            <div class="notion-editor-wrap">
                <?php if (!$current): ?>
                    <div style="padding: 18px; border: 1px dashed var(--border-subtle); border-radius: 12px; color: var(--text-secondary);">
                        Crie uma página e comece a digitar. Dica: use <b>/</b> para inserir blocos.
                    </div>
                <?php else: ?>
                    <div id="editorjs" style="background:transparent;"></div>
                <?php endif; ?>
                <div id="editor-hint" class="notion-editor-hint"></div>
            </div>
        </div>
    </div>
</div>

<div class="tuq-ctx" id="tuq-ctx">
    <input class="tuq-ctx-search" id="tuq-ctx-search" type="text" placeholder="Pesquisar ações..." />
    <div class="tuq-ctx-item" data-action="toggle-transform">
        <span>Transformar em</span>
        <small>›</small>
    </div>
    <div class="tuq-ctx-item" data-action="toggle-color">
        <span>Cor</span>
        <small>›</small>
    </div>
    <div class="tuq-ctx-item" data-action="duplicate">
        <span>Duplicar</span>
        <small>Ctrl+D</small>
    </div>
    <div class="tuq-ctx-item" data-action="delete">
        <span>Excluir</span>
        <small>Del</small>
    </div>

    <div class="tuq-ctx-sub" id="tuq-ctx-sub-transform">
        <div class="tuq-ctx-item" data-action="transform" data-to="header" data-level="1"><span>Título 1</span></div>
        <div class="tuq-ctx-item" data-action="transform" data-to="header" data-level="2"><span>Título 2</span></div>
        <div class="tuq-ctx-item" data-action="transform" data-to="header" data-level="3"><span>Título 3</span></div>
        <div class="tuq-ctx-item" data-action="transform" data-to="list" data-style="unordered"><span>Lista com marcadores</span></div>
        <div class="tuq-ctx-item" data-action="transform" data-to="list" data-style="ordered"><span>Lista numerada</span></div>
        <div class="tuq-ctx-item" data-action="transform" data-to="checklist"><span>Lista de tarefas</span></div>
        <div class="tuq-ctx-item" data-action="transform" data-to="quote"><span>Citação</span></div>
        <div class="tuq-ctx-item" data-action="transform" data-to="code"><span>Código</span></div>
    </div>

    <div class="tuq-ctx-sub" id="tuq-ctx-sub-color">
        <div style="font-size:12px; color:var(--text-secondary); padding: 6px 10px 4px 10px;">Cor do texto</div>
        <div class="tuq-ctx-item" data-action="color" data-kind="text" data-value="gray"><span>Texto cinza</span></div>
        <div class="tuq-ctx-item" data-action="color" data-kind="text" data-value="red"><span>Texto vermelho</span></div>
        <div class="tuq-ctx-item" data-action="color" data-kind="text" data-value="yellow"><span>Texto amarelo</span></div>
        <div class="tuq-ctx-item" data-action="color" data-kind="text" data-value="green"><span>Texto verde</span></div>
        <div class="tuq-ctx-item" data-action="color" data-kind="text" data-value="blue"><span>Texto azul</span></div>

        <div style="font-size:12px; color:var(--text-secondary); padding: 10px 10px 4px 10px;">Cor de fundo</div>
        <div class="tuq-ctx-item" data-action="color" data-kind="bg" data-value="gray"><span>Fundo cinza</span></div>
        <div class="tuq-ctx-item" data-action="color" data-kind="bg" data-value="brown"><span>Fundo marrom</span></div>
        <div class="tuq-ctx-item" data-action="color" data-kind="bg" data-value="yellow"><span>Fundo amarelo</span></div>
        <div class="tuq-ctx-item" data-action="color" data-kind="bg" data-value="blue"><span>Fundo azul</span></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@editorjs/editorjs@2.28.2"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/header@2.8.1/dist/bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/list@1.9.0/dist/bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/checklist@1.6.0/dist/bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/quote@2.5.0/dist/bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/code@2.8.0/dist/bundle.min.js"></script>
<script>
(function () {
    var pageId = <?= (int)$currentId ?>;
    var canEdit = <?= $canEdit ? 'true' : 'false' ?>;
    var isOwner = <?= $isOwner ? 'true' : 'false' ?>;
    var initialJson = <?= json_encode($contentJson !== '' ? $contentJson : '') ?>;

    var $ = function (id) { return document.getElementById(id); };

    function postForm(url, data) {
        var fd = new FormData();
        Object.keys(data || {}).forEach(function (k) { fd.append(k, data[k]); });
        return fetch(url, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: fd
        }).then(function (r) {
            var ct = (r.headers && r.headers.get) ? (r.headers.get('content-type') || '') : '';
            if (ct.toLowerCase().indexOf('application/json') >= 0) {
                return r.json().then(function (j) { return { ok: r.ok, status: r.status, json: j, text: null }; });
            }
            return r.text().then(function (t) {
                return { ok: r.ok, status: r.status, json: null, text: t || '' };
            });
        }).catch(function (err) {
            return { ok: false, status: 0, json: null, text: String(err || 'Erro') };
        });
    }

    function showActionError(res, fallback) {
        var msg = fallback || 'Falha ao executar ação.';
        if (res && res.json && res.json.error) msg = String(res.json.error);
        else if (res && res.status === 403) msg = 'Sem permissão ou sem assinatura ativa.';
        else if (res && res.status === 401) msg = 'Você precisa estar logado.';
        else if (res && res.status && res.status >= 500) msg = 'Erro no servidor.';
        setHint(msg);
        try { alert(msg); } catch (e) {}
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

    function getMissingEditorTools() {
        var missing = [];
        if (typeof EditorJS === 'undefined') missing.push('EditorJS');
        if (typeof Header === 'undefined') missing.push('Header');
        if (typeof List === 'undefined') missing.push('List');
        if (typeof Checklist === 'undefined') missing.push('Checklist');
        if (typeof Quote === 'undefined') missing.push('Quote');
        if (typeof CodeTool === 'undefined') missing.push('CodeTool');
        return missing;
    }

    var editorInitError = false;
    var editor = null;
    if (pageId) {
        var missingTools = getMissingEditorTools();
        if (missingTools.length) {
            editorInitError = true;
            setHint('Erro ao carregar editor: ' + missingTools.join(', ') + '. Recarregue a página.');
            try { console.error('Editor tools missing:', missingTools); } catch (e) {}
        } else {
            try {
                editor = new EditorJS({
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
            } catch (e) {
                editorInitError = true;
                setHint('Erro ao iniciar editor. Recarregue a página.');
                try { console.error('Editor init error:', e); } catch (err) {}
            }
        }
    }

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
        if (!editor) {
            saving = false;
            return;
        }
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

    if (!editorInitError && canEdit && editor) {
        document.addEventListener('keyup', function (e) {
            if (!e) return;
            debounceSave();
        }, true);
        document.addEventListener('mouseup', function () { debounceSave(); }, true);
    } else {
        if (!editorInitError && pageId && !canEdit) {
            setHint('Somente leitura (sem permissão de edição).');
        }
    }

    var btnSave = $('btn-save');
    if (btnSave && !editorInitError && canEdit && pageId) {
        btnSave.addEventListener('click', function () {
            scheduleSave();
        });
    }

    // Context menu (MVP): aplica ações no bloco atual
    var ctx = $('tuq-ctx');
    var ctxSearch = $('tuq-ctx-search');
    var subTransform = $('tuq-ctx-sub-transform');
    var subColor = $('tuq-ctx-sub-color');
    var currentBlockIndex = null;

    function hideCtx() {
        if (!ctx) return;
        ctx.style.display = 'none';
        if (subTransform) subTransform.style.display = 'none';
        if (subColor) subColor.style.display = 'none';
        if (ctxSearch) ctxSearch.value = '';
        currentBlockIndex = null;
    }

    function clamp(v, min, max) {
        return Math.min(max, Math.max(min, v));
    }

    function showCtx(x, y) {
        if (!ctx) return;
        var w = 280;
        var h = 360;
        var vx = clamp(x, 8, (window.innerWidth || 1200) - w - 8);
        var vy = clamp(y, 8, (window.innerHeight || 800) - h - 8);
        ctx.style.left = vx + 'px';
        ctx.style.top = vy + 'px';
        ctx.style.display = 'block';
        if (subTransform) subTransform.style.display = 'none';
        if (subColor) subColor.style.display = 'none';
        setTimeout(function () { if (ctxSearch) ctxSearch.focus(); }, 10);
    }

    function getBlockIndexFromTarget(target) {
        if (!target) return null;
        var el = target.nodeType === 3 ? target.parentElement : target;
        if (!el || !el.closest) return null;
        var block = el.closest('.ce-block');
        if (!block) return null;
        if (!block.parentElement) return null;
        var blocks = Array.prototype.slice.call(document.querySelectorAll('.ce-block'));
        var idx = blocks.indexOf(block);
        return idx >= 0 ? idx : null;
    }

    function applyVisualColorToBlock(kind, value) {
        if (currentBlockIndex === null) return;
        var blocks = Array.prototype.slice.call(document.querySelectorAll('.ce-block'));
        var block = blocks[currentBlockIndex];
        if (!block) return;

        var classes = block.className.split(/\s+/).filter(Boolean);
        classes = classes.filter(function (c) {
            return c.indexOf('tuq-block--c-') !== 0 && c.indexOf('tuq-block--bg-') !== 0;
        });
        if (kind === 'text') {
            classes.push('tuq-block--c-' + value);
        } else if (kind === 'bg') {
            classes.push('tuq-block--bg-' + value);
        }
        block.className = classes.join(' ');
    }

    function getBlockText(block) {
        if (!block) return '';
        try {
            if (block.type === 'header') {
                return (block.data && block.data.text) ? String(block.data.text) : '';
            }
            if (block.type === 'paragraph') {
                return (block.data && block.data.text) ? String(block.data.text) : '';
            }
            if (block.type === 'quote') {
                return (block.data && block.data.text) ? String(block.data.text) : '';
            }
            if (block.type === 'code') {
                return (block.data && block.data.code) ? String(block.data.code) : '';
            }
        } catch (e) {}
        return '';
    }

    function transformBlock(to, opts) {
        if (!editor || currentBlockIndex === null) return;
        var idx = currentBlockIndex;
        var cur = null;
        try {
            cur = editor.blocks.getBlockByIndex(idx);
        } catch (e) { cur = null; }
        if (!cur) return;

        var txt = getBlockText(cur);
        var data = {};
        if (to === 'header') {
            data = { text: txt, level: parseInt((opts && opts.level) || '2', 10) || 2 };
        } else if (to === 'quote') {
            data = { text: txt, caption: '' };
        } else if (to === 'code') {
            data = { code: txt };
        } else if (to === 'list') {
            data = { style: (opts && opts.style) ? opts.style : 'unordered', items: txt ? [txt] : [] };
        } else if (to === 'checklist') {
            data = { items: txt ? [{ text: txt, checked: false }] : [] };
        }

        try {
            editor.blocks.insert(to, data, {}, idx, true);
            editor.blocks.delete(idx + 1);
            currentBlockIndex = idx;
        } catch (e) {}
    }

    function duplicateBlock() {
        if (!editor || currentBlockIndex === null) return;
        var idx = currentBlockIndex;
        var cur = null;
        try { cur = editor.blocks.getBlockByIndex(idx); } catch (e) { cur = null; }
        if (!cur) return;
        try {
            editor.blocks.insert(cur.type, cur.data || {}, cur.tunes || {}, idx + 1, true);
        } catch (e) {}
    }

    function deleteBlock() {
        if (!editor || currentBlockIndex === null) return;
        try { editor.blocks.delete(currentBlockIndex); } catch (e) {}
        currentBlockIndex = null;
    }

    if (editor && canEdit) {
        document.addEventListener('contextmenu', function (e) {
            var target = e && e.target ? e.target : null;
            if (!target) return;
            var inside = false;
            try {
                inside = !!(target.closest && target.closest('#editorjs'));
            } catch (err) { inside = false; }
            if (!inside) return;
            e.preventDefault();
            currentBlockIndex = getBlockIndexFromTarget(target);
            showCtx(e.clientX, e.clientY);
        });

        document.addEventListener('click', function (e) {
            if (!ctx || ctx.style.display !== 'block') return;
            var t = e && e.target ? e.target : null;
            if (t && (t === ctx || (t.closest && t.closest('#tuq-ctx')))) return;
            hideCtx();
        });

        document.addEventListener('keydown', function (e) {
            if (!e) return;
            if (e.key === 'Escape') hideCtx();
            if (e.key === 'Delete' && ctx && ctx.style.display === 'block') {
                e.preventDefault();
                deleteBlock();
                hideCtx();
            }
            if ((e.ctrlKey || e.metaKey) && (e.key || '').toLowerCase() === 'd' && ctx && ctx.style.display === 'block') {
                e.preventDefault();
                duplicateBlock();
                hideCtx();
            }
        });

        if (ctx) {
            ctx.addEventListener('click', function (e) {
                var t = e && e.target ? e.target : null;
                if (!t) return;
                if (t && t.nodeType === 3) t = t.parentElement;
                var item = t && t.closest ? t.closest('.tuq-ctx-item') : null;
                if (!item) return;

                var action = item.getAttribute('data-action');
                if (action === 'toggle-transform') {
                    if (subTransform) subTransform.style.display = (subTransform.style.display === 'block') ? 'none' : 'block';
                    if (subColor) subColor.style.display = 'none';
                    return;
                }
                if (action === 'toggle-color') {
                    if (subColor) subColor.style.display = (subColor.style.display === 'block') ? 'none' : 'block';
                    if (subTransform) subTransform.style.display = 'none';
                    return;
                }
                if (action === 'duplicate') {
                    duplicateBlock();
                    hideCtx();
                    return;
                }
                if (action === 'delete') {
                    deleteBlock();
                    hideCtx();
                    return;
                }
                if (action === 'transform') {
                    transformBlock(item.getAttribute('data-to'), {
                        level: item.getAttribute('data-level'),
                        style: item.getAttribute('data-style')
                    });
                    hideCtx();
                    debounceSave();
                    return;
                }
                if (action === 'color') {
                    applyVisualColorToBlock(item.getAttribute('data-kind'), item.getAttribute('data-value'));
                    hideCtx();
                    return;
                }
            });
        }

        if (ctxSearch) {
            ctxSearch.addEventListener('input', function () {
                var q = (ctxSearch.value || '').toLowerCase().trim();
                var items = Array.prototype.slice.call(ctx.querySelectorAll('.tuq-ctx-item'));
                items.forEach(function (it) {
                    var text = (it.textContent || '').toLowerCase();
                    if (q === '' || text.indexOf(q) >= 0) {
                        it.style.display = '';
                    } else {
                        it.style.display = 'none';
                    }
                });
            });
        }
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
        if (!canEdit) return;

        var timer = null;
        function doRename() {
            postForm('/caderno/renomear', {
                page_id: String(pageId),
                title: title.value || 'Sem título',
                icon: icon.value || ''
            }).then(function (res) {
                if (res && res.json && res.json.ok) return;
                if (res && res.text && res.text !== '') {
                    showActionError(res, 'Falha ao renomear.');
                    return;
                }
                if (res && res.json && res.json.error) {
                    showActionError(res, 'Falha ao renomear.');
                }
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
                } else {
                    showActionError(res, 'Falha ao excluir.');
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
                } else {
                    showActionError(res, 'Falha ao compartilhar.');
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
                } else {
                    showActionError(res, 'Falha ao publicar.');
                }
            });
        });
    }
})();
</script>
