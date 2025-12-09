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

        $scraps = UserScrap::allForUser($targetId, 50);
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

        if ($website !== '' && !preg_match('/^https?:\/\//i', $website)) {
            $website = 'https://' . $website;
        }

        try {
            UserSocialProfile::upsertForUser($userId, [
                'about_me' => $aboutMe !== '' ? $aboutMe : null,
                'interests' => $interests !== '' ? $interests : null,
                'favorite_music' => $favoriteMusic !== '' ? $favoriteMusic : null,
                'favorite_movies' => $favoriteMovies !== '' ? $favoriteMovies : null,
                'favorite_books' => $favoriteBooks !== '' ? $favoriteBooks : null,
                'website' => $website !== '' ? $website : null,
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
