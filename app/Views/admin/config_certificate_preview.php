<?php
/**
 * View standalone para preview do certificado dentro do Admin Config.
 * Variáveis esperadas (vindas do AdminConfigController@certificatePreview):
 * - $theme (dark|light)
 * - $issuerName
 * - $issuerSignatureImage
 * - $verifyUrl
 * - $course
 * - $user
 * - $badge
 */
?><!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Preview do certificado</title>
    <style>
        :root {
            --border-subtle: rgba(255,255,255,0.14);
            --surface-card: #0a0a10;
            --surface-subtle: #111118;
            --text-primary: #f5f5f5;
            --text-secondary: #b0b0b0;
        }

        body[data-theme="light"] {
            --border-subtle: rgba(0,0,0,0.10);
            --surface-card: #ffffff;
            --surface-subtle: #f5f5f7;
            --text-primary: #121212;
            --text-secondary: #666;
        }

        html, body {
            margin: 0;
            padding: 0;
            background: var(--surface-subtle);
            color: var(--text-primary);
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .wrap {
            padding: 12px;
        }

        .no-print {
            display: none !important;
        }

        @media (max-width: 520px) {
            .wrap { padding: 8px; }
        }
    </style>
</head>
<body data-theme="<?= htmlspecialchars((string)$theme, ENT_QUOTES, 'UTF-8') ?>">
    <div class="wrap">
        <?php
            $issuerSignatureImage = (string)($issuerSignatureImage ?? '');
            $issuerName = (string)($issuerName ?? '');
            $verifyUrl = (string)($verifyUrl ?? '');

            $viewFile = __DIR__ . '/../certificates/show.php';
            if (file_exists($viewFile)) {
                include $viewFile;
            } else {
                echo 'Preview indisponível.';
            }
        ?>
    </div>
</body>
</html>
