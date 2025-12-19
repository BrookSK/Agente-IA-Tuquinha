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

// Gate de onboarding por indicação: se o usuário veio por indicação e o plano exige cartão,
// ele não pode navegar por outras telas até concluir o checkout (ou seja, até não existir mais
// um registro pending em user_referrals para este usuário).
try {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

    $allowedPrefixes = [
        '/',
        '/checkout',
        '/login',
        '/registrar',
        '/logout',
        '/verificar-email',
        '/senha',
        '/suporte',
    ];

    $isAllowed = false;
    foreach ($allowedPrefixes as $prefix) {
        if ($prefix === '/') {
            if ($path === '/') {
                $isAllowed = true;
                break;
            }
            continue;
        }
        if (strpos($path, $prefix) === 0) {
            $isAllowed = true;
            break;
        }
    }

    if (!$isAllowed && !empty($_SESSION['user_id']) && empty($_SESSION['is_admin'])) {
        $userId = (int)$_SESSION['user_id'];
        $pending = \App\Models\UserReferral::findFirstPendingForUser($userId);

        if ($pending && !empty($pending['plan_id'])) {
            $plan = \App\Models\Plan::findById((int)$pending['plan_id']);
            if ($plan && !empty($plan['referral_enabled']) && !empty($plan['referral_require_card'])) {
                $slug = (string)($plan['slug'] ?? '');
                if ($slug !== '') {
                    header('Location: /checkout?plan=' . urlencode($slug));
                    exit;
                }
            }
        }
    }
} catch (\Throwable $e) {
    // Se algo der errado no gate, não derruba o site.
}

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
$router->get('/projetos', 'ProjectController@index');
$router->get('/projetos/novo', 'ProjectController@createForm');
$router->post('/projetos/criar', 'ProjectController@create');
$router->get('/projetos/ver', 'ProjectController@show');
$router->post('/projetos/memoria/salvar', 'ProjectController@saveMemory');
$router->post('/projetos/chat/criar', 'ProjectController@createChat');
$router->post('/projetos/favoritar', 'ProjectController@toggleFavorite');
$router->post('/projetos/renomear', 'ProjectController@rename');
$router->post('/projetos/excluir', 'ProjectController@delete');
$router->post('/projetos/compartilhar/convidar', 'ProjectController@inviteCollaborator');
$router->get('/projetos/aceitar-convite', 'ProjectController@acceptInvite');
$router->post('/projetos/compartilhar/revogar', 'ProjectController@revokeInvite');
$router->post('/projetos/compartilhar/alterar-role', 'ProjectController@updateMemberRole');
$router->post('/projetos/compartilhar/remover', 'ProjectController@removeMember');
$router->post('/projetos/memoria-itens/atualizar', 'ProjectController@updateMemoryItem');
$router->post('/projetos/memoria-itens/excluir', 'ProjectController@deleteMemoryItem');
$router->post('/projetos/arquivo-base/upload', 'ProjectController@uploadBaseFile');
$router->post('/projetos/arquivo-base/texto', 'ProjectController@createBaseText');
$router->post('/projetos/arquivo-base/remover', 'ProjectController@removeBaseFile');
$router->get('/conta', 'AccountController@index');
$router->post('/conta', 'AccountController@updateProfile');
$router->post('/conta/senha', 'AccountController@updatePassword');
$router->post('/conta/assinatura/cancelar', 'AccountController@cancelSubscription');
$router->get('/conta/personalidade', 'PersonalityPreferenceController@index');
$router->post('/conta/personalidade', 'PersonalityPreferenceController@save');
$router->get('/tokens/comprar', 'TokenTopupController@show');
$router->post('/tokens/comprar', 'TokenTopupController@create');
$router->get('/tokens/historico', 'TokenTopupController@history');
$router->get('/personalidades', 'PersonalityController@index');
$router->get('/cursos', 'CourseController@index');
$router->get('/cursos/ver', 'CourseController@show');
$router->post('/cursos/inscrever', 'CourseController@enroll');
$router->post('/cursos/cancelar-inscricao', 'CourseController@unenroll');
$router->get('/cursos/lives', 'CourseController@lives');
$router->post('/cursos/lives/participar', 'CourseController@joinLive');
$router->get('/cursos/lives/ver', 'CourseController@watchLive');
$router->get('/cursos/aulas/ver', 'CourseController@watchLesson');
$router->post('/cursos/aulas/concluir', 'CourseController@completeLesson');
$router->get('/cursos/modulos/prova', 'CourseController@moduleExam');
$router->post('/cursos/modulos/prova', 'CourseController@moduleExamSubmit');
$router->get('/cursos/encerrar', 'CourseController@finishCourse');
$router->post('/cursos/encerrar', 'CourseController@finishCourseSubmit');
$router->post('/cursos/lives/comentar', 'CourseController@commentLive');
$router->post('/cursos/aulas/comentar', 'CourseController@commentLesson');
$router->get('/cursos/comprar', 'CoursePurchaseController@show');
$router->post('/cursos/comprar', 'CoursePurchaseController@process');
$router->get('/certificados', 'CertificateController@myCompletedCourses');
$router->get('/certificados/ver', 'CertificateController@show');
$router->get('/certificados/verificar', 'CertificateController@verify');
$router->get('/comunidade', 'CommunityController@index');
$router->post('/comunidade/postar', 'CommunityController@createPost');
$router->post('/comunidade/curtir', 'CommunityController@like');
$router->post('/comunidade/editar-post', 'CommunityController@editPost');
$router->post('/comunidade/excluir-post', 'CommunityController@deletePost');
$router->post('/comunidade/bloquear-usuario', 'CommunityController@blockUser');
$router->post('/comunidade/desbloquear-usuario', 'CommunityController@unblockUser');

