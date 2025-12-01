<?php

session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Core/Router.php';
require_once __DIR__ . '/../app/Core/Controller.php';

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

use App\Core\Router;

$router = new Router();

$router->get('/', 'HomeController@index');
$router->get('/planos', 'PlanController@index');
$router->get('/checkout', 'CheckoutController@show');
$router->post('/checkout', 'CheckoutController@process');
$router->get('/chat', 'ChatController@index');
$router->post('/chat/send', 'ChatController@send');
$router->post('/chat/audio', 'ChatController@sendAudio');

$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
