<?php

// Branding colors
$primaryColor = !empty($branding['primary_color']) ? $branding['primary_color'] : '#e53935';
$secondaryColor = !empty($branding['secondary_color']) ? $branding['secondary_color'] : '#ff6f60';
$accentColor = !empty($branding['accent_color']) ? $branding['accent_color'] : '#4caf50';

?>
<style>
    .friends-add-btn-primary {
        background: linear-gradient(135deg, <?= $primaryColor ?>, <?= $secondaryColor ?>);
        border: none;
        color: #fff;
    }
    .friends-add-btn-primary:hover {
        opacity: 0.9;
        color: #fff;
    }
    .friends-add-badge-sent {
        background: rgba(<?= hexdec(substr($accentColor, 1, 2)) ?>, <?= hexdec(substr($accentColor, 3, 2)) ?>, <?= hexdec(substr($accentColor, 5, 2)) ?>, 0.1);
        color: <?= $accentColor ?>;
        border: 1px solid rgba(<?= hexdec(substr($accentColor, 1, 2)) ?>, <?= hexdec(substr($accentColor, 3, 2)) ?>, <?= hexdec(substr($accentColor, 5, 2)) ?>, 0.3);
    }
</style>

<!-- Header -->
<div style="margin-bottom: 32px;">
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
        <h1 style="font-size: 32px; font-weight: 800; color: var(--text-primary); margin: 0;">Adicionar Amigo</h1>
        <a href="/painel-externo/amigos" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; background: linear-gradient(135deg, <?= $primaryColor ?>, <?= $secondaryColor ?>); color: #fff; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600;">
            ← Voltar para Amigos
        </a>
    </div>
</div>

<!-- Search Section -->
<div style="max-width: 800px;">
    <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 32px; margin-bottom: 24px;">
        <h2 style="font-size: 20px; font-weight: 700; color: var(--text-primary); margin: 0 0 8px 0;">Buscar Usuários</h2>
        <p style="font-size: 14px; color: var(--text-secondary); margin: 0 0 24px 0;">
            Pesquise por <strong>nickname</strong> (ex: <code style="background: var(--bg-main); border: 1px solid var(--border); padding: 2px 8px; border-radius: 6px; font-size: 13px;">@joao_silva</code>) ou por <strong>e-mail</strong>.
        </p>

        <div style="margin-bottom: 16px;">
            <input
                id="friendSearchInput"
                type="text"
                placeholder="Digite @nickname ou email e pressione Enter"
                style="width: 100%; background: var(--bg-main); border: 1px solid var(--border); color: var(--text-primary); padding: 14px 18px; border-radius: 10px; font-size: 15px; outline: none;"
                autocomplete="off"
            />
            <div id="friendSearchHint" style="font-size: 13px; color: var(--text-secondary); margin-top: 8px;">💡 Dica: você também pode só digitar que ele vai buscar automaticamente.</div>
        </div>

        <div id="friendSearchStatus" style="display:none; margin-top:16px; font-size:14px;"></div>
        <div id="friendSearchResults" style="display:flex; flex-direction:column; gap:12px; margin-top:20px;"></div>
    </div>

    <!-- Instructions -->
    <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 24px;">
        <h3 style="font-size: 18px; font-weight: 700; color: var(--text-primary); margin: 0 0 16px 0;">Como funciona?</h3>
        <ul style="font-size: 14px; color: var(--text-secondary); line-height: 1.8; padding-left: 20px; margin: 0;">
            <li style="margin-bottom: 8px;">Digite o nickname ou e-mail do usuário</li>
            <li style="margin-bottom: 8px;">Aguarde os resultados aparecerem</li>
            <li style="margin-bottom: 8px;">Clique em "Enviar solicitação" para adicionar</li>
            <li>Aguarde a pessoa aceitar seu pedido</li>
        </ul>
    </div>
</div>