$router->get('/perfil', 'ProfileController@show');
$router->post('/perfil/scrap', 'ProfileController@postScrap');
$router->post('/perfil/scrap/editar', 'ProfileController@editScrap');
$router->post('/perfil/scrap/excluir', 'ProfileController@deleteScrap');
$router->post('/perfil/scrap/visibilidade', 'ProfileController@toggleScrapVisibility');
$router->post('/perfil/depoimento', 'ProfileController@submitTestimonial');
$router->post('/perfil/depoimento/decidir', 'ProfileController@decideTestimonial');

$router->post('/perfil/salvar', 'ProfileController@saveProfile');

$router->get('/perfil/portfolio', 'SocialPortfolioController@listForUser');
$router->get('/perfil/portfolio/gerenciar', 'SocialPortfolioController@manage');
$router->get('/perfil/portfolio/ver', 'SocialPortfolioController@viewItem');
$router->post('/perfil/portfolio/salvar', 'SocialPortfolioController@upsert');
$router->post('/perfil/portfolio/excluir', 'SocialPortfolioController@delete');
$router->post('/perfil/portfolio/curtir', 'SocialPortfolioController@toggleLike');
$router->post('/perfil/portfolio/upload', 'SocialPortfolioController@uploadMedia');
$router->post('/perfil/portfolio/midia/excluir', 'SocialPortfolioController@deleteMedia');

$router->post('/perfil/portfolio/compartilhar/convidar', 'SocialPortfolioController@inviteCollaborator');
$router->get('/perfil/portfolio/aceitar-convite', 'SocialPortfolioController@acceptInvite');
$router->post('/perfil/portfolio/compartilhar/revogar', 'SocialPortfolioController@revokeInvite');
$router->post('/perfil/portfolio/compartilhar/alterar-role', 'SocialPortfolioController@updateCollaboratorRole');
$router->post('/perfil/portfolio/compartilhar/remover', 'SocialPortfolioController@removeCollaborator');

$router->get('/amigos', 'FriendsController@index');
$router->get('/amigos/adicionar', 'FriendsController@add');
$router->get('/amigos/buscar', 'FriendsController@search');
$router->post('/amigos/solicitar', 'FriendsController@request');
$router->post('/amigos/decidir', 'FriendsController@decide');
$router->post('/amigos/remover', 'FriendsController@remove');
$router->post('/amigos/favorito', 'FriendsController@favorite');

