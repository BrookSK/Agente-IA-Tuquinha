<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseModule;
use App\Models\CourseLesson;
use App\Models\CourseLessonProgress;
use App\Models\CoursePartnerBranding;
use App\Models\CourseAllowedCommunity;
use App\Models\CourseLessonComment;
use App\Models\CoursePurchase;
use App\Models\UserSocialProfile;
use App\Models\UserScrap;
use App\Models\UserTestimonial;
use App\Models\UserFriend;
use App\Models\CommunityMember;
use App\Models\UserCourseBadge;
use App\Models\SocialPortfolioItem;
use App\Models\SocialConversation;
use App\Models\SocialMessage;

class ExternalUserDashboardController extends Controller
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

    private function getBrandingForUser(array $user): ?array
    {
        $partnerId = User::getExternalCoursePartnerId((int)$user['id']);
        if (!$partnerId) {
            return null;
        }

        return CoursePartnerBranding::findByUserId($partnerId);
    }

    public function index(): void
    {
        $user = $this->requireLogin();
        $branding = $this->getBrandingForUser($user);
        $userId = (int)$user['id'];

        // Get enrolled courses count
        $enrolledCourses = CourseEnrollment::allByUser($userId);
        $enrolledCoursesCount = count($enrolledCourses);

        // Calculate average progress (simplified - count courses with any progress)
        $coursesWithProgress = 0;
        foreach ($enrolledCourses as $enrollment) {
            $courseId = (int)$enrollment['course_id'];
            $completedLessons = CourseLessonProgress::completedLessonIdsByUserAndCourse($courseId, $userId);
            if (count($completedLessons) > 0) {
                $coursesWithProgress++;
            }
        }
        $averageProgress = $enrolledCoursesCount > 0 ? round(($coursesWithProgress / $enrolledCoursesCount) * 100) : 0;

        // Get communities count
        $communities = CourseAllowedCommunity::allowedCommunitiesByUser($userId);
        $communitiesCount = count($communities);

        $this->view('external_dashboard/index', [
            'pageTitle' => 'Meu Painel',
            'user' => $user,
            'branding' => $branding,
            'enrolledCoursesCount' => $enrolledCoursesCount,
            'averageProgress' => $averageProgress,
            'communitiesCount' => $communitiesCount,
            'layout' => 'external_user_dashboard',
        ]);
    }

    public function allCourses(): void
    {
        $user = $this->requireLogin();
        $branding = $this->getBrandingForUser($user);
        $partnerId = User::getExternalCoursePartnerId((int)$user['id']);

        $courses = [];
        if ($partnerId) {
            $allCourses = Course::allActive();
            $enrollments = CourseEnrollment::allByUser((int)$user['id']);
            $enrolledCourseIds = [];
            foreach ($enrollments as $enrollment) {
                $enrolledCourseIds[(int)$enrollment['course_id']] = true;
            }

            foreach ($allCourses as $course) {
                if ((int)$course['owner_user_id'] === $partnerId) {
                    $course['user_has_access'] = !empty($enrolledCourseIds[(int)$course['id']]);
                    $courses[] = $course;
                }
            }
        }

        $this->view('external_dashboard/all_courses', [
            'pageTitle' => 'Cursos Disponíveis',
            'user' => $user,
            'branding' => $branding,
            'courses' => $courses,
            'layout' => 'external_user_dashboard',
        ]);
    }

    public function myCourses(): void
    {
        $user = $this->requireLogin();
        $branding = $this->getBrandingForUser($user);
        $partnerId = User::getExternalCoursePartnerId((int)$user['id']);

        $enrollments = CourseEnrollment::allByUser((int)$user['id']);
        $myCourses = [];

        foreach ($enrollments as $enrollment) {
            $course = Course::findById((int)$enrollment['course_id']);
            if ($course && $partnerId && (int)$course['owner_user_id'] === $partnerId) {
                $myCourses[] = $course;
            }
        }

        $this->view('external_dashboard/my_courses', [
            'pageTitle' => 'Meus Cursos',
            'user' => $user,
            'branding' => $branding,
            'courses' => $myCourses,
            'layout' => 'external_user_dashboard',
        ]);
    }

    public function community(): void
    {
        $user = $this->requireLogin();
        $branding = $this->getBrandingForUser($user);

        $allowedCommunities = CourseAllowedCommunity::allowedCommunitiesByUser((int)$user['id']);

        $this->view('external_dashboard/community', [
            'pageTitle' => 'Comunidade',
            'user' => $user,
            'branding' => $branding,
            'communities' => $allowedCommunities,
            'layout' => 'external_user_dashboard',
        ]);
    }

    public function viewCourse(): void
    {
        $user = $this->requireLogin();
        $branding = $this->getBrandingForUser($user);
        $partnerId = User::getExternalCoursePartnerId((int)$user['id']);
        
        $courseId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $course = Course::findById($courseId);
        
        if (!$course) {
            header('Location: /painel-externo/cursos');
            exit;
        }

        if ($partnerId && (int)$course['owner_user_id'] !== $partnerId) {
            header('Location: /painel-externo/cursos');
            exit;
        }

        $hasAccess = false;
        $enrollments = CourseEnrollment::allByUser((int)$user['id']);
        foreach ($enrollments as $enrollment) {
            if ((int)$enrollment['course_id'] === $courseId) {
                $hasAccess = true;
                break;
            }
        }

        $modules = [];
        if ($hasAccess) {
            $allModules = CourseModule::allByCourse($courseId);
            $allLessons = CourseLesson::allByCourseId($courseId);
            $completedLessonIds = CourseLessonProgress::completedLessonIdsByUserAndCourse($courseId, (int)$user['id']);
            
            foreach ($allModules as $module) {
                $moduleLessons = [];
                foreach ($allLessons as $lesson) {
                    if ((int)($lesson['module_id'] ?? 0) === (int)$module['id'] && !empty($lesson['is_published'])) {
                        $lesson['is_completed'] = !empty($completedLessonIds[(int)$lesson['id']]);
                        $moduleLessons[] = $lesson;
                    }
                }
                $module['lessons'] = $moduleLessons;
                $modules[] = $module;
            }
        }

        $this->view('external_dashboard/view_course', [
            'pageTitle' => $course['title'] ?? 'Curso',
            'user' => $user,
            'branding' => $branding,
            'course' => $course,
            'hasAccess' => $hasAccess,
            'modules' => $modules,
            'layout' => 'external_user_dashboard',
        ]);
    }

    public function watchLesson(): void
    {
        $user = $this->requireLogin();
        $branding = $this->getBrandingForUser($user);
        $partnerId = User::getExternalCoursePartnerId((int)$user['id']);

        $lessonId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

        if ($lessonId <= 0 || $courseId <= 0) {
            header('Location: /painel-externo/cursos');
            exit;
        }

        $lesson = CourseLesson::findById($lessonId);
        if (!$lesson || empty($lesson['is_published'])) {
            header('Location: /painel-externo/curso?id=' . $courseId);
            exit;
        }

        $course = Course::findById($courseId);
        if (!$course || empty($course['is_active'])) {
            header('Location: /painel-externo/cursos');
            exit;
        }

        if ($partnerId && (int)$course['owner_user_id'] !== $partnerId) {
            header('Location: /painel-externo/cursos');
            exit;
        }

        $isEnrolled = CourseEnrollment::isEnrolled($courseId, (int)$user['id']);
        if (!$isEnrolled) {
            $hasPaidPurchase = CoursePurchase::userHasPaidPurchase((int)$user['id'], $courseId);
            if (!$hasPaidPurchase) {
                header('Location: /painel-externo/curso?id=' . $courseId);
                exit;
            }
        }

        $completedLessonIds = CourseLessonProgress::completedLessonIdsByUserAndCourse($courseId, (int)$user['id']);
        $isLessonCompleted = !empty($completedLessonIds[$lessonId]);

        $lessons = CourseLesson::allByCourseId($courseId);
        $lessonComments = CourseLessonComment::allByLessonWithUser($lessonId);

        $prevUrl = null;
        $nextUrl = null;
        $currentModuleId = (int)($lesson['module_id'] ?? 0);

        $countLessons = count($lessons);
        $currentIndex = null;
        for ($i = 0; $i < $countLessons; $i++) {
            $lid = (int)($lessons[$i]['id'] ?? 0);
            if ($lid === $lessonId) {
                $currentIndex = $i;
                break;
            }
        }

        if ($currentIndex !== null) {
            if ($currentIndex - 1 >= 0) {
                $prevLesson = $lessons[$currentIndex - 1];
                $prevLessonId = (int)($prevLesson['id'] ?? 0);
                if ($prevLessonId > 0) {
                    $prevUrl = '/painel-externo/aula?id=' . $prevLessonId . '&course_id=' . $courseId;
                }
            }

            if ($currentIndex + 1 < $countLessons) {
                $nextLesson = $lessons[$currentIndex + 1];
                $nextLessonId = (int)($nextLesson['id'] ?? 0);
                if ($nextLessonId > 0) {
                    $nextUrl = '/painel-externo/aula?id=' . $nextLessonId . '&course_id=' . $courseId;
                }
            }
        }

        $this->view('external_dashboard/watch_lesson', [
            'pageTitle' => $lesson['title'] ?? 'Aula',
            'user' => $user,
            'branding' => $branding,
            'course' => $course,
            'lesson' => $lesson,
            'lessons' => $lessons,
            'lessonComments' => $lessonComments,
            'isEnrolled' => $isEnrolled,
            'completedLessonIds' => $completedLessonIds,
            'currentModuleId' => $currentModuleId,
            'hasModuleExam' => false,
            'canTakeModuleExam' => false,
            'showExamPrompt' => false,
            'prevUrl' => $prevUrl,
            'nextUrl' => $nextUrl,
            'nextIsExam' => false,
            'isLessonCompleted' => $isLessonCompleted,
            'canAccessContent' => true,
            'layout' => 'external_user_dashboard',
        ]);
    }

    public function completeLesson(): void
    {
        $user = $this->requireLogin();
        $partnerId = User::getExternalCoursePartnerId((int)$user['id']);

        $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
        $lessonId = isset($_POST['lesson_id']) ? (int)$_POST['lesson_id'] : 0;

        if ($courseId <= 0 || $lessonId <= 0) {
            header('Location: /painel-externo/cursos');
            exit;
        }

        $course = Course::findById($courseId);
        if (!$course || ($partnerId && (int)$course['owner_user_id'] !== $partnerId)) {
            header('Location: /painel-externo/cursos');
            exit;
        }

        $isEnrolled = CourseEnrollment::isEnrolled($courseId, (int)$user['id']);
        if ($isEnrolled) {
            CourseLessonProgress::markCompleted((int)$user['id'], $lessonId);
        }

        header('Location: /painel-externo/aula?id=' . $lessonId . '&course_id=' . $courseId);
        exit;
    }

    public function commentLesson(): void
    {
        $user = $this->requireLogin();
        $partnerId = User::getExternalCoursePartnerId((int)$user['id']);

        $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
        $lessonId = isset($_POST['lesson_id']) ? (int)$_POST['lesson_id'] : 0;
        $body = trim((string)($_POST['body'] ?? ''));

        if ($courseId <= 0 || $lessonId <= 0 || $body === '') {
            header('Location: /painel-externo/cursos');
            exit;
        }

        $course = Course::findById($courseId);
        if (!$course || ($partnerId && (int)$course['owner_user_id'] !== $partnerId)) {
            header('Location: /painel-externo/cursos');
            exit;
        }

        $isEnrolled = CourseEnrollment::isEnrolled($courseId, (int)$user['id']);
        if ($isEnrolled) {
            CourseLessonComment::create((int)$user['id'], $lessonId, $body);
        }

        header('Location: /painel-externo/aula?id=' . $lessonId . '&course_id=' . $courseId);
        exit;
    }

    public function viewCommunity(): void
    {
        $user = $this->requireLogin();
        $branding = $this->getBrandingForUser($user);

        $slug = isset($_GET['slug']) ? trim((string)$_GET['slug']) : '';
        if ($slug === '') {
            header('Location: /painel-externo/comunidade');
            exit;
        }

        $community = \App\Models\Community::findBySlug($slug);
        if (!$community || empty($community['is_active'])) {
            header('Location: /painel-externo/comunidade');
            exit;
        }

        $allowedCommunities = CourseAllowedCommunity::allowedCommunitiesByUser((int)$user['id']);
        $hasAccess = false;
        foreach ($allowedCommunities as $allowed) {
            if ((int)$allowed['id'] === (int)$community['id']) {
                $hasAccess = true;
                break;
            }
        }

        if (!$hasAccess) {
            header('Location: /painel-externo/comunidade');
            exit;
        }

        $communityId = (int)$community['id'];
        $isMember = \App\Models\CommunityMember::isMember($communityId, (int)$user['id']);

        // Auto-join user if they have access but aren't member yet
        if (!$isMember) {
            \App\Models\CommunityMember::join($communityId, (int)$user['id'], 'member');
            $isMember = true;
        }

        $topics = \App\Models\CommunityTopic::allByCommunity($communityId);

        $this->view('external_dashboard/view_community', [
            'pageTitle' => $community['name'] ?? 'Comunidade',
            'user' => $user,
            'branding' => $branding,
            'community' => $community,
            'isMember' => $isMember,
            'topics' => $topics,
            'layout' => 'external_user_dashboard',
        ]);
    }

    public function viewTopic(): void
    {
        $user = $this->requireLogin();
        $branding = $this->getBrandingForUser($user);

        $topicId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $slug = isset($_GET['slug']) ? trim((string)$_GET['slug']) : '';

        if ($topicId <= 0) {
            header('Location: /painel-externo/comunidade');
            exit;
        }

        $topic = \App\Models\CommunityTopic::findById($topicId);
        if (!$topic) {
            header('Location: /painel-externo/comunidade');
            exit;
        }

        $community = \App\Models\Community::findById((int)$topic['community_id']);
        if (!$community || empty($community['is_active'])) {
            header('Location: /painel-externo/comunidade');
            exit;
        }

        // Verify user has access to this community
        $allowedCommunities = CourseAllowedCommunity::allowedCommunitiesByUser((int)$user['id']);
        $hasAccess = false;
        foreach ($allowedCommunities as $allowed) {
            if ((int)$allowed['id'] === (int)$community['id']) {
                $hasAccess = true;
                break;
            }
        }

        if (!$hasAccess) {
            header('Location: /painel-externo/comunidade');
            exit;
        }

        $communityId = (int)$community['id'];
        $isMember = \App\Models\CommunityMember::isMember($communityId, (int)$user['id']);

        // Auto-join if not member
        if (!$isMember) {
            \App\Models\CommunityMember::join($communityId, (int)$user['id'], 'member');
            $isMember = true;
        }

        $posts = \App\Models\CommunityTopicPost::allByTopicWithUser($topicId);

        // Get like counts and user liked status
        $postIds = array_map(fn($p) => (int)$p['id'], $posts);
        $likesCount = \App\Models\CommunityPostLike::likesCountByPostIds($postIds);
        $likedByUser = \App\Models\CommunityPostLike::likedPostIdsByUser((int)$user['id'], $postIds);

        $this->view('external_dashboard/view_topic', [
            'pageTitle' => $topic['title'] ?? 'Tópico',
            'user' => $user,
            'branding' => $branding,
            'community' => $community,
            'topic' => $topic,
            'posts' => $posts,
            'isMember' => $isMember,
            'likesCount' => $likesCount,
            'likedByUser' => $likedByUser,
            'layout' => 'external_user_dashboard',
        ]);
    }

    public function replyTopic(): void
    {
        $user = $this->requireLogin();
        $partnerId = User::getExternalCoursePartnerId((int)$user['id']);

        $topicId = isset($_POST['topic_id']) ? (int)$_POST['topic_id'] : 0;
        $body = trim((string)($_POST['body'] ?? ''));

        if ($topicId <= 0 || $body === '') {
            header('Location: /painel-externo/comunidade');
            exit;
        }

        $topic = \App\Models\CommunityTopic::findById($topicId);
        if (!$topic) {
            header('Location: /painel-externo/comunidade');
            exit;
        }

        $community = \App\Models\Community::findById((int)$topic['community_id']);
        if (!$community) {
            header('Location: /painel-externo/comunidade');
            exit;
        }

        $communityId = (int)$community['id'];
        $isMember = \App\Models\CommunityMember::isMember($communityId, (int)$user['id']);

        if (!$isMember) {
            header('Location: /painel-externo/comunidade/topico?id=' . $topicId . '&slug=' . urlencode($community['slug'] ?? ''));
            exit;
        }

        $parentPostId = isset($_POST['parent_post_id']) ? (int)$_POST['parent_post_id'] : null;
        
        // Validate parent post if provided
        if ($parentPostId !== null && $parentPostId > 0) {
            $parentPost = \App\Models\CommunityTopicPost::findById($parentPostId);
            if (!$parentPost || (int)$parentPost['topic_id'] !== $topicId) {
                $parentPostId = null;
            }
        }

        $postId = \App\Models\CommunityTopicPost::create([
            'topic_id' => $topicId,
            'parent_post_id' => $parentPostId,
            'user_id' => (int)$user['id'],
            'body' => $body,
        ]);

        // Parse and store lesson mentions
        \App\Controllers\CommunitiesController::parseLessonMentionsStatic($body, $topicId, $postId, (int)$user['id']);
        
        // Parse and store user mentions
        \App\Controllers\CommunitiesController::parseUserMentionsStatic($body, $topicId, $postId, (int)$user['id']);

        header('Location: /painel-externo/comunidade/topico?id=' . $topicId . '&slug=' . urlencode($community['slug'] ?? ''));
        exit;
    }

    // ==================== SOCIAL FEATURES - PROFILE ====================
    
    public function editProfile(): void
    {
        $currentUser = $this->requireLogin();
        $branding = $this->getBrandingForUser($currentUser);
        $userId = (int)$currentUser['id'];

        $profile = UserSocialProfile::findByUserId($userId);
        if (!$profile) {
            $profile = [];
        }

        $success = $_SESSION['social_success'] ?? null;
        $error = $_SESSION['social_error'] ?? null;
        unset($_SESSION['social_success'], $_SESSION['social_error']);

        $this->view('external_dashboard/edit_profile', [
            'pageTitle' => 'Editar Perfil',
            'user' => $currentUser,
            'branding' => $branding,
            'profile' => $profile,
            'success' => $success,
            'error' => $error,
            'layout' => 'external_user_dashboard',
        ]);
    }

    public function showProfile(): void
    {
        $currentUser = $this->requireLogin();
        $branding = $this->getBrandingForUser($currentUser);
        $currentId = (int)$currentUser['id'];

        $targetId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $currentId;
        if ($targetId <= 0) {
            $targetId = $currentId;
        }

        $profileUser = User::findById($targetId);
        if (!$profileUser) {
            header('Location: /painel-externo/comunidade');
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
        $courseBadges = UserCourseBadge::allWithCoursesByUserId($targetId);

        $published = SocialPortfolioItem::publishedForUser($targetId, 1);
        $lastPublishedPortfolioItem = !empty($published) && is_array($published[0] ?? null) ? $published[0] : null;

        $isFavoriteFriend = false;
        if ($friendship && ($friendship['status'] ?? '') === 'accepted') {
            $pairUserId = (int)($friendship['user_id'] ?? 0);
            if ($pairUserId === $currentId) {
                $isFavoriteFriend = !empty($friendship['is_favorite_user1']);
            } else {
                $isFavoriteFriend = !empty($friendship['is_favorite_user2']);
            }
        }

        $success = $_SESSION['social_success'] ?? null;
        $error = $_SESSION['social_error'] ?? null;
        unset($_SESSION['social_success'], $_SESSION['social_error']);

        $displayName = $profileUser['preferred_name'] ?? $profileUser['name'] ?? '';
        $displayName = trim((string)$displayName);
        if ($displayName === '') {
            $displayName = 'Perfil';
        }

        $this->view('external_dashboard/profile', [
            'pageTitle' => 'Perfil - ' . $displayName,
            'user' => $currentUser,
            'branding' => $branding,
            'profileUser' => $profileUser,
            'profile' => $profile,
            'lastPublishedPortfolioItem' => $lastPublishedPortfolioItem,
            'scraps' => $scraps,
            'publicTestimonials' => $publicTestimonials,
            'pendingTestimonials' => $pendingTestimonials,
            'friends' => $friends,
            'communities' => $communities,
            'courseBadges' => $courseBadges,
            'friendship' => $friendship,
            'isFavoriteFriend' => $isFavoriteFriend,
            'success' => $success,
            'error' => $error,
            'layout' => 'external_user_dashboard',
        ]);
    }

    public function saveProfile(): void
    {
        $currentUser = $this->requireLogin();
        $userId = (int)$currentUser['id'];

        $aboutMe = trim((string)($_POST['about_me'] ?? ''));
        $interests = trim((string)($_POST['interests'] ?? ''));
        $website = trim((string)($_POST['website'] ?? ''));

        if ($website !== '' && !preg_match('/^https?:\/\//i', $website)) {
            $website = 'https://' . $website;
        }

        $existingProfile = UserSocialProfile::findByUserId($userId);
        $avatarPath = $existingProfile['avatar_path'] ?? null;

        if (!empty($_FILES['avatar_file']) && is_array($_FILES['avatar_file'])) {
            $uploadError = (int)($_FILES['avatar_file']['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($uploadError !== UPLOAD_ERR_NO_FILE && $uploadError === UPLOAD_ERR_OK) {
                $tmp = $_FILES['avatar_file']['tmp_name'] ?? '';
                $originalName = (string)($_FILES['avatar_file']['name'] ?? 'avatar');
                $type = (string)($_FILES['avatar_file']['type'] ?? '');
                $size = (int)($_FILES['avatar_file']['size'] ?? 0);

                if ($size > 0 && $size <= 2 * 1024 * 1024 && str_starts_with($type, 'image/')) {
                    $publicDir = __DIR__ . '/../../public/uploads/avatars';
                    if (!is_dir($publicDir)) {
                        @mkdir($publicDir, 0775, true);
                    }

                    $ext = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));
                    if ($ext === '') $ext = 'png';

                    $fileName = uniqid('avatar_', true) . '.' . $ext;
                    $targetPath = $publicDir . '/' . $fileName;

                    if (@move_uploaded_file($tmp, $targetPath)) {
                        $avatarPath = '/public/uploads/avatars/' . $fileName;
                    }
                }
            }
        }

        UserSocialProfile::upsertForUser($userId, [
            'about_me' => $aboutMe !== '' ? $aboutMe : null,
            'interests' => $interests !== '' ? $interests : null,
            'website' => $website !== '' ? $website : null,
            'avatar_path' => $avatarPath,
        ]);

        $_SESSION['social_success'] = 'Perfil atualizado com sucesso.';
        header('Location: /painel-externo/perfil');
        exit;
    }

    public function postScrap(): void
    {
        $currentUser = $this->requireLogin();
        $fromUserId = (int)$currentUser['id'];

        $toUserId = isset($_POST['to_user_id']) ? (int)$_POST['to_user_id'] : 0;
        $body = trim((string)($_POST['body'] ?? ''));

        if ($toUserId <= 0 || $body === '' || strlen($body) > 4000) {
            $_SESSION['social_error'] = 'Dados inválidos para o scrap.';
            header('Location: /painel-externo/perfil?user_id=' . $toUserId);
            exit;
        }

        UserScrap::create([
            'from_user_id' => $fromUserId,
            'to_user_id' => $toUserId,
            'body' => $body,
        ]);

        $_SESSION['social_success'] = 'Scrap enviado.';
        header('Location: /painel-externo/perfil?user_id=' . $toUserId);
        exit;
    }

    public function editScrap(): void
    {
        $currentUser = $this->requireLogin();
        $currentId = (int)$currentUser['id'];

        $scrapId = isset($_POST['scrap_id']) ? (int)$_POST['scrap_id'] : 0;
        $body = trim((string)($_POST['body'] ?? ''));

        $scrap = UserScrap::findById($scrapId);
        if (!$scrap || (int)($scrap['from_user_id'] ?? 0) !== $currentId || $body === '') {
            $_SESSION['social_error'] = 'Não foi possível editar o scrap.';
            header('Location: /painel-externo/perfil');
            exit;
        }

        UserScrap::updateBodyByAuthor($scrapId, $currentId, $body);
        $_SESSION['social_success'] = 'Scrap atualizado.';
        header('Location: /painel-externo/perfil?user_id=' . (int)($scrap['to_user_id'] ?? 0));
        exit;
    }

    public function deleteScrap(): void
    {
        $currentUser = $this->requireLogin();
        $currentId = (int)$currentUser['id'];

        $scrapId = isset($_POST['scrap_id']) ? (int)$_POST['scrap_id'] : 0;
        $scrap = UserScrap::findById($scrapId);
        
        if (!$scrap || (int)($scrap['from_user_id'] ?? 0) !== $currentId) {
            $_SESSION['social_error'] = 'Não foi possível excluir o scrap.';
            header('Location: /painel-externo/perfil');
            exit;
        }

        UserScrap::softDeleteByAuthor($scrapId, $currentId);
        $_SESSION['social_success'] = 'Scrap excluído.';
        header('Location: /painel-externo/perfil?user_id=' . (int)($scrap['to_user_id'] ?? 0));
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
        if (!$scrap || (int)($scrap['to_user_id'] ?? 0) !== $currentId) {
            $_SESSION['social_error'] = 'Operação não permitida.';
            header('Location: /painel-externo/perfil');
            exit;
        }

        UserScrap::setHiddenByProfileOwner($scrapId, $currentId, $hide);
        $_SESSION['social_success'] = $hide ? 'Scrap ocultado.' : 'Scrap visível novamente.';
        header('Location: /painel-externo/perfil');
        exit;
    }

    public function submitTestimonial(): void
    {
        $currentUser = $this->requireLogin();
        $fromUserId = (int)$currentUser['id'];

        $toUserId = isset($_POST['to_user_id']) ? (int)$_POST['to_user_id'] : 0;
        $body = trim((string)($_POST['body'] ?? ''));
        $isPublic = !empty($_POST['is_public']) ? 1 : 0;

        if ($toUserId <= 0 || $toUserId === $fromUserId || $body === '' || strlen($body) > 4000) {
            $_SESSION['social_error'] = 'Dados inválidos para o depoimento.';
            header('Location: /painel-externo/perfil?user_id=' . $toUserId);
            exit;
        }

        UserTestimonial::create([
            'from_user_id' => $fromUserId,
            'to_user_id' => $toUserId,
            'body' => $body,
            'is_public' => $isPublic,
            'status' => 'pending',
        ]);

        $_SESSION['social_success'] = 'Depoimento enviado para aprovação.';
        header('Location: /painel-externo/perfil?user_id=' . $toUserId);
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
            header('Location: /painel-externo/perfil');
            exit;
        }

        UserTestimonial::decide($testimonialId, $toUserId, $decision);
        $_SESSION['social_success'] = 'Decisão registrada.';
        header('Location: /painel-externo/perfil');
        exit;
    }

    // ==================== SOCIAL FEATURES - FRIENDS ====================

    private function wantsJson(): bool
    {
        $accept = (string)($_SERVER['HTTP_ACCEPT'] ?? '');
        if ($accept !== '' && stripos($accept, 'application/json') !== false) {
            return true;
        }
        $xrw = (string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
        return $xrw !== '' && strtolower($xrw) === 'xmlhttprequest';
    }

    public function friendsList(): void
    {
        $user = $this->requireLogin();
        $branding = $this->getBrandingForUser($user);
        $userId = (int)$user['id'];

        $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        $onlyFavorites = isset($_GET['fav']) && ($_GET['fav'] === '1' || strtolower($_GET['fav']) === 'true');

        $friends = UserFriend::friendsWithUsers($userId, $q, $onlyFavorites);
        $pending = UserFriend::pendingForUser($userId);

        $success = $_SESSION['friends_success'] ?? null;
        $error = $_SESSION['friends_error'] ?? null;
        unset($_SESSION['friends_success'], $_SESSION['friends_error']);

        $this->view('external_dashboard/friends', [
            'pageTitle' => 'Amigos',
            'user' => $user,
            'branding' => $branding,
            'friends' => $friends,
            'pending' => $pending,
            'success' => $success,
            'error' => $error,
            'q' => $q,
            'onlyFavorites' => $onlyFavorites,
            'layout' => 'external_user_dashboard',
        ]);
    }

    public function friendsAdd(): void
    {
        $user = $this->requireLogin();
        $branding = $this->getBrandingForUser($user);

        $this->view('external_dashboard/friends_add', [
            'pageTitle' => 'Adicionar Amigo',
            'user' => $user,
            'branding' => $branding,
            'layout' => 'external_user_dashboard',
        ]);
    }

    public function friendsSearch(): void
    {
        $user = $this->requireLogin();
        $userId = (int)$user['id'];

        $q = trim((string)($_GET['q'] ?? ''));
        $q = ltrim($q, '@');

        if ($q === '') {
            header('Content-Type: application/json');
            echo json_encode(['ok' => true, 'items' => []]);
            return;
        }

        $items = User::searchForFriend($q, $userId, 10);
        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'items' => $items]);
    }

    public function friendRequest(): void
    {
        $user = $this->requireLogin();
        $fromUserId = (int)$user['id'];

        $otherUserId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        if ($otherUserId <= 0 || $otherUserId === $fromUserId) {
            if ($this->wantsJson()) {
                header('Content-Type: application/json');
                echo json_encode(['ok' => false, 'error' => 'Usuário inválido']);
                return;
            }
            $_SESSION['friends_error'] = 'Usuário inválido.';
            header('Location: /painel-externo/amigos');
            exit;
        }

        UserFriend::request($fromUserId, $otherUserId);

        if ($this->wantsJson()) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => true]);
            return;
        }

        $_SESSION['friends_success'] = 'Pedido de amizade enviado.';
        header('Location: /painel-externo/perfil?user_id=' . $otherUserId);
        exit;
    }

    public function friendCancelRequest(): void
    {
        $user = $this->requireLogin();
        $fromUserId = (int)$user['id'];

        $otherUserId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        if ($otherUserId <= 0) {
            $_SESSION['friends_error'] = 'Usuário inválido.';
            header('Location: /painel-externo/amigos');
            exit;
        }

        UserFriend::cancelRequest($fromUserId, $otherUserId);
        $_SESSION['friends_success'] = 'Pedido cancelado.';
        header('Location: /painel-externo/perfil?user_id=' . $otherUserId);
        exit;
    }

    public function friendDecide(): void
    {
        $user = $this->requireLogin();
        $currentUserId = (int)$user['id'];

        $otherUserId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        $decision = (string)($_POST['decision'] ?? '');

        if ($otherUserId <= 0) {
            $_SESSION['friends_error'] = 'Pedido inválido.';
            header('Location: /painel-externo/amigos');
            exit;
        }

        UserFriend::decide($currentUserId, $otherUserId, $decision);
        $_SESSION['friends_success'] = 'Decisão registrada.';
        header('Location: /painel-externo/amigos');
        exit;
    }

    public function friendRemove(): void
    {
        $user = $this->requireLogin();
        $currentUserId = (int)$user['id'];

        $otherUserId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        if ($otherUserId <= 0) {
            $_SESSION['friends_error'] = 'Amigo inválido.';
            header('Location: /painel-externo/amigos');
            exit;
        }

        UserFriend::removeFriendship($currentUserId, $otherUserId);
        $_SESSION['friends_success'] = 'Amizade removida.';
        header('Location: /painel-externo/amigos');
        exit;
    }

    public function friendFavorite(): void
    {
        $user = $this->requireLogin();
        $currentUserId = (int)$user['id'];

        $otherUserId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        $isFavorite = !empty($_POST['is_favorite']);

        if ($otherUserId <= 0) {
            $_SESSION['friends_error'] = 'Amigo inválido.';
            header('Location: /painel-externo/amigos');
            exit;
        }

        UserFriend::setFavorite($currentUserId, $otherUserId, $isFavorite);

        if ($this->wantsJson()) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => true]);
            return;
        }

        header('Location: /painel-externo/perfil?user_id=' . $otherUserId);
        exit;
    }

    // ==================== SOCIAL FEATURES - CHAT ====================

    public function openChat(): void
    {
        $currentUser = $this->requireLogin();
        $branding = $this->getBrandingForUser($currentUser);
        $currentId = (int)$currentUser['id'];

        $otherUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
        if ($otherUserId <= 0 || $otherUserId === $currentId) {
            header('Location: /painel-externo/amigos');
            exit;
        }

        $otherUser = User::findById($otherUserId);
        if (!$otherUser) {
            header('Location: /painel-externo/amigos');
            exit;
        }

        $friendship = UserFriend::findFriendship($currentId, $otherUserId);
        if (!$friendship || ($friendship['status'] ?? '') !== 'accepted') {
            $_SESSION['friends_error'] = 'Você precisa ser amigo para conversar.';
            header('Location: /painel-externo/perfil?user_id=' . $otherUserId);
            exit;
        }

        $conversation = SocialConversation::findOrCreateForUsers($currentId, $otherUserId);
        $messages = SocialMessage::allForConversation((int)$conversation['id'], 50);

        $this->view('external_dashboard/chat', [
            'pageTitle' => 'Chat - ' . ($otherUser['name'] ?? 'Conversa'),
            'user' => $currentUser,
            'branding' => $branding,
            'otherUser' => $otherUser,
            'conversation' => $conversation,
            'messages' => $messages,
            'layout' => 'external_user_dashboard',
        ]);
    }

    public function sendMessage(): void
    {
        $currentUser = $this->requireLogin();
        $currentId = (int)$currentUser['id'];

        $conversationId = isset($_POST['conversation_id']) ? (int)$_POST['conversation_id'] : 0;
        $body = trim((string)($_POST['body'] ?? ''));

        if ($conversationId <= 0 || $body === '') {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => 'Dados inválidos']);
            exit;
        }

        $conversation = SocialConversation::findById($conversationId);
        if (!$conversation) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => 'Conversa não encontrada']);
            exit;
        }

        $user1 = (int)($conversation['user1_id'] ?? 0);
        $user2 = (int)($conversation['user2_id'] ?? 0);
        if ($currentId !== $user1 && $currentId !== $user2) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => 'Sem permissão']);
            exit;
        }

        $messageId = SocialMessage::create([
            'conversation_id' => $conversationId,
            'sender_user_id' => $currentId,
            'body' => $body,
        ]);

        SocialConversation::touchWithMessage($conversationId, $messageId);

        header('Content-Type: application/json');
        echo json_encode([
            'ok' => true,
            'message' => [
                'id' => $messageId,
                'body' => $body,
                'created_at' => date('Y-m-d H:i:s'),
            ]
        ]);
        exit;
    }

    public function chatStream(): void
    {
        $currentUser = $this->requireLogin();
        $currentId = (int)$currentUser['id'];

        $conversationId = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;
        $lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

        if ($conversationId <= 0) {
            http_response_code(400);
            exit;
        }

        $conversation = SocialConversation::findById($conversationId);
        if (!$conversation) {
            http_response_code(404);
            exit;
        }

        $user1 = (int)($conversation['user1_id'] ?? 0);
        $user2 = (int)($conversation['user2_id'] ?? 0);
        if ($currentId !== $user1 && $currentId !== $user2) {
            http_response_code(403);
            exit;
        }

        if (function_exists('session_write_close')) {
            @session_write_close();
        }

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');

        echo "event: ping\ndata: {}\n\n";
        @flush();

        $deadline = microtime(true) + 25.0;
        while (microtime(true) < $deadline) {
            $newMessages = SocialMessage::newSince($conversationId, $lastId);
            if (!empty($newMessages)) {
                echo "event: messages\n";
                echo "data: " . json_encode($newMessages) . "\n\n";
                @flush();
                break;
            }
            usleep(500000);
        }

        exit;
    }

    // ==================== SOCIAL FEATURES - WEBRTC ====================

    public function webrtcSend(): void
    {
        $currentUser = $this->requireLogin();
        $fromUserId = (int)$currentUser['id'];

        $toUserId = isset($_POST['to_user_id']) ? (int)$_POST['to_user_id'] : 0;
        $signal = (string)($_POST['signal'] ?? '');

        if ($toUserId <= 0 || $signal === '') {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false]);
            exit;
        }

        \App\Models\SocialWebRtcSignal::send($fromUserId, $toUserId, $signal);

        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
        exit;
    }

    public function webrtcPoll(): void
    {
        $currentUser = $this->requireLogin();
        $userId = (int)$currentUser['id'];

        $lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
        $signals = \App\Models\SocialWebRtcSignal::pollForUser($userId, $lastId);

        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'signals' => $signals]);
        exit;
    }

    // ==================== NOTIFICATIONS ====================

    public function notifications(): void
    {
        $currentUser = $this->requireLogin();
        $branding = $this->getBrandingForUser($currentUser);
        $userId = (int)$currentUser['id'];

        require_once __DIR__ . '/../Models/UserNotification.php';
        $notifications = \UserNotification::findByUserId($userId);

        $this->view('external_dashboard/notifications', [
            'pageTitle' => 'Notificações',
            'user' => $currentUser,
            'branding' => $branding,
            'notifications' => $notifications,
            'layout' => 'external_user_dashboard',
        ]);
    }

    public function markNotificationAsRead(): void
    {
        $currentUser = $this->requireLogin();
        $userId = (int)$currentUser['id'];
        $notificationId = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : 0;

        if ($notificationId <= 0) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false]);
            exit;
        }

        require_once __DIR__ . '/../Models/UserNotification.php';
        \UserNotification::markAsRead($notificationId);

        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
        exit;
    }

    public function markAllNotificationsAsRead(): void
    {
        $currentUser = $this->requireLogin();
        $userId = (int)$currentUser['id'];

        require_once __DIR__ . '/../Models/UserNotification.php';
        \UserNotification::markAllAsRead($userId);

        $_SESSION['social_success'] = 'Todas as notificações foram marcadas como lidas.';
        header('Location: /painel-externo/notificacoes');
        exit;
    }
}
