<?php
/** @var string $viewFile */
/** @var string|null $pageTitle */

use App\Models\CoursePartner;

$pageTitle = $pageTitle ?? 'Resenha 2.0';

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

$currentPath = '/';
try {
    $req = (string)($_SERVER['REQUEST_URI'] ?? '/');
    $p = parse_url($req, PHP_URL_PATH);
    if (is_string($p) && $p !== '') {
        $currentPath = $p;
    }
} catch (\Throwable $e) {
    $currentPath = '/';
}

$isActiveNav = function ($hrefOrPaths) use ($currentPath): bool {
    $paths = is_array($hrefOrPaths) ? $hrefOrPaths : [$hrefOrPaths];
    foreach ($paths as $href) {
        $href = (string)$href;
        if ($href === '') {
            continue;
        }
        $hrefPath = parse_url($href, PHP_URL_PATH);
        $hrefPath = is_string($hrefPath) && $hrefPath !== '' ? $hrefPath : $href;

        if ($hrefPath === '/') {
            if ($currentPath === '/') {
                return true;
            }
            continue;
        }

        if ($currentPath === $hrefPath) {
            return true;
        }
        if (str_starts_with($currentPath, rtrim($hrefPath, '/') . '/')) {
            return true;
        }
    }
    return false;
};

