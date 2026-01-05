<div>
    <h1 style="font-size: 28px; margin-bottom: 12px; font-weight: 650;">Bem-vindo ao Resenha 2.0 - Tuquinha</h1>
    <p style="color: var(--text-secondary); margin-bottom: 20px; font-size: 14px;">
        Seu mentor inteligente em branding e identidade visual, focado em designers que querem criar
        marcas com alma, estratégia e personalidade de verdade.
    </p>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin-bottom: 24px;">
        <div style="background: var(--surface-card); border-radius: 14px; padding: 14px; border: 1px solid var(--border-subtle);">
            <div style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.12em; color: var(--text-secondary); margin-bottom: 6px;">Essência</div>
            <div style="font-size: 14px;">Mentor que une estratégia profunda com linguagem acessível, no estilo amigo que te puxa pra cima.</div>
        </div>
        <div style="background: var(--surface-card); border-radius: 14px; padding: 14px; border: 1px solid var(--border-subtle);">
            <div style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.12em; color: var(--text-secondary); margin-bottom: 6px;">Foco</div>
            <div style="font-size: 14px;">Branding Vivo: Alma → Voz → Corpo → Vida. Nada de marca vazia, tudo começa na estratégia.</div>
        </div>
        <div style="background: var(--surface-card); border-radius: 14px; padding: 14px; border: 1px solid var(--border-subtle);">
            <div style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.12em; color: var(--text-secondary); margin-bottom: 6px;">Para quem</div>
            <div style="font-size: 14px;">Designers iniciantes ou experientes que querem elevar o nível estratégico dos projetos.</div>
        </div>
    </div>

    <div style="
        background: var(--surface-card);
        border-radius: 16px;
        border: 1px solid var(--border-subtle);
        padding: 14px;
        margin-bottom: 18px;
    ">
        <div style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.12em; color: var(--text-secondary); margin-bottom: 10px;">Quem é o Tuquinha</div>
        <div style="display:grid; grid-template-columns: minmax(240px, 1fr) minmax(260px, 1fr); gap: 14px; align-items: start;">
            <div style="
                border: 1px solid var(--border-subtle);
                border-radius: 14px;
                overflow: hidden;
                background: var(--surface-subtle);
                min-height: 200px;
                display:flex;
                align-items:center;
                justify-content:center;
                color: var(--text-secondary);
                font-size: 13px;
            ">
                <?php if (!empty($tuquinhaAboutVideoUrl)): ?>
                    <iframe
                        src="<?= htmlspecialchars($tuquinhaAboutVideoUrl) ?>"
                        title="Vídeo: Quem é o Tuquinha"
                        style="width:100%; height:100%; min-height:220px; border:0; display:block;"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowfullscreen
                    ></iframe>
                <?php else: ?>
                    Vídeo em breve
                <?php endif; ?>
            </div>
            <div>
                <div style="font-size: 16px; font-weight: 650; margin-bottom: 8px;">Sobre o Tuquinha</div>
                <div style="color: var(--text-secondary); font-size: 13px; line-height: 1.6;">
                    O Tuquinha é seu mentor inteligente em branding e identidade visual — pensado para designers que querem criar marcas com alma, estratégia e personalidade de verdade.
                    Aqui você também encontra guias práticos para aplicar o método no dia a dia.
                </div>
            </div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 14px; margin-bottom: 18px;">
        <div style="background: var(--surface-card); border-radius: 16px; padding: 16px; border: 1px solid var(--border-subtle);">
            <div style="font-size: 16px; font-weight: 700; margin-bottom: 6px;">Guia de Projetos</div>
            <div style="color: var(--text-secondary); font-size: 13px; line-height: 1.6; margin-bottom: 12px;">
                Um guia prático para entregar projetos de branding que impressionam e convertem.
            </div>
            <a href="/guias/guia-projeto-de-marca.html" target="_blank" rel="noopener" style="
                display:inline-flex;
                align-items:center;
                justify-content:center;
                gap:8px;
                padding: 10px 14px;
                border-radius: 999px;
                border: none;
                background: linear-gradient(135deg, #e53935, #ff6f60);
                color: #050509;
                font-weight: 650;
                font-size: 13px;
                text-decoration:none;
            ">
                <span>Acessar</span>
                <span>➜</span>
            </a>
        </div>

        <div style="background: var(--surface-card); border-radius: 16px; padding: 16px; border: 1px solid var(--border-subtle);">
            <div style="font-size: 16px; font-weight: 700; margin-bottom: 6px;">Metodologia</div>
            <div style="color: var(--text-secondary); font-size: 13px; line-height: 1.6; margin-bottom: 12px;">
                Um guia prático com a metodologia do Tuquinha e como aplicar no seu processo.
            </div>
            <a href="/guias/metodologia.html" target="_blank" rel="noopener" style="
                display:inline-flex;
                align-items:center;
                justify-content:center;
                gap:8px;
                padding: 10px 14px;
                border-radius: 999px;
                border: none;
                background: linear-gradient(135deg, #e53935, #ff6f60);
                color: #050509;
                font-weight: 650;
                font-size: 13px;
                text-decoration:none;
            ">
                <span>Acessar</span>
                <span>➜</span>
            </a>
        </div>
    </div>

    <!-- CTA para instalar o app (PWA) - exibido apenas em mobile via JS -->
    <div id="pwa-install-banner" style="display:none; margin-bottom: 18px;">
        <div style="background:var(--surface-card); border-radius:14px; border:1px solid var(--border-subtle); padding:12px 14px; display:flex; align-items:center; gap:10px;">
            <div style="width:36px; height:36px; border-radius:12px; overflow:hidden; background:var(--surface-subtle); display:flex; align-items:center; justify-content:center;">
                <img src="/public/favicon.png" alt="Tuquinha" style="width:100%; height:100%; display:block; object-fit:cover;">
            </div>
            <div style="flex:1;">
                <div style="font-size:13px; font-weight:600; margin-bottom:2px;">Leve o Tuquinha pro seu celular</div>
                <div style="font-size:12px; color:var(--text-secondary);">Instale o app na tela inicial e volte pro chat em 1 toque.</div>
            </div>
            <button id="pwa-install-button" type="button" style="border:none; border-radius:999px; padding:8px 12px; font-size:12px; font-weight:600; cursor:pointer; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509;">
                Instalar app
            </button>
        </div>
    </div>

    <form action="/chat" method="get">
        <input type="hidden" name="new" value="1">
        <button type="submit" style="
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 999px;
            border: none;
            background: linear-gradient(135deg, #e53935, #ff6f60);
            color: #050509;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            box-shadow: 0 8px 20px rgba(229, 57, 53, 0.45);
        ">
            <span>Começar um papo com o Tuquinha</span>
            <span>🚀</span>
        </button>
    </form>
</div>
<script>
(function () {
    var deferredPrompt = null;
    var banner = document.getElementById('pwa-install-banner');
    var button = document.getElementById('pwa-install-button');

    if (!banner || !button) return;

    // Detecta se é mobile (heurística simples) e se suporta beforeinstallprompt
    var isMobile = /Android|webOS|iPhone|iPad|iPod|Opera Mini|IEMobile/i.test(navigator.userAgent || '');

    if (!isMobile) {
        return; // só mostra para mobile
    }

    window.addEventListener('beforeinstallprompt', function (e) {
        // Evita o prompt automático
        e.preventDefault();
        deferredPrompt = e;

        // Mostra o banner
        banner.style.display = 'block';
    });

    button.addEventListener('click', function () {
        if (!deferredPrompt) {
            banner.style.display = 'none';
            return;
        }

        deferredPrompt.prompt();

        deferredPrompt.userChoice.then(function () {
            deferredPrompt = null;
            banner.style.display = 'none';
        });
    });
})();
</script>
