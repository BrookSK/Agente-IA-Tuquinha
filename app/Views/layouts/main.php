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
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="icon" type="image/png" href="/public/favicon.png">
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
            display: flex;
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
            background: radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 18px;
            color: #050509;
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
            flex: 1;
            display: flex;
            flex-direction: column;
            background: radial-gradient(circle at top, rgba(229, 57, 53, 0.1) 0, #050509 50%);
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

        @media (max-width: 900px) {
            body {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
                padding: 10px 14px;
            }
            .sidebar-footer {
                display: none;
            }
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div>
            <div class="brand">
                <div class="brand-logo">T</div>
                <div>
                    <div class="brand-text-title">Agente IA - Tuquinha</div>
                    <div class="brand-text-sub">Branding vivo na veia</div>
                </div>
            </div>
            <div style="margin-top: 10px;">
                <div class="sidebar-section-title">Conversa</div>
                <form action="/chat" method="get" style="margin-bottom: 8px;">
                    <input type="hidden" name="new" value="1">
                    <button class="sidebar-button primary" type="submit">
                        <span class="icon">+</span>
                        <span>Novo chat com o Tuquinha</span>
                    </button>
                </form>
                <?php
                    $hasUser = !empty($_SESSION['user_id']);
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

                <?php if (!empty($_SESSION['user_id'])): ?>
                    <div class="sidebar-section-title" style="margin-top: 10px;">Conta</div>
                    <a href="/conta" class="sidebar-button">
                        <span class="icon">👤</span>
                        <span>Minha conta</span>
                    </a>
                    <a href="/logout" class="sidebar-button" style="margin-top: 6px;">
                        <span class="icon">⏻</span>
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
                    <a href="/admin/usuarios" class="sidebar-button" style="margin-top: 6px;">
                        <span class="icon">👥</span>
                        <span>Usuários</span>
                    </a>
                    <a href="/admin/assinaturas" class="sidebar-button" style="margin-top: 6px;">
                        <span class="icon">📑</span>
                        <span>Assinaturas</span>
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
            <div class="main-header-title"><?= htmlspecialchars($pageTitle) ?></div>
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
</body>
</html>
