<?php
/** @var string $viewFile */
/** @var string|null $pageTitle */

use App\Models\CoursePartner;

$pageTitle = $pageTitle ?? 'Resenha 2.0 - Tuquinha';

$menuIconMap = [];
try {
    if (class_exists('App\\Models\\MenuIcon')) {
        $menuIconMap = \App\Models\MenuIcon::allAssoc();
    }
} catch (\Throwable $e) {
    $menuIconMap = [];
}

$renderMenuIcon = function (string $key, string $fallbackHtml) use ($menuIconMap): string {
    $entry = $menuIconMap[$key] ?? null;
    if (!is_array($entry)) {
        return $fallbackHtml;
    }
    $dark = isset($entry['dark_path']) ? (string)$entry['dark_path'] : '';
    $light = isset($entry['light_path']) ? (string)$entry['light_path'] : '';
    if ($dark === '' && $light === '') {
        return $fallbackHtml;
    }

    $darkImg = $dark !== '' ? '<img class="menu-custom-icon menu-custom-icon--dark" src="' . htmlspecialchars($dark, ENT_QUOTES, 'UTF-8') . '" alt="" />' : '';
    $lightImg = $light !== '' ? '<img class="menu-custom-icon menu-custom-icon--light" src="' . htmlspecialchars($light, ENT_QUOTES, 'UTF-8') . '" alt="" />' : '';
    $single = '';
    if ($darkImg !== '' && $lightImg === '') {
        $single = '<img class="menu-custom-icon" src="' . htmlspecialchars($dark, ENT_QUOTES, 'UTF-8') . '" alt="" />';
        return $single;
    }
    if ($lightImg !== '' && $darkImg === '') {
        $single = '<img class="menu-custom-icon" src="' . htmlspecialchars($light, ENT_QUOTES, 'UTF-8') . '" alt="" />';
        return $single;
    }
    return $darkImg . $lightImg;
};

