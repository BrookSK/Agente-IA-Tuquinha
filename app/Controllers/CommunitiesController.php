<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\Community;
use App\Models\CommunityCategory;
use App\Models\CommunityMember;
use App\Models\CommunityTopic;
use App\Models\CommunityTopicPost;
use App\Models\CommunityPoll;
use App\Models\CommunityPollVote;
use App\Models\CommunityInvite;
use App\Models\CommunityMemberReport;
use App\Models\Course;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\CoursePurchase;
use App\Models\Setting;
use App\Services\MediaStorageService;
use App\Services\MailService;

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

    private function findCourseForCommunity(array $community): ?array
    {
        $slug = trim((string)($community['slug'] ?? ''));
        if ($slug === '') {
            return null;
        }
        if (strpos($slug, 'curso-') !== 0) {
            return null;
        }

        $rest = substr($slug, 6);
        if (strpos($rest, 'id-') === 0) {
            $idPart = substr($rest, 3);
            $courseId = (int)$idPart;
            if ($courseId > 0) {
                return Course::findById($courseId);
            }
            return null;
        }

        return Course::findBySlug($rest);
    }

    private function resolvePlanForUser(?array $user): ?array
    {
        $plan = null;
        if ($user && !empty($user['email'])) {
            $sub = Subscription::findLastByEmail($user['email']);
            if ($sub && !empty($sub['plan_id'])) {
                $plan = Plan::findById((int)$sub['plan_id']);
            }
        }
        if (!$plan) {
            $plan = Plan::findBySessionSlug($_SESSION['plan_slug'] ?? null) ?: Plan::findBySlug('free');
        }
        return $plan;
    }

    private function userCanAccessCourseCommunity(array $course, array $user): bool
    {
        $plan = $this->resolvePlanForUser($user);

        $isAdmin = !empty($_SESSION['is_admin']);
        if ($isAdmin) {
            return true;
        }

        $planAllowsCourses = !empty($plan['allow_courses'] ?? false);
        if ($planAllowsCourses) {
            return true;
        }

        $isPaid = !empty($course['is_paid']);
        $allowPublicPurchase = !empty($course['allow_public_purchase']);

        if (!$isPaid) {
            return true;
        }

        if ($allowPublicPurchase && !empty($user['id']) && !empty($course['id'])) {
            $userId = (int)$user['id'];
            $courseId = (int)$course['id'];
            if (CoursePurchase::userHasPaidPurchase($userId, $courseId)) {
                return true;
            }
        }

        return false;
    }

    private function ensureCommunityModerator(array $community, array $user, bool $allowAdmin = true): void
    {
        $userId = (int)($user['id'] ?? 0);
        if ($userId <= 0) {
            header('Location: /login');
            exit;
        }

        if ($allowAdmin && !empty($_SESSION['is_admin'])) {
            return;
        }

        $ownerId = (int)($community['owner_user_id'] ?? 0);
        if ($ownerId > 0 && $ownerId === $userId) {
            return;
        }

        $communityId = (int)($community['id'] ?? 0);
        if ($communityId <= 0) {
            header('Location: /comunidades');
            exit;
        }

        $member = CommunityMember::findMember($communityId, $userId);
        $role = (string)($member['role'] ?? 'member');
        if ($role === 'moderator') {
            return;
        }

        $_SESSION['communities_error'] = 'Você não tem permissão para acessar esta área da comunidade.';
        header('Location: /comunidades/ver?slug=' . urlencode((string)($community['slug'] ?? '')));
        exit;
    }

    private function userCanClosePolls(array $community, array $user): bool
    {
        if (empty($community['allow_poll_closing'])) {
            return false;
        }

        if (!empty($_SESSION['is_admin'])) {
            return true;
        }

        $userId = (int)($user['id'] ?? 0);
        if ($userId <= 0) {
            return false;
        }

        $ownerId = (int)($community['owner_user_id'] ?? 0);
        if ($ownerId > 0 && $ownerId === $userId) {
            return true;
        }

        $communityId = (int)($community['id'] ?? 0);
        if ($communityId <= 0) {
            return false;
        }

        $member = CommunityMember::findMember($communityId, $userId);
        $role = (string)($member['role'] ?? 'member');
        return $role === 'moderator';
    }

    public function index(): void
    {
        $user = $this->requireLogin();
        $userId = (int)$user['id'];

        $category = isset($_GET['category']) ? trim((string)$_GET['category']) : '';

        $scope = isset($_GET['scope']) ? trim((string)$_GET['scope']) : 'all';
        if (!in_array($scope, ['all', 'owner', 'moderator', 'member'], true)) {
            $scope = 'all';
        }

        $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        $q = preg_replace('/\s+/', ' ', (string)$q);

        $communities = Community::allActiveWithUserFilter($userId, $category !== '' ? $category : null, $q !== '' ? $q : null, $scope);
        $memberships = [];
        foreach ($communities as $c) {
            $cid = (int)($c['id'] ?? 0);
            if ($cid <= 0) {
                continue;
            }
            $memberships[$cid] = !empty($c['member_role']) || ((int)($c['owner_user_id'] ?? 0) === $userId);
        }

        $success = $_SESSION['communities_success'] ?? null;
        $error = $_SESSION['communities_error'] ?? null;
        unset($_SESSION['communities_success'], $_SESSION['communities_error']);

        $categories = CommunityCategory::allActiveNames();

        $this->view('social/communities', [
            'pageTitle' => 'Comunidades do Tuquinha',
            'user' => $user,
            'communities' => $communities,
            'memberships' => $memberships,
            'categories' => $categories,
            'selectedCategory' => $category,
            'selectedScope' => $scope,
            'q' => $q,
            'success' => $success,
            'error' => $error,
        ]);
    }

    public function createForm(): void
    {
        $user = $this->requireLogin();

        $categories = CommunityCategory::allActiveNames();

        $error = $_SESSION['communities_error'] ?? null;
        unset($_SESSION['communities_error']);

        $old = $_SESSION['communities_form_old'] ?? [];
        unset($_SESSION['communities_form_old']);

        $this->view('social/community_create', [
            'pageTitle' => 'Criar comunidade',
            'user' => $user,
            'categories' => $categories,
            'error' => $error,
            'old' => $old,
        ]);
    }

    public function create(): void
    {
        $user = $this->requireLogin();
        $userId = (int)$user['id'];

        $name = trim((string)($_POST['name'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $language = trim((string)($_POST['language'] ?? ''));
        $category = trim((string)($_POST['category'] ?? ''));
        $communityType = (string)($_POST['community_type'] ?? 'public');
        $postingPolicy = (string)($_POST['posting_policy'] ?? 'any_member');
        $forumType = (string)($_POST['forum_type'] ?? 'non_anonymous');
        $allowPollClosing = !empty($_POST['allow_poll_closing']) ? 1 : 0;
        $moderatorsRaw = trim((string)($_POST['moderators_emails'] ?? ''));

        if ($communityType !== 'private') {
            $communityType = 'public';
        }
        if (!in_array($postingPolicy, ['any_member', 'owner_moderators'], true)) {
            $postingPolicy = 'any_member';
        }
        if (!in_array($forumType, ['non_anonymous', 'anonymous'], true)) {
            $forumType = 'non_anonymous';
        }

        $old = [
            'name' => $name,
            'description' => $description,
            'language' => $language,
            'category' => $category,
            'community_type' => $communityType,
            'posting_policy' => $postingPolicy,
            'forum_type' => $forumType,
            'moderators_emails' => $moderatorsRaw,
        ];

        if ($name === '') {
            $_SESSION['communities_error'] = 'Dê um nome para a comunidade.';
            $_SESSION['communities_form_old'] = $old;
            header('Location: /comunidades/nova');
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

        // Upload opcional de imagem de perfil
        $profileImagePath = null;
        if (!empty($_FILES['profile_image']) && is_array($_FILES['profile_image'])) {
            $uploadError = (int)($_FILES['profile_image']['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($uploadError === UPLOAD_ERR_OK) {
                $tmp = (string)($_FILES['profile_image']['tmp_name'] ?? '');
                $originalName = (string)($_FILES['profile_image']['name'] ?? '');
                $type = (string)($_FILES['profile_image']['type'] ?? '');
                if ($tmp !== '' && is_uploaded_file($tmp)) {
                    $url = MediaStorageService::uploadFile($tmp, $originalName, $type);
                    if ($url !== null) {
                        $profileImagePath = $url;
                    }
                }
            }
        }

        // Upload opcional de imagem de capa
        $coverImagePath = null;
        if (!empty($_FILES['cover_image']) && is_array($_FILES['cover_image'])) {
            $uploadError = (int)($_FILES['cover_image']['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($uploadError === UPLOAD_ERR_OK) {
                $tmp = (string)($_FILES['cover_image']['tmp_name'] ?? '');
                $originalName = (string)($_FILES['cover_image']['name'] ?? '');
                $type = (string)($_FILES['cover_image']['type'] ?? '');
                if ($tmp !== '' && is_uploaded_file($tmp)) {
                    $url = MediaStorageService::uploadFile($tmp, $originalName, $type);
                    if ($url !== null) {
                        $coverImagePath = $url;
                    }
                }
            }
        }

        $communityId = Community::create([
            'owner_user_id' => $userId,
            'name' => $name,
            'slug' => $slug,
            'description' => $description !== '' ? $description : null,
            'language' => $language !== '' ? $language : null,
            'category' => $category !== '' ? $category : null,
            'community_type' => $communityType,
            'posting_policy' => $postingPolicy,
            'forum_type' => $forumType,
            'allow_poll_closing' => $allowPollClosing,
            'image_path' => $profileImagePath,
            'cover_image_path' => $coverImagePath,
            'members_count' => 0,
            'topics_count' => 0,
            'is_active' => 1,
        ]);

        CommunityMember::join($communityId, $userId, 'owner');

        // Adiciona moderadores por e-mail, se existirem
        if ($moderatorsRaw !== '') {
            $normalized = str_replace(["\r", "\n", ';'], ',', $moderatorsRaw);
            $parts = explode(',', $normalized);
            foreach ($parts as $email) {
                $email = trim((string)$email);
                if ($email === '') {
                    continue;
                }
                $moderator = User::findByEmail($email);
                if ($moderator) {
                    $moderatorId = (int)($moderator['id'] ?? 0);
                    if ($moderatorId > 0 && $moderatorId !== $userId) {
                        CommunityMember::join($communityId, $moderatorId, 'moderator');
                    }
                }
            }
        }

        $_SESSION['communities_success'] = 'Comunidade criada com sucesso.';
        header('Location: /comunidades/ver?slug=' . urlencode($slug));
        exit;
    }

    public function editForm(): void
    {
        $user = $this->requireLogin();

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

        $this->ensureCommunityModerator($community, $user, false);

        $categories = CommunityCategory::allActiveNames();

        $error = $_SESSION['communities_error'] ?? null;
        unset($_SESSION['communities_error']);

        $old = $_SESSION['communities_edit_old'] ?? [];
        unset($_SESSION['communities_edit_old']);

        if (empty($old)) {
            $old = [
                'name' => (string)($community['name'] ?? ''),
                'description' => (string)($community['description'] ?? ''),
                'language' => (string)($community['language'] ?? ''),
                'category' => (string)($community['category'] ?? ''),
                'community_type' => (string)($community['community_type'] ?? 'public'),
                'posting_policy' => (string)($community['posting_policy'] ?? 'any_member'),
                'forum_type' => (string)($community['forum_type'] ?? 'non_anonymous'),
                'allow_poll_closing' => !empty($community['allow_poll_closing']) ? 1 : 0,
            ];
        }

        $this->view('social/community_edit', [
            'pageTitle' => 'Editar comunidade',
            'user' => $user,
            'community' => $community,
            'categories' => $categories,
            'error' => $error,
            'old' => $old,
        ]);
    }

    public function edit(): void
    {
        $user = $this->requireLogin();

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

        $this->ensureCommunityModerator($community, $user, false);

        $name = trim((string)($_POST['name'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $language = trim((string)($_POST['language'] ?? ''));
        $category = trim((string)($_POST['category'] ?? ''));
        $communityType = (string)($_POST['community_type'] ?? 'public');
        $postingPolicy = (string)($_POST['posting_policy'] ?? 'any_member');
        $forumType = (string)($_POST['forum_type'] ?? 'non_anonymous');
        $allowPollClosing = !empty($_POST['allow_poll_closing']) ? 1 : 0;

        if ($communityType !== 'private') {
            $communityType = 'public';
        }
        if (!in_array($postingPolicy, ['any_member', 'owner_moderators'], true)) {
            $postingPolicy = 'any_member';
        }
        if (!in_array($forumType, ['non_anonymous', 'anonymous'], true)) {
            $forumType = 'non_anonymous';
        }

        $old = [
            'name' => $name,
            'description' => $description,
            'language' => $language,
            'category' => $category,
            'community_type' => $communityType,
            'posting_policy' => $postingPolicy,
            'forum_type' => $forumType,
            'allow_poll_closing' => $allowPollClosing,
        ];

        if ($name === '') {
            $_SESSION['communities_error'] = 'Dê um nome para a comunidade.';
            $_SESSION['communities_edit_old'] = $old;
            header('Location: /comunidades/editar?slug=' . urlencode((string)($community['slug'] ?? '')));
            exit;
        }

        $profileImagePath = (string)($community['image_path'] ?? '');
        if (!empty($_FILES['profile_image']) && is_array($_FILES['profile_image'])) {
            $uploadError = (int)($_FILES['profile_image']['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($uploadError === UPLOAD_ERR_OK) {
                $tmp = (string)($_FILES['profile_image']['tmp_name'] ?? '');
                $originalName = (string)($_FILES['profile_image']['name'] ?? '');
                $type = (string)($_FILES['profile_image']['type'] ?? '');
                if ($tmp !== '' && is_uploaded_file($tmp)) {
                    $url = MediaStorageService::uploadFile($tmp, $originalName, $type);
                    if ($url !== null) {
                        $profileImagePath = $url;
                    }
                }
            }
        }

        $coverImagePath = (string)($community['cover_image_path'] ?? '');
        if (!empty($_FILES['cover_image']) && is_array($_FILES['cover_image'])) {
            $uploadError = (int)($_FILES['cover_image']['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($uploadError === UPLOAD_ERR_OK) {
                $tmp = (string)($_FILES['cover_image']['tmp_name'] ?? '');
                $originalName = (string)($_FILES['cover_image']['name'] ?? '');
                $type = (string)($_FILES['cover_image']['type'] ?? '');
                if ($tmp !== '' && is_uploaded_file($tmp)) {
                    $url = MediaStorageService::uploadFile($tmp, $originalName, $type);
                    if ($url !== null) {
                        $coverImagePath = $url;
                    }
                }
            }
        }

        Community::update($communityId, [
            'name' => $name,
            'description' => $description !== '' ? $description : null,
            'language' => $language !== '' ? $language : null,
            'category' => $category !== '' ? $category : null,
            'community_type' => $communityType,
            'posting_policy' => $postingPolicy,
            'forum_type' => $forumType,
            'allow_poll_closing' => $allowPollClosing,
            'image_path' => $profileImagePath !== '' ? $profileImagePath : null,
            'cover_image_path' => $coverImagePath !== '' ? $coverImagePath : null,
        ]);

        $_SESSION['communities_success'] = 'Comunidade atualizada com sucesso.';
        header('Location: /comunidades/ver?slug=' . urlencode((string)($community['slug'] ?? '')));
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

        // Se for comunidade vinculada a curso, exige acesso ao curso até para visualizar
        $course = $this->findCourseForCommunity($community);
        if ($course && !empty($course['is_active']) && !$this->userCanAccessCourseCommunity($course, $user)) {
            $_SESSION['communities_error'] = 'Você precisa ter acesso a este curso para visualizar esta comunidade.';
            $courseUrl = CourseController::buildCourseUrl($course);
            header('Location: ' . $courseUrl);
            exit;
        }

        $communityId = (int)$community['id'];
        $isMember = CommunityMember::isMember($communityId, $userId);
        $members = CommunityMember::allMembersWithUser($communityId);
        $topics = CommunityTopic::allByCommunity($communityId, 50);

        // Permissão de edição: apenas dono ou moderador da comunidade
        $canModerate = false;
        $ownerId = (int)($community['owner_user_id'] ?? 0);
        if ($ownerId === $userId) {
            $canModerate = true;
        } else {
            $member = CommunityMember::findMember($communityId, $userId);
            $role = (string)($member['role'] ?? 'member');
            if ($role === 'moderator') {
                $canModerate = true;
            }
        }

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
            'canModerate' => $canModerate,
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

        // Se for uma comunidade vinculada a curso, só permite participação para quem tem acesso ao curso
        $course = $this->findCourseForCommunity($community);
        if ($course && !empty($course['is_active']) && !$this->userCanAccessCourseCommunity($course, $user)) {
            $_SESSION['communities_error'] = 'Você precisa ter acesso a este curso para participar desta comunidade.';
            $courseUrl = CourseController::buildCourseUrl($course);
            header('Location: ' . $courseUrl);
            exit;
        }

        // Comunidades privadas: apenas dono/admin podem entrar diretamente.
        $communityType = (string)($community['community_type'] ?? 'public');
        if ($communityType === 'private') {
            $isAdmin = !empty($_SESSION['is_admin']);
            $ownerId = (int)($community['owner_user_id'] ?? 0);
            if (!$isAdmin && $ownerId !== $userId) {
                $_SESSION['communities_error'] = 'Esta comunidade é privada. Você precisa de um convite para participar.';
                header('Location: /comunidades/ver?slug=' . urlencode((string)$community['slug']));
                exit;
            }
        }

        if (CommunityMember::isBlocked($communityId, $userId)) {
            $_SESSION['communities_error'] = 'Você foi bloqueado nesta comunidade e não pode participar.';
            header('Location: /comunidades/ver?slug=' . urlencode((string)$community['slug']));
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

        // Se for comunidade de curso, exige acesso ao curso para criar tópicos
        $course = $this->findCourseForCommunity($community);
        if ($course && !empty($course['is_active']) && !$this->userCanAccessCourseCommunity($course, $user)) {
            $_SESSION['communities_error'] = 'Você precisa ter acesso a este curso para criar tópicos nesta comunidade.';
            $courseUrl = CourseController::buildCourseUrl($course);
            header('Location: ' . $courseUrl);
            exit;
        }

        if (!CommunityMember::isMember($communityId, $userId)) {
            $_SESSION['communities_error'] = 'Você precisa ser membro para criar tópicos aqui.';
            header('Location: /comunidades/ver?slug=' . urlencode((string)$community['slug']));
            exit;
        }

        if (CommunityMember::isBlocked($communityId, $userId)) {
            $_SESSION['communities_error'] = 'Você foi bloqueado nesta comunidade e não pode criar tópicos.';
            header('Location: /comunidades/ver?slug=' . urlencode((string)$community['slug']));
            exit;
        }

        // Política de postagem: em algumas comunidades apenas dono/moderadores podem criar tópicos
        $postingPolicy = (string)($community['posting_policy'] ?? 'any_member');
        if ($postingPolicy === 'owner_moderators') {
            $this->ensureCommunityModerator($community, $user);
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

        // Se for comunidade de curso, exige acesso ao curso até para visualizar o tópico
        $course = $this->findCourseForCommunity($community);
        if ($course && !empty($course['is_active']) && !$this->userCanAccessCourseCommunity($course, $user)) {
            $_SESSION['communities_error'] = 'Você precisa ter acesso a este curso para visualizar este tópico.';
            $courseUrl = CourseController::buildCourseUrl($course);
            header('Location: ' . $courseUrl);
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

        // Se for comunidade de curso, exige acesso ao curso também para responder
        $course = $this->findCourseForCommunity($community);
        if ($course && !empty($course['is_active']) && !$this->userCanAccessCourseCommunity($course, $user)) {
            $_SESSION['communities_error'] = 'Você precisa ter acesso a este curso para responder neste tópico.';
            $courseUrl = CourseController::buildCourseUrl($course);
            header('Location: ' . $courseUrl);
            exit;
        }

        $communityId = (int)$community['id'];
        if (!CommunityMember::isMember($communityId, $userId)) {
            $_SESSION['communities_error'] = 'Você precisa ser membro para responder neste tópico.';
            header('Location: /comunidades/topicos/ver?topic_id=' . $topicId);
            exit;
        }

        if (CommunityMember::isBlocked($communityId, $userId)) {
            $_SESSION['communities_error'] = 'Você foi bloqueado nesta comunidade e não pode responder em tópicos.';
            header('Location: /comunidades/topicos/ver?topic_id=' . $topicId);
            exit;
        }

        // Política de postagem: em algumas comunidades apenas dono/moderadores podem responder
        $postingPolicy = (string)($community['posting_policy'] ?? 'any_member');
        if ($postingPolicy === 'owner_moderators') {
            $this->ensureCommunityModerator($community, $user);
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

    public function members(): void
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

        $course = $this->findCourseForCommunity($community);
        if ($course && !empty($course['is_active']) && !$this->userCanAccessCourseCommunity($course, $user)) {
            $_SESSION['communities_error'] = 'Você precisa ter acesso a este curso para visualizar esta comunidade.';
            $courseUrl = CourseController::buildCourseUrl($course);
            header('Location: ' . $courseUrl);
            exit;
        }

        $communityId = (int)$community['id'];
        $isMember = CommunityMember::isMember($communityId, $userId);
        $members = CommunityMember::allMembersWithUser($communityId);

        $success = $_SESSION['communities_success'] ?? null;
        $error = $_SESSION['communities_error'] ?? null;
        unset($_SESSION['communities_success'], $_SESSION['communities_error']);

        // Permissão de moderação (para mostrar botões de gestão na view)
        $canModerate = !empty($_SESSION['is_admin']);
        if (!$canModerate) {
            $ownerId = (int)($community['owner_user_id'] ?? 0);
            if ($ownerId === $userId) {
                $canModerate = true;
            } else {
                $member = CommunityMember::findMember($communityId, $userId);
                $role = (string)($member['role'] ?? 'member');
                if ($role === 'moderator') {
                    $canModerate = true;
                }
            }
        }

        $reports = $canModerate ? CommunityMemberReport::allOpenForCommunity($communityId) : [];

        $this->view('social/community_members', [
            'pageTitle' => 'Membros da comunidade',
            'user' => $user,
            'community' => $community,
            'members' => $members,
            'isMember' => $isMember,
            'canModerate' => $canModerate,
            'reports' => $reports,
            'success' => $success,
            'error' => $error,
        ]);
    }

    public function polls(): void
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

        $course = $this->findCourseForCommunity($community);
        if ($course && !empty($course['is_active']) && !$this->userCanAccessCourseCommunity($course, $user)) {
            $_SESSION['communities_error'] = 'Você precisa ter acesso a este curso para visualizar esta comunidade.';
            $courseUrl = CourseController::buildCourseUrl($course);
            header('Location: ' . $courseUrl);
            exit;
        }

        $communityId = (int)$community['id'];
        $isMember = CommunityMember::isMember($communityId, $userId);
        $polls = CommunityPoll::allWithStatsForCommunity($communityId, $userId);

        $success = $_SESSION['communities_success'] ?? null;
        $error = $_SESSION['communities_error'] ?? null;
        unset($_SESSION['communities_success'], $_SESSION['communities_error']);

        // Apenas moderadores podem criar novas enquetes
        $canModerate = !empty($_SESSION['is_admin']);
        if (!$canModerate) {
            $ownerId = (int)($community['owner_user_id'] ?? 0);
            if ($ownerId === $userId) {
                $canModerate = true;
            } else {
                $member = CommunityMember::findMember($communityId, $userId);
                $role = (string)($member['role'] ?? 'member');
                if ($role === 'moderator') {
                    $canModerate = true;
                }
            }
        }

        $this->view('social/community_polls', [
            'pageTitle' => 'Enquetes da comunidade',
            'user' => $user,
            'community' => $community,
            'polls' => $polls,
            'isMember' => $isMember,
            'canModerate' => $canModerate,
            'canClosePolls' => $this->userCanClosePolls($community, $user),
            'success' => $success,
            'error' => $error,
        ]);
    }

    public function createPoll(): void
    {
        $user = $this->requireLogin();
        $userId = (int)$user['id'];

        $communityId = isset($_POST['community_id']) ? (int)$_POST['community_id'] : 0;
        if ($communityId <= 0) {
            $_SESSION['communities_error'] = 'Comunidade inválida para criar enquete.';
            header('Location: /comunidades');
            exit;
        }

        $community = Community::findById($communityId);
        if (!$community || empty($community['is_active'])) {
            $_SESSION['communities_error'] = 'Comunidade não encontrada.';
            header('Location: /comunidades');
            exit;
        }

        $course = $this->findCourseForCommunity($community);
        if ($course && !empty($course['is_active']) && !$this->userCanAccessCourseCommunity($course, $user)) {
            $_SESSION['communities_error'] = 'Você precisa ter acesso a este curso para criar enquetes nesta comunidade.';
            $courseUrl = CourseController::buildCourseUrl($course);
            header('Location: ' . $courseUrl);
            exit;
        }

        $communityId = (int)$community['id'];
        if (!CommunityMember::isMember($communityId, $userId)) {
            $_SESSION['communities_error'] = 'Você precisa ser membro para criar enquetes aqui.';
            header('Location: /comunidades/enquetes?slug=' . urlencode((string)$community['slug']));
            exit;
        }

        // Garante permissão de moderação
        $this->ensureCommunityModerator($community, $user);

        $question = trim((string)($_POST['question'] ?? ''));
        $option1 = trim((string)($_POST['option1'] ?? ''));
        $option2 = trim((string)($_POST['option2'] ?? ''));
        $option3 = trim((string)($_POST['option3'] ?? ''));
        $option4 = trim((string)($_POST['option4'] ?? ''));
        $option5 = trim((string)($_POST['option5'] ?? ''));

        if ($question === '' || $option1 === '' || $option2 === '') {
            $_SESSION['communities_error'] = 'Informe a pergunta e pelo menos duas opções da enquete.';
            header('Location: /comunidades/enquetes?slug=' . urlencode((string)$community['slug']));
            exit;
        }

        CommunityPoll::create([
            'community_id' => $communityId,
            'user_id' => $userId,
            'question' => $question,
            'option1' => $option1,
            'option2' => $option2,
            'option3' => $option3 !== '' ? $option3 : null,
            'option4' => $option4 !== '' ? $option4 : null,
            'option5' => $option5 !== '' ? $option5 : null,
            'allow_multiple' => 0,
        ]);

        $_SESSION['communities_success'] = 'Enquete criada com sucesso.';
        header('Location: /comunidades/enquetes?slug=' . urlencode((string)$community['slug']));
        exit;
    }

    public function votePoll(): void
    {
        $user = $this->requireLogin();
        $userId = (int)$user['id'];

        $pollId = isset($_POST['poll_id']) ? (int)$_POST['poll_id'] : 0;
        $optionNumber = isset($_POST['option']) ? (int)$_POST['option'] : 0;

        if ($pollId <= 0 || $optionNumber <= 0) {
            $_SESSION['communities_error'] = 'Enquete ou opção inválida.';
            header('Location: /comunidades');
            exit;
        }

        $poll = CommunityPoll::findById($pollId);
        if (!$poll) {
            $_SESSION['communities_error'] = 'Enquete não encontrada.';
            header('Location: /comunidades');
            exit;
        }

        if (!empty($poll['closed_at'])) {
            $_SESSION['communities_error'] = 'Esta enquete está encerrada e não aceita mais votos.';
            $community = Community::findById((int)$poll['community_id']);
            $slug = $community ? (string)($community['slug'] ?? '') : '';
            if ($slug !== '') {
                header('Location: /comunidades/enquetes?slug=' . urlencode($slug) . '#poll-' . $pollId);
                exit;
            }
            header('Location: /comunidades');
            exit;
        }

        $community = Community::findById((int)$poll['community_id']);
        if (!$community || empty($community['is_active'])) {
            $_SESSION['communities_error'] = 'Comunidade desta enquete não foi encontrada.';
            header('Location: /comunidades');
            exit;
        }

        $course = $this->findCourseForCommunity($community);
        if ($course && !empty($course['is_active']) && !$this->userCanAccessCourseCommunity($course, $user)) {
            $_SESSION['communities_error'] = 'Você precisa ter acesso a este curso para votar nesta enquete.';
            $courseUrl = CourseController::buildCourseUrl($course);
            header('Location: ' . $courseUrl);
            exit;
        }

        $communityId = (int)$community['id'];
        if (!CommunityMember::isMember($communityId, $userId)) {
            $_SESSION['communities_error'] = 'Você precisa ser membro para votar nas enquetes desta comunidade.';
            header('Location: /comunidades/enquetes?slug=' . urlencode((string)$community['slug']));
            exit;
        }

        if (CommunityMember::isBlocked($communityId, $userId)) {
            $_SESSION['communities_error'] = 'Você foi bloqueado nesta comunidade e não pode votar em enquetes.';
            header('Location: /comunidades/enquetes?slug=' . urlencode((string)$community['slug']));
            exit;
        }

        $optionField = 'option' . $optionNumber;
        if (empty($poll[$optionField])) {
            $_SESSION['communities_error'] = 'Opção de enquete inválida.';
            header('Location: /comunidades/enquetes?slug=' . urlencode((string)$community['slug']));
            exit;
        }

        CommunityPollVote::vote($pollId, $userId, $optionNumber);

        $_SESSION['communities_success'] = 'Seu voto foi registrado.';
        header('Location: /comunidades/enquetes?slug=' . urlencode((string)$community['slug']) . '#poll-' . $pollId);
        exit;
    }

    public function closePoll(): void
    {
        $user = $this->requireLogin();

        $pollId = isset($_POST['poll_id']) ? (int)$_POST['poll_id'] : 0;
        if ($pollId <= 0) {
            $_SESSION['communities_error'] = 'Enquete inválida.';
            header('Location: /comunidades');
            exit;
        }

        $poll = CommunityPoll::findById($pollId);
        if (!$poll) {
            $_SESSION['communities_error'] = 'Enquete não encontrada.';
            header('Location: /comunidades');
            exit;
        }

        $community = Community::findById((int)($poll['community_id'] ?? 0));
        if (!$community || empty($community['is_active'])) {
            $_SESSION['communities_error'] = 'Comunidade desta enquete não foi encontrada.';
            header('Location: /comunidades');
            exit;
        }

        if (!$this->userCanClosePolls($community, $user)) {
            $_SESSION['communities_error'] = 'Você não tem permissão para encerrar enquetes nesta comunidade.';
            header('Location: /comunidades/enquetes?slug=' . urlencode((string)($community['slug'] ?? '')));
            exit;
        }

        CommunityPoll::close($pollId);
        $_SESSION['communities_success'] = 'Enquete encerrada.';
        header('Location: /comunidades/enquetes?slug=' . urlencode((string)($community['slug'] ?? '')) . '#poll-' . $pollId);
        exit;
    }

    public function deletePoll(): void
    {
        $user = $this->requireLogin();

        $pollId = isset($_POST['poll_id']) ? (int)$_POST['poll_id'] : 0;
        if ($pollId <= 0) {
            $_SESSION['communities_error'] = 'Enquete inválida.';
            header('Location: /comunidades');
            exit;
        }

        $poll = CommunityPoll::findById($pollId);
        if (!$poll) {
            $_SESSION['communities_error'] = 'Enquete não encontrada.';
            header('Location: /comunidades');
            exit;
        }

        $community = Community::findById((int)($poll['community_id'] ?? 0));
        if (!$community || empty($community['is_active'])) {
            $_SESSION['communities_error'] = 'Comunidade desta enquete não foi encontrada.';
            header('Location: /comunidades');
            exit;
        }

        $course = $this->findCourseForCommunity($community);
        if ($course && !empty($course['is_active']) && !$this->userCanAccessCourseCommunity($course, $user)) {
            $_SESSION['communities_error'] = 'Você precisa ter acesso a este curso para moderar esta comunidade.';
            $courseUrl = CourseController::buildCourseUrl($course);
            header('Location: ' . $courseUrl);
            exit;
        }

        $this->ensureCommunityModerator($community, $user);

        try {
            CommunityPoll::deleteById($pollId);
            $_SESSION['communities_success'] = 'Enquete excluída.';
        } catch (\Throwable $e) {
            $_SESSION['communities_error'] = 'Não foi possível excluir a enquete.';
        }

        header('Location: /comunidades/enquetes?slug=' . urlencode((string)($community['slug'] ?? '')));
        exit;
    }

    public function reopenPoll(): void
    {
        $user = $this->requireLogin();

        $pollId = isset($_POST['poll_id']) ? (int)$_POST['poll_id'] : 0;
        if ($pollId <= 0) {
            $_SESSION['communities_error'] = 'Enquete inválida.';
            header('Location: /comunidades');
            exit;
        }

        $poll = CommunityPoll::findById($pollId);
        if (!$poll) {
            $_SESSION['communities_error'] = 'Enquete não encontrada.';
            header('Location: /comunidades');
            exit;
        }

        $community = Community::findById((int)($poll['community_id'] ?? 0));
        if (!$community || empty($community['is_active'])) {
            $_SESSION['communities_error'] = 'Comunidade desta enquete não foi encontrada.';
            header('Location: /comunidades');
            exit;
        }

        if (!$this->userCanClosePolls($community, $user)) {
            $_SESSION['communities_error'] = 'Você não tem permissão para reabrir enquetes nesta comunidade.';
            header('Location: /comunidades/enquetes?slug=' . urlencode((string)($community['slug'] ?? '')));
            exit;
        }

        CommunityPoll::reopen($pollId);
        $_SESSION['communities_success'] = 'Enquete reaberta.';
        header('Location: /comunidades/enquetes?slug=' . urlencode((string)($community['slug'] ?? '')) . '#poll-' . $pollId);
        exit;
    }

    public function invites(): void
    {
        $user = $this->requireLogin();

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

        $course = $this->findCourseForCommunity($community);
        if ($course && !empty($course['is_active']) && !$this->userCanAccessCourseCommunity($course, $user)) {
            $_SESSION['communities_error'] = 'Você precisa ter acesso a este curso para visualizar esta comunidade.';
            $courseUrl = CourseController::buildCourseUrl($course);
            header('Location: ' . $courseUrl);
            exit;
        }

        // Apenas moderadores/dono/admin acessam a tela de convites
        $this->ensureCommunityModerator($community, $user);

        $success = $_SESSION['communities_success'] ?? null;
        $error = $_SESSION['communities_error'] ?? null;
        unset($_SESSION['communities_success'], $_SESSION['communities_error']);

        $this->view('social/community_invites', [
            'pageTitle' => 'Convites da comunidade',
            'user' => $user,
            'community' => $community,
            'success' => $success,
            'error' => $error,
        ]);
    }

    public function sendInvite(): void
    {
        $user = $this->requireLogin();
        $userId = (int)$user['id'];

        $communityId = isset($_POST['community_id']) ? (int)$_POST['community_id'] : 0;
        if ($communityId <= 0) {
            $_SESSION['communities_error'] = 'Comunidade inválida para enviar convites.';
            header('Location: /comunidades');
            exit;
        }

        $community = Community::findById($communityId);
        if (!$community || empty($community['is_active'])) {
            $_SESSION['communities_error'] = 'Comunidade não encontrada.';
            header('Location: /comunidades');
            exit;
        }

        $course = $this->findCourseForCommunity($community);
        if ($course && !empty($course['is_active']) && !$this->userCanAccessCourseCommunity($course, $user)) {
            $_SESSION['communities_error'] = 'Você precisa ter acesso a este curso para enviar convites desta comunidade.';
            $courseUrl = CourseController::buildCourseUrl($course);
            header('Location: ' . $courseUrl);
            exit;
        }

        $communityId = (int)$community['id'];
        if (!CommunityMember::isMember($communityId, $userId)) {
            $_SESSION['communities_error'] = 'Você precisa ser membro para enviar convites desta comunidade.';
            header('Location: /comunidades/convites?slug=' . urlencode((string)$community['slug']));
            exit;
        }

        // Garante permissão de moderação
        $this->ensureCommunityModerator($community, $user);

        $email = trim((string)($_POST['email'] ?? ''));
        $name = trim((string)($_POST['name'] ?? ''));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['communities_error'] = 'Informe um e-mail válido para enviar o convite.';
            header('Location: /comunidades/convites?slug=' . urlencode((string)$community['slug']));
            exit;
        }

        $token = bin2hex(random_bytes(16));
        CommunityInvite::create($communityId, $userId, $email, $name !== '' ? $name : null, $token);

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $link = $scheme . $host . '/comunidades/aceitar-convite?token=' . urlencode($token);

        $subject = 'Convite para participar da comunidade "' . ($community['name'] ?? 'Comunidade do Tuquinha') . '"';
        $toName = $name !== '' ? $name : $email;
        $body = '<p>Você foi convidado para participar da comunidade <strong>' . htmlspecialchars((string)$community['name'], ENT_QUOTES, 'UTF-8') . '</strong> no Tuquinha.</p>' .
            '<p>Para aceitar o convite e entrar na comunidade, clique no link abaixo:</p>' .
            '<p><a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '</a></p>' .
            '<p>Se você não reconhece este convite, pode ignorar este e-mail.</p>';

        $sent = MailService::send($email, $toName, $subject, $body);
        if ($sent) {
            $_SESSION['communities_success'] = 'Convite enviado para ' . $toName . '.';
        } else {
            $_SESSION['communities_error'] = 'O convite foi registrado, mas não foi possível enviar o e-mail agora.';
        }

        header('Location: /comunidades/convites?slug=' . urlencode((string)$community['slug']));
        exit;
    }

    public function acceptInvite(): void
    {
        $user = $this->requireLogin();
        $userId = (int)$user['id'];

        $token = trim((string)($_GET['token'] ?? ''));
        if ($token === '') {
            $_SESSION['communities_error'] = 'Convite inválido.';
            header('Location: /comunidades');
            exit;
        }

        $invite = CommunityInvite::findByToken($token);
        if (!$invite || ($invite['status'] ?? '') !== 'pending') {
            $_SESSION['communities_error'] = 'Convite não encontrado ou já utilizado.';
            header('Location: /comunidades');
            exit;
        }

        $community = Community::findById((int)$invite['community_id']);
        if (!$community || empty($community['is_active'])) {
            $_SESSION['communities_error'] = 'Comunidade deste convite não foi encontrada.';
            header('Location: /comunidades');
            exit;
        }

        // Por segurança, tenta casar o e-mail do convite com o e-mail do usuário logado
        $invitedEmail = trim((string)($invite['invited_email'] ?? ''));
        $userEmail = trim((string)($user['email'] ?? ''));
        if ($invitedEmail !== '' && $userEmail !== '' && strcasecmp($invitedEmail, $userEmail) !== 0) {
            $_SESSION['communities_error'] = 'Este convite foi enviado para outro e-mail.';
            header('Location: /comunidades');
            exit;
        }

        $course = $this->findCourseForCommunity($community);
        if ($course && !empty($course['is_active']) && !$this->userCanAccessCourseCommunity($course, $user)) {
            $_SESSION['communities_error'] = 'Você precisa ter acesso ao curso vinculado para entrar nesta comunidade.';
            $courseUrl = CourseController::buildCourseUrl($course);
            header('Location: ' . $courseUrl);
            exit;
        }

        $communityId = (int)$community['id'];
        if (CommunityMember::isBlocked($communityId, $userId)) {
            $_SESSION['communities_error'] = 'Você foi bloqueado nesta comunidade e não pode entrar.';
            header('Location: /comunidades');
            exit;
        }

        CommunityMember::join($communityId, $userId, 'member');
        CommunityInvite::markAccepted((int)$invite['id'], $userId);

        $_SESSION['communities_success'] = 'Convite aceito. Você agora faz parte desta comunidade.';
        header('Location: /comunidades/ver?slug=' . urlencode((string)$community['slug']));
        exit;
    }

    public function reportMember(): void
    {
        $user = $this->requireLogin();
        $userId = (int)$user['id'];

        $communityId = isset($_POST['community_id']) ? (int)$_POST['community_id'] : 0;
        $reportedUserId = isset($_POST['reported_user_id']) ? (int)$_POST['reported_user_id'] : 0;
        $reason = trim((string)($_POST['reason'] ?? ''));

        if ($communityId <= 0 || $reportedUserId <= 0) {
            $_SESSION['communities_error'] = 'Dados inválidos para denúncia.';
            header('Location: /comunidades');
            exit;
        }

        if ($reportedUserId === $userId) {
            $_SESSION['communities_error'] = 'Você não pode se denunciar.';
            header('Location: /comunidades');
            exit;
        }

        $community = Community::findById($communityId);
        if (!$community || empty($community['is_active'])) {
            $_SESSION['communities_error'] = 'Comunidade não encontrada.';
            header('Location: /comunidades');
            exit;
        }

        $course = $this->findCourseForCommunity($community);
        if ($course && !empty($course['is_active']) && !$this->userCanAccessCourseCommunity($course, $user)) {
            $_SESSION['communities_error'] = 'Você precisa ter acesso a este curso para interagir nesta comunidade.';
            $courseUrl = CourseController::buildCourseUrl($course);
            header('Location: ' . $courseUrl);
            exit;
        }

        $communityId = (int)$community['id'];
        if (!CommunityMember::isMember($communityId, $userId)) {
            $_SESSION['communities_error'] = 'Você precisa ser membro para denunciar alguém nesta comunidade.';
            header('Location: /comunidades/membros?slug=' . urlencode((string)$community['slug']));
            exit;
        }

        $reportId = CommunityMemberReport::create([
            'community_id' => $communityId,
            'reporter_user_id' => $userId,
            'reported_user_id' => $reportedUserId,
            'reason' => $reason !== '' ? $reason : null,
        ]);

        // Notificação por e-mail para admin/responsável (configurável em settings)
        $adminEmail = Setting::get('admin_community_report_email', '')
            ?: Setting::get('admin_error_notification_email', '');

        if ($adminEmail !== '') {
            $reporter = User::findById($userId);
            $reported = User::findById($reportedUserId);

            $subject = 'Nova denúncia em comunidade no Tuquinha';

            $safeCommunityName = htmlspecialchars((string)($community['name'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeReporterName = htmlspecialchars((string)($reporter['name'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeReporterEmail = htmlspecialchars((string)($reporter['email'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeReportedName = htmlspecialchars((string)($reported['name'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeReason = $reason !== ''
                ? nl2br(htmlspecialchars($reason, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'))
                : 'Nenhum motivo detalhado informado.';

            $communitySlug = (string)($community['slug'] ?? '');
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $link = $scheme . $host . '/comunidades/membros?slug=' . urlencode($communitySlug);

            $body = '<p>Uma nova denúncia foi registrada em uma comunidade.</p>' .
                '<ul>' .
                '<li><strong>ID da denúncia:</strong> ' . (int)$reportId . '</li>' .
                '<li><strong>Comunidade:</strong> ' . $safeCommunityName . ' (ID ' . (int)$communityId . ')</li>' .
                '<li><strong>Denunciante:</strong> ' . $safeReporterName . ' (' . $safeReporterEmail . ')</li>' .
                '<li><strong>Usuário denunciado:</strong> ' . $safeReportedName . ' (ID ' . (int)$reportedUserId . ')</li>' .
                '</ul>' .
                '<p><strong>Motivo informado:</strong><br>' . $safeReason . '</p>' .
                '<p>Você pode revisar os membros e denúncias desta comunidade em:<br>' .
                '<a href="' . htmlspecialchars($link, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">' .
                htmlspecialchars($link, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</a></p>';

            try {
                MailService::send($adminEmail, $adminEmail, $subject, $body);
            } catch (\Throwable $e) {
                // Falha na notificação não deve impactar o fluxo do usuário
            }
        }

        $_SESSION['communities_success'] = 'Denúncia registrada para análise dos moderadores.';
        header('Location: /comunidades/membros?slug=' . urlencode((string)$community['slug']));
        exit;
    }

    public function blockMember(): void
    {
        $user = $this->requireLogin();
        $userId = (int)$user['id'];

        $communityId = isset($_POST['community_id']) ? (int)$_POST['community_id'] : 0;
        $targetUserId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        $reason = trim((string)($_POST['reason'] ?? ''));

        if ($communityId <= 0 || $targetUserId <= 0) {
            $_SESSION['communities_error'] = 'Dados inválidos para bloqueio.';
            header('Location: /comunidades');
            exit;
        }

        if ($targetUserId === $userId) {
            $_SESSION['communities_error'] = 'Você não pode se bloquear.';
            header('Location: /comunidades');
            exit;
        }

        $community = Community::findById($communityId);
        if (!$community || empty($community['is_active'])) {
            $_SESSION['communities_error'] = 'Comunidade não encontrada.';
            header('Location: /comunidades');
            exit;
        }

        $course = $this->findCourseForCommunity($community);
        if ($course && !empty($course['is_active']) && !$this->userCanAccessCourseCommunity($course, $user)) {
            $_SESSION['communities_error'] = 'Você precisa ter acesso a este curso para moderar esta comunidade.';
            $courseUrl = CourseController::buildCourseUrl($course);
            header('Location: ' . $courseUrl);
            exit;
        }

        // Garante permissão de moderação
        $this->ensureCommunityModerator($community, $user);

        $communityId = (int)$community['id'];

        $member = CommunityMember::findMember($communityId, $targetUserId);
        if (!$member) {
            $_SESSION['communities_error'] = 'Usuário não é membro desta comunidade.';
            header('Location: /comunidades/membros?slug=' . urlencode((string)$community['slug']));
            exit;
        }

        // Não permite bloquear o dono
        $ownerId = (int)($community['owner_user_id'] ?? 0);
        if ($targetUserId === $ownerId) {
            $_SESSION['communities_error'] = 'Não é possível bloquear o dono da comunidade.';
            header('Location: /comunidades/membros?slug=' . urlencode((string)$community['slug']));
            exit;
        }

        CommunityMember::block($communityId, $targetUserId, $reason !== '' ? $reason : null);

        $_SESSION['communities_success'] = 'Membro bloqueado na comunidade.';
        header('Location: /comunidades/membros?slug=' . urlencode((string)$community['slug']));
        exit;
    }

    public function unblockMember(): void
    {
        $user = $this->requireLogin();

        $communityId = isset($_POST['community_id']) ? (int)$_POST['community_id'] : 0;
        $targetUserId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

        if ($communityId <= 0 || $targetUserId <= 0) {
            $_SESSION['communities_error'] = 'Dados inválidos para desbloqueio.';
            header('Location: /comunidades');
            exit;
        }

        $community = Community::findById($communityId);
        if (!$community || empty($community['is_active'])) {
            $_SESSION['communities_error'] = 'Comunidade não encontrada.';
            header('Location: /comunidades');
            exit;
        }

        $course = $this->findCourseForCommunity($community);
        if ($course && !empty($course['is_active']) && !$this->userCanAccessCourseCommunity($course, $user)) {
            $_SESSION['communities_error'] = 'Você precisa ter acesso a este curso para moderar esta comunidade.';
            $courseUrl = CourseController::buildCourseUrl($course);
            header('Location: ' . $courseUrl);
            exit;
        }

        // Garante permissão de moderação
        $this->ensureCommunityModerator($community, $user);

        $communityId = (int)$community['id'];

        CommunityMember::unblock($communityId, $targetUserId);

        $_SESSION['communities_success'] = 'Membro desbloqueado na comunidade.';
        header('Location: /comunidades/membros?slug=' . urlencode((string)$community['slug']));
        exit;
    }

    public function resolveReport(): void
    {
        $user = $this->requireLogin();
        $userId = (int)$user['id'];

        $reportId = isset($_POST['report_id']) ? (int)$_POST['report_id'] : 0;
        if ($reportId <= 0) {
            $_SESSION['communities_error'] = 'Denúncia inválida.';
            header('Location: /comunidades');
            exit;
        }

        $report = CommunityMemberReport::findById($reportId);
        if (!$report) {
            $_SESSION['communities_error'] = 'Denúncia não encontrada.';
            header('Location: /comunidades');
            exit;
        }

        $community = Community::findById((int)$report['community_id']);
        if (!$community || empty($community['is_active'])) {
            $_SESSION['communities_error'] = 'Comunidade desta denúncia não foi encontrada.';
            header('Location: /comunidades');
            exit;
        }

        $course = $this->findCourseForCommunity($community);
        if ($course && !empty($course['is_active']) && !$this->userCanAccessCourseCommunity($course, $user)) {
            $_SESSION['communities_error'] = 'Você precisa ter acesso a este curso para moderar esta comunidade.';
            $courseUrl = CourseController::buildCourseUrl($course);
            header('Location: ' . $courseUrl);
            exit;
        }

        // Garante permissão de moderação
        $this->ensureCommunityModerator($community, $user);

        CommunityMemberReport::markResolved($reportId, $userId);

        $_SESSION['communities_success'] = 'Denúncia marcada como resolvida.';
        header('Location: /comunidades/membros?slug=' . urlencode((string)$community['slug']));
        exit;
    }
}
