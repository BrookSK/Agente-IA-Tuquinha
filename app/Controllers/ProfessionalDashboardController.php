<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\Course;
use App\Models\CoursePartner;
use App\Models\CourseEnrollment;
use App\Models\CoursePurchase;
use App\Models\CoursePartnerBranding;
use App\Models\ProfessionalMetrics;
use App\Models\Community;

class ProfessionalDashboardController extends Controller
{
    private function requireProfessional(): array
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

        $partner = CoursePartner::findByUserId((int)$user['id']);
        if (!$partner) {
            header('Location: /');
            exit;
        }

        return $user;
    }

    public function index(): void
    {
        $user = $this->requireProfessional();
        
        ProfessionalMetrics::updateMetrics((int)$user['id']);
        $metrics = ProfessionalMetrics::getOrCreate((int)$user['id']);

        $this->view('professional_dashboard/index', [
            'pageTitle' => 'Painel do Profissional',
            'user' => $user,
            'metrics' => $metrics,
        ]);
    }

    public function courses(): void
    {
        $user = $this->requireProfessional();
        $courses = Course::allByOwner((int)$user['id']);

        $this->view('professional_dashboard/courses', [
            'pageTitle' => 'Meus Cursos',
            'user' => $user,
            'courses' => $courses,
        ]);
    }

    public function students(): void
    {
        $user = $this->requireProfessional();
        
        $pdo = \App\Core\Database::getConnection();
        $stmt = $pdo->prepare('SELECT DISTINCT u.*, ce.created_at AS enrolled_at, c.title AS course_title
            FROM users u
            JOIN course_enrollments ce ON ce.user_id = u.id
            JOIN courses c ON c.id = ce.course_id
            WHERE c.owner_user_id = :owner_id
            ORDER BY ce.created_at DESC');
        $stmt->execute(['owner_id' => (int)$user['id']]);
        $students = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $this->view('professional_dashboard/students', [
            'pageTitle' => 'Alunos',
            'user' => $user,
            'students' => $students,
        ]);
    }

    public function sales(): void
    {
        $user = $this->requireProfessional();
        
        $pdo = \App\Core\Database::getConnection();
        $stmt = $pdo->prepare('SELECT cp.*, c.title AS course_title, u.name AS student_name, u.email AS student_email
            FROM course_purchases cp
            JOIN courses c ON c.id = cp.course_id
            JOIN users u ON u.id = cp.user_id
            WHERE c.owner_user_id = :owner_id
            ORDER BY cp.created_at DESC
            LIMIT 100');
        $stmt->execute(['owner_id' => (int)$user['id']]);
        $sales = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $this->view('professional_dashboard/sales', [
            'pageTitle' => 'Vendas',
            'user' => $user,
            'sales' => $sales,
        ]);
    }

    public function communities(): void
    {
        $user = $this->requireProfessional();
        $communities = Community::allActiveWithUserFilter((int)$user['id'], null, null, 'owner');

        $this->view('professional_dashboard/communities', [
            'pageTitle' => 'Comunidades',
            'user' => $user,
            'communities' => $communities,
        ]);
    }

    public function settings(): void
    {
        $user = $this->requireProfessional();
        $branding = CoursePartnerBranding::findByUserId((int)$user['id']);

        $this->view('professional_dashboard/settings', [
            'pageTitle' => 'Configurações',
            'user' => $user,
            'branding' => $branding,
        ]);
    }

    public function saveBranding(): void
    {
        $user = $this->requireProfessional();

        $companyName = trim($_POST['company_name'] ?? '');
        $primaryColor = trim($_POST['primary_color'] ?? '');
        $secondaryColor = trim($_POST['secondary_color'] ?? '');

        $existing = CoursePartnerBranding::findByUserId((int)$user['id']);
        $logoUrl = $existing['logo_url'] ?? null;

        $removeLogo = !empty($_POST['remove_logo']);
        if ($removeLogo) {
            $logoUrl = null;
        }

        if (!$removeLogo && !empty($_FILES['logo_upload']['tmp_name'])) {
            $err = $_FILES['logo_upload']['error'] ?? UPLOAD_ERR_NO_FILE;
            if ($err === UPLOAD_ERR_OK) {
                $tmp = (string)($_FILES['logo_upload']['tmp_name'] ?? '');
                $name = (string)($_FILES['logo_upload']['name'] ?? '');
                $mime = (string)($_FILES['logo_upload']['type'] ?? '');
                if ($tmp !== '' && is_file($tmp)) {
                    $remoteUrl = \App\Services\MediaStorageService::uploadFile($tmp, $name, $mime);
                    if ($remoteUrl !== null) {
                        $logoUrl = $remoteUrl;
                    }
                }
            }
        }

        CoursePartnerBranding::upsert((int)$user['id'], [
            'company_name' => $companyName,
            'logo_url' => $logoUrl,
            'primary_color' => $primaryColor,
            'secondary_color' => $secondaryColor,
        ]);

        $_SESSION['professional_success'] = 'Branding atualizado com sucesso.';
        header('Location: /profissional/configuracoes');
        exit;
    }
}
