<?php
/** @var string $viewFile */
/** @var array $user */
/** @var array|null $branding */

$companyName = '';
$logoUrl = '';
$primary = '';
$secondary = '';

if (isset($branding) && is_array($branding)) {
    $companyName = trim((string)($branding['company_name'] ?? ''));
    $logoUrl = trim((string)($branding['logo_url'] ?? ''));
    $primary = trim((string)($branding['primary_color'] ?? ''));
    $secondary = trim((string)($branding['secondary_color'] ?? ''));
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
            --text-primary: #f5f5f5;
            --text-secondary: #b0b0b0;
            --border: #272727;
            --accent: <?= $primary !== '' ? esc_attr($primary) : '#e53935' ?>;
            --accent2: <?= $secondary !== '' ? esc_attr($secondary) : '#ff6f60' ?>;
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
            color: #050509;
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
            transition: background 0.2s;
            cursor: pointer;
        }
        .nav-item:hover { background: rgba(255,255,255,0.05); }
        .nav-item.active {
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            color: #050509;
            font-weight: 700;
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
            color: #050509;
            font-weight: 700;
            font-size: 13px;
            border: none;
            cursor: pointer;
            text-decoration: none;
        }
        .btn:hover { opacity: 0.9; }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="logo">
                <div class="logo-img">
                    <?php if ($logoUrl !== ''): ?>
                        <img src="<?= esc_attr($logoUrl) ?>" alt="<?= esc_attr($companyName) ?>">
                    <?php else: ?>
                        <?= esc_attr(mb_strtoupper(mb_substr($companyName, 0, 1, 'UTF-8'), 'UTF-8')) ?>
                    <?php endif; ?>
                </div>
                <div class="logo-text"><?= esc_attr($companyName) ?></div>
            </div>
            
            <nav>
                <a href="/painel-externo" class="nav-item <?= strpos($_SERVER['REQUEST_URI'] ?? '', '/painel-externo') === 0 && strpos($_SERVER['REQUEST_URI'] ?? '', '/painel-externo/') === false ? 'active' : '' ?>">
                    <span>🏠</span>
                    <span>Início</span>
                </a>
                <a href="/painel-externo/cursos" class="nav-item <?= strpos($_SERVER['REQUEST_URI'] ?? '', '/painel-externo/cursos') !== false ? 'active' : '' ?>">
                    <span>📚</span>
                    <span>Cursos</span>
                </a>
                <a href="/painel-externo/meus-cursos" class="nav-item <?= strpos($_SERVER['REQUEST_URI'] ?? '', '/painel-externo/meus-cursos') !== false ? 'active' : '' ?>">
                    <span>✅</span>
                    <span>Meus Cursos</span>
                </a>
                <a href="/painel-externo/comunidade" class="nav-item <?= strpos($_SERVER['REQUEST_URI'] ?? '', '/painel-externo/comunidade') !== false ? 'active' : '' ?>">
                    <span>👥</span>
                    <span>Comunidade</span>
                </a>
                <a href="/logout" class="nav-item" style="margin-top: 20px; border-top: 1px solid var(--border); padding-top: 16px;">
                    <span>🚪</span>
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
