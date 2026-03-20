<?php
/** @var string $viewFile */
/** @var array|null $branding */
/** @var string|null $pageTitle */

$companyName = '';
$logoUrl = '';
$primary = '';
$secondary = '';
$textColor = '';
$buttonTextColor = '';
$linkColor = '';
$paragraphColor = '';
$headerImageUrl = '';
$footerImageUrl = '';
$heroImageUrl = '';
$backgroundImageUrl = '';
$brandSubtitle = '';
$faviconUrl = '';

if (isset($branding) && is_array($branding)) {
    $companyName = trim((string)($branding['company_name'] ?? ''));
    $logoUrl = trim((string)($branding['logo_url'] ?? ''));
    $faviconUrl = trim((string)($branding['favicon_url'] ?? ''));
    $primary = trim((string)($branding['primary_color'] ?? ''));
    $secondary = trim((string)($branding['secondary_color'] ?? ''));
    $textColor = trim((string)($branding['text_color'] ?? ''));
    $buttonTextColor = trim((string)($branding['button_text_color'] ?? ''));
    $linkColor = trim((string)($branding['link_color'] ?? ''));
    $paragraphColor = trim((string)($branding['paragraph_color'] ?? ''));
    $headerImageUrl = trim((string)($branding['header_image_url'] ?? ''));
    $footerImageUrl = trim((string)($branding['footer_image_url'] ?? ''));
    $heroImageUrl = trim((string)($branding['hero_image_url'] ?? ''));
    $backgroundImageUrl = trim((string)($branding['background_image_url'] ?? ''));
}

if ($companyName === '') {
    $companyName = 'Plataforma de Cursos';
}

$isPartnerSite = !empty($isPartnerSite);
$slug = isset($slug) ? trim((string)$slug) : '';

$brandHref = '/';
if ($slug !== '') {
    $brandHref = '/curso/' . urlencode($slug);
}

$loginHref = '';
$ctaHref = '';
$ctaText = 'Começar Agora';
if (empty($_SESSION['user_id'])) {
    if ($slug !== '') {
        $loginHref = '/curso/' . urlencode($slug) . '/login';
        $ctaHref = '/curso/' . urlencode($slug) . '/checkout';
        // Busca preço do curso para mostrar no botão
        if (isset($course) && is_array($course)) {
            $priceCents = isset($course['price_cents']) ? (int)$course['price_cents'] : 0;
            if ($priceCents > 0) {
                $price = number_format($priceCents / 100, 2, ',', '.');
                $ctaText = 'Comprar por R$ ' . $price;
            } else {
                $ctaText = 'Criar Conta Grátis';
            }
        }
    } elseif (!$isPartnerSite) {
        $loginHref = '/login';
        $ctaHref = '/registrar';
    }
}

