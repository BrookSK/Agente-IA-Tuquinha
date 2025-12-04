<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CoursePartner;
use App\Models\CoursePartnerCommission;
use App\Models\User;

class CoursePartnerDashboardController extends Controller
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

        $partner = CoursePartner::findByUserId((int)$user['id']);
        $partnerId = $partner['id'] ?? null;

        $courses = Course::allByOwner((int)$user['id']);

        $rows = [];
        foreach ($courses as $course) {
            $courseId = (int)($course['id'] ?? 0);
            if ($courseId <= 0) {
                continue;
            }

            $percent = null;
            if ($partnerId) {
                $commission = CoursePartnerCommission::findByPartnerAndCourse((int)$partnerId, $courseId);
                if ($commission && isset($commission['commission_percent'])) {
                    $percent = (float)$commission['commission_percent'];
                } elseif ($partner && isset($partner['default_commission_percent'])) {
                    $percent = (float)$partner['default_commission_percent'];
                }
            }

            $enrollments = CourseEnrollment::allByCourse($courseId);

            $rows[] = [
                'course' => $course,
                'commission_percent' => $percent,
                'enrollment_count' => count($enrollments),
            ];
        }

        $this->view('partner/courses', [
            'pageTitle' => 'Meus cursos como parceiro',
            'user' => $user,
            'partner' => $partner,
            'rows' => $rows,
        ]);
    }
}
