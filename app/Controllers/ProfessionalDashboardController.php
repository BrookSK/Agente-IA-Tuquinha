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
use App\Models\Setting;
use App\Services\MailService;

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

        $baseDomain = trim((string)Setting::get('partner_courses_base_domain', ''));
        if ($baseDomain === '') {
            $appPublicUrl = trim((string)Setting::get('app_public_url', ''));
            $host = $appPublicUrl !== '' ? (string)(parse_url($appPublicUrl, PHP_URL_HOST) ?? '') : '';
            $baseDomain = trim($host);
        }

        $this->view('professional_dashboard/settings', [
            'pageTitle' => 'Configurações',
            'user' => $user,
            'branding' => $branding,
            'baseDomain' => $baseDomain,
        ]);
    }

    public function checkSubdomain(): void
    {
        $user = $this->requireProfessional();

        header('Content-Type: application/json; charset=utf-8');

        $raw = isset($_GET['value']) ? (string)$_GET['value'] : '';
        $value = strtolower(trim($raw));

        $value = preg_replace('/\s+/', '', $value);
        $value = preg_replace('/[^a-z0-9\-]/', '', (string)$value);
        $value = trim((string)$value, '-');
        if ($value !== '') {
            $value = preg_replace('/\-+/', '-', $value);
        }

        $reserved = ['www', 'admin', 'api', 'app', 'mail', 'smtp', 'ftp', 'localhost'];
        $minLen = 3;
        $maxLen = 63;

        if ($value === '') {
            echo json_encode(['ok' => false, 'available' => false, 'value' => '', 'error' => 'Informe um subdomínio.']);
            return;
        }
        if (strlen($value) < $minLen) {
            echo json_encode(['ok' => false, 'available' => false, 'value' => $value, 'error' => 'Mínimo de ' . $minLen . ' caracteres.']);
            return;
        }
        if (strlen($value) > $maxLen) {
            echo json_encode(['ok' => false, 'available' => false, 'value' => $value, 'error' => 'Máximo de ' . $maxLen . ' caracteres.']);
            return;
        }
        if (in_array($value, $reserved, true)) {
            echo json_encode(['ok' => false, 'available' => false, 'value' => $value, 'error' => 'Este subdomínio é reservado.']);
            return;
        }
        if (preg_match('/^\-/', $value) || preg_match('/\-$/', $value)) {
            echo json_encode(['ok' => false, 'available' => false, 'value' => $value, 'error' => 'Não pode começar ou terminar com hífen.']);
            return;
        }
        if (preg_match('/\-\-/', $value)) {
            echo json_encode(['ok' => false, 'available' => false, 'value' => $value, 'error' => 'Não pode ter hífens repetidos.']);
            return;
        }

        $available = CoursePartnerBranding::isSubdomainAvailable($value, (int)$user['id']);
        echo json_encode(['ok' => true, 'available' => $available, 'value' => $value]);
    }

    public function saveBranding(): void
    {
        $user = $this->requireProfessional();

        $companyName = trim($_POST['company_name'] ?? '');
        $primaryColor = trim($_POST['primary_color'] ?? '');
        $secondaryColor = trim($_POST['secondary_color'] ?? '');
        $textColor = trim($_POST['text_color'] ?? '');
        $buttonTextColor = trim($_POST['button_text_color'] ?? '');

        $existing = CoursePartnerBranding::findByUserId((int)$user['id']);
        $logoUrl = $existing['logo_url'] ?? null;
        $faviconUrl = $existing['favicon_url'] ?? null;
        $headerImageUrl = $existing['header_image_url'] ?? null;
        $footerImageUrl = $existing['footer_image_url'] ?? null;
        $heroImageUrl = $existing['hero_image_url'] ?? null;
        $backgroundImageUrl = $existing['background_image_url'] ?? null;

        $existingSubdomain = strtolower(trim((string)($existing['subdomain'] ?? '')));
        $existingSubdomainStatus = (string)($existing['subdomain_status'] ?? 'none');

        $rawSubdomain = trim((string)($_POST['subdomain'] ?? ''));
        $subdomain = strtolower($rawSubdomain);
        $subdomain = preg_replace('/\s+/', '', $subdomain);
        $subdomain = preg_replace('/[^a-z0-9\-]/', '', (string)$subdomain);
        $subdomain = trim((string)$subdomain, '-');
        if ($subdomain !== '') {
            $subdomain = preg_replace('/\-+/', '-', $subdomain);
        }

        if ($subdomain === '' && $existingSubdomain !== '') {
            $subdomain = $existingSubdomain;
        }

        $subdomainChanged = ($subdomain !== '' && $subdomain !== $existingSubdomain);
        $subdomainRequestedAt = null;
        $subdomainStatus = $existingSubdomainStatus;
        $subdomainApprovedAt = $existing['subdomain_approved_at'] ?? null;
        if ($subdomainChanged) {
            $subdomainStatus = 'pending';
            $subdomainRequestedAt = date('Y-m-d H:i:s');
            $subdomainApprovedAt = null;
        }

        if ($subdomainChanged) {
            $reserved = ['www', 'admin', 'api', 'app', 'mail', 'smtp', 'ftp', 'localhost'];
            if (strlen($subdomain) < 3 || strlen($subdomain) > 63) {
                $_SESSION['professional_success'] = null;
                $_SESSION['professional_error'] = 'Subdomínio inválido.';
                header('Location: /profissional/configuracoes');
                exit;
            }
            if (in_array($subdomain, $reserved, true) || preg_match('/\-\-/', $subdomain)) {
                $_SESSION['professional_success'] = null;
                $_SESSION['professional_error'] = 'Subdomínio inválido.';
                header('Location: /profissional/configuracoes');
                exit;
            }
            if (!CoursePartnerBranding::isSubdomainAvailable($subdomain, (int)$user['id'])) {
                $_SESSION['professional_success'] = null;
                $_SESSION['professional_error'] = 'Este subdomínio já está em uso.';
                header('Location: /profissional/configuracoes');
                exit;
            }
        }

        // Handle logo upload
        $removeLogo = !empty($_POST['remove_logo']);
        if ($removeLogo) {
            $logoUrl = null;
        }

        $removeFavicon = !empty($_POST['remove_favicon']);
        if ($removeFavicon) {
            $faviconUrl = null;
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

        if (!$removeFavicon && !empty($_FILES['favicon_upload']['tmp_name'])) {
            $err = $_FILES['favicon_upload']['error'] ?? UPLOAD_ERR_NO_FILE;
            if ($err === UPLOAD_ERR_OK) {
                $tmp = (string)($_FILES['favicon_upload']['tmp_name'] ?? '');
                $name = (string)($_FILES['favicon_upload']['name'] ?? '');
                $mime = (string)($_FILES['favicon_upload']['type'] ?? '');
                if ($tmp !== '' && is_file($tmp)) {
                    $remoteUrl = \App\Services\MediaStorageService::uploadFile($tmp, $name, $mime);
                    if ($remoteUrl !== null) {
                        $faviconUrl = $remoteUrl;
                    }
                }
            }
        }

        // Handle header image upload
        if (!empty($_FILES['header_image_upload']['tmp_name'])) {
            $err = $_FILES['header_image_upload']['error'] ?? UPLOAD_ERR_NO_FILE;
            if ($err === UPLOAD_ERR_OK) {
                $tmp = (string)($_FILES['header_image_upload']['tmp_name'] ?? '');
                $name = (string)($_FILES['header_image_upload']['name'] ?? '');
                $mime = (string)($_FILES['header_image_upload']['type'] ?? '');
                if ($tmp !== '' && is_file($tmp)) {
                    $remoteUrl = \App\Services\MediaStorageService::uploadFile($tmp, $name, $mime);
                    if ($remoteUrl !== null) {
                        $headerImageUrl = $remoteUrl;
                    }
                }
            }
        }

        // Handle hero image upload
        if (!empty($_FILES['hero_image_upload']['tmp_name'])) {
            $err = $_FILES['hero_image_upload']['error'] ?? UPLOAD_ERR_NO_FILE;
            if ($err === UPLOAD_ERR_OK) {
                $tmp = (string)($_FILES['hero_image_upload']['tmp_name'] ?? '');
                $name = (string)($_FILES['hero_image_upload']['name'] ?? '');
                $mime = (string)($_FILES['hero_image_upload']['type'] ?? '');
                if ($tmp !== '' && is_file($tmp)) {
                    $remoteUrl = \App\Services\MediaStorageService::uploadFile($tmp, $name, $mime);
                    if ($remoteUrl !== null) {
                        $heroImageUrl = $remoteUrl;
                    }
                }
            }
        }

        // Handle footer image upload
        if (!empty($_FILES['footer_image_upload']['tmp_name'])) {
            $err = $_FILES['footer_image_upload']['error'] ?? UPLOAD_ERR_NO_FILE;
            if ($err === UPLOAD_ERR_OK) {
                $tmp = (string)($_FILES['footer_image_upload']['tmp_name'] ?? '');
                $name = (string)($_FILES['footer_image_upload']['name'] ?? '');
                $mime = (string)($_FILES['footer_image_upload']['type'] ?? '');
                if ($tmp !== '' && is_file($tmp)) {
                    $remoteUrl = \App\Services\MediaStorageService::uploadFile($tmp, $name, $mime);
                    if ($remoteUrl !== null) {
                        $footerImageUrl = $remoteUrl;
                    }
                }
            }
        }

        // Handle background image upload
        if (!empty($_FILES['background_image_upload']['tmp_name'])) {
            $err = $_FILES['background_image_upload']['error'] ?? UPLOAD_ERR_NO_FILE;
            if ($err === UPLOAD_ERR_OK) {
                $tmp = (string)($_FILES['background_image_upload']['tmp_name'] ?? '');
                $name = (string)($_FILES['background_image_upload']['name'] ?? '');
                $mime = (string)($_FILES['background_image_upload']['type'] ?? '');
                if ($tmp !== '' && is_file($tmp)) {
                    $remoteUrl = \App\Services\MediaStorageService::uploadFile($tmp, $name, $mime);
                    if ($remoteUrl !== null) {
                        $backgroundImageUrl = $remoteUrl;
                    }
                }
            }
        }

        CoursePartnerBranding::upsert((int)$user['id'], [
            'subdomain' => $subdomain !== '' ? $subdomain : null,
            'subdomain_status' => $subdomainStatus,
            'subdomain_requested_at' => $subdomainRequestedAt,
            'subdomain_approved_at' => $subdomainApprovedAt,
            'company_name' => $companyName,
            'logo_url' => $logoUrl,
            'favicon_url' => $faviconUrl,
            'primary_color' => $primaryColor,
            'secondary_color' => $secondaryColor,
            'text_color' => $textColor,
            'button_text_color' => $buttonTextColor,
            'header_image_url' => $headerImageUrl,
            'footer_image_url' => $footerImageUrl,
            'hero_image_url' => $heroImageUrl,
            'background_image_url' => $backgroundImageUrl,
        ]);

        if ($subdomainChanged) {
            $adminEmail = trim((string)Setting::get('admin_error_notification_email', ''));
            if ($adminEmail !== '') {
                $baseDomain = trim((string)Setting::get('partner_courses_base_domain', ''));
                if ($baseDomain === '') {
                    $appPublicUrl = trim((string)Setting::get('app_public_url', ''));
                    $host = $appPublicUrl !== '' ? (string)(parse_url($appPublicUrl, PHP_URL_HOST) ?? '') : '';
                    $baseDomain = trim($host);
                }
                $fullHost = $baseDomain !== '' ? ($subdomain . '.' . $baseDomain) : $subdomain;
                $subject = 'Novo subdomínio pendente: ' . $subdomain;
                $content = '<p style="font-size:14px; margin:0 0 10px 0;">Um parceiro solicitou um novo subdomínio.</p>'
                    . '<p style="font-size:14px; margin:0 0 10px 0;"><strong>Parceiro:</strong> ' . htmlspecialchars((string)($user['name'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ' (' . htmlspecialchars((string)($user['email'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ')</p>'
                    . '<p style="font-size:14px; margin:0 0 10px 0;"><strong>Subdomínio:</strong> ' . htmlspecialchars($fullHost, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>'
                    . '<p style="font-size:13px; margin:0; color:#b0b0b0;">Após configurar o DNS (Cloudflare), aprove em /admin/branding-parceiros.</p>';
                $body = MailService::buildDefaultTemplate('Admin', $content, null, null, null);
                MailService::send($adminEmail, 'Admin', $subject, $body);
            }
        }

        $_SESSION['professional_success'] = 'Branding atualizado com sucesso.';
        header('Location: /profissional/configuracoes');
        exit;
    }
}