function esc_attr(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="<?= $primary !== '' ? esc_attr($primary) : '#e53935' ?>">
    <title><?= esc_attr($pageTitle ?? $companyName) ?></title>
    <?php if ($faviconUrl !== ''): ?>
        <link rel="icon" type="image/png" href="<?= esc_attr($faviconUrl) ?>">
    <?php else: ?>
        <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>📚</text></svg>">
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-main: #0a0a0f;
            --bg-card: #14141f;
            --bg-elevated: #1a1a2e;
            --text-primary: <?= $textColor !== '' ? esc_attr($textColor) : '#ffffff' ?>;
            --text-secondary: #a0a0b0;
            --text-muted: #6b6b7b;
            --paragraph-color: <?= $paragraphColor !== '' ? esc_attr($paragraphColor) : '#a0a0b0' ?>;
            --border: #2a2a3e;
            --accent: <?= $primary !== '' ? esc_attr($primary) : '#e53935' ?>;
            --accent2: <?= $secondary !== '' ? esc_attr($secondary) : ($primary !== '' ? esc_attr($primary) : '#c62828') ?>;
            --button-text: <?= $buttonTextColor !== '' ? esc_attr($buttonTextColor) : '#ffffff' ?>;
            --link-color: <?= $linkColor !== '' ? esc_attr($linkColor) : ($primary !== '' ? esc_attr($primary) : '#e53935') ?>;
            --success: #10b981;
            --error: #ef4444;
            --warning: #f59e0b;
            --bg-image: <?= $backgroundImageUrl !== '' ? ("url('" . esc_attr($backgroundImageUrl) . "')") : 'none' ?>;
            --bg-attachment: <?= $backgroundImageUrl !== '' ? 'fixed' : 'scroll' ?>;
            --bg-overlay-opacity: <?= $backgroundImageUrl !== '' ? '1' : '0' ?>;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        html {
            background: #0a0a0f !important;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #0a0a0f 0%, #14141f 50%, #1a1a2e 100%) !important;
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding-top: 100px;
            background-image: var(--bg-image);
            background-size: cover;
            background-position: center;
            background-attachment: var(--bg-attachment);
        }
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(10,10,15,0.95) 0%, rgba(20,20,31,0.9) 100%);
            z-index: 0;
            opacity: var(--bg-overlay-opacity);
            pointer-events: none;
        }
        
        .site-wrapper {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        /* Header */
        .site-header {
            background: rgba(8,9,13,.95);
            backdrop-filter: blur(12px);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            border-bottom: 1px solid var(--border);
        }
        
        .site-header::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 50%;
            transform: translateX(-50%);
            width: calc(100% - 160px);
            height: 4px;
            background: linear-gradient(90deg, #ff6b35 0%, #f7931e 50%, #fdc830 100%);
            border-radius: 4px;
            z-index: -1;
        }
        
        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1rem 80px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 2rem;
        }
        
        .header-brand {
            display: flex;
            align-items: center;
            gap: 1rem;
            text-decoration: none;
            position: relative;
            z-index: 10;
        }
        
        .header-logo {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            overflow: hidden;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 24px;
            color: var(--button-text);
        }
        
        .header-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .header-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-primary);
            letter-spacing: -0.02em;
        }
        
        .header-nav {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            position: relative;
            z-index: 10;
        }
        
        .header-nav a:not(.btn) {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: color 0.2s;
            background: transparent;
            border: none;
            padding: 0;
            box-shadow: none;
        }
        
        .header-nav a:not(.btn):hover {
            color: var(--text-primary);
        }
        
        a:not(.btn):not(.header-brand):not(.nav-item) {
            color: var(--link-color);
            transition: opacity 0.2s;
        }
        
        a:not(.btn):not(.header-brand):not(.nav-item):hover {
            opacity: 0.8;
        }
        
        p {
            color: var(--paragraph-color);
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            padding: 3rem 2rem;
            background: transparent;
            position: relative;
            z-index: 1;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .container-narrow {
            max-width: 600px;
            margin: 0 auto;
        }
        
        /* Footer */
        .site-footer {
            background: rgba(20, 20, 31, 0.8);
            backdrop-filter: blur(20px);
            border-top: 1px solid var(--border);
            padding: 3rem 2rem;
            margin-top: auto;
        }
        
        .footer-content {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }
        
        .footer-section h3 {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }
        
        .footer-section p,
        .footer-section a {
            color: var(--text-secondary);
            font-size: 0.9rem;
            text-decoration: none;
            display: block;
            margin-bottom: 0.5rem;
        }
        
        .footer-section a:hover {
            color: var(--accent);
        }
        
        .footer-image {
            width: 100%;
            max-width: 200px;
            border-radius: 8px;
            margin-top: 1rem;
        }
        
        .footer-bottom {
            max-width: 1400px;
            margin: 2rem auto 0;
            padding: 1.5rem 2rem;
            border-top: 1px solid var(--border);
            text-align: center;
            color: var(--text-muted);
            font-size: 0.85rem;
            z-index: 1000;
        }
        
        /* Cards */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.3);
        }
        
        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.875rem 1.75rem;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            color: var(--button-text) !important;
            font-weight: 700;
            font-size: 1rem;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(99, 102, 241, 0.3);
            color: var(--button-text) !important;
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid var(--accent);
            color: var(--accent);
        }
        
        .btn-outline:hover {
            background: var(--accent);
            color: var(--button-text);
        }
        
        /* Form Elements */
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }
        
        .form-input {
            width: 100%;
            padding: 0.875rem 1rem;
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text-primary);
            font-size: 1rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .form-hint {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-top: 0.5rem;
        }
        
        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid var(--error);
            border-radius: 10px;
            padding: 1rem;
            color: var(--error);
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }
        
        .success-message {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid var(--success);
            border-radius: 10px;
            padding: 1rem;
            color: var(--success);
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }
        
        /* Hero Section */
        .hero-section {
            padding: 4rem 0;
            text-align: center;
        }
        
        .hero-image {
            width: 100%;
            max-width: 800px;
            border-radius: 20px;
            margin: 0 auto 2rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
        }
        
        .hero-title {
            font-size: 3rem;
            font-weight: 900;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .hero-subtitle {
            font-size: 1.25rem;
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }
        
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            padding: 8px;
            cursor: pointer;
            color: var(--text-primary);
        }
        .mobile-menu-toggle svg {
            width: 24px;
            height: 24px;
        }
        
        @media (max-width: 768px) {
            body { overflow-x: hidden; padding-top: 140px; }
            .container { padding: 0 1rem; max-width: 100%; }
            .site-header { padding: 1rem; }
            .header-content {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                gap: 1rem;
            }
            .header-brand img { height: 36px !important; max-width: 160px !important; }
            .mobile-menu-toggle { display: block; }
            .header-nav {
                position: fixed;
                top: 72px;
                left: 0;
                right: 0;
                background: rgba(8,9,13,.98);
                backdrop-filter: blur(12px);
                flex-direction: column;
                padding: 20px 16px;
                gap: 12px;
                border-bottom: 1px solid var(--border);
                transform: translateY(-100%);
                opacity: 0;
                visibility: hidden;
                transition: transform 0.3s ease, opacity 0.3s ease, visibility 0.3s;
                z-index: 9998;
            }
            .header-nav.active {
                transform: translateY(0);
                opacity: 1;
                visibility: visible;
            }
            .header-nav a { font-size: 0.95rem; width: 100%; text-align: center; padding: 12px; }
            .header-nav .btn { padding: 12px 20px; font-size: 0.95rem; width: 100%; }
            .hero-title { font-size: 1.75rem; line-height: 1.2; }
            .card { padding: 1.25rem; border-radius: 12px; }
            .form-group { margin-bottom: 1rem; }
            .form-label { font-size: 0.85rem; margin-bottom: 0.375rem; }
            .form-input, .form-select { padding: 0.75rem; font-size: 0.9rem; }
            .form-hint { font-size: 0.75rem; }
            .btn { padding: 0.75rem 1.25rem; font-size: 0.9rem; }
            .footer-content { grid-template-columns: 1fr; gap: 1.5rem; }
            .footer-section { text-align: center; }
            .footer-bottom { font-size: 0.8rem; padding: 1rem; }
        }
        
        @media (max-width: 640px) {
            body { padding-top: 120px; }
            .container { padding: 0 0.75rem; }
            .site-header { padding: 0.75rem; height: 60px; }
            .header-content { padding: 0; }
            .header-brand img { height: 32px !important; max-width: 140px !important; }
            .header-nav { top: 60px; }
            .header-nav a, .header-nav .btn { width: 100%; text-align: center; }
            h1 { font-size: 1.5rem; line-height: 1.25; }
            h2 { font-size: 1.25rem; }
            p { font-size: 0.9rem; }
            .card { padding: 1rem; }
            .form-input, .form-select { font-size: 1rem; }
            .btn { width: 100%; padding: 0.875rem; }
        }
    </style>