$router->get('/social/chat', 'SocialChatController@open');
$router->post('/social/chat/enviar', 'SocialChatController@send');

$router->get('/social/chat/stream', 'SocialChatController@stream');

$router->post('/social/webrtc/send', 'SocialWebRtcController@send');
$router->get('/social/webrtc/poll', 'SocialWebRtcController@poll');

$router->get('/social/socket/token', 'SocialSocketController@token');

$router->get('/comunidades', 'CommunitiesController@index');
$router->get('/comunidades/ver', 'CommunitiesController@show');
$router->get('/comunidades/nova', 'CommunitiesController@createForm');
$router->post('/comunidades/criar', 'CommunitiesController@create');
$router->get('/comunidades/editar', 'CommunitiesController@editForm');
$router->post('/comunidades/editar', 'CommunitiesController@edit');
$router->post('/comunidades/entrar', 'CommunitiesController@join');
$router->post('/comunidades/sair', 'CommunitiesController@leave');
$router->post('/comunidades/topicos/novo', 'CommunitiesController@createTopic');
$router->get('/comunidades/topicos/ver', 'CommunitiesController@showTopic');
$router->post('/comunidades/topicos/responder', 'CommunitiesController@replyTopic');
$router->get('/comunidades/membros', 'CommunitiesController@members');
$router->get('/comunidades/enquetes', 'CommunitiesController@polls');
$router->post('/comunidades/enquetes/criar', 'CommunitiesController@createPoll');
$router->post('/comunidades/enquetes/votar', 'CommunitiesController@votePoll');
$router->get('/comunidades/convites', 'CommunitiesController@invites');
$router->post('/comunidades/convites/enviar', 'CommunitiesController@sendInvite');
$router->get('/comunidades/aceitar-convite', 'CommunitiesController@acceptInvite');
$router->post('/comunidades/membros/denunciar', 'CommunitiesController@reportMember');
$router->post('/comunidades/membros/bloquear', 'CommunitiesController@blockMember');
$router->post('/comunidades/membros/desbloquear', 'CommunitiesController@unblockMember');
$router->post('/comunidades/membros/denuncias/resolver', 'CommunitiesController@resolveReport');

