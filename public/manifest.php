<?php
require_once __DIR__ . '/../config/config.php';

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strpos($class, $prefix) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relative);
    $file = __DIR__ . '/../app/' . $relativePath . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: public, max-age=3600');

$name = \App\Models\Branding::platformName();
$shortName = \App\Models\Branding::mascotName() . ' IA';

echo json_encode([
    'name' => $name,
    'short_name' => $shortName,
    'start_url' => '/chat',
    'display' => 'standalone',
    'background_color' => '#050509',
    'theme_color' => '#e53935',
    'lang' => 'pt-BR',
    'orientation' => 'portrait-primary',
    'icons' => [
        [
            'src' => '/public/icons/icon-192.png',
            'sizes' => '192x192',
            'type' => 'image/png',
        ],
        [
            'src' => '/public/icons/icon-512.png',
            'sizes' => '512x512',
            'type' => 'image/png',
        ],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
