<div data-tour="home-root" style="max-width: 880px; margin: 0 auto; padding: 22px 14px 34px 14px;">
    <div style="display:flex; flex-direction:column; align-items:center; text-align:center; gap: 12px; margin-bottom: 22px;">
        <div style="display:inline-flex; align-items:center; gap:8px; padding:6px 12px; border-radius:999px; border:1px solid rgba(229,57,53,0.25); background: rgba(229,57,53,0.10); color: var(--accent-soft); font-size: 12px;">
            <span style="opacity:0.9;">✦</span>
            <span>Nova versão disponível</span>
        </div>
        <h1 data-tour="home-title" style="font-size: 34px; line-height: 1.15; font-weight: 800; letter-spacing: -0.02em;">
            Bem-vindo ao <span style="color: var(--accent-soft);">Resenha 2.0</span>
        </h1>
        <div style="color: var(--text-secondary); font-size: 14px; line-height: 1.5; max-width: 560px;">
            Seu ecossistema completo para designers que querem ir além<br>
            do freelance.
        </div>
    </div>

    <?php
        $isLogged = !empty($isLogged);
        $hasPaidActiveSubscription = !empty($hasPaidActiveSubscription);
        $planAllowsProjects = !empty($planAllowsProjects);
        $planAllowsCourses = !empty($planAllowsCourses);
        $hasCourseEnrollment = !empty($hasCourseEnrollment);

        $guideHref = function (string $path) use ($isLogged, $hasPaidActiveSubscription): string {
            if (!$isLogged) {
                return '/login';
            }
            if (!$hasPaidActiveSubscription) {
                return '/planos';
            }
            return $path;
        };

        $menuHref = function (string $path) use ($isLogged): string {
            return $isLogged ? $path : '/login';
        };

        $menuTiles = [];

        // Novo chat: sempre disponível (se não logado, cai no /login)
        $newChatHref = $menuHref('/chat?new=1');
        // Para assinantes pagos: abre o seletor de personalidades antes de criar o chat.
        if ($isLogged && $hasPaidActiveSubscription) {
            $newChatHref = $menuHref('/personalities');
        }
        $menuTiles[] = [
            'label' => 'Novo chat',
            'href' => $newChatHref,
            'hot' => true,
            'icon_html' => '<span style="font-size:18px; font-weight:900; line-height:1;">+</span>',
        ];

        // Projetos: aparece apenas quando tiver assinatura ativa e o plano permitir projetos
        if ($isLogged && $hasPaidActiveSubscription && $planAllowsProjects) {
            $menuTiles[] = [
                'label' => 'Meus projetos',
                'href' => $menuHref('/projetos'),
                'hot' => false,
                'icon_html' => isset($renderMenuIcon) ? $renderMenuIcon('projects_list', '📁') : '📁',
            ];
        }

        // Histórico: controller bloqueia plano free
        if ($isLogged && $hasPaidActiveSubscription) {
            $menuTiles[] = [
                'label' => 'Histórico de chats',
                'href' => $menuHref('/historico'),
                'hot' => false,
                'icon_html' => isset($renderMenuIcon) ? $renderMenuIcon('chat_history', '🕘') : '🕘',
            ];
        }

        // Cursos: mostra se o plano permite cursos OU se já está matriculado em algum curso
        if ($isLogged && ($planAllowsCourses || $hasCourseEnrollment)) {
            $menuTiles[] = [
                'label' => 'Cursos',
                'href' => $menuHref('/cursos'),
                'hot' => false,
                'icon_html' => isset($renderMenuIcon) ? $renderMenuIcon('quick_courses', '🎓') : '🎓',
            ];
        }

        // Notícias: controller exige assinatura paga (não-free)
        if ($isLogged) {
            $menuTiles[] = [
                'label' => 'Notícias',
                'href' => $menuHref('/noticias'),
                'hot' => true,
                'icon_html' => isset($renderMenuIcon) ? $renderMenuIcon('quick_news', '🗞') : '🗞',
            ];
        }

        // Comunidade: CommunityController exige pelo menos 1 curso inscrito
        if ($isLogged && $hasCourseEnrollment) {
            $menuTiles[] = [
                'label' => 'Comunidades',
                'href' => $menuHref('/comunidades'),
                'hot' => false,
                'icon_html' => isset($renderMenuIcon) ? $renderMenuIcon('social_communities', '💬') : '💬',
            ];
        }

        // Social: só exige login
        if ($isLogged) {
            $menuTiles[] = [
                'label' => 'Amigos',
                'href' => $menuHref('/amigos'),
                'hot' => false,
                'icon_html' => isset($renderMenuIcon) ? $renderMenuIcon('social_friends', '👥') : '👥',
            ];
            $menuTiles[] = [
                'label' => 'Perfil Social',
                'href' => $menuHref('/perfil'),
                'hot' => false,
                'icon_html' => isset($renderMenuIcon) ? $renderMenuIcon('social_profile', '🧑') : '🧑',
            ];
        }
    ?>

    <div style="
        display:grid;
        grid-template-columns: repeat(auto-fit, 130px);
        justify-content: center;
        gap: 14px;
        max-width: 620px;
        margin: 0 auto 22px auto;
    ">
        <?php foreach ($menuTiles as $tile): ?>
            <?php
                $hot = !empty($tile['hot']);
                $bg = $hot ? 'rgba(229,57,53,0.12)' : 'var(--surface-card)';
                $border = $hot ? 'rgba(229,57,53,0.26)' : 'var(--border-subtle)';
                $iconBg = $hot ? 'rgba(229,57,53,0.92)' : 'rgba(255,255,255,0.06)';
                $iconColor = $hot ? '#050509' : 'var(--text-primary)';
                $labelColor = $hot ? 'var(--accent)' : 'var(--text-primary)';
            ?>
            <a href="<?= htmlspecialchars((string)($tile['href'] ?? '#')) ?>" style="text-decoration:none;">
                <div style="
                    background: <?= $bg ?>;
                    border: 1px solid <?= $border ?>;
                    border-radius: 18px;
                    height: 96px;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    box-shadow: var(--shadow-tile);
                    position: relative;
                ">
                    <div style="
                        width: 46px;
                        height: 46px;
                        border-radius: 16px;
                        background: <?= $iconBg ?>;
                        color: <?= $iconColor ?>;
                        display:flex;
                        align-items:center;
                        justify-content:center;
                        font-size: 18px;
                        border: 1px solid var(--border-subtle);
                    ">
                        <?php echo (string)($tile['icon_html'] ?? ''); ?>
                    </div>
                </div>
                <div style="
                    text-align:center;
                    margin-top: 8px;
                    font-size: 12px;
                    font-weight: 650;
                    color: <?= $labelColor ?>;
                    line-height: 1.2;
                ">
                    <?= htmlspecialchars((string)($tile['label'] ?? '')) ?>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <div data-tour="home-about" style="
        margin: 0 -14px 0 -14px;
        padding: 34px 14px;
        background: radial-gradient(720px 320px at 50% 0%, rgba(229,57,53,0.10), transparent 55%),
            linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.00));
        border-top: 1px solid var(--border-subtle);
        border-bottom: 1px solid var(--border-subtle);
    ">
        <?php
            $videoUrl = (string)($tuquinhaAboutVideoUrl ?? '');
            $path = $videoUrl !== '' ? parse_url($videoUrl, PHP_URL_PATH) : null;
            $ext = is_string($path) ? strtolower(pathinfo($path, PATHINFO_EXTENSION)) : '';
            $isDirectVideo = $videoUrl !== '' && in_array($ext, ['mp4', 'webm', 'ogg', 'mov'], true);
        ?>

        <div style="text-align:center; font-size: 15px; font-weight: 800; margin-bottom: 14px;">
            Quem é o <span style="color:#ff6f60;">Tuquinha</span>?
        </div>

        <div style="max-width: 520px; margin: 0 auto;">
            <div id="tuq-about-video-card" style="
                position: relative;
                border-radius: 16px;
                border: 1px solid var(--border-subtle);
                overflow: hidden;
                background: linear-gradient(135deg, rgba(229,57,53,0.22), rgba(0,0,0,0.35));
                min-height: 220px;
                box-shadow: var(--shadow-card-strong);
                display:flex;
                align-items:center;
                justify-content:center;
            ">
                <?php if ($videoUrl !== ''): ?>
                    <button type="button" id="tuq-about-play" style="
                        width: 54px;
                        height: 54px;
                        border-radius: 999px;
                        border: none;
                        cursor: pointer;
                        background: rgba(229,57,53,0.95);
                        box-shadow: var(--shadow-accent);
                        display:flex;
                        align-items:center;
                        justify-content:center;
                    " aria-label="Assistir vídeo">
                        <span style="display:inline-block; width:0; height:0; border-top:8px solid transparent; border-bottom:8px solid transparent; border-left:12px solid #050509; margin-left:2px;"></span>
                    </button>
                <?php else: ?>
                    <div style="color: var(--text-secondary); font-size: 13px;">Vídeo em breve</div>
                <?php endif; ?>
            </div>

            <div style="margin-top: 10px; font-size: 11px; color: var(--text-secondary);">
                Conheça a plataforma e o Tuquinha
            </div>

            <div style="margin-top: 14px; color: var(--text-secondary); font-size: 13px; line-height: 1.65;">
                O Tuquinha é seu parceiro de jornada. Ele entende <strong style="color: var(--text-primary);">branding, vendas, gestão, redes sociais</strong> e tudo mais que você precisa para tocar seu negócio.
                <br>
                É como ter uma equipe inteira de especialistas, só que mais gente boa!
                <span style="display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border-radius:999px; background:rgba(229,57,53,0.14); border:1px solid rgba(229,57,53,0.22); color: var(--accent-soft); font-size:11px; margin-top:10px;">
                    (e com um bico colorido)
                </span>
            </div>
        </div>

        <div id="tuqAboutModal" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.72); align-items:center; justify-content:center; padding:18px;">
            <div style="width:100%; max-width:860px; border-radius:16px; overflow:hidden; border:1px solid rgba(255,255,255,0.10); background:#050509; box-shadow:0 18px 48px rgba(0,0,0,0.7);">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; padding:10px 12px; background:#0b0b10; border-bottom:1px solid rgba(255,255,255,0.08);">
                    <div style="font-size:12px; color:rgba(255,255,255,0.75); font-weight:650;">Quem é o Tuquinha</div>
                    <button type="button" id="tuqAboutModalClose" style="border:1px solid rgba(255,255,255,0.12); background:transparent; color:rgba(255,255,255,0.85); border-radius:999px; padding:6px 10px; cursor:pointer; font-size:12px;">Fechar</button>
                </div>
                <div style="position:relative; width:100%; padding-top:56.25%; background:#000;">
                    <?php if ($videoUrl !== ''): ?>
                        <?php if ($isDirectVideo): ?>
                            <video id="tuqAboutVideoEl" src="<?= htmlspecialchars($videoUrl) ?>" controls playsinline style="position:absolute; inset:0; width:100%; height:100%;"></video>
                        <?php else: ?>
                            <iframe id="tuqAboutIframe" src="" data-src="<?= htmlspecialchars($videoUrl) ?>" title="Vídeo: Quem é o Tuquinha" style="position:absolute; inset:0; width:100%; height:100%; border:0;" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <style>
        @media (min-width: 900px) {
            #home-pillars {
                max-width: 980px !important;
                display: grid !important;
                grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
                gap: 26px !important;
                padding: 0 10px !important;
            }
        }
    </style>
    <div id="home-pillars" style="max-width: 520px; margin: 30px auto 30px auto; display:flex; flex-direction:column; gap: 14px;">
        <div style="background: var(--surface-card); border-radius: 14px; padding: 14px; border: 1px solid var(--border-subtle); box-shadow: var(--shadow-card);">
            <div style="font-size: 14px; font-weight: 800; margin-bottom: 8px;">Essência</div>
            <div style="color: var(--text-secondary); font-size: 13px; line-height: 1.6;">
                Criado por quem viveu a solidão de freelancer e decidiu:<br>
                ninguém mais precisa passar por isso sozinho.
            </div>
        </div>
        <div style="background: var(--surface-card); border-radius: 14px; padding: 14px; border: 1px solid var(--border-subtle); box-shadow: var(--shadow-card);">
            <div style="font-size: 14px; font-weight: 800; margin-bottom: 8px;">Foco</div>
            <div style="color: var(--text-secondary); font-size: 13px; line-height: 1.6;">
                Eliminar os erros que custam anos. Dar as ferramentas certas.<br>
                Conectar você com quem já passou por isso.
            </div>
        </div>
        <div style="background: var(--surface-card); border-radius: 14px; padding: 14px; border: 1px solid var(--border-subtle); box-shadow: var(--shadow-card);">
            <div style="font-size: 14px; font-weight: 800; margin-bottom: 8px;">Para quem</div>
            <div style="color: var(--text-secondary); font-size: 13px; line-height: 1.6;">
                Para o designer que está cansado de trocar horas por dinheiro e
                quer construir algo maior, mais sólido, mais seu.
            </div>
        </div>
    </div>

    <?php if ($isLogged && $hasPaidActiveSubscription): ?>
        <div style="text-align:center; font-size: 13px; font-weight: 800; margin: 30px 0 14px 0;">Recursos essenciais</div>

        <div data-tour="home-guides" style="max-width: 520px; margin: 0 auto 30px auto; display:flex; flex-direction:column; gap: 14px;">
            <div data-tour="home-guide-project" style="background: var(--surface-card); border-radius: 14px; padding: 16px; border: 1px solid var(--border-subtle); box-shadow: var(--shadow-card);">
                <div style="font-size: 16px; font-weight: 750; margin-bottom: 6px;">Guia de Projetos</div>
                <div style="color: var(--text-secondary); font-size: 13px; line-height: 1.6; margin-bottom: 12px;">
                    Um guia prático para entregar projetos de branding que impressionam e convertem.
                </div>
                <div style="height: 1px; background: rgba(255,255,255,0.08); margin: 10px 0 12px 0;"></div>
                <a href="<?= htmlspecialchars($guideHref('/guia/projeto-de-marca')) ?>" target="_blank" rel="noopener" style="
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

            <div data-tour="home-guide-method" style="background: var(--surface-card); border-radius: 14px; padding: 16px; border: 1px solid var(--border-subtle); box-shadow: var(--shadow-card);">
                <div style="font-size: 16px; font-weight: 750; margin-bottom: 6px;">Metodologia</div>
                <div style="color: var(--text-secondary); font-size: 13px; line-height: 1.6; margin-bottom: 12px;">
                    Um guia prático com a metodologia do Tuquinha e como aplicar no seu processo.
                </div>
                <div style="height: 1px; background: rgba(255,255,255,0.08); margin: 10px 0 12px 0;"></div>
                <a href="<?= htmlspecialchars($guideHref('/guia/metodologia')) ?>" target="_blank" rel="noopener" style="
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
    <?php endif; ?>

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

    <div style="
        margin: 0 -14px;
        padding: 34px 14px;
        background: radial-gradient(720px 340px at 50% 0%, rgba(229,57,53,0.10), transparent 60%);
        border-top: 1px solid rgba(255,255,255,0.06);
    ">
        <div style="text-align:center; font-size: 14px; font-weight: 800; margin-bottom: 8px;">Pronto para começar?</div>
        <div style="text-align:center; color: var(--text-secondary); font-size: 12px; margin-bottom: 14px;">O Tuquinha está esperando para te ajudar.</div>

        <div style="display:flex; justify-content:center;">
            <form data-tour="home-cta-chat" action="/chat" method="get">
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
                    font-weight: 650;
                    font-size: 13px;
                    cursor: pointer;
                    box-shadow: 0 10px 26px rgba(229, 57, 53, 0.35);
                ">
                    <span>Começar um papo com o Tuquinha</span>
                </button>
            </form>
        </div>
    </div>
</div>
<script>
(function () {
    var playBtn = document.getElementById('tuq-about-play');
    var modal = document.getElementById('tuqAboutModal');
    var modalClose = document.getElementById('tuqAboutModalClose');
    var iframe = document.getElementById('tuqAboutIframe');
    var vid = document.getElementById('tuqAboutVideoEl');

    function openModal() {
        if (!modal) return;
        modal.style.display = 'flex';
        if (iframe && iframe.getAttribute('data-src')) {
            iframe.src = iframe.getAttribute('data-src');
        }
        if (vid) {
            try { vid.play(); } catch (e) {}
        }
    }

    function closeModal() {
        if (!modal) return;
        modal.style.display = 'none';
        if (iframe) {
            iframe.src = '';
        }
        if (vid) {
            try { vid.pause(); } catch (e) {}
        }
    }

    if (playBtn) {
        playBtn.addEventListener('click', openModal);
    }
    if (modalClose) {
        modalClose.addEventListener('click', closeModal);
    }
    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e && e.target === modal) {
                closeModal();
            }
        });
    }
    window.addEventListener('keydown', function (e) {
        if (e && e.key === 'Escape') {
            closeModal();
        }
    });

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
