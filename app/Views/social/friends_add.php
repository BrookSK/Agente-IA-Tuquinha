<?php

?>
<div style="max-width: 980px; margin: 0 auto; display:flex; flex-direction:column; gap:14px;">
    <section style="background:#111118; border-radius:16px; border:1px solid #272727; padding:12px 14px;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom:10px;">
            <h1 style="font-size:18px;">Adicionar amigo</h1>
            <a href="/amigos" style="font-size:12px; color:#b0b0b0; text-decoration:none;">Voltar</a>
        </div>

        <div style="font-size:12px; color:#b0b0b0; margin-bottom:8px;">
            Pesquise por <strong>nickname</strong> (ex: <code style="background:#050509; border:1px solid #272727; padding:2px 6px; border-radius:8px;">@joao_silva</code>) ou por <strong>e-mail</strong>.
        </div>

        <input
            id="friendSearchInput"
            type="text"
            placeholder="Digite @nickname ou email e pressione Enter"
            style="width:100%; padding:10px 12px; border-radius:12px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:13px; outline:none;"
            autocomplete="off"
        />
        <div id="friendSearchHint" style="font-size:11px; color:#8d8d8d; margin-top:6px;">Dica: você também pode só digitar que ele vai buscar automaticamente.</div>

        <div id="friendSearchStatus" style="display:none; margin-top:10px; font-size:12px;"></div>
        <div id="friendSearchResults" style="display:flex; flex-direction:column; gap:8px; margin-top:10px;"></div>
    </section>
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

    async function sendRequest(userId, btn){
        if (!userId) return;
        var fd = new FormData();
        fd.append('user_id', String(userId));
        if (btn) btn.disabled = true;
        try {
            var res = await fetch('/amigos/solicitar', {
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
            if (btn) {
                btn.textContent = 'Enviado';
            }
        } catch (e) {
            setStatus('Não foi possível enviar o pedido.', false);
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    function render(items){
        results.innerHTML = '';
        if (!items || !items.length) {
            results.innerHTML = '<div style="font-size:12px; color:#b0b0b0;">Nenhum usuário encontrado.</div>';
            return;
        }
        items.forEach(function(u){
            var name = (u.preferred_name || u.name || 'Usuário');
            var nick = (u.nickname || '').trim();
            var email = (u.email || '').trim();

            var div = document.createElement('div');
            div.style.background = '#050509';
            div.style.border = '1px solid #272727';
            div.style.borderRadius = '12px';
            div.style.padding = '10px 12px';
            div.style.display = 'flex';
            div.style.justifyContent = 'space-between';
            div.style.alignItems = 'center';
            div.style.gap = '10px';

            var left = document.createElement('div');
            left.style.minWidth = '0';
            left.innerHTML = '<div style="font-size:13px; color:#f5f5f5; font-weight:650;">' + escapeHtml(name) + '</div>'
                + (nick ? '<div style="font-size:11px; color:#b0b0b0;">@' + escapeHtml(nick) + '</div>' : '')
                + (email ? '<div style="font-size:11px; color:#8d8d8d;">' + escapeHtml(email) + '</div>' : '');

            var right = document.createElement('div');
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = 'Enviar solicitação';
            btn.style.border = 'none';
            btn.style.borderRadius = '999px';
            btn.style.padding = '7px 10px';
            btn.style.background = 'linear-gradient(135deg,#e53935,#ff6f60)';
            btn.style.color = '#050509';
            btn.style.fontWeight = '700';
            btn.style.cursor = 'pointer';
            btn.addEventListener('click', function(){ sendRequest(u.id, btn); });
            right.appendChild(btn);

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
            var url = '/amigos/buscar?q=' + encodeURIComponent(term);
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
