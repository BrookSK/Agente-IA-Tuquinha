<?php
/** @var array $user */
/** @var array $events */
/** @var array $shares */
/** @var string|null $publicToken */
/** @var bool $canShare */
/** @var int $year */
/** @var int $month */

$monthNames = ['','Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
$dayNames = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
$typeLabels = ['post'=>'Post','story'=>'Story','reels'=>'Reels','video'=>'Vídeo','email'=>'E-mail','anuncio'=>'Anúncio','outro'=>'Outro'];
$statusLabels = ['planejado'=>'Planejado','produzido'=>'Produzido','postado'=>'Postado'];

$today = date('Y-m-d');
$firstDay = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = (int)date('t', $firstDay);
$startWeekday = (int)date('w', $firstDay);

$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

// Group events by day
$eventsByDay = [];
foreach ($events as $ev) {
    $d = (int)date('j', strtotime($ev['event_date']));
    $eventsByDay[$d][] = $ev;
}

$publicUrl = $publicToken ? (((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/agenda-marketing/publico?token=' . urlencode($publicToken)) : null;
?>

<style>
.mc-wrap { max-width: 1100px; margin: 0 auto; }
.mc-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:18px; flex-wrap:wrap; gap:10px; }
.mc-header h1 { font-size:22px; font-weight:700; }
.mc-nav { display:flex; align-items:center; gap:8px; }
.mc-nav a { display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:999px; border:1px solid var(--border-subtle); background:var(--surface-card); color:var(--text-primary); font-size:16px; text-decoration:none; transition:background .15s; }
.mc-nav a:hover { background:rgba(229,57,53,0.12); }
.mc-nav span { font-size:16px; font-weight:600; min-width:180px; text-align:center; }
.mc-actions { display:flex; gap:8px; flex-wrap:wrap; }
.mc-btn { border:none; border-radius:999px; padding:8px 16px; font-size:13px; font-weight:600; cursor:pointer; transition:filter .15s; display:inline-flex; align-items:center; gap:6px; }
.mc-btn-primary { background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; }
.mc-btn-secondary { background:var(--surface-card); border:1px solid var(--border-subtle); color:var(--text-primary); }
.mc-btn:hover { filter:brightness(1.1); }

.mc-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:1px; background:var(--border-subtle); border-radius:14px; overflow:hidden; border:1px solid var(--border-subtle); }
.mc-day-header { background:var(--surface-card); padding:8px 4px; text-align:center; font-size:12px; font-weight:600; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.08em; }
.mc-day { background:var(--bg-secondary); min-height:100px; padding:6px; position:relative; cursor:default; transition:background .12s; }
.mc-day:hover { background:rgba(229,57,53,0.04); }
.mc-day.mc-empty { background:var(--bg-main); opacity:.4; }
.mc-day-num { font-size:13px; font-weight:600; margin-bottom:4px; color:var(--text-secondary); }
.mc-day.mc-today .mc-day-num { background:linear-gradient(135deg,#e53935,#ff6f60); color:#fff; width:26px; height:26px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; }
.mc-event { font-size:11px; padding:3px 6px; border-radius:6px; margin-bottom:3px; cursor:pointer; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; color:#fff; font-weight:500; transition:filter .12s; }
.mc-event:hover { filter:brightness(1.2); }

/* Modal */
.mc-modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:1000; align-items:center; justify-content:center; }
.mc-modal-overlay.active { display:flex; }
.mc-modal { background:var(--bg-secondary); border:1px solid var(--border-subtle); border-radius:16px; width:95%; max-width:520px; max-height:90vh; overflow-y:auto; padding:24px; position:relative; box-shadow:var(--shadow-card-strong); }
.mc-modal h2 { font-size:18px; font-weight:700; margin-bottom:14px; }
.mc-modal-close { position:absolute; top:12px; right:14px; background:none; border:none; color:var(--text-secondary); font-size:22px; cursor:pointer; }
.mc-field { margin-bottom:12px; }
.mc-field label { display:block; font-size:13px; color:var(--text-primary); margin-bottom:4px; font-weight:500; }
.mc-field input, .mc-field select, .mc-field textarea { width:100%; padding:8px 10px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px; font-family:inherit; }
.mc-field textarea { resize:vertical; min-height:70px; }
.mc-color-options { display:flex; gap:6px; flex-wrap:wrap; }
.mc-color-opt { width:28px; height:28px; border-radius:50%; cursor:pointer; border:2px solid transparent; transition:border-color .15s, transform .1s; }
.mc-color-opt:hover, .mc-color-opt.selected { border-color:#fff; transform:scale(1.15); }
.mc-links-list { display:flex; flex-direction:column; gap:6px; }
.mc-link-row { display:flex; gap:6px; align-items:center; }
.mc-link-row input { flex:1; }
.mc-link-remove { background:none; border:none; color:#e53935; font-size:18px; cursor:pointer; padding:0 4px; }

/* Share panel */
.mc-share-panel { margin-top:18px; padding:16px; border-radius:14px; border:1px solid var(--border-subtle); background:var(--surface-card); }
.mc-share-panel h3 { font-size:15px; font-weight:600; margin-bottom:10px; }
.mc-share-row { display:flex; align-items:center; gap:8px; padding:6px 0; border-bottom:1px solid var(--border-subtle); font-size:13px; }
.mc-share-row:last-child { border-bottom:none; }

/* Detail modal */
.mc-detail-field { margin-bottom:10px; }
.mc-detail-label { font-size:11px; text-transform:uppercase; letter-spacing:.08em; color:var(--text-secondary); margin-bottom:2px; }
.mc-detail-value { font-size:14px; color:var(--text-primary); }
.mc-status-badge { display:inline-block; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:600; }

@media (max-width:700px) {
    .mc-day { min-height:60px; padding:3px; }
    .mc-event { font-size:10px; padding:2px 4px; }
    .mc-day-num { font-size:11px; }
    .mc-header h1 { font-size:18px; }
}
</style>

<div class="mc-wrap">
    <div class="mc-header">
        <h1>📅 Agenda de Marketing</h1>
        <div class="mc-actions">
            <button class="mc-btn mc-btn-primary" onclick="mcOpenCreate()">+ Novo conteúdo</button>
            <?php if ($canShare): ?>
                <button class="mc-btn mc-btn-secondary" onclick="mcToggleShare()">🔗 Compartilhar</button>
            <?php endif; ?>
            <button class="mc-btn mc-btn-secondary" onclick="mcToggleApi()">🔌 API</button>
        </div>
    </div>

    <div class="mc-nav">
        <a href="/agenda-marketing?year=<?= $prevYear ?>&month=<?= $prevMonth ?>" title="Mês anterior">‹</a>
        <span><?= $monthNames[$month] ?> <?= $year ?></span>
        <a href="/agenda-marketing?year=<?= $nextYear ?>&month=<?= $nextMonth ?>" title="Próximo mês">›</a>
    </div>

    <div class="mc-grid" style="margin-top:14px;">
        <?php foreach ($dayNames as $dn): ?>
            <div class="mc-day-header"><?= $dn ?></div>
        <?php endforeach; ?>

        <?php for ($i = 0; $i < $startWeekday; $i++): ?>
            <div class="mc-day mc-empty"></div>
        <?php endfor; ?>

        <?php for ($d = 1; $d <= $daysInMonth; $d++):
            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
            $isToday = ($dateStr === $today);
            $dayEvents = $eventsByDay[$d] ?? [];
        ?>
            <div class="mc-day<?= $isToday ? ' mc-today' : '' ?>">
                <div class="mc-day-num"><?= $d ?></div>
                <?php foreach ($dayEvents as $ev): ?>
                    <div class="mc-event" style="background:<?= htmlspecialchars($ev['color'] ?? '#e53935') ?>" onclick="mcShowEvent(<?= (int)$ev['id'] ?>)" title="<?= htmlspecialchars($ev['title']) ?>">
                        <?= htmlspecialchars($ev['title']) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endfor; ?>

        <?php
            $totalCells = $startWeekday + $daysInMonth;
            $remaining = (7 - ($totalCells % 7)) % 7;
            for ($i = 0; $i < $remaining; $i++):
        ?>
            <div class="mc-day mc-empty"></div>
        <?php endfor; ?>
    </div>

    <!-- Share Panel -->
    <?php if ($canShare): ?>
    <div class="mc-share-panel" id="mc-share-panel" style="display:none;">
        <h3>Compartilhar agenda</h3>

        <!-- Public link -->
        <div style="margin-bottom:14px;">
            <div style="font-size:13px; font-weight:500; margin-bottom:6px;">Link público</div>
            <?php if ($publicUrl): ?>
                <div style="display:flex; gap:6px; align-items:center;">
                    <input type="text" id="mc-public-url" readonly value="<?= htmlspecialchars($publicUrl) ?>" style="flex:1; padding:7px 10px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:12px;">
                    <button class="mc-btn mc-btn-secondary" onclick="navigator.clipboard.writeText(document.getElementById('mc-public-url').value)" style="padding:6px 12px; font-size:12px;">Copiar</button>
                    <button class="mc-btn mc-btn-secondary" onclick="mcTogglePublish(false)" style="padding:6px 12px; font-size:12px; color:#e53935;">Desativar</button>
                </div>
            <?php else: ?>
                <button class="mc-btn mc-btn-secondary" onclick="mcTogglePublish(true)" style="font-size:12px;">Gerar link público</button>
            <?php endif; ?>
        </div>

        <!-- Private shares -->
        <div style="font-size:13px; font-weight:500; margin-bottom:6px;">Compartilhar com pessoa</div>
        <div style="display:flex; gap:6px; margin-bottom:10px;">
            <input type="email" id="mc-share-email" placeholder="E-mail do usuário" style="flex:1; padding:7px 10px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
            <select id="mc-share-role" style="padding:7px 10px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                <option value="view">Visualizar</option>
                <option value="edit">Editar</option>
            </select>
            <button class="mc-btn mc-btn-primary" onclick="mcShareAdd()" style="padding:6px 14px; font-size:12px;">Adicionar</button>
        </div>
        <div id="mc-shares-list">
            <?php foreach ($shares as $s): ?>
                <div class="mc-share-row">
                    <span style="flex:1;"><?= htmlspecialchars($s['name'] ?? $s['email']) ?> (<?= htmlspecialchars($s['email']) ?>)</span>
                    <span style="font-size:11px; color:var(--text-secondary);"><?= $s['role'] === 'edit' ? 'Editar' : 'Visualizar' ?></span>
                    <button class="mc-link-remove" onclick="mcShareRemove(<?= (int)$s['shared_with_user_id'] ?>)">×</button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- API Integration Panel -->
    <div class="mc-share-panel" id="mc-api-panel" style="display:none; margin-top:12px;">
        <h3>🔌 Integração via API</h3>
        <p style="font-size:12px; color:var(--text-secondary); margin-bottom:12px;">
            Use a API para sincronizar eventos com outros sistemas. Autentique com <code>Authorization: Bearer &lt;api_key&gt;</code>.
        </p>

        <div style="margin-bottom:12px;">
            <div style="font-size:13px; font-weight:500; margin-bottom:6px;">Suas API Keys</div>
            <div id="mc-api-keys-list" style="margin-bottom:8px; font-size:13px; color:var(--text-secondary);">Carregando...</div>
            <div style="display:flex; gap:6px;">
                <input type="text" id="mc-api-key-label" placeholder="Nome da integração (ex: Sistema X)" style="flex:1; padding:7px 10px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                <button class="mc-btn mc-btn-primary" onclick="mcGenerateApiKey()" style="padding:6px 14px; font-size:12px;">Gerar chave</button>
            </div>
        </div>

        <details style="margin-top:14px;">
            <summary style="cursor:pointer; font-size:13px; font-weight:600; color:var(--text-primary);">📖 Documentação da API</summary>
            <div style="font-size:12px; color:var(--text-secondary); margin-top:10px; line-height:1.7;">
                <p style="margin-bottom:8px;"><strong>Base URL:</strong> <code id="mc-api-base-url"></code></p>

                <p style="margin-bottom:4px;"><strong>Autenticação:</strong> Envie o header ou use query param:</p>
                <pre style="background:var(--surface-subtle); padding:8px 10px; border-radius:8px; overflow-x:auto; font-size:11px; margin-bottom:4px;">Authorization: Bearer tuq_sua_chave_aqui</pre>
                <p style="margin-bottom:10px; font-size:11px; color:var(--text-secondary);">Ou passe <code>?api_token=tuq_sua_chave_aqui</code> na URL como fallback.</p>

                <p style="margin-bottom:4px; font-weight:600;">GET /api/marketing-calendar/events</p>
                <p style="margin-bottom:2px;">Lista eventos do mês. Parâmetros: <code>year</code>, <code>month</code>.</p>
                <pre style="background:var(--surface-subtle); padding:8px 10px; border-radius:8px; overflow-x:auto; font-size:11px; margin-bottom:10px;">GET /api/marketing-calendar/events?year=2026&month=4</pre>

                <p style="margin-bottom:4px; font-weight:600;">GET /api/marketing-calendar/events/show?id=ID</p>
                <p style="margin-bottom:10px;">Retorna um evento específico.</p>

                <p style="margin-bottom:4px; font-weight:600;">POST /api/marketing-calendar/events</p>
                <p style="margin-bottom:2px;">Cria um evento. Body JSON:</p>
                <pre style="background:var(--surface-subtle); padding:8px 10px; border-radius:8px; overflow-x:auto; font-size:11px; margin-bottom:10px;">{
  "title": "Post de lançamento",
  "event_date": "2026-04-20",
  "event_type": "post",
  "status": "planejado",
  "responsible": "João",
  "color": "#e53935",
  "notes": "Ideias...",
  "reference_links": ["https://exemplo.com"]
}</pre>
                <p style="margin-bottom:2px; font-size:11px;">Tipos: <code>post</code>, <code>story</code>, <code>reels</code>, <code>video</code>, <code>email</code>, <code>anuncio</code>, <code>outro</code></p>
                <p style="margin-bottom:10px; font-size:11px;">Status: <code>planejado</code>, <code>produzido</code>, <code>postado</code></p>

                <p style="margin-bottom:4px; font-weight:600;">POST /api/marketing-calendar/events/update</p>
                <p style="margin-bottom:2px;">Atualiza um evento. Body JSON (inclua <code>id</code> + campos a alterar):</p>
                <pre style="background:var(--surface-subtle); padding:8px 10px; border-radius:8px; overflow-x:auto; font-size:11px; margin-bottom:10px;">{ "id": 123, "title": "Novo título", "status": "produzido" }</pre>

                <p style="margin-bottom:4px; font-weight:600;">POST /api/marketing-calendar/events/delete</p>
                <p style="margin-bottom:2px;">Exclui um evento. Body JSON:</p>
                <pre style="background:var(--surface-subtle); padding:8px 10px; border-radius:8px; overflow-x:auto; font-size:11px; margin-bottom:10px;">{ "id": 123 }</pre>

                <p style="margin-top:10px; font-size:11px; color:var(--text-secondary);">Todas as respostas retornam JSON com <code>{ "ok": true/false, ... }</code>. Erros incluem <code>"error"</code>.</p>
            </div>
        </details>
    </div>

    <!-- Create/Edit Modal -->
    <div class="mc-modal-overlay" id="mc-create-modal">
        <div class="mc-modal">
            <button class="mc-modal-close" onclick="mcCloseCreate()">×</button>
            <h2 id="mc-form-title">Novo conteúdo</h2>
            <input type="hidden" id="mc-edit-id" value="">
            <div class="mc-field">
                <label>Título *</label>
                <input type="text" id="mc-title" placeholder="Ex: Post sobre lançamento">
            </div>
            <div class="mc-field">
                <label>Data *</label>
                <input type="date" id="mc-date" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="mc-field">
                <label>Tipo</label>
                <select id="mc-type">
                    <option value="post">Post</option>
                    <option value="story">Story</option>
                    <option value="reels">Reels</option>
                    <option value="video">Vídeo</option>
                    <option value="email">E-mail</option>
                    <option value="anuncio">Anúncio</option>
                    <option value="outro">Outro</option>
                </select>
            </div>
            <div class="mc-field">
                <label>Status</label>
                <select id="mc-status">
                    <option value="planejado">Planejado</option>
                    <option value="produzido">Produzido</option>
                    <option value="postado">Postado</option>
                </select>
            </div>
            <div class="mc-field">
                <label>Responsável</label>
                <input type="text" id="mc-responsible" placeholder="Nome do responsável">
            </div>
            <div class="mc-field">
                <label>Cor do evento</label>
                <div class="mc-color-options" id="mc-colors">
                    <?php
                    $colors = ['#e53935','#ff6f60','#fb8c00','#fdd835','#43a047','#00acc1','#1e88e5','#5e35b1','#8e24aa','#6d4c41','#546e7a','#ec407a'];
                    foreach ($colors as $c):
                    ?>
                        <div class="mc-color-opt" style="background:<?= $c ?>" data-color="<?= $c ?>" onclick="mcSelectColor(this)"></div>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" id="mc-color" value="#e53935">
            </div>
            <div class="mc-field">
                <label>Notas</label>
                <textarea id="mc-notes" placeholder="Ideias, referências, observações..."></textarea>
            </div>
            <div class="mc-field">
                <label>Links de referência</label>
                <div class="mc-links-list" id="mc-links">
                    <div class="mc-link-row">
                        <input type="url" class="mc-link-input" placeholder="https://...">
                        <button class="mc-link-remove" onclick="mcRemoveLink(this)">×</button>
                    </div>
                </div>
                <button class="mc-btn mc-btn-secondary" onclick="mcAddLink()" style="margin-top:6px; padding:5px 12px; font-size:12px;">+ Adicionar link</button>
            </div>
            <div style="display:flex; gap:8px; margin-top:16px;">
                <button class="mc-btn mc-btn-primary" onclick="mcSave()" style="flex:1;">Salvar</button>
                <button class="mc-btn mc-btn-secondary" onclick="mcCloseCreate()">Cancelar</button>
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div class="mc-modal-overlay" id="mc-detail-modal">
        <div class="mc-modal">
            <button class="mc-modal-close" onclick="mcCloseDetail()">×</button>
            <h2 id="mc-detail-title" style="margin-bottom:16px;"></h2>
            <div id="mc-detail-body"></div>
            <div style="display:flex; gap:8px; margin-top:18px;">
                <button class="mc-btn mc-btn-secondary" onclick="mcEditFromDetail()" style="flex:1;">✏️ Editar</button>
                <button class="mc-btn mc-btn-secondary" onclick="mcDeleteFromDetail()" style="color:#e53935;">🗑 Excluir</button>
            </div>
        </div>
    </div>
</div>

<script>
const typeLabels = <?= json_encode($typeLabels) ?>;
const statusLabels = <?= json_encode($statusLabels) ?>;
let currentDetailEvent = null;

function mcOpenCreate() {
    document.getElementById('mc-edit-id').value = '';
    document.getElementById('mc-form-title').textContent = 'Novo conteúdo';
    document.getElementById('mc-title').value = '';
    document.getElementById('mc-date').value = '<?= date('Y-m-d') ?>';
    document.getElementById('mc-type').value = 'post';
    document.getElementById('mc-status').value = 'planejado';
    document.getElementById('mc-responsible').value = '';
    document.getElementById('mc-color').value = '#e53935';
    document.getElementById('mc-notes').value = '';
    document.querySelectorAll('.mc-color-opt').forEach(el => el.classList.remove('selected'));
    document.querySelector('.mc-color-opt[data-color="#e53935"]')?.classList.add('selected');
    document.getElementById('mc-links').innerHTML = '<div class="mc-link-row"><input type="url" class="mc-link-input" placeholder="https://..."><button class="mc-link-remove" onclick="mcRemoveLink(this)">×</button></div>';
    document.getElementById('mc-create-modal').classList.add('active');
}

function mcCloseCreate() {
    document.getElementById('mc-create-modal').classList.remove('active');
}

function mcSelectColor(el) {
    document.querySelectorAll('.mc-color-opt').forEach(e => e.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('mc-color').value = el.dataset.color;
}

function mcAddLink() {
    const row = document.createElement('div');
    row.className = 'mc-link-row';
    row.innerHTML = '<input type="url" class="mc-link-input" placeholder="https://..."><button class="mc-link-remove" onclick="mcRemoveLink(this)">×</button>';
    document.getElementById('mc-links').appendChild(row);
}

function mcRemoveLink(btn) {
    const list = document.getElementById('mc-links');
    if (list.children.length > 1) {
        btn.parentElement.remove();
    } else {
        btn.parentElement.querySelector('input').value = '';
    }
}

function mcSave() {
    const id = document.getElementById('mc-edit-id').value;
    const fd = new FormData();
    if (id) fd.append('id', id);
    fd.append('title', document.getElementById('mc-title').value);
    fd.append('event_date', document.getElementById('mc-date').value);
    fd.append('event_type', document.getElementById('mc-type').value);
    fd.append('status', document.getElementById('mc-status').value);
    fd.append('responsible', document.getElementById('mc-responsible').value);
    fd.append('color', document.getElementById('mc-color').value);
    fd.append('notes', document.getElementById('mc-notes').value);
    document.querySelectorAll('.mc-link-input').forEach(inp => {
        if (inp.value.trim()) fd.append('reference_links[]', inp.value.trim());
    });

    const url = id ? '/agenda-marketing/atualizar' : '/agenda-marketing/criar';
    fetch(url, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                location.reload();
            } else {
                alert(data.error || 'Erro ao salvar.');
            }
        })
        .catch(() => alert('Erro de conexão.'));
}

function mcShowEvent(id) {
    fetch('/agenda-marketing/evento?id=' + id, { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
            if (!data.ok) { alert(data.error || 'Erro'); return; }
            currentDetailEvent = data.event;
            const ev = data.event;
            document.getElementById('mc-detail-title').textContent = ev.title;

            const statusColors = { planejado: '#fb8c00', produzido: '#1e88e5', postado: '#43a047' };
            let html = '';
            html += '<div class="mc-detail-field"><div class="mc-detail-label">Data</div><div class="mc-detail-value">' + ev.event_date + '</div></div>';
            html += '<div class="mc-detail-field"><div class="mc-detail-label">Tipo</div><div class="mc-detail-value">' + (typeLabels[ev.event_type] || ev.event_type) + '</div></div>';
            html += '<div class="mc-detail-field"><div class="mc-detail-label">Status</div><div class="mc-detail-value"><span class="mc-status-badge" style="background:' + (statusColors[ev.status] || '#546e7a') + ';color:#fff;">' + (statusLabels[ev.status] || ev.status) + '</span></div></div>';
            if (ev.responsible) html += '<div class="mc-detail-field"><div class="mc-detail-label">Responsável</div><div class="mc-detail-value">' + escHtml(ev.responsible) + '</div></div>';
            html += '<div class="mc-detail-field"><div class="mc-detail-label">Cor</div><div class="mc-detail-value"><span style="display:inline-block;width:20px;height:20px;border-radius:50%;background:' + ev.color + ';vertical-align:middle;"></span></div></div>';
            if (ev.notes) html += '<div class="mc-detail-field"><div class="mc-detail-label">Notas</div><div class="mc-detail-value" style="white-space:pre-wrap;">' + escHtml(ev.notes) + '</div></div>';
            if (ev.reference_links && ev.reference_links.length) {
                html += '<div class="mc-detail-field"><div class="mc-detail-label">Links de referência</div>';
                ev.reference_links.forEach(l => {
                    html += '<div class="mc-detail-value"><a href="' + escHtml(l) + '" target="_blank" rel="noopener" style="color:var(--accent-soft);text-decoration:underline;font-size:13px;">' + escHtml(l) + '</a></div>';
                });
                html += '</div>';
            }
            document.getElementById('mc-detail-body').innerHTML = html;
            document.getElementById('mc-detail-modal').classList.add('active');
        })
        .catch(() => alert('Erro de conexão.'));
}

function escHtml(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

function mcCloseDetail() {
    document.getElementById('mc-detail-modal').classList.remove('active');
    currentDetailEvent = null;
}

function mcEditFromDetail() {
    if (!currentDetailEvent) return;
    const ev = currentDetailEvent;
    mcCloseDetail();
    document.getElementById('mc-edit-id').value = ev.id;
    document.getElementById('mc-form-title').textContent = 'Editar conteúdo';
    document.getElementById('mc-title').value = ev.title;
    document.getElementById('mc-date').value = ev.event_date;
    document.getElementById('mc-type').value = ev.event_type;
    document.getElementById('mc-status').value = ev.status;
    document.getElementById('mc-responsible').value = ev.responsible || '';
    document.getElementById('mc-color').value = ev.color;
    document.getElementById('mc-notes').value = ev.notes || '';
    document.querySelectorAll('.mc-color-opt').forEach(el => {
        el.classList.toggle('selected', el.dataset.color === ev.color);
    });
    const linksContainer = document.getElementById('mc-links');
    linksContainer.innerHTML = '';
    const links = ev.reference_links && ev.reference_links.length ? ev.reference_links : [''];
    links.forEach(l => {
        const row = document.createElement('div');
        row.className = 'mc-link-row';
        row.innerHTML = '<input type="url" class="mc-link-input" placeholder="https://..." value="' + escHtml(l) + '"><button class="mc-link-remove" onclick="mcRemoveLink(this)">×</button>';
        linksContainer.appendChild(row);
    });
    document.getElementById('mc-create-modal').classList.add('active');
}

function mcDeleteFromDetail() {
    if (!currentDetailEvent) return;
    if (!confirm('Tem certeza que deseja excluir este evento?')) return;
    const fd = new FormData();
    fd.append('id', currentDetailEvent.id);
    fetch('/agenda-marketing/excluir', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            if (data.ok) location.reload();
            else alert(data.error || 'Erro ao excluir.');
        })
        .catch(() => alert('Erro de conexão.'));
}

function mcToggleShare() {
    const panel = document.getElementById('mc-share-panel');
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
}

function mcTogglePublish(publish) {
    const fd = new FormData();
    fd.append('publish', publish ? '1' : '');
    fetch('/agenda-marketing/publicar', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            if (data.ok) location.reload();
            else alert(data.error || 'Erro.');
        })
        .catch(() => alert('Erro de conexão.'));
}

function mcShareAdd() {
    const email = document.getElementById('mc-share-email').value.trim();
    const role = document.getElementById('mc-share-role').value;
    if (!email) { alert('Informe o e-mail.'); return; }
    const fd = new FormData();
    fd.append('email', email);
    fd.append('role', role);
    fetch('/agenda-marketing/compartilhar/adicionar', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            if (data.ok) location.reload();
            else alert(data.error || 'Erro.');
        })
        .catch(() => alert('Erro de conexão.'));
}

function mcShareRemove(userId) {
    if (!confirm('Remover acesso?')) return;
    const fd = new FormData();
    fd.append('user_id', userId);
    fetch('/agenda-marketing/compartilhar/remover', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            if (data.ok) location.reload();
            else alert(data.error || 'Erro.');
        })
        .catch(() => alert('Erro de conexão.'));
}

// API Panel
function mcToggleApi() {
    const panel = document.getElementById('mc-api-panel');
    if (panel.style.display === 'none') {
        panel.style.display = 'block';
        mcLoadApiKeys();
        document.getElementById('mc-api-base-url').textContent = location.origin;
    } else {
        panel.style.display = 'none';
    }
}

function mcLoadApiKeys() {
    fetch('/agenda-marketing/api-keys', { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
            const list = document.getElementById('mc-api-keys-list');
            if (!data.ok || !data.keys || data.keys.length === 0) {
                list.innerHTML = '<div style="font-size:12px; color:var(--text-secondary);">Nenhuma chave gerada.</div>';
                return;
            }
            let html = '';
            data.keys.forEach(k => {
                const active = k.is_active == 1;
                html += '<div class="mc-share-row">';
                html += '<span style="flex:1;">' + escHtml(k.label || 'Sem nome') + ' — <code>' + escHtml(k.api_key_masked) + '</code></span>';
                html += '<span style="font-size:11px; color:' + (active ? '#43a047' : '#e53935') + ';">' + (active ? 'Ativa' : 'Revogada') + '</span>';
                if (active) {
                    html += '<button class="mc-link-remove" onclick="mcRevokeApiKey(' + k.id + ')">×</button>';
                }
                html += '</div>';
            });
            list.innerHTML = html;
        })
        .catch(() => {
            document.getElementById('mc-api-keys-list').innerHTML = '<div style="color:#e53935;">Erro ao carregar.</div>';
        });
}

function mcGenerateApiKey() {
    const label = document.getElementById('mc-api-key-label').value.trim() || 'Integração';
    const fd = new FormData();
    fd.append('label', label);
    fetch('/agenda-marketing/api-key/gerar', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            if (data.ok && data.api_key) {
                prompt('Copie sua chave agora (ela não será exibida novamente):', data.api_key);
                document.getElementById('mc-api-key-label').value = '';
                mcLoadApiKeys();
            } else {
                alert(data.error || 'Erro ao gerar chave.');
            }
        })
        .catch(() => alert('Erro de conexão.'));
}

function mcRevokeApiKey(id) {
    if (!confirm('Revogar esta chave? Ela deixará de funcionar imediatamente.')) return;
    const fd = new FormData();
    fd.append('id', id);
    fetch('/agenda-marketing/api-key/revogar', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            if (data.ok) mcLoadApiKeys();
            else alert(data.error || 'Erro.');
        })
        .catch(() => alert('Erro de conexão.'));
}
</script>
