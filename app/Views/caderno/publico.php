<?php
/** @var array $page */
$title = (string)($page['title'] ?? 'Caderno');
$icon = trim((string)($page['icon'] ?? ''));
$contentJson = (string)($page['content_json'] ?? '');
?>

<style>
    #public-editor .cdx-attaches {
        border: 1px solid var(--border-subtle);
        border-radius: 12px;
        background: rgba(255,255,255,0.06);
        color: var(--text-primary);
        box-shadow: none;
    }
    body[data-theme="light"] #public-editor .cdx-attaches {
        background: rgba(15,23,42,0.04);
    }
    #public-editor .cdx-attaches__title {
        color: var(--text-primary) !important;
        font-weight: 650;
    }
    #public-editor .cdx-attaches__size {
        color: var(--text-secondary) !important;
        opacity: 0.95;
    }
    #public-editor .cdx-attaches__download-button {
        border-radius: 10px;
        background: rgba(255,255,255,0.08) !important;
        border: 1px solid var(--border-subtle) !important;
        color: var(--text-primary) !important;
    }
    body[data-theme="light"] #public-editor .cdx-attaches__download-button {
        background: rgba(15,23,42,0.06) !important;
    }
    #public-editor .cdx-attaches__download-button:hover {
        background: rgba(255,255,255,0.14) !important;
    }
    body[data-theme="light"] #public-editor .cdx-attaches__download-button:hover {
        background: rgba(15,23,42,0.10) !important;
    }
    #public-editor .cdx-attaches__download-button svg {
        fill: currentColor !important;
        color: var(--text-primary) !important;
        opacity: 0.95;
    }
    #public-editor .cdx-attaches__file-icon {
        border-radius: 12px;
        border: 1px solid var(--border-subtle);
        box-shadow: none;
    }

    #public-editor .cdx-quote__caption { display:none !important; }
    #public-editor .image-tool__caption { display:none !important; }
</style>

<div style="max-width: 880px; margin: 0 auto;">
    <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
        <div style="width:42px; height:42px; border-radius:12px; display:flex; align-items:center; justify-content:center; background:var(--surface-subtle); border:1px solid var(--border-subtle); font-size:18px;">
            <?= $icon !== '' ? htmlspecialchars($icon) : '📄' ?>
        </div>
        <div style="min-width:0;">
            <h1 style="margin:0; font-size:22px; font-weight:800;"><?= htmlspecialchars($title) ?></h1>
            <div style="font-size:12px; color:var(--text-secondary);">Página pública (somente leitura)</div>
        </div>
    </div>

    <div style="border:1px solid var(--border-subtle); border-radius:12px; background:var(--surface-card); padding:14px;">
        <div id="public-editor"></div>
    </div>
</div>

<script src="https://unpkg.com/@editorjs/editorjs@2.28.2"></script>
<script src="https://unpkg.com/@editorjs/header@2.8.1/dist/header.umd.js"></script>
<script src="https://unpkg.com/@editorjs/list@1.9.0/dist/list.umd.js"></script>
<script src="https://unpkg.com/@editorjs/checklist@1.6.0/dist/checklist.umd.js"></script>
<script src="https://unpkg.com/@editorjs/quote@2.5.0/dist/bundle.js"></script>
<script src="https://unpkg.com/@editorjs/code@2.8.0/dist/bundle.js"></script>
<script src="https://unpkg.com/@editorjs/image@2.10.1/dist/image.umd.js"></script>
<script src="https://unpkg.com/@editorjs/attaches@1.3.0/dist/bundle.js"></script>
<script>
(function () {
    var token = <?= json_encode((string)($_GET['token'] ?? '')) ?>;
    var raw = <?= json_encode($contentJson !== '' ? $contentJson : '') ?>;
    var data = null;
    try {
        if (raw && typeof raw === 'string') {
            data = JSON.parse(raw);
            if (data && typeof data === 'string') {
                data = JSON.parse(data);
            }
        }
    } catch (e) {}
    if (!data) data = { time: Date.now(), blocks: [] };

    function esc(s) {
        s = String(s === null || typeof s === 'undefined' ? '' : s);
        return s
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function SubpageTool(opts) {
        opts = opts || {};
        this.api = opts.api;
        this.data = opts.data || {};
        this.readOnly = true;
    }
    SubpageTool.toolbox = { title: 'Subpágina', icon: '↳' };
    SubpageTool.isReadOnlySupported = true;
    SubpageTool.prototype.render = function () {
        var d = this.data || {};
        var id = String(d.id || '');
        var title = String(d.title || 'Sem título');

        var wrap = document.createElement('div');
        wrap.className = 'tuq-subpage-card';
        try { wrap.contentEditable = 'false'; } catch (e) {}

        var a = document.createElement('a');
        a.className = 'tuq-subpage-inline';
        var href = '/caderno/publico?token=' + encodeURIComponent(String(token || '')) + '&id=' + encodeURIComponent(id);
        a.setAttribute('href', href);
        a.setAttribute('draggable', 'false');
        a.style.cssText = 'display:flex; align-items:center; gap:10px; width:100%; padding:12px 12px; border-radius:12px; border:1px solid var(--border-subtle); background: var(--surface-subtle); color: var(--text-primary); text-decoration:none;';

        var iconBox = document.createElement('div');
        iconBox.style.width = '34px';
        iconBox.style.height = '34px';
        iconBox.style.borderRadius = '10px';
        iconBox.style.display = 'flex';
        iconBox.style.alignItems = 'center';
        iconBox.style.justifyContent = 'center';
        iconBox.style.background = 'rgba(255,255,255,0.06)';
        iconBox.style.border = '1px solid var(--border-subtle)';
        iconBox.textContent = '↳';

        var text = document.createElement('div');
        text.style.minWidth = '0';
        text.innerHTML = '<div style="font-weight:750;">' + esc(title) + '</div>'
            + '<div style="font-size:12px; color:var(--text-secondary);">Abrir subpágina</div>';

        a.appendChild(iconBox);
        a.appendChild(text);
        wrap.appendChild(a);
        return wrap;
    };
    SubpageTool.prototype.save = function () {
        return this.data || {};
    };

    new EditorJS({
        holder: 'public-editor',
        readOnly: true,
        data: data,
        tools: {
            subpage: { class: SubpageTool },
            header: { class: (window.Header || null), inlineToolbar: false, config: { levels: [1,2,3], defaultLevel: 2 } },
            list: { class: (window.List || null), inlineToolbar: false },
            checklist: { class: (window.Checklist || null), inlineToolbar: false },
            quote: { class: (window.Quote || null), inlineToolbar: false },
            code: { class: (window.CodeTool || null) },
            image: { class: (window.ImageTool || null), inlineToolbar: false },
            attaches: { class: (window.AttachesTool || null), inlineToolbar: false }
        }
    });
})();
</script>
