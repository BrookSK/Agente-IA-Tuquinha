<?php
/** @var array $user */
/** @var array $profileUser */
/** @var array $item */
/** @var array $blocks */
/** @var bool|null $isOwner */
/** @var bool|null $canEdit */

$itemId = (int)($item['id'] ?? 0);
$title = (string)($item['title'] ?? 'Projeto');
$cover = trim((string)($item['cover_url'] ?? ''));
$status = (string)($item['status'] ?? 'draft');
?>
<style>
    @media (max-width: 980px) {
        #portfolioEditorLayout {
            grid-template-columns: 1fr !important;
        }
        #portfolioEditorCanvas {
            min-height: 420px !important;
        }
    }
    .peTool {
        border: 1px solid var(--border-subtle);
        background: var(--surface-subtle);
        color: var(--text-primary);
        border-radius: 12px;
        padding: 10px 12px;
        font-size: 12px;
        cursor: grab;
        user-select: none;
    }
    .peTool:active {
        cursor: grabbing;
    }
    .peBlock {
        border: 1px solid var(--border-subtle);
        background: var(--surface-card);
        border-radius: 14px;
        overflow: hidden;
    }
</style>

<div style="max-width: 1200px; margin: 0 auto; display:flex; flex-direction:column; gap:12px;">
    <section style="background:var(--surface-card); border-radius:16px; border:1px solid var(--border-subtle); padding:12px 14px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">
            <div style="min-width:0;">
                <div style="font-size:12px; color:var(--text-secondary);">Editor do projeto</div>
                <div style="font-size:16px; font-weight:800; color:var(--text-primary); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width: 820px;">
                    <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>
            <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap; justify-content:flex-end;">
                <a href="/perfil/portfolio/gerenciar?owner_user_id=<?= (int)($item['user_id'] ?? 0) ?>&edit_id=<?= (int)$itemId ?>" style="border-radius:999px; padding:7px 12px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:12px; text-decoration:none;">Detalhes</a>
                <a href="/perfil/portfolio/ver?id=<?= (int)$itemId ?>" target="_blank" rel="noopener noreferrer" style="border-radius:999px; padding:7px 12px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:12px; text-decoration:none;">Preview</a>
                <button type="button" id="peSaveBtn" style="border:none; border-radius:999px; padding:7px 12px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-size:12px; font-weight:800; cursor:pointer;">Salvar rascunho</button>
                <?php if ($status !== 'published'): ?>
                    <form action="/perfil/portfolio/publicar" method="post" style="margin:0;" onsubmit="return confirm('Publicar este projeto? Depois de publicado, ficará visível para todos.');">
                        <input type="hidden" name="item_id" value="<?= (int)$itemId ?>" />
                        <button type="submit" style="border:none; border-radius:999px; padding:7px 12px; background:#1f6feb; color:#fff; font-size:12px; font-weight:800; cursor:pointer;">Publicar</button>
                    </form>
                <?php else: ?>
                    <form action="/perfil/portfolio/despublicar" method="post" style="margin:0;" onsubmit="return confirm('Despublicar este projeto? Ele voltará para rascunho e ficará oculto para visitantes.');">
                        <input type="hidden" name="item_id" value="<?= (int)$itemId ?>" />
                        <button type="submit" style="border:1px solid var(--border-subtle); border-radius:999px; padding:7px 12px; background:var(--surface-subtle); color:#ffbaba; font-size:12px; font-weight:800; cursor:pointer;">Despublicar</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <div id="peFeedback" style="display:none; margin-top:8px; font-size:12px;"></div>
    </section>

    <div id="portfolioEditorLayout" style="display:grid; grid-template-columns: 1fr 340px; gap:12px; align-items:start;">
        <section id="portfolioEditorCanvas" style="background:var(--surface-card); border-radius:16px; border:1px solid var(--border-subtle); padding:12px 14px; min-height: 560px;">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">
                <div>
                    <div style="font-size:14px; font-weight:750; color:var(--text-primary);">Página</div>
                    <div style="font-size:12px; color:var(--text-secondary);">Arraste blocos da direita para montar seu projeto.</div>
                </div>
                <div style="font-size:12px; color:var(--text-secondary);">ID: <?= (int)$itemId ?></div>
            </div>

            <div id="peCanvasDrop" style="margin-top:12px; border-radius:16px; border:2px dashed rgba(255,255,255,0.16); background: rgba(255,255,255,0.02); padding:16px; min-height: 460px;">
                <div id="peCanvasBlocks" style="display:flex; flex-direction:column; gap:12px;"></div>
                <div id="peCanvasEmpty" style="color:var(--text-secondary); font-size:12px; padding:10px;">Solte aqui para adicionar um bloco.</div>
            </div>
        </section>

        <aside style="background:var(--surface-card); border-radius:16px; border:1px solid var(--border-subtle); padding:12px 14px; position:sticky; top:12px;">
            <div style="font-size:14px; font-weight:750; color:var(--text-primary);">Blocos</div>
            <div style="font-size:12px; color:var(--text-secondary); margin-top:4px;">Arraste para a página.</div>

            <div style="margin-top:12px; display:flex; flex-direction:column; gap:10px;">
                <div class="peTool" draggable="true" data-type="text">Texto</div>
                <div class="peTool" draggable="true" data-type="image">Imagem</div>
                <div class="peTool" draggable="true" data-type="gallery">PhotoGrid</div>
                <div class="peTool" draggable="true" data-type="video">Vídeo</div>
                <div class="peTool" draggable="true" data-type="audio">Áudio</div>
                <div class="peTool" draggable="true" data-type="embed">Código/Embed</div>
            </div>

            <div style="margin-top:12px; border-top:1px solid var(--border-subtle); padding-top:12px;">
                <div style="font-size:12px; color:var(--text-secondary);">Capa atual</div>
                <div style="margin-top:8px; border-radius:14px; overflow:hidden; border:1px solid var(--border-subtle); background:var(--surface-subtle);">
                    <?php if ($cover !== ''): ?>
                        <img src="<?= htmlspecialchars($cover, ENT_QUOTES, 'UTF-8') ?>" alt="Capa" style="width:100%; height:160px; object-fit:cover; display:block;" />
                    <?php else: ?>
                        <div style="height:160px; display:flex; align-items:center; justify-content:center; color:var(--text-secondary); font-size:12px;">Sem capa</div>
                    <?php endif; ?>
                </div>
                <div style="margin-top:8px; font-size:12px; color:var(--text-secondary);">Dica: a capa é preenchida automaticamente pela primeira imagem/grade que você colocar.</div>
            </div>
        </aside>
    </div>
