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
$router->get('/historico', 'HistoryController@index');
$router->post('/historico/renomear', 'HistoryController@rename');
$router->get('/checkout', 'CheckoutController@show');
$router->post('/checkout', 'CheckoutController@process');
$router->get('/login', 'AuthController@showLogin');
$router->post('/login', 'AuthController@login');
$router->get('/registrar', 'AuthController@showRegister');
$router->post('/registrar', 'AuthController@register');
$router->get('/logout', 'AuthController@logout');
$router->get('/senha/esqueci', 'AuthController@showForgotPassword');
$router->post('/senha/esqueci', 'AuthController@sendForgotPassword');
$router->get('/senha/reset', 'AuthController@showResetPassword');
$router->post('/senha/reset', 'AuthController@resetPassword');
$router->get('/conta', 'AccountController@index');
$router->post('/conta', 'AccountController@updateProfile');
$router->post('/conta/senha', 'AccountController@updatePassword');
$router->get('/admin/login', 'AdminAuthController@login');
$router->post('/admin/login', 'AdminAuthController@authenticate');
$router->get('/admin/logout', 'AdminAuthController@logout');
$router->get('/admin', 'AdminDashboardController@index');
$router->get('/admin/config', 'AdminConfigController@index');
$router->post('/admin/config', 'AdminConfigController@save');
$router->get('/admin/planos', 'AdminPlanController@index');
$router->get('/admin/planos/novo', 'AdminPlanController@form');
$router->get('/admin/planos/editar', 'AdminPlanController@form');
$router->post('/admin/planos/salvar', 'AdminPlanController@save');
$router->get('/admin/planos/ativar', 'AdminPlanController@toggleActive');
$router->get('/admin/usuarios', 'AdminUserController@index');
$router->get('/admin/usuarios/ver', 'AdminUserController@show');
$router->post('/admin/usuarios/toggle', 'AdminUserController@toggleActive');
$router->post('/admin/usuarios/toggle-admin', 'AdminUserController@toggleAdmin');
$router->get('/admin/assinaturas', 'AdminSubscriptionController@index');
$router->get('/chat', 'ChatController@index');
$router->post('/chat/send', 'ChatController@send');
$router->post('/chat/audio', 'ChatController@sendAudio');

$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
