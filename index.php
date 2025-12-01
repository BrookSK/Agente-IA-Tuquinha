<?php
// Redireciona requisições na raiz para a pasta public
$uri = $_SERVER['REQUEST_URI'] ?? '/';

// Evita loop se já estiver em /public
if (strpos($uri, '/public') !== 0) {
    header('Location: /public' . $uri);
    exit;
}

// Se o servidor estiver apontando diretamente pra raiz,
// e a URL já incluir /public, delega para o index da public.
require __DIR__ . '/public/index.php';
