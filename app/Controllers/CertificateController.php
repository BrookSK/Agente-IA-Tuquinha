<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Course;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserCourseBadge;

class CertificateController extends Controller
{
    private function getCurrentUser(): ?array
    {
        if (empty($_SESSION['user_id'])) {
            return null;
        }
        $user = User::findById((int)$_SESSION['user_id']);
        if (!$user) {
            unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email']);
            return null;
        }
        return $user;
    }

    private function requireLogin(): array
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            header('Location: /login');
            exit;
        }
        return $user;
    }

    public function myCompletedCourses(): void
    {
        $user = $this->requireLogin();
        $items = UserCourseBadge::allWithCoursesByUserId((int)$user['id']);

        $this->view('certificates/index', [
            'pageTitle' => 'Cursos concluídos',
            'user' => $user,
            'items' => $items,
        ]);
    }

    public function show(): void
    {
        $user = $this->requireLogin();
        $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
        if ($courseId <= 0) {
            header('Location: /certificados');
            exit;
        }

        $badge = UserCourseBadge::findByUserAndCourse((int)$user['id'], $courseId);
        if (!$badge) {
            header('Location: /certificados');
            exit;
        }

        $course = Course::findById($courseId);
        if (!$course) {
            header('Location: /certificados');
            exit;
        }

        $issuerName = Setting::get('certificate_issuer_name', 'Thiago Marques') ?: 'Thiago Marques';
        $issuerSignatureImage = Setting::get('certificate_signature_image_path', '') ?: '';

        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $verifyUrl = $scheme . $host . '/certificados/verificar?code=' . urlencode((string)($badge['certificate_code'] ?? ''));

        $this->view('certificates/show', [
            'pageTitle' => 'Certificado - ' . (string)($course['title'] ?? ''),
            'user' => $user,
            'course' => $course,
            'badge' => $badge,
            'issuerName' => $issuerName,
            'issuerSignatureImage' => $issuerSignatureImage,
            'verifyUrl' => $verifyUrl,
        ]);
    }

    public function verify(): void
    {
        $code = isset($_GET['code']) ? trim((string)$_GET['code']) : '';
        if ($code === '') {
            header('Location: /');
            exit;
        }

        $badge = UserCourseBadge::findByCertificateCode($code);
        if (!$badge) {
            $this->view('certificates/verify', [
                'pageTitle' => 'Verificação de certificado',
                'badge' => null,
                'course' => null,
                'student' => null,
                'issuerName' => Setting::get('certificate_issuer_name', 'Thiago Marques') ?: 'Thiago Marques',
            ]);
            return;
        }

        $courseId = (int)($badge['course_id'] ?? 0);
        $userId = (int)($badge['user_id'] ?? 0);
        $course = $courseId > 0 ? Course::findById($courseId) : null;
        $student = $userId > 0 ? User::findById($userId) : null;

        $this->view('certificates/verify', [
            'pageTitle' => 'Verificação de certificado',
            'badge' => $badge,
            'course' => $course,
            'student' => $student,
            'issuerName' => Setting::get('certificate_issuer_name', 'Thiago Marques') ?: 'Thiago Marques',
        ]);
    }
}
