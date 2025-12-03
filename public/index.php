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
$router->get('/debug/asaas', 'CheckoutController@debugLastAsaas');
$router->get('/suporte', 'SupportController@index');
$router->get('/login', 'AuthController@showLogin');
$router->post('/login', 'AuthController@login');
$router->get('/registrar', 'AuthController@showRegister');
$router->post('/registrar', 'AuthController@register');
$router->get('/logout', 'AuthController@logout');
$router->get('/senha/esqueci', 'AuthController@showForgotPassword');
$router->post('/senha/esqueci', 'AuthController@sendForgotPassword');
$router->get('/senha/reset', 'AuthController@showResetPassword');
$router->post('/senha/reset', 'AuthController@resetPassword');
$router->get('/verificar-email', 'AuthController@showVerifyEmail');
$router->post('/verificar-email', 'AuthController@verifyEmail');
$router->post('/verificar-email/reenviar', 'AuthController@resendVerification');
$router->get('/conta', 'AccountController@index');
$router->post('/conta', 'AccountController@updateProfile');
$router->post('/conta/senha', 'AccountController@updatePassword');
$router->post('/conta/assinatura/cancelar', 'AccountController@cancelSubscription');
$router->get('/admin/login', 'AdminAuthController@login');
$router->post('/admin/login', 'AdminAuthController@authenticate');
$router->get('/admin/logout', 'AdminAuthController@logout');
$router->get('/admin', 'AdminDashboardController@index');
$router->get('/admin/config', 'AdminConfigController@index');
$router->post('/admin/config', 'AdminConfigController@save');
$router->post('/admin/config/test-email', 'AdminConfigController@sendTestEmail');
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
$router->get('/admin/erros', 'AdminErrorReportController@index');
$router->get('/admin/erros/ver', 'AdminErrorReportController@show');
$router->post('/admin/erros/estornar', 'AdminErrorReportController@refund');
$router->post('/admin/erros/resolver', 'AdminErrorReportController@resolve');
$router->post('/admin/erros/descartar', 'AdminErrorReportController@dismiss');
$router->get('/chat', 'ChatController@index');
$router->post('/chat/send', 'ChatController@send');
$router->post('/chat/audio', 'ChatController@sendAudio');

// Configurações por conversa (regras/memórias específicas do chat)
$router->post('/chat/settings', 'ChatController@saveSettings');

// Webhook de eventos do Asaas (renovações, pagamentos etc.)
$router->post('/webhooks/asaas', 'AsaasWebhookController@handle');

// Relato de erros de análise pelos usuários
$router->post('/erro/reportar', 'ErrorReportController@store');

$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