</div>

<script>
(function(){
    var itemId = <?= (int)$itemId ?>;
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

    function uploadFile(file){
        var fd = new FormData();
        fd.append('item_id', String(itemId));
        fd.append('file', file);
        return fetch('/perfil/portfolio/blocos/upload', {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function(res){ return res.json().catch(function(){ return null; }); });
    }

    function render(){
        var wrap = document.getElementById('peCanvasBlocks');
        var empty = document.getElementById('peCanvasEmpty');
        if (!wrap) return;
        wrap.innerHTML = '';
        if (empty) empty.style.display = state.length ? 'none' : 'block';

        state.forEach(function(b, idx){
            var block = el('div');
            block.className = 'peBlock';
            block.setAttribute('data-idx', String(idx));

            var head = el('div');
            head.style.display = 'flex';
            head.style.alignItems = 'center';
            head.style.justifyContent = 'space-between';
            head.style.gap = '10px';
            head.style.padding = '10px 12px';
            head.style.borderBottom = '1px solid var(--border-subtle)';
            head.style.background = 'var(--surface-subtle)';

            var tag = el('div', { text: String((b.type || 'text')).toUpperCase() });
            tag.style.fontSize = '11px';
            tag.style.color = 'var(--text-secondary)';
            tag.style.fontWeight = '800';

            var actions = el('div');
            actions.style.display = 'flex';
            actions.style.gap = '8px';
            actions.style.flexWrap = 'wrap';

            function aBtn(txt){
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

            var up = aBtn('↑');
            var down = aBtn('↓');
            var del = aBtn('Excluir');
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
            head.appendChild(tag);
            head.appendChild(actions);
            block.appendChild(head);

            var body = el('div');
            body.style.padding = '12px';

            if (b.type === 'text') {
                var ta = el('textarea');
                ta.rows = 6;
                ta.value = b.text || '';
                ta.style.width = '100%';
                ta.style.padding = '10px 12px';
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
                preview.style.flex = '1 1 320px';
                preview.style.minWidth = '240px';
                preview.style.border = '1px solid var(--border-subtle)';
                preview.style.borderRadius = '12px';
                preview.style.overflow = 'hidden';
                preview.style.background = 'var(--surface-subtle)';
                preview.style.minHeight = '160px';
                preview.style.display = 'flex';
                preview.style.alignItems = 'center';
                preview.style.justifyContent = 'center';
                preview.style.color = 'var(--text-secondary)';
                preview.style.fontSize = '12px';

                function renderImage(){
                    if (b.media_url) {
                        preview.innerHTML = '<img src="' + String(b.media_url).replace(/"/g,'&quot;') + '" style="width:100%; height:100%; object-fit:cover; display:block;" />';
                    } else {
                        preview.textContent = 'Sem imagem';
                    }
                }
                renderImage();

                input.addEventListener('change', async function(){
                    if (!input.files || !input.files[0]) return;
                    input.disabled = true;
                    try {
                        var json = await uploadFile(input.files[0]);
                        if (json && json.ok && json.url) {
                            b.media_url = json.url;
                            b.media_mime = json.mime_type || null;
                            renderImage();
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
                grid.style.gridTemplateColumns = 'repeat(auto-fill, minmax(160px, 1fr))';
                grid.style.gap = '10px';

                function renderGrid(){
                    grid.innerHTML = '';
                    b.media.forEach(function(m, midx){
                        var box = el('div');
                        box.style.border = '1px solid var(--border-subtle)';
                        box.style.borderRadius = '12px';
                        box.style.overflow = 'hidden';
                        box.style.background = 'var(--surface-subtle)';

                        var img = el('img');
                        img.src = m.url;
                        img.style.width = '100%';
                        img.style.height = '120px';
                        img.style.objectFit = 'cover';
                        img.style.display = 'block';

                        var rm = el('button', { type: 'button', text: 'Excluir' });
                        rm.style.width = '100%';
                        rm.style.border = 'none';
                        rm.style.borderTop = '1px solid var(--border-subtle)';
                        rm.style.background = 'var(--surface-card)';
                        rm.style.color = '#ffbaba';
                        rm.style.padding = '8px 10px';
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
                            var json = await uploadFile(inputG.files[i]);
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
                urlIn.style.padding = '10px 12px';
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
                    upV.disabled = true;
                    try {
                        var json = await uploadFile(upV.files[0]);
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

            if (b.type === 'audio') {
                var upA = el('input', { type: 'file', accept: 'audio/*' });
                upA.style.fontSize = '12px';

                var p = el('div');
                p.style.marginTop = '10px';
                p.style.border = '1px solid var(--border-subtle)';
                p.style.borderRadius = '12px';
                p.style.background = 'var(--surface-subtle)';
                p.style.padding = '10px 12px';
                p.style.color = 'var(--text-secondary)';
                p.style.fontSize = '12px';

                function renderAudio(){
                    if (b.media_url) {
                        p.innerHTML = '<audio controls style="width:100%;"><source src="' + String(b.media_url).replace(/"/g,'&quot;') + '"></audio>';
                    } else {
                        p.textContent = 'Envie um áudio.';
                    }
                }
                renderAudio();

                upA.addEventListener('change', async function(){
                    if (!upA.files || !upA.files[0]) return;
                    upA.disabled = true;
                    try {
                        var json = await uploadFile(upA.files[0]);
                        if (json && json.ok && json.url) {
                            b.media_url = json.url;
                            b.media_mime = json.mime_type || null;
                            renderAudio();
                        }
                    } catch(e) {
                    } finally {
                        upA.disabled = false;
                    }
                });

                body.appendChild(upA);
                body.appendChild(p);
            }

            if (b.type === 'embed') {
                var ta2 = el('textarea');
                ta2.rows = 6;
                ta2.value = b.text || '';
                ta2.placeholder = 'Cole aqui um código embed ou HTML (ex: iframe)';
                ta2.style.width = '100%';
                ta2.style.padding = '10px 12px';
                ta2.style.borderRadius = '12px';
                ta2.style.border = '1px solid var(--border-subtle)';
                ta2.style.background = 'var(--surface-card)';
                ta2.style.color = 'var(--text-primary)';
                ta2.style.resize = 'vertical';
                ta2.addEventListener('input', function(){ b.text = ta2.value; });
                body.appendChild(ta2);
            }

            block.appendChild(body);
            wrap.appendChild(block);
        });
    }

    function addBlock(type){
        type = String(type || 'text');
        var b = { type: type };
        if (type === 'text') b.text = '';
        if (type === 'gallery') b.media = [];
        if (type === 'embed') b.text = '';
        state.push(b);
        render();
    }

    document.querySelectorAll('.peTool').forEach(function(tool){
        tool.addEventListener('dragstart', function(ev){
            ev.dataTransfer.setData('text/plain', tool.getAttribute('data-type') || 'text');
            ev.dataTransfer.effectAllowed = 'copy';
        });
    });

    var drop = document.getElementById('peCanvasDrop');
    if (drop) {
        drop.addEventListener('dragover', function(ev){
            ev.preventDefault();
            ev.dataTransfer.dropEffect = 'copy';
        });
        drop.addEventListener('drop', function(ev){
            ev.preventDefault();
            var type = ev.dataTransfer.getData('text/plain') || 'text';
            addBlock(type);
        });
    }

    var saveBtn = document.getElementById('peSaveBtn');
    var fb = document.getElementById('peFeedback');

    if (saveBtn) {
        saveBtn.addEventListener('click', async function(){
            saveBtn.disabled = true;
            if (fb) fb.style.display = 'none';
            try {
                var payload = state.map(function(b){
                    var out = { type: b.type };
                    if (b.type === 'text') out.text = b.text || '';
                    if (b.type === 'image') { out.media_url = b.media_url || ''; out.media_mime = b.media_mime || null; }
                    if (b.type === 'gallery') { out.media = Array.isArray(b.media) ? b.media : []; }
                    if (b.type === 'video') { out.media_url = b.media_url || ''; out.media_mime = b.media_mime || null; }
                    if (b.type === 'audio') { out.media_url = b.media_url || ''; out.media_mime = b.media_mime || null; }
                    if (b.type === 'embed') { out.text = b.text || ''; }
                    return out;
                });

                var fd = new FormData();
                fd.append('item_id', String(itemId));
                fd.append('blocks_json', JSON.stringify(payload));

                var res = await fetch('/perfil/portfolio/blocos/salvar', {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                var json = await res.json().catch(function(){ return null; });
                if (fb) {
                    fb.style.display = 'block';
                    fb.style.color = (json && json.ok) ? '#c8ffd4' : '#ffbaba';
                    var msg = (json && json.ok) ? 'Rascunho salvo.' : ((json && json.error) ? json.error : 'Não foi possível salvar.');
                    if (json && json.ok && Array.isArray(json.warnings) && json.warnings.length) {
                        msg = msg + ' ' + json.warnings.join(' ');
                        fb.style.color = '#ffe7a1';
                    }
                    fb.textContent = msg;
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
    }

    render();
})();
</script>
