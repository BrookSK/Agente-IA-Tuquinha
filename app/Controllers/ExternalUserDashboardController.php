<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CoursePartnerBranding;
use App\Models\CourseAllowedCommunity;

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
            $allPartnerCourses = Course::allByOwner($partnerId);
            foreach ($allPartnerCourses as $course) {
                if (!empty($course['is_active'])) {
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

        $this->view('external_dashboard/view_course', [
            'pageTitle' => $course['title'] ?? 'Curso',
            'user' => $user,
            'branding' => $branding,
            'course' => $course,
            'hasAccess' => $hasAccess,
            'layout' => 'external_user_dashboard',
        ]);
    }
}
