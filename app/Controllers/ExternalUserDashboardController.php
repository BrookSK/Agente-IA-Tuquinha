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

        $this->view('external_dashboard/index', [
            'pageTitle' => 'Meu Painel',
            'user' => $user,
            'branding' => $branding,
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
            foreach ($allModules as $module) {
                $lessons = CourseLesson::allByModule((int)$module['id']);
                $lessonsWithProgress = [];
                foreach ($lessons as $lesson) {
                    $lesson['is_completed'] = CourseLessonProgress::isCompleted((int)$user['id'], (int)$lesson['id']);
                    $lessonsWithProgress[] = $lesson;
                }
                $module['lessons'] = $lessonsWithProgress;
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
}
