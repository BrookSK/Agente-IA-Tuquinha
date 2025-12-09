<?php
/** @var string $viewFile */
/** @var string|null $pageTitle */

$pageTitle = $pageTitle ?? 'Agente IA - Tuquinha';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#e53935">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="icon" type="image/png" href="/public/favicon.png">
    <link rel="manifest" href="/public/manifest.webmanifest">
    <style>
        :root {
            --bg-main: #050509;
            --bg-secondary: #111118;
            --accent: #e53935;
            --accent-soft: #ff6f60;
            --text-primary: #f5f5f5;
            --text-secondary: #b0b0b0;
            --border-subtle: #272727;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: var(--bg-main);
            color: var(--text-primary);
            min-height: 100vh;
        }
        a { color: inherit; text-decoration: none; }

        .sidebar {
            width: 260px;
            background: radial-gradient(circle at top left, #e53935 0, #050509 40%);
            border-right: 1px solid var(--border-subtle);
            padding: 16px 14px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            height: 100vh;
            overflow-y: auto;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 20;
            transition: transform 0.2s ease-out, opacity 0.2s ease-out;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }
        .brand-logo {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            overflow: hidden;
            background: #050509;
            box-shadow: 0 0 20px rgba(229, 57, 53, 0.7);
        }
        .brand-text-title {
            font-weight: 700;
            letter-spacing: 0.02em;
        }
        .brand-text-sub {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .sidebar-section-title {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--text-secondary);
            margin-bottom: 6px;
        }

        .sidebar-button {
            width: 100%;
            border-radius: 999px;
            padding: 9px 12px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(0, 0, 0, 0.35);
            color: var(--text-primary);
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: background 0.15s ease, border-color 0.15s ease, transform 0.1s ease;
        }
        .sidebar-button span.icon {
            width: 18px;
            height: 18px;
            border-radius: 999px;
            background: rgba(229, 57, 53, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        .sidebar-button.primary {
            background: linear-gradient(135deg, #e53935, #ff6f60);
            border-color: transparent;
            color: #050509;
            font-weight: 600;
        }
        .sidebar-button:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.18);
            transform: translateY(-1px);
        }
        .sidebar-button.primary:hover {
            filter: brightness(1.05);
        }

        .sidebar-footer {
            margin-top: auto;
            font-size: 11px;
            color: var(--text-secondary);
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            padding-top: 10px;
        }
        .sidebar-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            padding: 4px 9px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            margin-bottom: 6px;
            color: var(--accent-soft);
        }

        .main {
            margin-left: 260px;
            display: flex;
            flex-direction: column;
            background: radial-gradient(circle at top, rgba(229, 57, 53, 0.1) 0, #050509 50%);
            min-height: 100vh;
        }

        .main-header {
            height: 56px;
            border-bottom: 1px solid var(--border-subtle);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            backdrop-filter: blur(18px);
            background: linear-gradient(to bottom, rgba(5, 5, 9, 0.92), rgba(5, 5, 9, 0.8));
            position: sticky;
            top: 0;
            z-index: 15;
        }
        .main-header-title {
            font-size: 14px;
            font-weight: 500;
        }
        .env-pill {
            font-size: 11px;
            padding: 3px 9px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            color: var(--text-secondary);
        }

        .main-content {
            flex: 1;
            padding: 24px;
            overflow-y: auto;
        }

        .menu-toggle {
            display: none;
            width: 32px;
            height: 32px;
            border-radius: 999px;
            border: 1px solid var(--border-subtle);
            background: rgba(0,0,0,0.4);
            align-items: center;
            justify-content: center;
            cursor: pointer;
            margin-right: 10px;
        }
        .menu-toggle span {
            display: block;
            width: 16px;
            height: 2px;
            background: var(--text-primary);
            position: relative;
        }
        .menu-toggle span::before,
        .menu-toggle span::after {
            content: '';
            position: absolute;
            left: 0;
            width: 16px;
            height: 2px;
            background: var(--text-primary);
        }
        .menu-toggle span::before { top: -5px; }
        .menu-toggle span::after { top: 5px; }

        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            z-index: 10;
        }

        /* Ajuste do ícone de calendário em inputs de data no tema escuro */
        input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(1);
        }

        /* Scrollbar customizado para a sidebar */
        .sidebar::-webkit-scrollbar {
            width: 8px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.18);
            border-radius: 999px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Scrollbar global (janela) para combinar com o tema escuro */
        html::-webkit-scrollbar,
        body::-webkit-scrollbar {
            height: 8px;
            width: 8px;
        }

        html::-webkit-scrollbar-track,
        body::-webkit-scrollbar-track {
            background: #050509;
        }

        html::-webkit-scrollbar-thumb,
        body::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.18);
            border-radius: 999px;
        }

        html::-webkit-scrollbar-thumb:hover,
        body::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Scrollbar customizado para o conteúdo principal e carrosséis horizontais */
        .main-content::-webkit-scrollbar,
        #persona-carousel::-webkit-scrollbar,
        #persona-default-list::-webkit-scrollbar {
            height: 8px;
            width: 8px;
        }

        .main-content::-webkit-scrollbar-track,
        #persona-carousel::-webkit-scrollbar-track,
        #persona-default-list::-webkit-scrollbar-track {
            background: transparent;
        }

        .main-content::-webkit-scrollbar-thumb,
        #persona-carousel::-webkit-scrollbar-thumb,
        #persona-default-list::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.18);
            border-radius: 999px;
        }

        .main-content::-webkit-scrollbar-thumb:hover,
        #persona-carousel::-webkit-scrollbar-thumb:hover,
        #persona-default-list::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Scrollbar em navegadores que suportam scrollbar-color (ex: Firefox) */
        #persona-carousel,
        #persona-default-list {
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.4) transparent;
        }

        @media (max-width: 900px) {
            .sidebar {
                position: fixed;
                left: 0;
                top: 0;
                bottom: 0;
                width: 260px;
                transform: translateX(-100%);
                opacity: 0;
            }
            .sidebar--open {
                transform: translateX(0);
                opacity: 1;
            }
            .sidebar-overlay {
                display: none;
            }
            .sidebar-overlay.active {
                display: block;
            }
            .main {
                margin-left: 0;
            }
            .main-header {
                padding: 0 14px;
            }
            .main-content {
                padding: 16px 14px 20px 14px;
            }
            .menu-toggle {
                display: flex;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>
    <aside class="sidebar" id="sidebar">
        <div>
            <div class="brand">
                <div class="brand-logo"><img src="/public/favicon.png" alt="Tuquinha" style="width:100%; height:100%; display:block; object-fit:cover;"></div>
                <div>
                    <div class="brand-text-title">Agente IA - Tuquinha</div>
                    <div class="brand-text-sub">Branding vivo na veia</div>
                </div>
            </div>
            <div style="margin-top: 10px;">
                <div class="sidebar-section-title">Conversa</div>
                <?php
                    $hasUser = !empty($_SESSION['user_id']);
                    $defaultPersonaId = $_SESSION['default_persona_id'] ?? null;
                    // Convidados vão direto para um chat novo padrão; seleção de personalidade só é usada para usuários logados
                    $newChatHref = '/chat?new=1';
                    if ($hasUser && empty($defaultPersonaId)) {
                        // Usuário logado sem personalidade padrão definida pode passar pela tela de personalidades
                        $newChatHref = '/personalidades';
                    }
                ?>
                <a href="<?= htmlspecialchars($newChatHref) ?>" class="sidebar-button primary" style="margin-bottom: 8px;">
                    <span class="icon">+</span>
                    <span>Novo chat com o Tuquinha</span>
                </a>
                <?php
                    $currentSlug = $_SESSION['plan_slug'] ?? null;
                    $isAdmin = !empty($_SESSION['is_admin']);
                    $canSeeHistory = $hasUser && ($isAdmin || ($currentSlug && $currentSlug !== 'free'));
                ?>
                <?php if ($canSeeHistory): ?>
                    <a href="/historico" class="sidebar-button" style="margin-bottom: 8px;">
                        <span class="icon">🕒</span>
                        <span>Histórico de chats</span>
                    </a>
                <?php endif; ?>
                <div class="sidebar-section-title" style="margin-top: 10px;">Guias rápidos</div>
                <a href="/" class="sidebar-button">
                    <span class="icon">🏠</span>
                    <span>Quem é o Tuquinha</span>
                </a>
                <a href="/planos" class="sidebar-button">
                    <span class="icon">💳</span>
                    <span>Planos e limites</span>
                </a>
                <a href="/cursos" class="sidebar-button">
                    <span class="icon">🎓</span>
                    <span>Cursos</span>
                </a>

                <?php if (!empty($_SESSION['user_id'])): ?>
                    <a href="/comunidade" class="sidebar-button">
                        <span class="icon">🌐</span>
                        <span>Comunidade</span>
                    </a>
                <?php endif; ?>

                <?php if (!empty($_SESSION['user_id'])): ?>
                    <div class="sidebar-section-title" style="margin-top: 10px;">Orkut do Tuquinha</div>
                    <a href="/perfil" class="sidebar-button">
                        <span class="icon">
                            🧑
                        </span>
                        <span>Perfil social</span>
                    </a>
                    <a href="/amigos" class="sidebar-button">
                        <span class="icon">
                            👥
                        </span>
                        <span>Amigos</span>
                    </a>
                    <a href="/comunidades" class="sidebar-button">
                        <span class="icon">
                            💬
                        </span>
                        <span>Comunidades Orkut</span>
                    </a>
                <?php endif; ?>

                <?php if (!empty($_SESSION['user_id'])): ?>
                    <div class="sidebar-section-title" style="margin-top: 10px;">Conta</div>
                    <a href="/conta" class="sidebar-button">
                        <span class="icon">
                            👤
                        </span>
                        <span>Minha conta</span>
                    </a>
                    <a href="/conta/personalidade" class="sidebar-button">
                        <span class="icon">
                            🎭
                        </span>
                        <span>Personalidade padrão</span>
                    </a>
                    <a href="/tokens/historico" class="sidebar-button">
                        <span class="icon">
                            🔋
                        </span>
                        <span>Histórico de tokens extras</span>
                    </a>
                    <a href="/parceiro/cursos" class="sidebar-button">
                        <span class="icon">
                            🎓
                        </span>
                        <span>Meus cursos (parceiro)</span>
                    </a>
                    <a href="/logout" class="sidebar-button" style="margin-top: 6px;">
                        <span class="icon">
                            ⏻
                        </span>
                        <span>Sair da conta</span>
                    </a>
                <?php endif; ?>

                <?php if (!empty($_SESSION['is_admin'])): ?>
                    <div class="sidebar-section-title" style="margin-top: 10px;">Admin</div>
                    <a href="/admin" class="sidebar-button">
                        <span class="icon">📊</span>
                        <span>Dashboard</span>
                    </a>
                    <a href="/admin/config" class="sidebar-button">
                        <span class="icon">⚙</span>
                        <span>Configurações do sistema</span>
                    </a>
                    <a href="/admin/planos" class="sidebar-button" style="margin-top: 6px;">
                        <span class="icon">🧩</span>
                        <span>Gerenciar planos</span>
                    </a>
                    <a href="/admin/cursos" class="sidebar-button" style="margin-top: 6px;">
                        <span class="icon">🎓</span>
                        <span>Cursos</span>
                    </a>
                    <a href="/admin/personalidades" class="sidebar-button" style="margin-top: 6px;">
                        <span class="icon">🎭</span>
                        <span>Personalidades do Tuquinha</span>
                    </a>
                    <a href="/admin/usuarios" class="sidebar-button" style="margin-top: 6px;">
                        <span class="icon">👥</span>
                        <span>Usuários</span>
                    </a>
                    <a href="/admin/comunidade/bloqueios" class="sidebar-button" style="margin-top: 6px;">
                        <span class="icon">🚫</span>
                        <span>Bloqueios da comunidade</span>
                    </a>
                    <a href="/admin/assinaturas" class="sidebar-button" style="margin-top: 6px;">
                        <span class="icon">📑</span>
                        <span>Assinaturas</span>
                    </a>
                    <a href="/debug/asaas" class="sidebar-button" style="margin-top: 6px;">
                        <span class="icon">🧪</span>
                        <span>Debug Asaas</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="sidebar-footer">
            <div class="sidebar-badge">
                <span>Branding Vivo</span>
            </div>
            <div>Mentor IA focado em designers de marca. Educação primeiro, execução depois.</div>
            <div style="margin-top: 8px; font-size: 10px; color: var(--text-secondary);">
                Desenvolvido por <a href="https://lrvweb.com.br" target="_blank" rel="noopener noreferrer" style="color: var(--accent-soft); text-decoration: none;">LRV Web</a>
            </div>
        </div>
    </aside>

    <main class="main">
        <header class="main-header">
            <div style="display:flex; align-items:center; gap:8px;">
                <button type="button" class="menu-toggle" id="menu-toggle" aria-label="Abrir menu">
                    <span></span>
                </button>
                <div class="main-header-title"><?= htmlspecialchars($pageTitle) ?></div>
            </div>
            <div>
                <?php if (!empty($_SESSION['user_id'])): ?>
                    <div class="env-pill">
                        <?php $nomeSaudacao = $_SESSION['user_name'] ?? 'designer'; ?>
                        Olá, <?= htmlspecialchars($nomeSaudacao) ?>
                    </div>
                <?php else: ?>
                    <a href="/login" class="env-pill" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                        <span>Entrar</span>
                        <span>↪</span>
                    </a>
                <?php endif; ?>
            </div>
        </header>
        <section class="main-content">
            <?php include $viewFile; ?>
        </section>
    </main>
    <script>
    (function () {
        var sidebar = document.getElementById('sidebar');
        var toggle = document.getElementById('menu-toggle');
        var overlay = document.getElementById('sidebar-overlay');
        if (!sidebar || !toggle || !overlay) return;

        function closeSidebar() {
            sidebar.classList.remove('sidebar--open');
            overlay.classList.remove('active');
        }

        toggle.addEventListener('click', function () {
            var isOpen = sidebar.classList.toggle('sidebar--open');
            if (isOpen) {
                overlay.classList.add('active');
            } else {
                overlay.classList.remove('active');
            }
        });

        overlay.addEventListener('click', closeSidebar);
    })();

    // Registro do Service Worker para PWA
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/public/service-worker.js').catch(function (err) {
                console.error('Falha ao registrar service worker:', err);
            });
        });
    }
    </script>
</body>
</html>