$router->get('/parceiro/cursos', 'CoursePartnerDashboardController@index');
$router->get('/parceiro/comissoes', 'PartnerCommissionsController@index');
$router->post('/parceiro/comissoes/salvar-dados', 'PartnerCommissionsController@savePayoutDetails');
$router->get('/admin/login', 'AdminAuthController@login');
$router->post('/admin/login', 'AdminAuthController@authenticate');
$router->get('/admin/logout', 'AdminAuthController@logout');
$router->get('/admin', 'AdminDashboardController@index');
$router->get('/admin/comissoes', 'AdminCommissionsController@index');
$router->get('/admin/comissoes/detalhes', 'AdminCommissionsController@details');
$router->post('/admin/comissoes/marcar-pago', 'AdminCommissionsController@markPaid');
$router->get('/admin/config', 'AdminConfigController@index');
$router->post('/admin/config', 'AdminConfigController@save');
$router->post('/admin/config/test-email', 'AdminConfigController@sendTestEmail');
$router->get('/admin/menu-icones', 'AdminMenuIconController@index');
$router->post('/admin/menu-icones/salvar', 'AdminMenuIconController@save');
$router->get('/admin/planos', 'AdminPlanController@index');
$router->get('/admin/planos/novo', 'AdminPlanController@form');
$router->get('/admin/planos/editar', 'AdminPlanController@form');
$router->post('/admin/planos/salvar', 'AdminPlanController@save');
$router->get('/admin/planos/ativar', 'AdminPlanController@toggleActive');
$router->get('/admin/cursos', 'AdminCourseController@index');
$router->get('/admin/cursos/novo', 'AdminCourseController@form');
$router->get('/admin/cursos/editar', 'AdminCourseController@form');
$router->post('/admin/cursos/salvar', 'AdminCourseController@save');
$router->get('/admin/cursos/modulos', 'AdminCourseController@modules');
$router->get('/admin/cursos/modulos/novo', 'AdminCourseController@moduleForm');
$router->get('/admin/cursos/modulos/editar', 'AdminCourseController@moduleForm');
$router->post('/admin/cursos/modulos/salvar', 'AdminCourseController@moduleSave');
$router->post('/admin/cursos/modulos/excluir', 'AdminCourseController@moduleDelete');
$router->get('/admin/cursos/modulos/prova', 'AdminCourseController@moduleExamForm');
$router->post('/admin/cursos/modulos/prova', 'AdminCourseController@moduleExamSave');
$router->get('/admin/cursos/aulas', 'AdminCourseController@lessons');
$router->get('/admin/cursos/aulas/nova', 'AdminCourseController@lessonForm');
$router->get('/admin/cursos/aulas/editar', 'AdminCourseController@lessonForm');
$router->post('/admin/cursos/aulas/salvar', 'AdminCourseController@lessonSave');
$router->post('/admin/cursos/aulas/excluir', 'AdminCourseController@lessonDelete');
$router->get('/admin/cursos/lives', 'AdminCourseController@lives');
$router->get('/admin/cursos/lives/nova', 'AdminCourseController@liveForm');
$router->get('/admin/cursos/lives/editar', 'AdminCourseController@liveForm');
$router->post('/admin/cursos/lives/salvar', 'AdminCourseController@liveSave');
$router->post('/admin/cursos/lives/enviar-lembretes', 'AdminCourseController@sendLiveReminders');
$router->post('/admin/cursos/lives/buscar-gravacao', 'AdminCourseController@fetchLiveRecording');
$router->get('/admin/personalidades', 'AdminPersonalityController@index');
$router->get('/admin/personalidades/novo', 'AdminPersonalityController@form');
$router->get('/admin/personalidades/editar', 'AdminPersonalityController@form');
$router->post('/admin/personalidades/salvar', 'AdminPersonalityController@save');
$router->get('/admin/personalidades/ativar', 'AdminPersonalityController@toggleActive');
$router->get('/admin/personalidades/padrao', 'AdminPersonalityController@setDefault');
$router->get('/admin/usuarios', 'AdminUserController@index');
$router->get('/admin/usuarios/ver', 'AdminUserController@show');
$router->post('/admin/usuarios/toggle', 'AdminUserController@toggleActive');
$router->post('/admin/usuarios/toggle-admin', 'AdminUserController@toggleAdmin');
$router->post('/admin/usuarios/toggle-professor', 'AdminUserController@toggleProfessor');
$router->get('/admin/assinaturas', 'AdminSubscriptionController@index');
$router->get('/admin/erros', 'AdminErrorReportController@index');
$router->get('/admin/erros/ver', 'AdminErrorReportController@show');
$router->post('/admin/erros/estornar', 'AdminErrorReportController@refund');
$router->post('/admin/erros/resolver', 'AdminErrorReportController@resolve');
$router->post('/admin/erros/descartar', 'AdminErrorReportController@dismiss');
$router->get('/admin/anexos', 'AdminAttachmentController@index');
$router->post('/admin/anexos/excluir', 'AdminAttachmentController@delete');
$router->get('/admin/comunidade/bloqueios', 'AdminCommunityController@blocks');
$router->get('/admin/comunidade/categorias', 'AdminCommunityController@categories');
$router->post('/admin/comunidade/categorias/criar', 'AdminCommunityController@createCategory');
$router->get('/admin/comunidade/categorias/toggle', 'AdminCommunityController@toggleCategory');
$router->get('/chat', 'ChatController@index');
$router->post('/chat/send', 'ChatController@send');
$router->get('/chat/project-files', 'ChatController@projectFiles');
$router->post('/chat/audio', 'ChatController@sendAudio');

$router->post('/chat/persona', 'ChatController@changePersona');

// Configurações por conversa (regras/memórias específicas do chat)
$router->post('/chat/settings', 'ChatController@saveSettings');

// Webhook de eventos do Asaas (renovações, pagamentos etc.)
$router->post('/webhooks/asaas', 'AsaasWebhookController@handle');

// Relato de erros de análise pelos usuários
$router->post('/erro/reportar', 'ErrorReportController@store');

$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
