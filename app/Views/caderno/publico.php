<?php
/** @var array $page */
$title = (string)($page['title'] ?? 'Caderno');
$icon = trim((string)($page['icon'] ?? ''));
$contentJson = (string)($page['content_json'] ?? '');
?>

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

<script src="https://cdn.jsdelivr.net/npm/@editorjs/editorjs@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/header@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/list@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/checklist@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/quote@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/code@latest"></script>
<script src="https://unpkg.com/@editorjs/image@2.10.1/dist/image.umd.js"></script>
<script src="https://unpkg.com/@editorjs/attaches@1.3.0/dist/bundle.js"></script>
<script>
(function () {
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

    new EditorJS({
        holder: 'public-editor',
        readOnly: true,
        data: data,
        tools: {
            header: { class: Header, inlineToolbar: false, config: { levels: [1,2,3], defaultLevel: 2 } },
            list: { class: List, inlineToolbar: false },
            checklist: { class: Checklist, inlineToolbar: false },
            quote: { class: Quote, inlineToolbar: false },
            code: { class: CodeTool },
            image: { class: ImageTool, inlineToolbar: false },
            attaches: { class: AttachesTool, inlineToolbar: false }
        }
    });
})();
</script>
