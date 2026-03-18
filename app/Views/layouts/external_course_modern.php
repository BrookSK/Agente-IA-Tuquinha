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
$headerImageUrl = '';
$footerImageUrl = '';
$heroImageUrl = '';
$backgroundImageUrl = '';
$brandSubtitle = '';

if (isset($branding) && is_array($branding)) {
    $companyName = trim((string)($branding['company_name'] ?? ''));
    $logoUrl = trim((string)($branding['logo_url'] ?? ''));
    $primary = trim((string)($branding['primary_color'] ?? ''));
    $secondary = trim((string)($branding['secondary_color'] ?? ''));
    $textColor = trim((string)($branding['text_color'] ?? ''));
    $buttonTextColor = trim((string)($branding['button_text_color'] ?? ''));
    $headerImageUrl = trim((string)($branding['header_image_url'] ?? ''));
    $footerImageUrl = trim((string)($branding['footer_image_url'] ?? ''));
    $heroImageUrl = trim((string)($branding['hero_image_url'] ?? ''));
    $backgroundImageUrl = trim((string)($branding['background_image_url'] ?? ''));
}

if ($companyName === '') {
    $companyName = 'Plataforma de Cursos';
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
            --border: #2a2a3e;
            --accent: <?= $primary !== '' ? esc_attr($primary) : '#6366f1' ?>;
            --accent2: <?= $secondary !== '' ? esc_attr($secondary) : '#8b5cf6' ?>;
            --button-text: <?= $buttonTextColor !== '' ? esc_attr($buttonTextColor) : '#ffffff' ?>;
            --success: #10b981;
            --error: #ef4444;
            --warning: #f59e0b;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg-main);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            <?php if ($backgroundImageUrl !== ''): ?>
            background-image: url('<?= esc_attr($backgroundImageUrl) ?>');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            <?php endif; ?>
        }
        
        <?php if ($backgroundImageUrl !== ''): ?>
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(10,10,15,0.95) 0%, rgba(20,20,31,0.9) 100%);
            z-index: 0;
        }
        <?php endif; ?>
        
        .site-wrapper {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        /* Header */
        .site-header {
            background: rgba(20, 20, 31, 0.8);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1rem 2rem;
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
        }
        
        .header-nav a {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: color 0.2s;
        }
        
        .header-nav a:hover {
            color: var(--text-primary);
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            padding: 3rem 2rem;
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
            padding-top: 2rem;
            border-top: 1px solid var(--border);
            text-align: center;
            color: var(--text-muted);
            font-size: 0.85rem;
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
            color: var(--button-text);
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
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
            }
            
            .header-nav {
                flex-direction: column;
                gap: 0.75rem;
            }
            
            .hero-title {
                font-size: 2rem;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="site-wrapper">
        <header class="site-header">
            <div class="header-content">
                <a href="<?= isset($token) ? '/curso-externo?token=' . urlencode($token) : '/' ?>" class="header-brand">
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
                
                <nav class="header-nav">
                    <?php if (empty($_SESSION['user_id'])): ?>
                        <a href="<?= isset($token) ? '/curso-externo/login?token=' . urlencode($token) : '/login' ?>">Entrar</a>
                        <a href="<?= isset($token) ? '/curso-externo/checkout?token=' . urlencode($token) : '/registrar' ?>" class="btn" style="padding: 0.5rem 1.25rem; font-size: 0.9rem;">Começar Agora</a>
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
                © <?= date('Y') ?> <?= esc_attr($companyName) ?>. Todos os direitos reservados.
            </div>
        </footer>
    </div>
</body>
</html>
