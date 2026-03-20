<?php
/** @var string $viewFile */
/** @var array $course */
/** @var array|null $branding */

$brandSubtitle = isset($brandSubtitle) ? (string)$brandSubtitle : 'Área de membros';
$hideTopbarAction = array_key_exists('hideTopbarAction', get_defined_vars()) ? !empty($hideTopbarAction) : true;

$companyName = '';
$logoUrl = '';
$primary = '';
$secondary = '';
$faviconUrl = '';

$isPartnerSite = !empty($isPartnerSite);
$slug = isset($slug) ? trim((string)$slug) : '';
$loginHref = ($isPartnerSite && $slug !== '') ? ('/curso/' . urlencode($slug) . '/login') : '/login';

if (isset($branding) && is_array($branding)) {
    $companyName = trim((string)($branding['company_name'] ?? ''));
    $logoUrl = trim((string)($branding['logo_url'] ?? ''));
    $faviconUrl = trim((string)($branding['favicon_url'] ?? ''));
    $primary = trim((string)($branding['primary_color'] ?? ''));
    $secondary = trim((string)($branding['secondary_color'] ?? ''));
}

if ($companyName === '') {
    $companyName = trim((string)($course['title'] ?? 'Curso'));
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
    <title><?= esc_attr($companyName) ?></title>
    <link rel="icon" type="image/png" href="<?= $faviconUrl !== '' ? esc_attr($faviconUrl) : '/public/favicon.png' ?>">
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
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg-main);
            color: var(--text-primary);
            min-height: 100vh;
        }
        a { color: inherit; text-decoration: none; }
        .shell {
            max-width: 980px;
            margin: 0 auto;
            padding: 20px 14px 28px 14px;
        }
        .topbar {
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap: 10px;
            padding: 12px 14px;
            border: 1px solid var(--border);
            border-radius: 16px;
            background: linear-gradient(135deg, rgba(255,255,255,0.04), rgba(255,255,255,0.01));
            box-shadow: 0 16px 34px rgba(0,0,0,0.35);
        }
        .brand {
            display:flex;
            align-items:center;
            gap: 10px;
            min-width: 0;
        }
        .logo {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--border);
            background: rgba(255,255,255,0.06);
            flex: 0 0 42px;
            display:flex;
            align-items:center;
            justify-content:center;
        }
        .logo img { width:100%; height:100%; object-fit:cover; display:block; }
        .logo-fallback {
            width: 100%;
            height: 100%;
            display:flex;
            align-items:center;
            justify-content:center;
            font-weight: 800;
            color: #050509;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
        }
        .brand-title { font-weight: 850; letter-spacing: 0.01em; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .brand-sub { font-size: 12px; color: var(--text-secondary); }
        .content {
            margin-top: 14px;
            border: 1px solid var(--border);
            border-radius: 18px;
            background: var(--bg-card);
            padding: 16px 16px;
        }
        .btn {
            border: none;
            border-radius: 999px;
            padding: 10px 16px;
            cursor: pointer;
            font-weight: 800;
            font-size: 13px;
            color: #050509;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
        }
        .btn-outline {
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: 10px 16px;
            cursor: pointer;
            font-weight: 700;
            font-size: 13px;
            color: var(--text-primary);
            background: rgba(255,255,255,0.04);
        }
        .footer {
            margin-top: 14px;
            font-size: 11px;
            color: var(--text-secondary);
            text-align: center;
            opacity: 0.95;
        }
        input, select {
            width: 100%;
            padding: 9px 10px;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: rgba(0,0,0,0.35);
            color: var(--text-primary);
            outline: none;
            font-size: 13px;
        }
        label { font-size: 12px; color: var(--text-secondary); display:block; margin-bottom:4px; }
        .grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 12px; }
        .hint { font-size: 12px; color: var(--text-secondary); line-height:1.5; }
        .error {
            background: rgba(255, 111, 96, 0.12);
            border: 1px solid rgba(255, 111, 96, 0.35);
            color: #ffbaba;
            padding: 10px 12px;
            border-radius: 12px;
            font-size: 13px;
            margin-bottom: 12px;
        }
    </style>
</head>
<body>
    <div class="shell">
        <div class="topbar">
            <div class="brand">
                <div class="logo">
                    <?php if ($logoUrl !== ''): ?>
                        <img src="<?= esc_attr($logoUrl) ?>" alt="<?= esc_attr($companyName) ?>">
                    <?php else: ?>
                        <div class="logo-fallback"><?= esc_attr(mb_strtoupper(mb_substr($companyName, 0, 1, 'UTF-8'), 'UTF-8')) ?></div>
                    <?php endif; ?>
                </div>
                <div style="min-width:0;">
                    <div class="brand-title"><?= esc_attr($companyName) ?></div>
                    <div class="brand-sub"><?= esc_attr($brandSubtitle) ?></div>
                </div>
            </div>
            <?php if (!$hideTopbarAction): ?>
                <div>
                    <a class="btn-outline" href="<?= $loginHref ?>">Entrar</a>
                </div>
            <?php endif; ?>
        </div>

        <div class="content">
            <?php include $viewFile; ?>
        </div>

        <div class="footer">
            Copyright © Tuquinha IA
        </div>
    </div>
</body>
</html>