$sidebarAvatarPath = '';
$sidebarInitial = 'U';
if (!empty($_SESSION['user_id'])) {
    $sidebarName = trim((string)($_SESSION['user_name'] ?? ''));
    if ($sidebarName !== '') {
        $sidebarInitial = mb_strtoupper(mb_substr($sidebarName, 0, 1, 'UTF-8'), 'UTF-8');
    }
    try {
        $sp = \App\Models\UserSocialProfile::findByUserId((int)$_SESSION['user_id']);
        if (is_array($sp)) {
            $sidebarAvatarPath = trim((string)($sp['avatar_path'] ?? ''));
        }
    } catch (\Throwable $e) {
        $sidebarAvatarPath = '';
    }
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

        /* Esconde a barra de rolagem da sidebar (mantém scroll funcionando) */
        .sidebar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        .sidebar::-webkit-scrollbar {
            width: 0;
            height: 0;
        }
        body[data-theme="light"] .sidebar {
            background: var(--bg-main);
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        body[data-theme="light"] .brand-logo {
            box-shadow: none;
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
        .sidebar-button.sidebar-button--active {
            border-color: rgba(229, 57, 53, 0.35);
            background: rgba(229, 57, 53, 0.12);
            box-shadow: 0 0 0 1px rgba(229, 57, 53, 0.16);
        }
        body[data-theme="light"] .sidebar-button.sidebar-button--active {
            border-color: rgba(229, 57, 53, 0.35);
            background: rgba(229, 57, 53, 0.08);
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
        body[data-theme="light"] .sidebar-button span.icon {
            background: transparent;
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
            background: linear-gradient(135deg, #e53935, #ff6f60);
            border-color: transparent;
            color: #050509;
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

        .env-pill.env-pill--user {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
        }
        .env-pill-avatar {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            overflow: hidden;
            background: radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #050509;
            font-weight: 800;
            font-size: 11px;
            flex: 0 0 auto;
        }
        .env-pill-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
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
                    <div class="brand-text-title">Resenha 2.0</div>
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
                <a href="<?= htmlspecialchars($newChatHref) ?>" class="sidebar-button primary<?= $isActiveNav(['/chat', '/personalidades']) ? ' sidebar-button--active' : '' ?>" data-tour="nav-new-chat" style="margin-bottom: 8px;">
                    <span class="icon">+</span>
                    <span>Novo chat com o Tuquinha</span>
                </a>
                <?php
                    $currentSlug = $_SESSION['plan_slug'] ?? null;
                    $isAdmin = !empty($_SESSION['is_admin']);
                    $canSeeHistory = $hasUser;

                    $canUseProjects = false;
                    $canUseNews = false;
                    if ($hasUser && $isAdmin) {
                        $canUseProjects = true;
                        $canUseNews = true;
                    } elseif ($hasUser) {
                        $userEmail = (string)($_SESSION['user_email'] ?? '');
                        if ($userEmail !== '') {
                            $sub = \App\Models\Subscription::findLastByEmail($userEmail);
                            if ($sub && !empty($sub['plan_id'])) {
                                $status = strtolower((string)($sub['status'] ?? ''));
                                $isActive = !in_array($status, ['canceled', 'expired'], true);
                                if ($isActive) {
                                    $plan = \App\Models\Plan::findById((int)$sub['plan_id']);
                                    $canUseProjects = !empty($plan['allow_projects_access']);
                                    $slug = (string)($plan['slug'] ?? '');
                                    $canUseNews = ($slug !== '' && $slug !== 'free');
                                }
                            }
                        }
                    }
                ?>
                <?php if ($canUseProjects): ?>
                    <a href="/projetos" class="sidebar-button<?= $isActiveNav('/projetos') ? ' sidebar-button--active' : '' ?>" data-tour="nav-projects">
                        <span class="icon" aria-hidden="true"><?php echo $renderMenuIcon('projects_list', '📁'); ?></span>
                        <span>Meus projetos</span>
                    </a>
                <?php endif; ?>

                <?php if ($canSeeHistory): ?>
                    <a href="/historico" class="sidebar-button<?= $isActiveNav('/historico') ? ' sidebar-button--active' : '' ?>" data-tour="nav-history">
                        <span class="icon" aria-hidden="true"><?php echo $renderMenuIcon('chat_history', '�'); ?></span>
                        <span>Histórico de chats</span>
                    </a>
                <?php endif; ?>

                <div class="sidebar-section-title" style="margin-top: 10px;">Guias rápidos</div>
                <a href="/" class="sidebar-button<?= $isActiveNav('/') ? ' sidebar-button--active' : '' ?>" data-tour="nav-home">
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
                <a href="/planos" class="sidebar-button<?= $isActiveNav('/planos') ? ' sidebar-button--active' : '' ?>" data-tour="nav-plans">
                    <span class="icon" aria-hidden="true"><?php echo $renderMenuIcon('quick_plans', '💳'); ?></span>
                    <span>Planos e limites</span>
                </a>
                <a href="/cursos" class="sidebar-button<?= $isActiveNav('/cursos') ? ' sidebar-button--active' : '' ?>" data-tour="nav-courses">
                    <span class="icon" aria-hidden="true"><?php echo $renderMenuIcon('quick_courses', '🎓'); ?></span>
                    <span>Cursos</span>
                </a>

                <?php if ($canUseNews): ?>
                    <a href="/noticias" class="sidebar-button<?= $isActiveNav('/noticias') ? ' sidebar-button--active' : '' ?>">
                        <span class="icon" aria-hidden="true"><?php echo $renderMenuIcon('quick_news', '🗞'); ?></span>
                        <span>Notícias</span>
                    </a>
                <?php endif; ?>

                <?php if (!empty($_SESSION['user_id'])): ?>
                    <div class="sidebar-section-title" style="margin-top: 10px;">Rede social do Tuquinha</div>
                    <a href="/perfil" class="sidebar-button<?= $isActiveNav('/perfil') ? ' sidebar-button--active' : '' ?>">
                        <span class="icon" aria-hidden="true"><?php echo $renderMenuIcon('social_profile', '🧑'); ?></span>
                        <span>Perfil social</span>
                    </a>
                    <a href="/amigos" class="sidebar-button<?= $isActiveNav('/amigos') ? ' sidebar-button--active' : '' ?>">
                        <span class="icon" aria-hidden="true"><?php echo $renderMenuIcon('social_friends', '👥'); ?></span>
                        <span>Amigos</span>
                    </a>
                    <a href="/comunidades" class="sidebar-button<?= $isActiveNav('/comunidades') ? ' sidebar-button--active' : '' ?>">
                        <span class="icon" aria-hidden="true"><?php echo $renderMenuIcon('social_communities', '💬'); ?></span>
                        <span>Comunidades</span>
                    </a>
                <?php endif; ?>

                <?php if (!empty($_SESSION['user_id'])): ?>
                    <div class="sidebar-section-title" style="margin-top: 10px;">Conta</div>
                    <?php
                        $userEmailForPlan = (string)($_SESSION['user_email'] ?? '');
                        $subscriptionForMenu = null;
                        $currentPlanForMenu = null;
                        $hasActiveSubscriptionForMenu = false;

                        if ($userEmailForPlan !== '') {
                            try {
                                $subscriptionForMenu = \App\Models\Subscription::findLastByEmail($userEmailForPlan);
                                if ($subscriptionForMenu && !empty($subscriptionForMenu['plan_id'])) {
                                    $status = strtolower((string)($subscriptionForMenu['status'] ?? ''));
                                    $hasActiveSubscriptionForMenu = !in_array($status, ['canceled', 'expired'], true);
                                    if ($hasActiveSubscriptionForMenu) {
                                        $currentPlanForMenu = \App\Models\Plan::findById((int)$subscriptionForMenu['plan_id']);
                                    }
                                }
                            } catch (\Throwable $e) {
                                $subscriptionForMenu = null;
                                $currentPlanForMenu = null;
                                $hasActiveSubscriptionForMenu = false;
                            }
                        }

                        $canUsePersonalities = !empty($_SESSION['is_admin']) || (!empty($currentPlanForMenu) && !empty($currentPlanForMenu['allow_personalities']));
                        $monthlyTokenLimitForMenu = !empty($currentPlanForMenu) ? (int)($currentPlanForMenu['monthly_token_limit'] ?? 0) : 0;
                        $canBuyExtraTokens = !empty($_SESSION['is_admin']) || ($hasActiveSubscriptionForMenu && $monthlyTokenLimitForMenu > 0);

                        $hasCompletedCoursesForMenu = false;
                        try {
                            $hasCompletedCoursesForMenu = \App\Models\UserCourseBadge::hasAnyByUserId((int)$_SESSION['user_id']);
                        } catch (\Throwable $e) {
                            $hasCompletedCoursesForMenu = false;
                        }
                    ?>
                    <a href="/conta" class="sidebar-button<?= $isActiveNav('/conta') ? ' sidebar-button--active' : '' ?>" data-tour="nav-account">
                        <span class="icon" aria-hidden="true"><?php echo $renderMenuIcon('account_home', '👤'); ?></span>
                        <span>Minha conta</span>
                    </a>
                    <?php if ($hasCompletedCoursesForMenu): ?>
                        <a href="/certificados" class="sidebar-button<?= $isActiveNav('/certificados') ? ' sidebar-button--active' : '' ?>">
                            <span class="icon" aria-hidden="true"><?php echo $renderMenuIcon('account_certificates', '🏅'); ?></span>
                            <span>Cursos concluídos</span>
                        </a>
                    <?php endif; ?>
                    <?php if ($canUsePersonalities): ?>
                        <a href="/conta/personalidade" class="sidebar-button<?= $isActiveNav('/conta/personalidade') ? ' sidebar-button--active' : '' ?>">
                            <span class="icon" aria-hidden="true"><?php echo $renderMenuIcon('account_persona', '🎭'); ?></span>
                            <span>Personalidade padrão</span>
                        </a>
                    <?php endif; ?>
                    <?php if ($canBuyExtraTokens): ?>
                        <a href="/tokens/historico" class="sidebar-button<?= $isActiveNav('/tokens') ? ' sidebar-button--active' : '' ?>">
                            <span class="icon" aria-hidden="true"><?php echo $renderMenuIcon('account_tokens', '🔋'); ?></span>
                            <span>Histórico de tokens extras</span>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($_SESSION['is_admin']) || $hasActiveSubscriptionForMenu): ?>
                        <a href="/suporte" class="sidebar-button<?= $isActiveNav('/suporte') ? ' sidebar-button--active' : '' ?>" style="margin-top: 6px;">
                            <span class="icon" aria-hidden="true"><?php echo $renderMenuIcon('account_support', '🛟'); ?></span>
                            <span>Suporte</span>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($isCoursePartner)): ?>
                        <a href="/parceiro/cursos" class="sidebar-button<?= $isActiveNav('/parceiro/cursos') ? ' sidebar-button--active' : '' ?>">
                            <span class="icon" aria-hidden="true"><?php echo $renderMenuIcon('partner_courses', '🎓'); ?></span>
                            <span>Meus cursos (parceiro)</span>
                        </a>
                        <a href="/parceiro/comissoes" class="sidebar-button<?= $isActiveNav('/parceiro/comissoes') ? ' sidebar-button--active' : '' ?>" style="margin-top: 6px;">
                            <span class="icon" aria-hidden="true"><?php echo $renderMenuIcon('partner_commissions', '💰'); ?></span>
                            <span>Minhas comissões</span>
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
                        <span class="icon" aria-hidden="true"><?php echo $renderMenuIcon('admin_menu_icons', '🖼'); ?></span>
                        <span>Ícones do menu</span>
                    </a>
                    <a href="/admin/planos" class="sidebar-button" style="margin-top: 6px;">
                        <span class="icon" aria-hidden="true"><?php echo $renderMenuIcon('admin_plans', '🧩'); ?></span>
                        <span>Gerenciar planos</span>
                    </a>
                    <a href="/admin/financas" class="sidebar-button" style="margin-top: 6px;">
                        <span class="icon" aria-hidden="true"><?php echo $renderMenuIcon('admin_finance', '📈'); ?></span>
                        <span>Finanças</span>
                    </a>
                    <a href="/admin/cursos" class="sidebar-button" style="margin-top: 6px;">
                        <span class="icon" aria-hidden="true"><?php echo $renderMenuIcon('admin_courses', '🎓'); ?></span>
                        <span>Cursos</span>
                    </a>
                    <a href="/admin/comissoes" class="sidebar-button" style="margin-top: 6px;">
                        <span class="icon" aria-hidden="true"><?php echo $renderMenuIcon('admin_commissions', '💰'); ?></span>
                        <span>Comissões</span>
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
                    <a href="/perfil" class="env-pill env-pill--user" style="text-decoration:none;">
                        <span class="env-pill-avatar" aria-hidden="true">
                            <?php if ($sidebarAvatarPath !== ''): ?>
                                <img src="<?= htmlspecialchars($sidebarAvatarPath, ENT_QUOTES, 'UTF-8') ?>" alt="">
                            <?php else: ?>
                                <?= htmlspecialchars($sidebarInitial, ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                        </span>
                        <?php $nomeSaudacao = $_SESSION['user_name'] ?? 'designer'; ?>
                        <span>Olá, <?= htmlspecialchars($nomeSaudacao) ?></span>
                    </a>
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

    <div id="incomingCallModal" style="position:fixed; inset:0; display:none; align-items:center; justify-content:center; z-index:9999;">
        <div id="incomingCallBackdrop" style="position:absolute; inset:0; background:rgba(0,0,0,0.65);"></div>
        <div style="position:relative; width:min(420px, calc(100vw - 28px)); border-radius:18px; border:1px solid #272727; background:#111118; padding:14px 14px 12px 14px; box-shadow:0 18px 50px rgba(0,0,0,0.6);">
            <div style="font-size:12px; color:#b0b0b0;">Chamada de vídeo recebida</div>
            <div style="font-size:16px; font-weight:650; color:#f5f5f5; margin-top:4px;">
                <span id="incomingCallName">Seu amigo</span> está chamando você
            </div>
            <div style="font-size:12px; color:#b0b0b0; margin-top:6px; line-height:1.35;">Clique para entrar na chamada.</div>
            <div style="display:flex; gap:8px; margin-top:12px;">
                <button type="button" id="incomingCallAccept" style="flex:1; border:none; border-radius:999px; padding:9px 12px; font-size:13px; font-weight:650; cursor:pointer; background:linear-gradient(135deg,#4caf50,#8bc34a); color:#050509;">
                    Entrar na chamada
                </button>
                <button type="button" id="incomingCallDismiss" style="flex:1; border:none; border-radius:999px; padding:9px 12px; font-size:13px; cursor:pointer; background:#1c1c24; color:#f5f5f5; border:1px solid #272727;">
                    Agora não
                </button>
            </div>
        </div>
    </div>

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

    // Popup global de chamada recebida (exceto ao assistir aula/live)
    (function () {
        try {
            if (!document.body) return;
            if (!<?= json_encode(!empty($_SESSION['user_id'])) ?>) return;

            var path = window.location.pathname || '';
            if (path.indexOf('/cursos/aulas/ver') === 0 || path.indexOf('/cursos/lives/ver') === 0) {
                return;
            }

            var modal = document.getElementById('incomingCallModal');
            var backdrop = document.getElementById('incomingCallBackdrop');
            var acceptBtn = document.getElementById('incomingCallAccept');
            var dismissBtn = document.getElementById('incomingCallDismiss');
            var nameSpan = document.getElementById('incomingCallName');
            if (!modal || !acceptBtn || !dismissBtn) return;

            var currentIncoming = null; // { conversation_id, from_user_id, from_user_name }
            var inFlight = false;
            var snoozeUntil = 0;

            function lockKey(data) {
                if (!data) return '';
                var cid = Number(data.conversation_id) || 0;
                var fromId = Number(data.from_user_id) || 0;
                var createdAt = String(data.offer_created_at || '');
                if (!cid || !fromId || !createdAt) return '';
                return 'tuquinha_webrtc_incoming_ack:' + cid + ':' + fromId + ':' + createdAt;
            }

            function hasLock(data) {
                try {
                    var key = lockKey(data);
                    if (!key) return false;
                    if (!window.localStorage) return false;
                    return !!localStorage.getItem(key);
                } catch (e) {
                    return false;
                }
            }

            function setLock(data) {
                try {
                    var key = lockKey(data);
                    if (!key) return;
                    if (!window.localStorage) return;
                    localStorage.setItem(key, String(Date.now ? Date.now() : 1));
                } catch (e) {}
            }

            function nowMs() {
                return Date.now ? Date.now() : 0;
            }

            function playIncomingBeep() {
                try {
                    var AudioCtx = window.AudioContext || window.webkitAudioContext;
                    if (!AudioCtx) return;
                    var ctx = new AudioCtx();
                    var o = ctx.createOscillator();
                    var g = ctx.createGain();
                    o.type = 'sine';
                    o.frequency.value = 880;
                    g.gain.value = 0.001;
                    o.connect(g);
                    g.connect(ctx.destination);
                    o.start();
                    g.gain.exponentialRampToValueAtTime(0.12, ctx.currentTime + 0.02);
                    g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.18);
                    o.stop(ctx.currentTime + 0.2);
                    o.onended = function () {
                        try { ctx.close(); } catch (e) {}
                    };
                } catch (e) {}
            }

            function showModal(data) {
                currentIncoming = data;
                if (nameSpan && data && data.from_user_name) {
                    nameSpan.textContent = String(data.from_user_name);
                }
                modal.style.display = 'flex';
                playIncomingBeep();
            }

            function hideModal() {
                modal.style.display = 'none';
            }

            function pollIncoming() {
                if (inFlight) return;
                if (nowMs() && nowMs() < snoozeUntil) return;
                inFlight = true;

                fetch('/social/webrtc/incoming', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    credentials: 'same-origin'
                }).then(function (r) {
                    return r.json();
                }).then(function (json) {
                    if (!json || !json.ok) return;

                    if (!json.incoming) {
                        currentIncoming = null;
                        hideModal();
                        return;
                    }

                    var cid = Number(json.conversation_id) || 0;
                    var fromId = Number(json.from_user_id) || 0;
                    if (!cid || !fromId) return;

                    var offerCreatedAt = String(json.offer_created_at || '');
                    var incomingData = {
                        conversation_id: cid,
                        from_user_id: fromId,
                        from_user_name: String(json.from_user_name || 'Seu amigo'),
                        offer_created_at: offerCreatedAt
                    };

                    if (hasLock(incomingData)) {
                        currentIncoming = null;
                        hideModal();
                        return;
                    }

                    if (!currentIncoming || currentIncoming.conversation_id !== cid || currentIncoming.from_user_id !== fromId || String(currentIncoming.offer_created_at || '') !== offerCreatedAt) {
                        showModal(incomingData);
                    }
                }).catch(function () {
                }).finally(function () {
                    inFlight = false;
                });
            }

            if (backdrop) {
                backdrop.addEventListener('click', function () {
                    hideModal();
                    snoozeUntil = (nowMs() || 0) + 30000;
                });
            }
            dismissBtn.addEventListener('click', function () {
                hideModal();
                snoozeUntil = (nowMs() || 0) + 30000;
            });
            acceptBtn.addEventListener('click', function () {
                if (!currentIncoming || !currentIncoming.from_user_id) return;
                setLock(currentIncoming);
                hideModal();
                var url = '/social/chat?user_id=' + encodeURIComponent(String(currentIncoming.from_user_id)) + '&join_call=1';
                window.location.href = url;
            });

            // Loop simples
            setInterval(pollIncoming, 2500);
            pollIncoming();
        } catch (e) {}
    })();
    </script>

    <?php
        $tuqOnboarding = !empty($_SESSION['tuq_onboarding_tour']);
        $tuqOnboardingForce = !empty($_SESSION['tuq_onboarding_tour_force']);
        if ($tuqOnboarding) {
            unset($_SESSION['tuq_onboarding_tour']);
        }
        if ($tuqOnboardingForce) {
            unset($_SESSION['tuq_onboarding_tour_force']);
        }
    ?>
    <script>
        window.TUQ_TOUR_CONFIG = {
            onboarding: <?= $tuqOnboarding ? 'true' : 'false' ?>,
            force: <?= $tuqOnboardingForce ? 'true' : 'false' ?>,
            allowFab: false
        };
    </script>
    <?php
        $tuqTourJsPath = dirname(__DIR__, 3) . '/public/tuquinha-tour.js';
        $tuqTourJsV = is_file($tuqTourJsPath) ? (string)filemtime($tuqTourJsPath) : (string)time();
    ?>
    <script src="/public/tuquinha-tour.js?v=<?= htmlspecialchars($tuqTourJsV) ?>"></script>
</body>
</html>
