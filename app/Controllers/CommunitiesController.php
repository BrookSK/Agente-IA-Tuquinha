<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\Community;
use App\Models\CommunityMember;
use App\Models\CommunityTopic;
use App\Models\CommunityTopicPost;

class CommunitiesController extends Controller
{
    private function requireLogin(): array
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $user = User::findById((int)$_SESSION['user_id']);
        if (!$user) {
            unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email']);
            header('Location: /login');
            exit;
        }

        return $user;
    }

    public function index(): void
    {
        $user = $this->requireLogin();
        $userId = (int)$user['id'];

        $communities = Community::allActive();
        $memberships = [];
        foreach ($communities as $c) {
            $cid = (int)($c['id'] ?? 0);
            if ($cid <= 0) {
                continue;
            }
            $memberships[$cid] = CommunityMember::isMember($cid, $userId);
        }

        $success = $_SESSION['communities_success'] ?? null;
        $error = $_SESSION['communities_error'] ?? null;
        unset($_SESSION['communities_success'], $_SESSION['communities_error']);

        $this->view('social/communities', [
            'pageTitle' => 'Comunidades do Tuquinha',
            'user' => $user,
            'communities' => $communities,
            'memberships' => $memberships,
            'success' => $success,
            'error' => $error,
        ]);
    }

    public function create(): void
    {
        $user = $this->requireLogin();
        $userId = (int)$user['id'];

        $name = trim((string)($_POST['name'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));

        if ($name === '') {
            $_SESSION['communities_error'] = 'Dê um nome para a comunidade.';
            header('Location: /comunidades');
            exit;
        }

        $baseSlug = mb_strtolower($name, 'UTF-8');
        $baseSlug = preg_replace('/[^a-z0-9]+/i', '-', $baseSlug);
        $baseSlug = trim((string)$baseSlug, '-');
        if ($baseSlug === '') {
            $baseSlug = 'comunidade-' . $userId;
        }

        $slug = $baseSlug;
        $suffix = 1;
        while (Community::findBySlug($slug)) {
            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
            if ($suffix > 50) {
                $slug = $baseSlug . '-' . bin2hex(random_bytes(3));
                break;
            }
        }

        $communityId = Community::create([
            'owner_user_id' => $userId,
            'name' => $name,
            'slug' => $slug,
            'description' => $description !== '' ? $description : null,
            'image_path' => null,
            'members_count' => 0,
            'topics_count' => 0,
            'is_active' => 1,
        ]);

        CommunityMember::join($communityId, $userId, 'owner');

        $_SESSION['communities_success'] = 'Comunidade criada com sucesso.';
        header('Location: /comunidades/ver?slug=' . urlencode($slug));
        exit;
    }

    public function show(): void
    {
        $user = $this->requireLogin();
        $userId = (int)$user['id'];

        $slug = trim((string)($_GET['slug'] ?? ''));
        if ($slug === '') {
            header('Location: /comunidades');
            exit;
        }

        $community = Community::findBySlug($slug);
        if (!$community || empty($community['is_active'])) {
            $_SESSION['communities_error'] = 'Comunidade não encontrada.';
            header('Location: /comunidades');
            exit;
        }

        $communityId = (int)$community['id'];
        $isMember = CommunityMember::isMember($communityId, $userId);
        $members = CommunityMember::allMembersWithUser($communityId);
        $topics = CommunityTopic::allByCommunity($communityId, 50);

        $success = $_SESSION['communities_success'] ?? null;
        $error = $_SESSION['communities_error'] ?? null;
        unset($_SESSION['communities_success'], $_SESSION['communities_error']);

        $this->view('social/community_show', [
            'pageTitle' => (string)($community['name'] ?? 'Comunidade'),
            'user' => $user,
            'community' => $community,
            'members' => $members,
            'topics' => $topics,
            'isMember' => $isMember,
            'success' => $success,
            'error' => $error,
        ]);
    }

    public function join(): void
    {
        $user = $this->requireLogin();
        $userId = (int)$user['id'];

        $communityId = isset($_POST['community_id']) ? (int)$_POST['community_id'] : 0;
        if ($communityId <= 0) {
            $_SESSION['communities_error'] = 'Comunidade inválida.';
            header('Location: /comunidades');
            exit;
        }

        $community = Community::findById($communityId);
        if (!$community || empty($community['is_active'])) {
            $_SESSION['communities_error'] = 'Comunidade não encontrada.';
            header('Location: /comunidades');
            exit;
        }

        CommunityMember::join($communityId, $userId);

        $_SESSION['communities_success'] = 'Você agora faz parte desta comunidade.';
        header('Location: /comunidades/ver?slug=' . urlencode((string)$community['slug']));
        exit;
    }

    public function leave(): void
    {
        $user = $this->requireLogin();
        $userId = (int)$user['id'];

        $communityId = isset($_POST['community_id']) ? (int)$_POST['community_id'] : 0;
        if ($communityId <= 0) {
            $_SESSION['communities_error'] = 'Comunidade inválida.';
            header('Location: /comunidades');
            exit;
        }

        $community = Community::findById($communityId);
        if (!$community || empty($community['is_active'])) {
            $_SESSION['communities_error'] = 'Comunidade não encontrada.';
            header('Location: /comunidades');
            exit;
        }

        CommunityMember::leave($communityId, $userId);

        $_SESSION['communities_success'] = 'Você saiu desta comunidade.';
        header('Location: /comunidades/ver?slug=' . urlencode((string)$community['slug']));
        exit;
    }

    public function createTopic(): void
    {
        $user = $this->requireLogin();
        $userId = (int)$user['id'];

        $communityId = isset($_POST['community_id']) ? (int)$_POST['community_id'] : 0;
        $title = trim((string)($_POST['title'] ?? ''));
        $body = trim((string)($_POST['body'] ?? ''));

        if ($communityId <= 0) {
            $_SESSION['communities_error'] = 'Comunidade inválida para criar tópico.';
            header('Location: /comunidades');
            exit;
        }

        $community = Community::findById($communityId);
        if (!$community || empty($community['is_active'])) {
            $_SESSION['communities_error'] = 'Comunidade não encontrada.';
            header('Location: /comunidades');
            exit;
        }

        if (!CommunityMember::isMember($communityId, $userId)) {
            $_SESSION['communities_error'] = 'Você precisa ser membro para criar tópicos aqui.';
            header('Location: /comunidades/ver?slug=' . urlencode((string)$community['slug']));
            exit;
        }

        if ($title === '') {
            $_SESSION['communities_error'] = 'Dê um título para o tópico.';
            header('Location: /comunidades/ver?slug=' . urlencode((string)$community['slug']));
            exit;
        }

        if (strlen($title) > 255) {
            $_SESSION['communities_error'] = 'O título do tópico pode ter no máximo 255 caracteres.';
            header('Location: /comunidades/ver?slug=' . urlencode((string)$community['slug']));
            exit;
        }

        if (strlen($body) > 4000) {
            $_SESSION['communities_error'] = 'O texto do tópico pode ter no máximo 4000 caracteres.';
            header('Location: /comunidades/ver?slug=' . urlencode((string)$community['slug']));
            exit;
        }

        $topicId = CommunityTopic::create([
            'community_id' => $communityId,
            'user_id' => $userId,
            'title' => $title,
            'body' => $body,
        ]);

        $_SESSION['communities_success'] = 'Tópico criado com sucesso.';
        header('Location: /comunidades/topicos/ver?topic_id=' . $topicId);
        exit;
    }

    public function showTopic(): void
    {
        $user = $this->requireLogin();
        $userId = (int)$user['id'];

        $topicId = isset($_GET['topic_id']) ? (int)$_GET['topic_id'] : 0;
        if ($topicId <= 0) {
            $_SESSION['communities_error'] = 'Tópico não encontrado.';
            header('Location: /comunidades');
            exit;
        }

        $topic = CommunityTopic::findById($topicId);
        if (!$topic) {
            $_SESSION['communities_error'] = 'Tópico não encontrado.';
            header('Location: /comunidades');
            exit;
        }

        $community = Community::findById((int)$topic['community_id']);
        if (!$community || empty($community['is_active'])) {
            $_SESSION['communities_error'] = 'Comunidade deste tópico não foi encontrada.';
            header('Location: /comunidades');
            exit;
        }

        $communityId = (int)$community['id'];
        $isMember = CommunityMember::isMember($communityId, $userId);
        $posts = CommunityTopicPost::allByTopicWithUser($topicId);

        $success = $_SESSION['communities_success'] ?? null;
        $error = $_SESSION['communities_error'] ?? null;
        unset($_SESSION['communities_success'], $_SESSION['communities_error']);

        $this->view('social/community_topic', [
            'pageTitle' => (string)($topic['title'] ?? 'Tópico'),
            'user' => $user,
            'community' => $community,
            'topic' => $topic,
            'posts' => $posts,
            'isMember' => $isMember,
            'success' => $success,
            'error' => $error,
        ]);
    }

    public function replyTopic(): void
    {
        $user = $this->requireLogin();
        $userId = (int)$user['id'];

        $topicId = isset($_POST['topic_id']) ? (int)$_POST['topic_id'] : 0;
        $body = trim((string)($_POST['body'] ?? ''));

        if ($topicId <= 0) {
            $_SESSION['communities_error'] = 'Tópico não encontrado.';
            header('Location: /comunidades');
            exit;
        }

        $topic = CommunityTopic::findById($topicId);
        if (!$topic) {
            $_SESSION['communities_error'] = 'Tópico não encontrado.';
            header('Location: /comunidades');
            exit;
        }

        $community = Community::findById((int)$topic['community_id']);
        if (!$community || empty($community['is_active'])) {
            $_SESSION['communities_error'] = 'Comunidade deste tópico não foi encontrada.';
            header('Location: /comunidades');
            exit;
        }

        $communityId = (int)$community['id'];
        if (!CommunityMember::isMember($communityId, $userId)) {
            $_SESSION['communities_error'] = 'Você precisa ser membro para responder neste tópico.';
            header('Location: /comunidades/topicos/ver?topic_id=' . $topicId);
            exit;
        }

        if ($body === '') {
            $_SESSION['communities_error'] = 'Escreva algo antes de responder.';
            header('Location: /comunidades/topicos/ver?topic_id=' . $topicId);
            exit;
        }

        if (strlen($body) > 4000) {
            $_SESSION['communities_error'] = 'A resposta pode ter no máximo 4000 caracteres.';
            header('Location: /comunidades/topicos/ver?topic_id=' . $topicId);
            exit;
        }

        CommunityTopicPost::create([
            'topic_id' => $topicId,
            'user_id' => $userId,
            'body' => $body,
        ]);

        $_SESSION['communities_success'] = 'Resposta enviada.';
        header('Location: /comunidades/topicos/ver?topic_id=' . $topicId);
        exit;
    }
}
