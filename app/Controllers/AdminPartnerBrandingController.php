<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CoursePartner;
use App\Models\CoursePartnerBranding;
use App\Models\Setting;
use App\Models\User;
use App\Services\MediaStorageService;
use App\Services\MailService;

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

        $baseDomain = trim((string)Setting::get('partner_courses_base_domain', ''));
        if ($baseDomain === '') {
            $appPublicUrl = trim((string)Setting::get('app_public_url', ''));
            $host = $appPublicUrl !== '' ? (string)(parse_url($appPublicUrl, PHP_URL_HOST) ?? '') : '';
            $baseDomain = trim($host);
        }

        $this->view('admin/partner_branding/form', [
            'pageTitle' => 'Branding: ' . (string)($partner['user_name'] ?? ''),
            'partner' => $partner,
            'branding' => $branding,
            'baseDomain' => $baseDomain,
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
        $paragraphColor = trim((string)($_POST['paragraph_color'] ?? ''));

        $existing = CoursePartnerBranding::findByUserId($userId);
        $logoUrl = $existing['logo_url'] ?? null;
        $faviconUrl = $existing['favicon_url'] ?? null;
        $headerImageUrl = $existing['header_image_url'] ?? null;
        $footerImageUrl = $existing['footer_image_url'] ?? null;
        $heroImageUrl = $existing['hero_image_url'] ?? null;
        $backgroundImageUrl = $existing['background_image_url'] ?? null;

        $removeLogo = !empty($_POST['remove_logo']);
        if ($removeLogo) {
            $logoUrl = null;
        }

        $removeFavicon = !empty($_POST['remove_favicon']);
        if ($removeFavicon) {
            $faviconUrl = null;
        }

        $removeHeaderImage = !empty($_POST['remove_header_image']);
        if ($removeHeaderImage) {
            $headerImageUrl = null;
        }

        $removeHeroImage = !empty($_POST['remove_hero_image']);
        if ($removeHeroImage) {
            $heroImageUrl = null;
        }

        $removeFooterImage = !empty($_POST['remove_footer_image']);
        if ($removeFooterImage) {
            $footerImageUrl = null;
        }

        $removeBackgroundImage = !empty($_POST['remove_background_image']);
        if ($removeBackgroundImage) {
            $backgroundImageUrl = null;
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

        if (!$removeFavicon && !empty($_FILES['favicon_upload']['tmp_name'])) {
            $err = $_FILES['favicon_upload']['error'] ?? UPLOAD_ERR_NO_FILE;
            if ($err === UPLOAD_ERR_OK) {
                $tmp = (string)($_FILES['favicon_upload']['tmp_name'] ?? '');
                $name = (string)($_FILES['favicon_upload']['name'] ?? '');
                $mime = (string)($_FILES['favicon_upload']['type'] ?? '');
                if ($tmp !== '' && is_file($tmp)) {
                    $remoteUrl = MediaStorageService::uploadFile($tmp, $name, $mime);
                    if ($remoteUrl !== null) {
                        $faviconUrl = $remoteUrl;
                    }
                }
            }
        }

        if (!$removeHeaderImage && !empty($_FILES['header_image_upload']['tmp_name'])) {
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

        if (!$removeFooterImage && !empty($_FILES['footer_image_upload']['tmp_name'])) {
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

        if (!$removeHeroImage && !empty($_FILES['hero_image_upload']['tmp_name'])) {
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

        if (!$removeBackgroundImage && !empty($_FILES['background_image_upload']['tmp_name'])) {
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
            'favicon_url' => $faviconUrl,
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

    public function approveSubdomain(): void
    {
        $this->ensureAdmin();

        $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        if ($userId <= 0) {
            $_SESSION['admin_partner_branding_error'] = 'Parceiro inválido.';
            header('Location: /admin/branding-parceiros');
            exit;
        }

        $branding = CoursePartnerBranding::findByUserId($userId);
        $subdomain = strtolower(trim((string)($branding['subdomain'] ?? '')));
        if ($subdomain === '') {
            $_SESSION['admin_partner_branding_error'] = 'Este parceiro não tem subdomínio.';
            header('Location: /admin/branding-parceiros/editar?user_id=' . $userId);
            exit;
        }

        $now = date('Y-m-d H:i:s');
        CoursePartnerBranding::upsert($userId, [
            'subdomain' => $subdomain,
            'subdomain_status' => 'approved',
            'subdomain_approved_at' => $now,
        ]);

        $user = User::findById($userId);
        if ($user && !empty($user['email'])) {
            $baseDomain = trim((string)Setting::get('partner_courses_base_domain', ''));
            if ($baseDomain === '') {
                $appPublicUrl = trim((string)Setting::get('app_public_url', ''));
                $host = $appPublicUrl !== '' ? (string)(parse_url($appPublicUrl, PHP_URL_HOST) ?? '') : '';
                $baseDomain = trim($host);
            }
            $fullHost = $baseDomain !== '' ? ($subdomain . '.' . $baseDomain) : $subdomain;
            $subject = 'Seu subdomínio foi aprovado!';
            $content = '<p style="font-size:14px; margin:0 0 10px 0;">Seu subdomínio foi aprovado e já pode ser acessado:</p>'
                . '<p style="font-size:14px; margin:0 0 10px 0;"><strong>' . htmlspecialchars($fullHost, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</strong></p>'
                . '<p style="font-size:13px; margin:0; color:#b0b0b0;">Se você ainda não consegue acessar, aguarde a propagação do DNS.</p>';

            $cta = $baseDomain !== '' ? ('https://' . $fullHost . '/') : null;
            $body = MailService::buildDefaultTemplate((string)($user['name'] ?? 'Parceiro'), $content, $cta ? 'Abrir catálogo' : null, $cta, null);
            MailService::send((string)$user['email'], (string)($user['name'] ?? ''), $subject, $body);
        }

        $_SESSION['admin_partner_branding_success'] = 'Subdomínio aprovado.';
        header('Location: /admin/branding-parceiros/editar?user_id=' . $userId);
        exit;
    }
}