</head>
<body>
    <div class="site-wrapper">
        <header class="site-header">
            <div class="header-content">
                <a href="<?= $brandHref ?>" class="header-brand">
                    <?php if ($logoUrl !== ''): ?>
                        <img src="<?= esc_attr($logoUrl) ?>" alt="<?= esc_attr($companyName) ?>" style="height: 50px; width: auto; max-width: 250px; object-fit: contain;">
                    <?php else: ?>
                        <div class="header-logo">
                            <?= esc_attr(mb_strtoupper(mb_substr($companyName, 0, 1, 'UTF-8'), 'UTF-8')) ?>
                        </div>
                        <span class="header-title"><?= esc_attr($companyName) ?></span>
                    <?php endif; ?>
                </a>
                
                <?php if ($headerImageUrl !== ''): ?>
                    <img src="<?= esc_attr($headerImageUrl) ?>" alt="Header" style="height: 50px; object-fit: contain;">
                <?php endif; ?>
                
                <button class="mobile-menu-toggle" onclick="toggleMobileMenu()" aria-label="Menu">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
                
                <nav class="header-nav" id="mobileMenu">
                    <?php if (empty($_SESSION['user_id'])): ?>
                        <?php if ($loginHref !== ''): ?>
                            <a href="<?= $loginHref ?>">Entrar</a>
                        <?php endif; ?>
                        <?php if ($ctaHref !== ''): ?>
                            <a href="<?= $ctaHref ?>" class="btn" style="padding: 0.5rem 1.25rem; font-size: 0.9rem;"><?= esc_attr($ctaText) ?></a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="/painel-externo">Meu Painel</a>
                        <a href="/logout">Sair</a>
                    <?php endif; ?>
                </nav>
            </div>
        </header>
        
        <main class="main-content">
            <?php include $viewFile; ?>
        </main>
        
        <footer class="site-footer">
            <div class="footer-content" style="text-align: center;">
                <div class="footer-section">
                    <h3><?= esc_attr($companyName) ?></h3>
                    <p>Plataforma profissional de cursos online.</p>
                    <?php if ($footerImageUrl !== ''): ?>
                        <img src="<?= esc_attr($footerImageUrl) ?>" alt="Footer" class="footer-image">
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="footer-bottom">
                Resenha 2.0 - Uma empresa Nuvem Labs
            </div>
        </footer>
    </div>
    
    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            const toggle = document.querySelector('.mobile-menu-toggle');
            if (menu) {
                menu.classList.toggle('active');
            }
            if (toggle) {
                toggle.classList.toggle('active');
            }
        }
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('mobileMenu');
            const toggle = document.querySelector('.mobile-menu-toggle');
            if (menu && toggle && !menu.contains(event.target) && !toggle.contains(event.target)) {
                menu.classList.remove('active');
                toggle.classList.remove('active');
            }
        });
    </script>
</body>
</html>
