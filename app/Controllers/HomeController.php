<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CourseEnrollment;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;

class HomeController extends Controller
{
    public function index(): void
    {
        $tuquinhaAboutVideoUrl = Setting::get('tuquinha_about_video_url', '') ?? '';

        $isLogged = !empty($_SESSION['user_id']);
        $user = null;
        if ($isLogged) {
            $user = User::findById((int)$_SESSION['user_id']);
            if (!$user) {
                unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email']);
                $isLogged = false;
            }
        }

        $currentPlan = null;
        $hasPaidActiveSubscription = false;
        if (!empty($_SESSION['is_admin'])) {
            $currentPlan = Plan::findTopActive();
            if ($currentPlan && !empty($currentPlan['slug'])) {
                $_SESSION['plan_slug'] = $currentPlan['slug'];
            }
            if ($currentPlan) {
                $slug = (string)($currentPlan['slug'] ?? '');
                if ($slug !== '' && $slug !== 'free') {
                    $hasPaidActiveSubscription = true;
                }
            }
        } elseif ($isLogged && $user && !empty($user['email'])) {
            $subscription = Subscription::findLastByEmail((string)$user['email']);
            if ($subscription && !empty($subscription['plan_id'])) {
                $status = strtolower((string)($subscription['status'] ?? ''));
                $planFromSub = Plan::findById((int)$subscription['plan_id']);
                if ($planFromSub) {
                    $currentPlan = $planFromSub;
                    if (!empty($currentPlan['slug'])) {
                        $_SESSION['plan_slug'] = $currentPlan['slug'];
                    }
                    $slug = (string)($planFromSub['slug'] ?? '');
                    if ($slug !== 'free' && !in_array($status, ['canceled', 'expired'], true)) {
                        $hasPaidActiveSubscription = true;
                    }
                }
            }
        }

        if (!$currentPlan) {
            $currentPlan = Plan::findBySessionSlug($_SESSION['plan_slug'] ?? null);
            if (!$currentPlan) {
                $currentPlan = Plan::findBySlug('free');
                if ($currentPlan && !empty($currentPlan['slug'])) {
                    $_SESSION['plan_slug'] = $currentPlan['slug'];
                }
            }
        }

        $planAllowsCourses = !empty($currentPlan['allow_courses']);
        $planAllowsProjects = !empty($currentPlan['allow_projects_access']);

        $hasCourseEnrollment = false;
        if ($isLogged && $user && !empty($user['id'])) {
            try {
                $hasCourseEnrollment = !empty(CourseEnrollment::allByUser((int)$user['id']));
            } catch (\Throwable $e) {
                $hasCourseEnrollment = false;
            }
        }

        $this->view('home/index', [
            'pageTitle' => 'Resenha 2.0',
            'tuquinhaAboutVideoUrl' => $tuquinhaAboutVideoUrl,
            'isLogged' => $isLogged,
            'currentPlan' => $currentPlan,
            'hasPaidActiveSubscription' => $hasPaidActiveSubscription,
            'planAllowsCourses' => $planAllowsCourses,
            'planAllowsProjects' => $planAllowsProjects,
            'hasCourseEnrollment' => $hasCourseEnrollment,
        ]);
    }
}
