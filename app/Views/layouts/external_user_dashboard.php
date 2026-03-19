<?php
/** @var string $viewFile */
/** @var array $user */
/** @var array|null $branding */

$companyName = '';
$logoUrl = '';
$primary = '';
$secondary = '';
$textColor = '';
$buttonTextColor = '';

if (isset($branding) && is_array($branding)) {
    $companyName = trim((string)($branding['company_name'] ?? ''));
    $logoUrl = trim((string)($branding['logo_url'] ?? ''));
    $primary = trim((string)($branding['primary_color'] ?? ''));
    $secondary = trim((string)($branding['secondary_color'] ?? ''));
    $textColor = trim((string)($branding['text_color'] ?? ''));
    $buttonTextColor = trim((string)($branding['button_text_color'] ?? ''));
}

if ($companyName === '') {
    $companyName = 'Meu Painel';
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
    <meta name="theme-color" content="#111118">
    <title><?= esc_attr($pageTitle ?? $companyName) ?></title>
    <style>
        :root {
            --bg-main: #050509;
            --bg-card: #111118;
            --text-primary: <?= $textColor !== '' ? esc_attr($textColor) : '#f5f5f5' ?>;
            --text-secondary: #b0b0b0;
            --border: #272727;
            --accent: <?= $primary !== '' ? esc_attr($primary) : '#e53935' ?>;
            --accent2: <?= $secondary !== '' ? esc_attr($secondary) : '#ff6f60' ?>;
            --button-text: <?= $buttonTextColor !== '' ? esc_attr($buttonTextColor) : '#050509' ?>;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg-main);
            color: var(--text-primary);
            min-height: 100vh;
        }
        a { color: inherit; text-decoration: none; }
        .container {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 260px;
            background: var(--bg-card);
            border-right: 1px solid var(--border);
            padding: 20px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border);
        }
        .logo-img {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            overflow: hidden;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            color: var(--button-text);
        }
        .logo-img img { width: 100%; height: 100%; object-fit: cover; }
        .logo-text { font-weight: 700; font-size: 16px; }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 10px;
            margin-bottom: 6px;
            font-size: 14px;
            transition: background 0.2s, color 0.2s;
            cursor: pointer;
        }
        .nav-item svg {
            flex-shrink: 0;
            color: var(--text-secondary);
            transition: color 0.2s;
        }
        .nav-item:hover { background: rgba(255,255,255,0.05); }
        .nav-item:hover svg {
            color: var(--accent);
        }
        .nav-item.active {
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            color: var(--button-text);
        }
        .nav-item.active svg {
            color: var(--button-text);
        }
        .main-content {
            margin-left: 260px;
            flex: 1;
            padding: 30px;
        }
        .header {
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 6px;
        }
        .header p {
            font-size: 14px;
            color: var(--text-secondary);
        }
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            padding: 10px 16px;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            color: var(--button-text);
            font-weight: 700;
            font-size: 13px;
            border: none;
            cursor: pointer;
            text-decoration: none;
        }
        .btn:hover { opacity: 0.9; }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .container { flex-direction: column; }
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                padding: 16px;
            }
            .logo {
                margin-bottom: 16px;
                padding-bottom: 12px;
            }
            .logo img { max-height: 36px !important; max-width: 160px !important; }
            .logo-img { width: 36px; height: 36px; font-size: 16px; }
            .logo-text { font-size: 16px; }
            nav {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 8px;
            }
            .nav-item {
                padding: 12px;
                font-size: 13px;
                flex-direction: column;
                text-align: center;
                gap: 6px;
            }
            .nav-item svg { width: 18px; height: 18px; }
            .nav-item:last-child {
                grid-column: 1 / -1;
                margin-top: 8px;
                padding-top: 12px;
            }
            .main-content {
                margin-left: 0;
                padding: 20px 16px;
            }
            .header h1 { font-size: 22px; }
            .header p { font-size: 13px; }
            .card { padding: 16px; border-radius: 12px; }
        }
        
        @media (max-width: 640px) {
            .sidebar { padding: 12px; }
            .logo { margin-bottom: 12px; padding-bottom: 10px; }
            .logo img { max-height: 32px !important; max-width: 140px !important; }
            nav { gap: 6px; }
            .nav-item {
                padding: 10px 8px;
                font-size: 12px;
                gap: 4px;
            }
            .nav-item svg { width: 16px; height: 16px; }
            .main-content { padding: 16px 12px; }
            .header { margin-bottom: 20px; }
            .header h1 { font-size: 20px; }
            .header p { font-size: 12px; }
            .card { padding: 14px; margin-bottom: 16px; }
            .btn { padding: 9px 14px; font-size: 12px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="logo">
                <?php if ($logoUrl !== ''): ?>
                    <img src="<?= esc_attr($logoUrl) ?>" alt="<?= esc_attr($companyName) ?>" style="max-height: 50px; width: auto; max-width: 220px; object-fit: contain;">
                <?php else: ?>
                    <div class="logo-img">
                        <?= esc_attr(mb_strtoupper(mb_substr($companyName, 0, 1, 'UTF-8'), 'UTF-8')) ?>
                    </div>
                    <div class="logo-text"><?= esc_attr($companyName) ?></div>
                <?php endif; ?>
            </div>
            
            <nav>
                <a href="/painel-externo" class="nav-item <?= strpos($_SERVER['REQUEST_URI'] ?? '', '/painel-externo') === 0 && strpos($_SERVER['REQUEST_URI'] ?? '', '/painel-externo/') === false ? 'active' : '' ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    <span>Início</span>
                </a>
                <a href="/painel-externo/cursos" class="nav-item <?= strpos($_SERVER['REQUEST_URI'] ?? '', '/painel-externo/cursos') !== false ? 'active' : '' ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                        <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                    </svg>
                    <span>Catálogo de Cursos</span>
                </a>
                <a href="/painel-externo/meus-cursos" class="nav-item <?= strpos($_SERVER['REQUEST_URI'] ?? '', '/painel-externo/meus-cursos') !== false ? 'active' : '' ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 11 12 14 22 4"></polyline>
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                    </svg>
                    <span>Meus Cursos</span>
                </a>
                <a href="/painel-externo/perfil/editar" class="nav-item <?= strpos($_SERVER['REQUEST_URI'] ?? '', '/painel-externo/perfil/editar') !== false ? 'active' : '' ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span>Editar Perfil</span>
                </a>
                <a href="/painel-externo/amigos" class="nav-item <?= strpos($_SERVER['REQUEST_URI'] ?? '', '/painel-externo/amigos') !== false || strpos($_SERVER['REQUEST_URI'] ?? '', '/painel-externo/perfil') !== false || strpos($_SERVER['REQUEST_URI'] ?? '', '/painel-externo/chat') !== false ? 'active' : '' ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <span>Amigos</span>
                </a>
                <a href="/logout" class="nav-item" style="margin-top: 20px; border-top: 1px solid var(--border); padding-top: 16px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    <span>Sair</span>
                </a>
            </nav>
        </div>
        
        <div class="main-content">
            <?php include $viewFile; ?>
        </div>
    </div>
</body>
</html>