<script>
(function(){
    var input = document.getElementById('friendSearchInput');
    var results = document.getElementById('friendSearchResults');
    var statusEl = document.getElementById('friendSearchStatus');
    if (!input || !results) return;

    function setStatus(text, ok) {
        if (!statusEl) return;
        if (!text) {
            statusEl.style.display = 'none';
            statusEl.textContent = '';
            return;
        }
        statusEl.style.display = 'block';
        statusEl.style.color = ok ? '#c8ffd4' : '#ffbaba';
        statusEl.textContent = text;
    }

    function escapeHtml(s){
        return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\"/g,'&quot;').replace(/'/g,'&#039;');
    }

    function setRightMode(rightEl, mode, userId){
        if (!rightEl) return;
        rightEl.innerHTML = '';

        if (mode === 'sent') {
            var sent = document.createElement('span');
            sent.textContent = '✓ Enviado';
            sent.className = 'badge friends-add-badge-sent';
            sent.style.display = 'inline-block';
            sent.style.fontSize = '13px';
            sent.style.fontWeight = '600';
            sent.style.padding = '8px 14px';
            sent.style.borderRadius = '8px';

            var cancelBtn = document.createElement('button');
            cancelBtn.type = 'button';
            cancelBtn.textContent = 'Cancelar';
            cancelBtn.className = 'btn btn-outline-danger btn-sm';
            cancelBtn.style.border = '1px solid rgba(220, 53, 69, 0.3)';
            cancelBtn.style.borderRadius = '8px';
            cancelBtn.style.padding = '8px 14px';
            cancelBtn.style.background = 'transparent';
            cancelBtn.style.color = '#dc3545';
            cancelBtn.style.fontWeight = '600';
            cancelBtn.style.fontSize = '13px';
            cancelBtn.style.cursor = 'pointer';
            cancelBtn.addEventListener('click', function(){ cancelRequest(userId, rightEl, cancelBtn); });

            rightEl.style.display = 'flex';
            rightEl.style.alignItems = 'center';
            rightEl.style.gap = '10px';
            rightEl.appendChild(sent);
            rightEl.appendChild(cancelBtn);
            return;
        }

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = 'Enviar solicitação';
        btn.className = 'btn friends-add-btn-primary';
        btn.style.borderRadius = '8px';
        btn.style.padding = '10px 20px';
        btn.style.fontWeight = '600';
        btn.style.fontSize = '14px';
        btn.style.cursor = 'pointer';
        btn.style.whiteSpace = 'nowrap';
        btn.addEventListener('click', function(){ sendRequest(userId, rightEl, btn); });
        rightEl.appendChild(btn);
    }

    async function sendRequest(userId, rightEl, btn){
        if (!userId) return;
        var fd = new FormData();
        fd.append('user_id', String(userId));
        if (btn) btn.disabled = true;
        try {
            var res = await fetch('/painel-externo/amigos/solicitar', {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            var json = await res.json().catch(function(){ return null; });
            if (res.status === 401) {
                window.location.href = '/login';
                return;
            }
            if (!res.ok || !json || !json.ok) {
                setStatus((json && json.error) ? json.error : 'Não foi possível enviar o pedido.', false);
                return;
            }
            setStatus('Pedido de amizade enviado.', true);
            setRightMode(rightEl, 'sent', userId);
        } catch (e) {
            setStatus('Não foi possível enviar o pedido.', false);
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    async function cancelRequest(userId, rightEl, btn){
        if (!userId) return;
        var fd = new FormData();
        fd.append('user_id', String(userId));
        if (btn) btn.disabled = true;
        try {
            var res = await fetch('/painel-externo/amigos/cancelar', {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            var json = await res.json().catch(function(){ return null; });
            if (res.status === 401) {
                window.location.href = '/login';
                return;
            }
            if (!res.ok || !json || !json.ok) {
                setStatus((json && json.error) ? json.error : 'Não foi possível cancelar o pedido.', false);
                return;
            }
            setStatus('Pedido de amizade cancelado.', true);
            setRightMode(rightEl, 'send', userId);
        } catch (e) {
            setStatus('Não foi possível cancelar o pedido.', false);
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    function render(items){
        results.innerHTML = '';
        if (!items || !items.length) {
            results.innerHTML = '<div class="text-center py-4" style="font-size:14px; color:var(--text-secondary);">Nenhum usuário encontrado.</div>';
            return;
        }
        items.forEach(function(u){
            var name = (u.preferred_name || u.name || 'Usuário');
            var nick = (u.nickname || '').trim();
            var email = (u.email || '').trim();

            var div = document.createElement('div');
            div.className = 'card';
            div.style.background = 'var(--bg-main)';
            div.style.border = '1px solid var(--border)';
            div.style.borderRadius = '12px';
            div.style.padding = '16px';
            div.style.display = 'flex';
            div.style.justifyContent = 'space-between';
            div.style.alignItems = 'center';
            div.style.gap = '16px';

            var left = document.createElement('div');
            left.style.minWidth = '0';
            left.style.flex = '1';
            left.innerHTML = '<div style="font-size:16px; color:var(--text-primary); font-weight:600; margin-bottom:4px;">' + escapeHtml(name) + '</div>'
                + (nick ? '<div style="font-size:13px; color:var(--text-secondary);">@' + escapeHtml(nick) + '</div>' : '')
                + (email ? '<div style="font-size:13px; color:var(--text-secondary);">' + escapeHtml(email) + '</div>' : '');

            var right = document.createElement('div');
            right.style.flexShrink = '0';
            setRightMode(right, 'send', u.id);

            div.appendChild(left);
            div.appendChild(right);
            results.appendChild(div);
        });
    }

    var lastTerm = '';
    var t = null;
    async function search(term){
        term = String(term || '').trim();
        if (!term) {
            results.innerHTML = '';
            setStatus('', true);
            return;
        }
        lastTerm = term;
        setStatus('Buscando...', true);
        try {
            var url = '/painel-externo/amigos/buscar?q=' + encodeURIComponent(term);
            var res = await fetch(url, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            var json = await res.json().catch(function(){ return null; });
            if (res.status === 401) {
                window.location.href = '/login';
                return;
            }
            if (!res.ok || !json || !json.ok) {
                setStatus((json && json.error) ? json.error : 'Erro ao buscar usuários.', false);
                return;
            }
            setStatus('', true);
            render(json.items || []);
        } catch (e) {
            setStatus('Erro ao buscar usuários.', false);
        }
    }

    input.addEventListener('keydown', function(e){
        if (e.key === 'Enter') {
            e.preventDefault();
            search(input.value);
        }
    });

    input.addEventListener('input', function(){
        window.clearTimeout(t);
        t = window.setTimeout(function(){
            search(input.value);
        }, 400);
    });
})();
</script>