$isCoursePartner = false;
if (!empty($_SESSION['user_id'])) {
    $isCoursePartner = (bool)CoursePartner::findByUserId((int)$_SESSION['user_id']);
}
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
            --surface-card: #111118;
            --surface-subtle: #050509;
            --input-bg: #050509;
            --scrollbar-track: #050509;
            --scrollbar-thumb: rgba(255, 255, 255, 0.18);
        }

        /* Tema claro (hot / cold) controlado via atributo data-theme="light" no body */
        body[data-theme="light"] {
            --bg-main: #fdf7f7;
            --bg-secondary: #ffffff;
            --accent: #e53935;
            --accent-soft: #ff8a65;
            --text-primary: #1f2933;
            --text-secondary: #4b5563;
            --border-subtle: #d1d5db;
            --surface-card: #ffffff;
            --surface-subtle: #fff5f5;
            --input-bg: #fff5f5;
            --scrollbar-track: #f3f4f6;
            --scrollbar-thumb: rgba(148, 163, 184, 0.9);
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
            overflow-x: hidden;
        }
        html {
            overflow-x: hidden;
        }
        a { color: inherit; text-decoration: none; }

        /* Overrides globais para views antigas no tema claro (inline styles escuros) */
        body[data-theme="light"] [style*="background:#111118"],
        body[data-theme="light"] [style*="background: #111118"] {
            background: var(--surface-card) !important;
        }
        body[data-theme="light"] [style*="background:#050509"],
        body[data-theme="light"] [style*="background: #050509"] {
            background: var(--surface-subtle) !important;
        }
        body[data-theme="light"] [style*="background:#0b0b10"],
        body[data-theme="light"] [style*="background: #0b0b10"] {
            background: var(--surface-subtle) !important;
        }
        body[data-theme="light"] [style*="background:#000"],
        body[data-theme="light"] [style*="background: #000"],
        body[data-theme="light"] [style*="background:#000000"],
        body[data-theme="light"] [style*="background: #000000"] {
            background: var(--surface-card) !important;
        }
        body[data-theme="light"] [style*="border:1px solid #272727"],
        body[data-theme="light"] [style*="border: 1px solid #272727"] {
            border-color: var(--border-subtle) !important;
        }
        body[data-theme="light"] [style*="color:#f5f5f5"],
        body[data-theme="light"] [style*="color: #f5f5f5"] {
            color: var(--text-primary) !important;
        }
        body[data-theme="light"] [style*="color:#b0b0b0"],
        body[data-theme="light"] [style*="color: #b0b0b0"] {
            color: var(--text-secondary) !important;
        }
        body[data-theme="light"] [style*="background:#1c1c24"],
        body[data-theme="light"] [style*="background: #1c1c24"] {
            background: var(--surface-subtle) !important;
        }
        body[data-theme="light"] #social-chat-messages [style*="linear-gradient(135deg,#e53935,#ff6f60)"],
        body[data-theme="light"] #social-chat-messages [style*="linear-gradient(135deg, #e53935,#ff6f60)"] {
            color: #ffffff !important;
        }

        .sidebar {
            width: 260px;
            background: radial-gradient(circle at top left, var(--accent) 0, var(--bg-main) 40%);
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
            background: var(--bg-main);
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
        body[data-theme="light"] .sidebar-button {
            background: #f3f4f6;
            border-color: var(--border-subtle);
            color: #111827;
        }
        body[data-theme="light"] .sidebar-button.primary {
            background: linear-gradient(135deg, #fecaca, #fed7d7);
            border-color: transparent;
            color: #111827;
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
            background: radial-gradient(circle at top, rgba(229, 57, 53, 0.1) 0, var(--bg-main) 50%);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* No tema claro, remove o "blur" vermelho de fundo e usa apenas a cor base */
        body[data-theme="light"] .main {
            background: var(--bg-main);
        }

        body[data-theme="light"] .tuquinha-home-icon--dark {
            display: none !important;
        }

        body[data-theme="light"] .tuquinha-home-icon--light {
            display: inline-block !important;
        }

        .menu-custom-icon {
            width: 18px;
            height: 18px;
            object-fit: contain;
            display: inline-block;
        }
        .menu-custom-icon--light {
            display: none;
        }
        body[data-theme="light"] .menu-custom-icon--dark {
            display: none;
        }
        body[data-theme="light"] .menu-custom-icon--light {
            display: inline-block;
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
        body[data-theme="light"] .main-header {
            background: linear-gradient(to bottom, rgba(255, 255, 255, 0.96), rgba(255, 255, 255, 0.92));
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

        .main-content > div[style*="max-width"] {
            max-width: none !important;
            width: 100% !important;
        }

        .main-content > div[style*="margin: 0 auto"],
        .main-content > div[style*="margin:0 auto"] {
            margin-left: 0 !important;
            margin-right: 0 !important;
        }

        .menu-toggle {
            display: none;
            width: 32px;
            height: 32px;
            border-radius: 999px;
            border: 1px solid var(--border-subtle);
            background: rgba(15, 23, 42, 0.9);
            align-items: center;
            justify-content: center;
            cursor: pointer;
            margin-right: 10px;
            box-shadow: 0 6px 14px rgba(0,0,0,0.45);
        }
        body[data-theme="light"] .menu-toggle {
            background: #ffffff;
            border-color: rgba(148, 163, 184, 0.7);
            box-shadow: 0 4px 12px rgba(15,23,42,0.16);
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
            background: var(--scrollbar-thumb);
            border-radius: 999px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: var(--scrollbar-thumb);
        }

        /* Scrollbar global (janela) para combinar com o tema escuro */
        html::-webkit-scrollbar,
        body::-webkit-scrollbar {
            height: 8px;
            width: 8px;
        }

        html::-webkit-scrollbar-track,
        body::-webkit-scrollbar-track {
            background: var(--scrollbar-track);
        }

        html::-webkit-scrollbar-thumb,
        body::-webkit-scrollbar-thumb {
            background: var(--scrollbar-thumb);
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
            background: var(--scrollbar-thumb);
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
            scrollbar-color: var(--scrollbar-thumb) transparent;
        }

        /* Bordas das pills um pouco mais visíveis no tema claro */
        body[data-theme="light"] .env-pill {
            border-color: rgba(148, 163, 184, 0.7);
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
                    <div class="brand-text-title">Resenha 2.0 - Tuquinha</div>
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
                    <span class="icon" aria-hidden="true"><?php
                        echo $renderMenuIcon('quick_home', '<svg class="tuquinha-home-icon tuquinha-home-icon--dark" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="display:inline-block;">
                            <path d="M3 10.5L12 3l9 7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M5 9.8V20a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1V9.8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M18 4.8V7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <svg class="tuquinha-home-icon tuquinha-home-icon--light" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="display:none;">
                            <path d="M3 10.5L12 3l9 7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M5 9.8V20a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1V9.8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M18 4.8V7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>');
                    ?></span>
                    <span>Quem é o Tuquinha</span>
                </a>
                <a href="/planos" class="sidebar-button">
                    <span class="icon" aria-hidden="true"><?php echo $renderMenuIcon('quick_plans', '💳'); ?></span>
                    <span>Planos e limites</span>
                </a>
                <a href="/cursos" class="sidebar-button">
                    <span class="icon" aria-hidden="true"><?php echo $renderMenuIcon('quick_courses', '🎓'); ?></span>
                    <span>Cursos</span>
                </a>

                <?php
                    $hasUser = !empty($_SESSION['user_id']);
                    $isAdmin = !empty($_SESSION['is_admin']);
                    $currentSlug = $_SESSION['plan_slug'] ?? null;
                    $canUseProjects = $hasUser && ($isAdmin || ($currentSlug && $currentSlug !== 'free'));
                ?>

                <?php if ($canUseProjects): ?>
                    <div class="sidebar-section-title" style="margin-top: 10px;">Projetos</div>
                    <a href="/projetos" class="sidebar-button">
                        <span class="icon" aria-hidden="true"><?php echo $renderMenuIcon('projects_list', '📁'); ?></span>
                        <span>Meus projetos</span>
                    </a>
                    <a href="/projetos/novo" class="sidebar-button">
                        <span class="icon" aria-hidden="true"><?php echo $renderMenuIcon('projects_new', '➕'); ?></span>
                        <span>Novo projeto</span>
                    </a>
                <?php endif; ?>

                <?php if (!empty($_SESSION['user_id'])): ?>
                    <div class="sidebar-section-title" style="margin-top: 10px;">Rede social do Tuquinha</div>
                    <a href="/perfil" class="sidebar-button">
                        <span class="icon" aria-hidden="true"><?php echo $renderMenuIcon('social_profile', '🧑'); ?></span>
                        <span>Perfil social</span>
                    </a>
                    <a href="/amigos" class="sidebar-button">
                        <span class="icon" aria-hidden="true"><?php echo $renderMenuIcon('social_friends', '👥'); ?></span>
                        <span>Amigos</span>
                    </a>
                    <a href="/comunidades" class="sidebar-button">
                        <span class="icon" aria-hidden="true"><?php echo $renderMenuIcon('social_communities', '💬'); ?></span>
                        <span>Comunidades</span>
                    </a>
                <?php endif; ?>

                <?php if (!empty($_SESSION['user_id'])): ?>
                    <div class="sidebar-section-title" style="margin-top: 10px;">Conta</div>
                    <a href="/conta" class="sidebar-button">
                        <span class="icon" aria-hidden="true"><?php echo $renderMenuIcon('account_home', '👤'); ?></span>
                        <span>Minha conta</span>
                    </a>
                    <a href="/conta/personalidade" class="sidebar-button">
                        <span class="icon" aria-hidden="true"><?php echo $renderMenuIcon('account_persona', '🎭'); ?></span>
                        <span>Personalidade padrão</span>
                    </a>
                    <a href="/tokens/historico" class="sidebar-button">
                        <span class="icon" aria-hidden="true"><?php echo $renderMenuIcon('account_tokens', '🔋'); ?></span>
                        <span>Histórico de tokens extras</span>
                    </a>
                    <?php if (!empty($isCoursePartner)): ?>
                        <a href="/parceiro/cursos" class="sidebar-button">
                            <span class="icon" aria-hidden="true"><?php echo $renderMenuIcon('partner_courses', '🎓'); ?></span>
                            <span>Meus cursos (parceiro)</span>
                        </a>
                    <?php endif; ?>
                    <a href="/logout" class="sidebar-button" style="margin-top: 6px;">
                        <span class="icon" aria-hidden="true"><?php echo $renderMenuIcon('logout', '⏻'); ?></span>
                        <span>Sair da conta</span>
                    </a>
                <?php endif; ?>

                <?php if (!empty($_SESSION['is_admin'])): ?>
                    <div class="sidebar-section-title" style="margin-top: 10px;">Admin</div>
                    <a href="/admin" class="sidebar-button">
                        <span class="icon" aria-hidden="true"><?php echo $renderMenuIcon('admin_dashboard', '📊'); ?></span>
                        <span>Dashboard</span>
                    </a>
                    <a href="/admin/config" class="sidebar-button">
                        <span class="icon" aria-hidden="true"><?php echo $renderMenuIcon('admin_config', '⚙'); ?></span>
                        <span>Configurações do sistema</span>
                    </a>
                    <a href="/admin/menu-icones" class="sidebar-button" style="margin-top: 6px;">
                        <span class="icon" aria-hidden="true">🖼</span>
                        <span>Ícones do menu</span>
                    </a>
                    <a href="/admin/planos" class="sidebar-button" style="margin-top: 6px;">
                        <span class="icon" aria-hidden="true"><?php echo $renderMenuIcon('admin_plans', '🧩'); ?></span>
                        <span>Gerenciar planos</span>
                    </a>
                    <a href="/admin/cursos" class="sidebar-button" style="margin-top: 6px;">
                        <span class="icon" aria-hidden="true"><?php echo $renderMenuIcon('admin_courses', '🎓'); ?></span>
                        <span>Cursos</span>
                    </a>
                    <a href="/admin/personalidades" class="sidebar-button" style="margin-top: 6px;">
                        <span class="icon" aria-hidden="true"><?php echo $renderMenuIcon('admin_personalities', '🎭'); ?></span>
                        <span>Personalidades do Tuquinha</span>
                    </a>
                    <a href="/admin/usuarios" class="sidebar-button" style="margin-top: 6px;">
                        <span class="icon" aria-hidden="true"><?php echo $renderMenuIcon('admin_users', '👥'); ?></span>
                        <span>Usuários</span>
                    </a>
                    <!-- <a href="/admin/comunidade/bloqueios" class="sidebar-button" style="margin-top: 6px;">
                        <span class="icon">🚫</span>
                        <span>Bloqueios da comunidade</span>
                    </a> -->
                    <a href="/admin/assinaturas" class="sidebar-button" style="margin-top: 6px;">
                        <span class="icon" aria-hidden="true"><?php echo $renderMenuIcon('admin_subscriptions', '📑'); ?></span>
                        <span>Assinaturas</span>
                    </a>
                    <a href="/admin/comunidade/categorias" class="sidebar-button" style="margin-top: 6px;">
                        <span class="icon" aria-hidden="true"><?php echo $renderMenuIcon('admin_community_categories', '💬'); ?></span>
                        <span>Categorias de comunidades</span>
                    </a>
                    <!-- <a href="/debug/asaas" class="sidebar-button" style="margin-top: 6px;">
                        <span class="icon">🧪</span>
                        <span>Debug Asaas</span>
                    </a> -->
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
            <div style="display:flex; align-items:center; gap:10px;">
                <button type="button" id="theme-toggle" class="env-pill" style="display:inline-flex; align-items:center; gap:6px; cursor:pointer; background:transparent;">
                    <span id="theme-toggle-icon">🌙</span>
                    <span id="theme-toggle-label">Tema escuro</span>
                </button>
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

    // Tema claro/escuro com persistência em localStorage
    (function () {
        var body = document.body;
        var toggleBtn = document.getElementById('theme-toggle');
        var iconSpan = document.getElementById('theme-toggle-icon');
        var labelSpan = document.getElementById('theme-toggle-label');
        if (!body || !toggleBtn || !iconSpan || !labelSpan) return;

        function applyTheme(theme) {
            if (theme === 'light') {
                body.setAttribute('data-theme', 'light');
                iconSpan.textContent = '☀️';
                labelSpan.textContent = 'Tema claro';
            } else {
                body.removeAttribute('data-theme');
                iconSpan.textContent = '🌙';
                labelSpan.textContent = 'Tema escuro';
            }
        }

        var savedTheme = null;
        try {
            savedTheme = window.localStorage ? localStorage.getItem('tuquinha_theme') : null;
        } catch (e) {}

        if (savedTheme === 'light' || savedTheme === 'dark') {
            applyTheme(savedTheme);
        } else {
            applyTheme('dark');
        }

        toggleBtn.addEventListener('click', function () {
            var current = body.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
            var next = current === 'light' ? 'dark' : 'light';
            applyTheme(next);
            try {
                if (window.localStorage) {
                    localStorage.setItem('tuquinha_theme', next);
                }
            } catch (e) {}
        });
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
