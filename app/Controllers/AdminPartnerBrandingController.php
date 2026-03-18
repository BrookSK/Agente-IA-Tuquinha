<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CoursePartner;
use App\Models\CoursePartnerBranding;
use App\Services\MediaStorageService;

class AdminPartnerBrandingController extends Controller
{
    private function ensureAdmin(): void
    {
        if (empty($_SESSION['is_admin'])) {
            header('Location: /admin/login');
            exit;
        }
    }

    public function index(): void
    {
        $this->ensureAdmin();

        $partners = CoursePartner::allWithUser();

        $this->view('admin/partner_branding/index', [
            'pageTitle' => 'Branding de parceiros',
            'partners' => $partners,
        ]);
    }

    public function form(): void
    {
        $this->ensureAdmin();

        $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
        if ($userId <= 0) {
            header('Location: /admin/branding-parceiros');
            exit;
        }

        $partners = CoursePartner::allWithUser();
        $partner = null;
        foreach ($partners as $p) {
            if ((int)($p['user_id'] ?? 0) === $userId) {
                $partner = $p;
                break;
            }
        }

        if (!$partner) {
            header('Location: /admin/branding-parceiros');
            exit;
        }

        $branding = CoursePartnerBranding::findByUserId($userId);

        $this->view('admin/partner_branding/form', [
            'pageTitle' => 'Branding: ' . (string)($partner['user_name'] ?? ''),
            'partner' => $partner,
            'branding' => $branding,
        ]);
    }

    public function save(): void
    {
        $this->ensureAdmin();

        $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        if ($userId <= 0) {
            $_SESSION['admin_partner_branding_error'] = 'Parceiro inválido.';
            header('Location: /admin/branding-parceiros');
            exit;
        }

        $companyName = trim((string)($_POST['company_name'] ?? ''));
        $primary = trim((string)($_POST['primary_color'] ?? ''));
        $secondary = trim((string)($_POST['secondary_color'] ?? ''));
        $textColor = trim((string)($_POST['text_color'] ?? ''));
        $buttonTextColor = trim((string)($_POST['button_text_color'] ?? ''));
        $linkColor = trim((string)($_POST['link_color'] ?? ''));

        $existing = CoursePartnerBranding::findByUserId($userId);
        $logoUrl = $existing['logo_url'] ?? null;
        $headerImageUrl = $existing['header_image_url'] ?? null;
        $footerImageUrl = $existing['footer_image_url'] ?? null;
        $heroImageUrl = $existing['hero_image_url'] ?? null;
        $backgroundImageUrl = $existing['background_image_url'] ?? null;

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
                    $remoteUrl = MediaStorageService::uploadFile($tmp, $name, $mime);
                    if ($remoteUrl !== null) {
                        $logoUrl = $remoteUrl;
                    }
                }
            }
        }

        if (!empty($_FILES['header_image_upload']['tmp_name'])) {
            $err = $_FILES['header_image_upload']['error'] ?? UPLOAD_ERR_NO_FILE;
            if ($err === UPLOAD_ERR_OK) {
                $tmp = (string)($_FILES['header_image_upload']['tmp_name'] ?? '');
                $name = (string)($_FILES['header_image_upload']['name'] ?? '');
                $mime = (string)($_FILES['header_image_upload']['type'] ?? '');
                if ($tmp !== '' && is_file($tmp)) {
                    $remoteUrl = MediaStorageService::uploadFile($tmp, $name, $mime);
                    if ($remoteUrl !== null) {
                        $headerImageUrl = $remoteUrl;
                    }
                }
            }
        }

        if (!empty($_FILES['footer_image_upload']['tmp_name'])) {
            $err = $_FILES['footer_image_upload']['error'] ?? UPLOAD_ERR_NO_FILE;
            if ($err === UPLOAD_ERR_OK) {
                $tmp = (string)($_FILES['footer_image_upload']['tmp_name'] ?? '');
                $name = (string)($_FILES['footer_image_upload']['name'] ?? '');
                $mime = (string)($_FILES['footer_image_upload']['type'] ?? '');
                if ($tmp !== '' && is_file($tmp)) {
                    $remoteUrl = MediaStorageService::uploadFile($tmp, $name, $mime);
                    if ($remoteUrl !== null) {
                        $footerImageUrl = $remoteUrl;
                    }
                }
            }
        }

        if (!empty($_FILES['hero_image_upload']['tmp_name'])) {
            $err = $_FILES['hero_image_upload']['error'] ?? UPLOAD_ERR_NO_FILE;
            if ($err === UPLOAD_ERR_OK) {
                $tmp = (string)($_FILES['hero_image_upload']['tmp_name'] ?? '');
                $name = (string)($_FILES['hero_image_upload']['name'] ?? '');
                $mime = (string)($_FILES['hero_image_upload']['type'] ?? '');
                if ($tmp !== '' && is_file($tmp)) {
                    $remoteUrl = MediaStorageService::uploadFile($tmp, $name, $mime);
                    if ($remoteUrl !== null) {
                        $heroImageUrl = $remoteUrl;
                    }
                }
            }
        }

        if (!empty($_FILES['background_image_upload']['tmp_name'])) {
            $err = $_FILES['background_image_upload']['error'] ?? UPLOAD_ERR_NO_FILE;
            if ($err === UPLOAD_ERR_OK) {
                $tmp = (string)($_FILES['background_image_upload']['tmp_name'] ?? '');
                $name = (string)($_FILES['background_image_upload']['name'] ?? '');
                $mime = (string)($_FILES['background_image_upload']['type'] ?? '');
                if ($tmp !== '' && is_file($tmp)) {
                    $remoteUrl = MediaStorageService::uploadFile($tmp, $name, $mime);
                    if ($remoteUrl !== null) {
                        $backgroundImageUrl = $remoteUrl;
                    }
                }
            }
        }

        CoursePartnerBranding::upsert($userId, [
            'company_name' => $companyName,
            'logo_url' => $logoUrl,
            'primary_color' => $primary,
            'secondary_color' => $secondary,
            'text_color' => $textColor,
            'button_text_color' => $buttonTextColor,
            'link_color' => $linkColor,
            'header_image_url' => $headerImageUrl,
            'footer_image_url' => $footerImageUrl,
            'hero_image_url' => $heroImageUrl,
            'background_image_url' => $backgroundImageUrl,
        ]);

        $_SESSION['admin_partner_branding_success'] = 'Branding atualizado.';
        header('Location: /admin/branding-parceiros/editar?user_id=' . $userId);
        exit;
    }
}
