<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\UserSocialProfile;
use App\Models\UserScrap;
use App\Models\UserTestimonial;
use App\Models\UserFriend;
use App\Models\CommunityMember;

class ProfileController extends Controller
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

    public function show(): void
    {
        $currentUser = $this->requireLogin();
        $currentId = (int)$currentUser['id'];

        $targetId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $currentId;
        if ($targetId <= 0) {
            $targetId = $currentId;
        }

        $profileUser = User::findById($targetId);
        if (!$profileUser) {
            header('Location: /comunidade');
            exit;
        }

        $profile = UserSocialProfile::findByUserId($targetId);
        if ($targetId !== $currentId) {
            if (!$profile) {
                UserSocialProfile::upsertForUser($targetId, []);
                $profile = UserSocialProfile::findByUserId($targetId);
            }
            UserSocialProfile::incrementVisit($targetId);
        }

        $scraps = $targetId === $currentId
            ? UserScrap::allForUser($targetId, 50)
            : UserScrap::allVisibleForUser($targetId, 50);
        $publicTestimonials = UserTestimonial::allPublicForUser($targetId);
        $pendingTestimonials = $targetId === $currentId ? UserTestimonial::pendingForUser($currentId) : [];
        $friends = UserFriend::friendsWithUsers($targetId);
        $communities = CommunityMember::communitiesForUser($targetId);
        $friendship = $targetId !== $currentId ? UserFriend::findFriendship($currentId, $targetId) : null;

        $success = $_SESSION['social_success'] ?? null;
        $error = $_SESSION['social_error'] ?? null;
        unset($_SESSION['social_success'], $_SESSION['social_error']);

        $displayName = $profileUser['preferred_name'] ?? $profileUser['name'] ?? '';
        $displayName = trim((string)$displayName);
        if ($displayName === '') {
            $displayName = 'Perfil';
        }

        $this->view('social/profile', [
            'pageTitle' => 'Perfil social - ' . $displayName,
            'user' => $currentUser,
            'profileUser' => $profileUser,
            'profile' => $profile,
            'scraps' => $scraps,
            'publicTestimonials' => $publicTestimonials,
            'pendingTestimonials' => $pendingTestimonials,
            'friends' => $friends,
            'communities' => $communities,
            'friendship' => $friendship,
            'success' => $success,
            'error' => $error,
        ]);
    }

    public function saveProfile(): void
    {
        $currentUser = $this->requireLogin();
        $userId = (int)$currentUser['id'];

        $aboutMe = trim((string)($_POST['about_me'] ?? ''));
        $interests = trim((string)($_POST['interests'] ?? ''));
        $favoriteMusic = trim((string)($_POST['favorite_music'] ?? ''));
        $favoriteMovies = trim((string)($_POST['favorite_movies'] ?? ''));
        $favoriteBooks = trim((string)($_POST['favorite_books'] ?? ''));
        $website = trim((string)($_POST['website'] ?? ''));

        $language = trim((string)($_POST['language'] ?? ''));
        $profileCategory = trim((string)($_POST['profile_category'] ?? ''));

        $profilePrivacy = (string)($_POST['profile_privacy'] ?? 'public');
        if (!in_array($profilePrivacy, ['public', 'private'], true)) {
            $profilePrivacy = 'public';
        }

        $visibilityScope = (string)($_POST['visibility_scope'] ?? 'everyone');
        if (!in_array($visibilityScope, ['everyone', 'community', 'friends'], true)) {
            $visibilityScope = 'everyone';
        }

        $relationshipStatus = trim((string)($_POST['relationship_status'] ?? ''));
        $birthday = trim((string)($_POST['birthday'] ?? ''));
        $age = isset($_POST['age']) ? (int)$_POST['age'] : 0;
        $children = trim((string)($_POST['children'] ?? ''));
        $ethnicity = trim((string)($_POST['ethnicity'] ?? ''));
        $mood = trim((string)($_POST['mood'] ?? ''));
        $sexualOrientation = trim((string)($_POST['sexual_orientation'] ?? ''));
        $style = trim((string)($_POST['style'] ?? ''));
        $smokes = trim((string)($_POST['smokes'] ?? ''));
        $drinks = trim((string)($_POST['drinks'] ?? ''));
        $pets = trim((string)($_POST['pets'] ?? ''));
        $hometown = trim((string)($_POST['hometown'] ?? ''));
        $location = trim((string)($_POST['location'] ?? ''));
        $sports = trim((string)($_POST['sports'] ?? ''));
        $passions = trim((string)($_POST['passions'] ?? ''));
        $activities = trim((string)($_POST['activities'] ?? ''));
        $instagramRaw = trim((string)($_POST['instagram'] ?? ''));
        $facebookRaw = trim((string)($_POST['facebook'] ?? ''));
        $youtubeRaw = trim((string)($_POST['youtube'] ?? ''));

        if ($website !== '' && !preg_match('/^https?:\/\//i', $website)) {
            $website = 'https://' . $website;
        }

        // Normaliza URLs de redes sociais (aceita link completo ou apenas usuário)
        $instagram = null;
        if ($instagramRaw !== '') {
            if (preg_match('/^https?:\/\//i', $instagramRaw)) {
                $instagram = $instagramRaw;
            } else {
                $username = ltrim($instagramRaw, '@');
                $instagram = 'https://instagram.com/' . $username;
            }
        }

        $facebook = null;
        if ($facebookRaw !== '') {
            if (preg_match('/^https?:\/\//i', $facebookRaw)) {
                $facebook = $facebookRaw;
            } else {
                $username = ltrim($facebookRaw, '@');
                $facebook = 'https://facebook.com/' . $username;
            }
        }

        $youtube = null;
        if ($youtubeRaw !== '') {
            if (preg_match('/^https?:\/\//i', $youtubeRaw)) {
                $youtube = $youtubeRaw;
            } else {
                $channel = ltrim($youtubeRaw, '@');
                $youtube = 'https://youtube.com/' . $channel;
            }
        }

        // Avatar atual (caso não envie um novo, preserva o existente)
        $existingProfile = UserSocialProfile::findByUserId($userId);
        $avatarPath = $existingProfile['avatar_path'] ?? null;

        // Upload opcional de nova foto de perfil
        if (!empty($_FILES['avatar_file']) && is_array($_FILES['avatar_file'])) {
            $uploadError = (int)($_FILES['avatar_file']['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($uploadError !== UPLOAD_ERR_NO_FILE) {
                if ($uploadError !== UPLOAD_ERR_OK) {
                    $_SESSION['social_error'] = 'Erro ao enviar a foto de perfil.';
                    header('Location: /perfil');
                    exit;
                }

                $tmp = $_FILES['avatar_file']['tmp_name'] ?? '';
                $originalName = (string)($_FILES['avatar_file']['name'] ?? 'avatar');
                $type = (string)($_FILES['avatar_file']['type'] ?? '');
                $size = (int)($_FILES['avatar_file']['size'] ?? 0);

                $maxSize = 2 * 1024 * 1024; // 2MB
                if ($size <= 0 || $size > $maxSize) {
                    $_SESSION['social_error'] = 'A foto de perfil deve ter até 2 MB.';
                    header('Location: /perfil');
                    exit;
                }

                if (!str_starts_with($type, 'image/')) {
                    $_SESSION['social_error'] = 'Envie apenas arquivos de imagem (como JPG ou PNG) para a foto de perfil.';
                    header('Location: /perfil');
                    exit;
                }

                $publicDir = __DIR__ . '/../../public/uploads/avatars';
                if (!is_dir($publicDir)) {
                    @mkdir($publicDir, 0775, true);
                }

                $ext = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));
                if ($ext === '') {
                    $ext = 'png';
                }

                $fileName = uniqid('avatar_', true) . '.' . $ext;
                $targetPath = $publicDir . '/' . $fileName;

                if (!@move_uploaded_file($tmp, $targetPath)) {
                    $_SESSION['social_error'] = 'Não foi possível salvar a foto de perfil enviada. Tente novamente.';
                    header('Location: /perfil');
                    exit;
                }

                $avatarPath = '/public/uploads/avatars/' . $fileName;
            }
        }

        try {
            UserSocialProfile::upsertForUser($userId, [
                'about_me' => $aboutMe !== '' ? $aboutMe : null,
                'interests' => $interests !== '' ? $interests : null,
                'favorite_music' => $favoriteMusic !== '' ? $favoriteMusic : null,
                'favorite_movies' => $favoriteMovies !== '' ? $favoriteMovies : null,
                'favorite_books' => $favoriteBooks !== '' ? $favoriteBooks : null,
                'website' => $website !== '' ? $website : null,
                'avatar_path' => $avatarPath,
                'language' => $language !== '' ? $language : null,
                'profile_category' => $profileCategory !== '' ? $profileCategory : null,
                'profile_privacy' => $profilePrivacy,
                'visibility_scope' => $visibilityScope,
                'relationship_status' => $relationshipStatus !== '' ? $relationshipStatus : null,
                'birthday' => $birthday !== '' ? $birthday : null,
                'age' => $age > 0 ? $age : null,
                'children' => $children !== '' ? $children : null,
                'ethnicity' => $ethnicity !== '' ? $ethnicity : null,
                'mood' => $mood !== '' ? $mood : null,
                'sexual_orientation' => $sexualOrientation !== '' ? $sexualOrientation : null,
                'style' => $style !== '' ? $style : null,
                'smokes' => $smokes !== '' ? $smokes : null,
                'drinks' => $drinks !== '' ? $drinks : null,
                'pets' => $pets !== '' ? $pets : null,
                'hometown' => $hometown !== '' ? $hometown : null,
                'location' => $location !== '' ? $location : null,
                'sports' => $sports !== '' ? $sports : null,
                'passions' => $passions !== '' ? $passions : null,
                'activities' => $activities !== '' ? $activities : null,
                'instagram' => $instagram ?? null,
                'facebook' => $facebook ?? null,
                'youtube' => $youtube ?? null,
            ]);

            $_SESSION['social_success'] = 'Seu perfil social foi atualizado.';
        } catch (\Throwable $e) {
            $_SESSION['social_error'] = 'Não foi possível salvar seu perfil social agora. Tente novamente em alguns instantes.';
        }

        header('Location: /perfil');
        exit;
    }

    public function postScrap(): void
    {
        $currentUser = $this->requireLogin();
        $fromUserId = (int)$currentUser['id'];

        $toUserId = isset($_POST['to_user_id']) ? (int)$_POST['to_user_id'] : 0;
        $body = trim((string)($_POST['body'] ?? ''));

        if ($toUserId <= 0 || $toUserId === $fromUserId) {
            $_SESSION['social_error'] = 'Escolha um usuário válido para enviar o scrap.';
            header('Location: /perfil');
            exit;
        }

        $target = User::findById($toUserId);
        if (!$target) {
            $_SESSION['social_error'] = 'Usuário não encontrado para receber o scrap.';
            header('Location: /perfil');
            exit;
        }

        if ($body === '') {
            $_SESSION['social_error'] = 'Escreva algo antes de enviar o scrap.';
            header('Location: /perfil?user_id=' . $toUserId);
            exit;
        }

        if (strlen($body) > 4000) {
            $_SESSION['social_error'] = 'O scrap pode ter no máximo 4000 caracteres.';
            header('Location: /perfil?user_id=' . $toUserId);
            exit;
        }

        UserScrap::create([
            'from_user_id' => $fromUserId,
            'to_user_id' => $toUserId,
            'body' => $body,
        ]);

        $_SESSION['social_success'] = 'Scrap enviado no mural.';
        header('Location: /perfil?user_id=' . $toUserId);
        exit;
    }

    public function editScrap(): void
    {
        $currentUser = $this->requireLogin();
        $currentId = (int)$currentUser['id'];

        $scrapId = isset($_POST['scrap_id']) ? (int)$_POST['scrap_id'] : 0;
        $body = trim((string)($_POST['body'] ?? ''));

        $scrap = UserScrap::findById($scrapId);
        if (!$scrap || (int)($scrap['is_deleted'] ?? 0) === 1) {
            $_SESSION['social_error'] = 'Scrap não encontrado.';
            header('Location: /perfil');
            exit;
        }

        if ((int)($scrap['from_user_id'] ?? 0) !== $currentId) {
            $_SESSION['social_error'] = 'Você não tem permissão para editar esse scrap.';
            header('Location: /perfil?user_id=' . (int)($scrap['to_user_id'] ?? 0));
            exit;
        }

        if ($body === '') {
            $_SESSION['social_error'] = 'O scrap não pode ficar em branco.';
            header('Location: /perfil?user_id=' . (int)($scrap['to_user_id'] ?? 0));
            exit;
        }

        if (strlen($body) > 4000) {
            $_SESSION['social_error'] = 'O scrap pode ter no máximo 4000 caracteres.';
            header('Location: /perfil?user_id=' . (int)($scrap['to_user_id'] ?? 0));
            exit;
        }

        UserScrap::updateBodyByAuthor($scrapId, $currentId, $body);
        $_SESSION['social_success'] = 'Scrap atualizado.';
        header('Location: /perfil?user_id=' . (int)($scrap['to_user_id'] ?? 0) . '#scraps');
        exit;
    }

    public function deleteScrap(): void
    {
        $currentUser = $this->requireLogin();
        $currentId = (int)$currentUser['id'];

        $scrapId = isset($_POST['scrap_id']) ? (int)$_POST['scrap_id'] : 0;
        $scrap = UserScrap::findById($scrapId);
        if (!$scrap || (int)($scrap['is_deleted'] ?? 0) === 1) {
            $_SESSION['social_error'] = 'Scrap não encontrado.';
            header('Location: /perfil');
            exit;
        }

        if ((int)($scrap['from_user_id'] ?? 0) !== $currentId) {
            $_SESSION['social_error'] = 'Você não tem permissão para excluir esse scrap.';
            header('Location: /perfil?user_id=' . (int)($scrap['to_user_id'] ?? 0));
            exit;
        }

        UserScrap::softDeleteByAuthor($scrapId, $currentId);
        $_SESSION['social_success'] = 'Scrap excluído.';
        header('Location: /perfil?user_id=' . (int)($scrap['to_user_id'] ?? 0) . '#scraps');
        exit;
    }

    public function toggleScrapVisibility(): void
    {
        $currentUser = $this->requireLogin();
        $currentId = (int)$currentUser['id'];

        $scrapId = isset($_POST['scrap_id']) ? (int)$_POST['scrap_id'] : 0;
        $action = trim((string)($_POST['action'] ?? ''));
        $hide = $action === 'hide';

        $scrap = UserScrap::findById($scrapId);
        if (!$scrap || (int)($scrap['is_deleted'] ?? 0) === 1) {
            $_SESSION['social_error'] = 'Scrap não encontrado.';
            header('Location: /perfil');
            exit;
        }

        if ((int)($scrap['to_user_id'] ?? 0) !== $currentId) {
            $_SESSION['social_error'] = 'Só o dono do perfil pode ocultar/mostrar scraps.';
            header('Location: /perfil?user_id=' . (int)($scrap['to_user_id'] ?? 0));
            exit;
        }

        UserScrap::setHiddenByProfileOwner($scrapId, $currentId, $hide);
        $_SESSION['social_success'] = $hide ? 'Scrap ocultado do seu perfil.' : 'Scrap voltou a aparecer no seu perfil.';
        header('Location: /perfil#scraps');
        exit;
    }

    public function submitTestimonial(): void
    {
        $currentUser = $this->requireLogin();
        $fromUserId = (int)$currentUser['id'];

        $toUserId = isset($_POST['to_user_id']) ? (int)$_POST['to_user_id'] : 0;
        $body = trim((string)($_POST['body'] ?? ''));
        $isPublic = !empty($_POST['is_public']) ? 1 : 0;

        if ($toUserId <= 0 || $toUserId === $fromUserId) {
            $_SESSION['social_error'] = 'Escolha alguém para receber seu depoimento.';
            header('Location: /perfil');
            exit;
        }

        $target = User::findById($toUserId);
        if (!$target) {
            $_SESSION['social_error'] = 'Usuário não encontrado para receber o depoimento.';
            header('Location: /perfil');
            exit;
        }

        if ($body === '') {
            $_SESSION['social_error'] = 'Escreva algo no depoimento antes de enviar.';
            header('Location: /perfil?user_id=' . $toUserId);
            exit;
        }

        if (strlen($body) > 4000) {
            $_SESSION['social_error'] = 'O depoimento pode ter no máximo 4000 caracteres.';
            header('Location: /perfil?user_id=' . $toUserId);
            exit;
        }

        UserTestimonial::create([
            'from_user_id' => $fromUserId,
            'to_user_id' => $toUserId,
            'body' => $body,
            'is_public' => $isPublic,
            'status' => 'pending',
        ]);

        $_SESSION['social_success'] = 'Depoimento enviado para aprovação da pessoa.';
        header('Location: /perfil?user_id=' . $toUserId);
        exit;
    }

    public function decideTestimonial(): void
    {
        $currentUser = $this->requireLogin();
        $toUserId = (int)$currentUser['id'];

        $testimonialId = isset($_POST['testimonial_id']) ? (int)$_POST['testimonial_id'] : 0;
        $decision = (string)($_POST['decision'] ?? '');

        if ($testimonialId <= 0) {
            $_SESSION['social_error'] = 'Depoimento inválido.';
            header('Location: /perfil');
            exit;
        }

        UserTestimonial::decide($testimonialId, $toUserId, $decision);

        $_SESSION['social_success'] = 'Escolha registrada para o depoimento.';
        header('Location: /perfil');
        exit;
    }
}
