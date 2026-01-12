<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Plan;

class AdminPlanController extends Controller
{
    private function slugify(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
            if (is_string($converted) && $converted !== '') {
                $text = $converted;
            }
        }

        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/i', '-', $text);
        $text = trim((string)$text, '-');
        return (string)$text;
    }

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
        $plans = Plan::all();

        $this->view('admin/planos/index', [
            'pageTitle' => 'Gerenciar planos',
            'plans' => $plans,
        ]);
    }

    public function form(): void
    {
        $this->ensureAdmin();
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $plan = null;
        if ($id > 0) {
            $plan = Plan::findById($id);
        }

        $this->view('admin/planos/form', [
            'pageTitle' => $plan ? 'Editar plano' : 'Novo plano',
            'plan' => $plan,
        ]);
    }

    public function save(): void
    {
        $this->ensureAdmin();

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $name = trim($_POST['name'] ?? '');
        $slug = '';
        $price = trim($_POST['price'] ?? '0');
        $description = trim($_POST['description'] ?? '');
        $benefits = trim($_POST['benefits'] ?? '');
        $monthlyTokenLimitRaw = trim($_POST['monthly_token_limit'] ?? '');
        $allowAudio = !empty($_POST['allow_audio']) ? 1 : 0;
        $allowImages = !empty($_POST['allow_images']) ? 1 : 0;
        $allowFiles = !empty($_POST['allow_files']) ? 1 : 0;
        $allowPersonalities = !empty($_POST['allow_personalities']) ? 1 : 0;
        $allowCourses = !empty($_POST['allow_courses']) ? 1 : 0;
        $allowVideoChat = !empty($_POST['allow_video_chat']) ? 1 : 0;
        $allowPages = !empty($_POST['allow_pages']) ? 1 : 0;
        $allowKanban = !empty($_POST['allow_kanban']) ? 1 : 0;
        $allowProjectsAccess = !empty($_POST['allow_projects_access']) ? 1 : 0;
        $allowProjectsCreate = $allowProjectsAccess;
        $allowProjectsEdit = $allowProjectsAccess;
        $allowProjectsShare = $allowProjectsAccess;
        $isActive = !empty($_POST['is_active']) ? 1 : 0;
        $isDefaultForUsers = !empty($_POST['is_default_for_users']) ? 1 : 0;
        $allowedModels = isset($_POST['allowed_models']) && is_array($_POST['allowed_models'])
            ? array_values(array_filter(array_map('trim', $_POST['allowed_models'])))
            : [];

        $nanoAllowedModels = [
            'nano-banana-pro',
            'gemini-2.5-flash-image',
            'gemini-3-pro-image-preview',
        ];
        $allowNanoBananaPro = 0;
        foreach ($nanoAllowedModels as $nm) {
            if (in_array($nm, $allowedModels, true)) {
                $allowNanoBananaPro = 1;
                break;
            }
        }

        $courseDiscountPercent = null;
        $courseDiscountPercentRaw = trim((string)($_POST['course_discount_percent'] ?? ''));
        if ($courseDiscountPercentRaw !== '') {
            $courseDiscountPercent = (float)str_replace([',', ' '], ['.', ''], $courseDiscountPercentRaw);
            if ($courseDiscountPercent < 0) {
                $courseDiscountPercent = 0.0;
            }
            if ($courseDiscountPercent > 100) {
                $courseDiscountPercent = 100.0;
            }
        }
        $defaultModel = trim($_POST['default_model'] ?? '');
        $historyRetentionDays = isset($_POST['history_retention_days']) && $_POST['history_retention_days'] !== ''
            ? max(1, (int)$_POST['history_retention_days'])
            : null;

        $referralEnabled = !empty($_POST['referral_enabled']) ? 1 : 0;
        $referralMinActiveDaysRaw = trim($_POST['referral_min_active_days'] ?? '');
        $referralReferrerTokensRaw = trim($_POST['referral_referrer_tokens'] ?? '');
        $referralFriendTokensRaw = trim($_POST['referral_friend_tokens'] ?? '');
        $referralFreeDaysRaw = trim($_POST['referral_free_days'] ?? '');
        $referralRequireCard = !empty($_POST['referral_require_card']) ? 1 : 0;

        $billingCycle = $_POST['billing_cycle'] ?? 'monthly';

        $priceCents = (int)round(str_replace([',', ' '], ['.', ''], $price) * 100);
        if ($priceCents < 0) {
            $priceCents = 0;
        }

        $monthlyTokenLimit = null;
        if ($monthlyTokenLimitRaw !== '') {
            $monthlyTokenLimit = max(0, (int)$monthlyTokenLimitRaw);
        }

        $referralMinActiveDays = null;
        if ($referralMinActiveDaysRaw !== '') {
            $referralMinActiveDays = max(0, (int)$referralMinActiveDaysRaw);
        }

        $referralReferrerTokens = null;
        if ($referralReferrerTokensRaw !== '') {
            $referralReferrerTokens = max(0, (int)$referralReferrerTokensRaw);
        }

        $referralFriendTokens = null;
        if ($referralFriendTokensRaw !== '') {
            $referralFriendTokens = max(0, (int)$referralFriendTokensRaw);
        }

        $referralFreeDays = null;
        if ($referralFreeDaysRaw !== '') {
            $referralFreeDays = max(0, (int)$referralFreeDaysRaw);
        }

        // Slug é sempre gerado automaticamente a partir do nome + ciclo
        $existing = null;
        if ($id > 0) {
            $existing = Plan::findById($id);
        }

        if ($existing && (string)($existing['slug'] ?? '') === 'free') {
            $slug = 'free';
        } else {
            $baseSlug = $this->slugify($name);
            if ($baseSlug === '') {
                $baseSlug = 'plano';
            }

            if ($billingCycle === 'semiannual') {
                $slug = $baseSlug . '-semestral';
            } elseif ($billingCycle === 'annual') {
                $slug = $baseSlug . '-anual';
            } else {
                $slug = $baseSlug . '-mensal';
            }
        }

        $data = [
            'name' => $name,
            'slug' => $slug,
            'price_cents' => $priceCents,
            'description' => $description,
            'benefits' => $benefits,
            'monthly_token_limit' => $monthlyTokenLimit,
            'allowed_models' => $allowedModels ? json_encode($allowedModels) : null,
            'default_model' => $defaultModel !== '' ? $defaultModel : null,
            'history_retention_days' => $historyRetentionDays,
            'allow_audio' => $allowAudio,
            'allow_images' => $allowImages,
            'allow_nano_banana_pro' => $allowNanoBananaPro,
            'allow_files' => $allowFiles,
            'allow_personalities' => $allowPersonalities,
            'allow_courses' => $allowCourses,
            'allow_video_chat' => $allowVideoChat,
            'course_discount_percent' => $courseDiscountPercent,
            'allow_pages' => $allowPages,
            'allow_kanban' => $allowKanban,
            'allow_projects_access' => $allowProjectsAccess,
            'allow_projects_create' => $allowProjectsCreate,
            'allow_projects_edit' => $allowProjectsEdit,
            'allow_projects_share' => $allowProjectsShare,
            'is_active' => $isActive,
            'is_default_for_users' => $isDefaultForUsers,
            'referral_enabled' => $referralEnabled,
            'referral_min_active_days' => $referralMinActiveDays,
            'referral_referrer_tokens' => $referralReferrerTokens,
            'referral_friend_tokens' => $referralFriendTokens,
            'referral_free_days' => $referralFreeDays,
            'referral_require_card' => $referralRequireCard,
        ];

        if ($id > 0) {
            Plan::updateById($id, $data);
        } else {
            Plan::create($data);
        }

        header('Location: /admin/planos');
        exit;
    }

    public function toggleActive(): void
    {
        $this->ensureAdmin();
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $value = isset($_GET['v']) ? (int)$_GET['v'] : 0;
        if ($id > 0) {
            Plan::setActive($id, $value === 1);
        }
        header('Location: /admin/planos');
        exit;
    }
}
