<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseLesson;
use App\Models\CourseLive;
use App\Models\CourseLiveParticipant;
use App\Models\CoursePartner;
use App\Models\CoursePartnerCommission;
use App\Models\CourseModule;
use App\Models\CourseModuleExam;
use App\Models\CourseExamQuestion;
use App\Models\CourseExamOption;
use App\Models\User;
use App\Models\Setting;
use App\Services\MailService;
use App\Services\GoogleCalendarService;
use App\Services\MediaStorageService;

class AdminCourseController extends Controller
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
        $courses = Course::all();

        $this->view('admin/cursos/index', [
            'pageTitle' => 'Cursos do Tuquinha',
            'courses' => $courses,
        ]);
    }

    public function form(): void
    {
        $this->ensureAdmin();
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $course = null;
        if ($id > 0) {
            $course = Course::findById($id);
        }

        $partnerCommissionPercent = null;
        $partnerDefaultPercent = null;
        $partnerEmail = '';

        if ($course && !empty($course['owner_user_id'])) {
            $partner = CoursePartner::findByUserId((int)$course['owner_user_id']);
            if ($partner) {
                if (isset($partner['default_commission_percent'])) {
                    $partnerDefaultPercent = (float)$partner['default_commission_percent'];
                }

                $partnerId = (int)($partner['id'] ?? 0);
                if ($partnerId > 0 && !empty($course['id'])) {
                    $commission = CoursePartnerCommission::findByPartnerAndCourse($partnerId, (int)$course['id']);
                    if ($commission && isset($commission['commission_percent'])) {
                        $partnerCommissionPercent = (float)$commission['commission_percent'];
                    }
                }
            }

            $owner = User::findById((int)$course['owner_user_id']);
            if ($owner && !empty($owner['email'])) {
                $partnerEmail = (string)$owner['email'];
            }
        }

        $this->view('admin/cursos/form', [
            'pageTitle' => $course ? 'Editar curso' : 'Novo curso',
            'course' => $course,
            'partnerCommissionPercent' => $partnerCommissionPercent,
            'partnerDefaultPercent' => $partnerDefaultPercent,
            'partnerEmail' => $partnerEmail,
        ]);
    }

    public function save(): void
    {
        $this->ensureAdmin();

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $shortDescription = trim($_POST['short_description'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $imagePath = trim($_POST['image_path'] ?? '');
        $badgeImagePath = trim($_POST['badge_image_path'] ?? '');
        $certificateSyllabus = trim($_POST['certificate_syllabus'] ?? '');
        if ($certificateSyllabus !== '') {
            $certificateSyllabus = str_replace(["\\r\\n", "\\n", "\\r"], ["\n", "\n", "\n"], $certificateSyllabus);
        }
        $certificateWorkloadHours = isset($_POST['certificate_workload_hours']) ? (int)$_POST['certificate_workload_hours'] : 0;
        $certificateLocation = trim($_POST['certificate_location'] ?? '');
        $removeImage = !empty($_POST['remove_image']);
        $removeBadgeImage = !empty($_POST['remove_badge_image']);
        $partnerEmail = trim($_POST['partner_email'] ?? '');
        $isPaid = !empty($_POST['is_paid']) ? 1 : 0;
        $priceRaw = trim($_POST['price'] ?? '0');
        $partnerCommissionRaw = trim($_POST['partner_commission_percent'] ?? '');
        $allowPlanAccessOnly = !empty($_POST['allow_plan_access_only']) ? 1 : 0;
        $allowPublicPurchase = !empty($_POST['allow_public_purchase']) ? 1 : 0;
        $isActive = !empty($_POST['is_active']) ? 1 : 0;

        // Upload de imagem do curso para o servidor de mídia externo, se um arquivo tiver sido enviado
        if (!$removeImage && !empty($_FILES['image_upload']['tmp_name'])) {
            $imgError = $_FILES['image_upload']['error'] ?? UPLOAD_ERR_NO_FILE;
            if ($imgError === UPLOAD_ERR_OK) {
                $imgTmp = (string)($_FILES['image_upload']['tmp_name'] ?? '');
                $imgName = (string)($_FILES['image_upload']['name'] ?? '');
                $imgMime = (string)($_FILES['image_upload']['type'] ?? '');

                if ($imgTmp !== '' && is_file($imgTmp)) {
                    $remoteImageUrl = MediaStorageService::uploadFile($imgTmp, $imgName, $imgMime);
                    if ($remoteImageUrl !== null) {
                        $imagePath = $remoteImageUrl;
                    }
                }
            }
        }

        // Upload de imagem da insígnia do curso para o servidor de mídia externo, se um arquivo tiver sido enviado
        if (!$removeBadgeImage && !empty($_FILES['badge_image_upload']['tmp_name'])) {
            $imgError = $_FILES['badge_image_upload']['error'] ?? UPLOAD_ERR_NO_FILE;
            if ($imgError === UPLOAD_ERR_OK) {
                $imgTmp = (string)($_FILES['badge_image_upload']['tmp_name'] ?? '');
                $imgName = (string)($_FILES['badge_image_upload']['name'] ?? '');
                $imgMime = (string)($_FILES['badge_image_upload']['type'] ?? '');

                if ($imgTmp !== '' && is_file($imgTmp)) {
                    $remoteImageUrl = MediaStorageService::uploadFile($imgTmp, $imgName, $imgMime);
                    if ($remoteImageUrl !== null) {
                        $badgeImagePath = $remoteImageUrl;
                    }
                }
            }
        }

        $ownerUserId = null;

        if ($partnerEmail !== '') {
            $ownerUser = User::findByEmail($partnerEmail);
            if (!$ownerUser) {
                $_SESSION['admin_course_error'] = 'Nenhum usuário encontrado com o e-mail informado para professor/parceiro.';
                $target = $id > 0 ? '/admin/cursos/editar?id=' . $id : '/admin/cursos/novo';
                header('Location: ' . $target);
                exit;
            }
            $ownerUserId = (int)$ownerUser['id'];
        }

        $priceCents = 0;
        if ($priceRaw !== '') {
            $priceCents = (int)round(str_replace([',', ' '], ['.', ''], $priceRaw) * 100);
            if ($priceCents < 0) {
                $priceCents = 0;
            }
        }

        $partnerCommissionPercent = null;
        if ($partnerCommissionRaw !== '') {
            $partnerCommissionPercent = (float)str_replace([',', ' '], ['.', ''], $partnerCommissionRaw);
            if ($partnerCommissionPercent < 0) {
                $partnerCommissionPercent = 0.0;
            }
        }

        if ($title === '' || $slug === '') {
            $_SESSION['admin_course_error'] = 'Preencha pelo menos título e slug do curso.';
            $target = $id > 0 ? '/admin/cursos/editar?id=' . $id : '/admin/cursos/novo';
            header('Location: ' . $target);
            exit;
        }

        if ($removeImage) {
            $imagePath = '';
        }

        if ($removeBadgeImage) {
            $badgeImagePath = '';
        }

        $data = [
            'owner_user_id' => $ownerUserId ?: null,
            'title' => $title,
            'slug' => $slug,
            'short_description' => $shortDescription !== '' ? $shortDescription : null,
            'description' => $description !== '' ? $description : null,
            'image_path' => $imagePath !== '' ? $imagePath : null,
            'badge_image_path' => $badgeImagePath !== '' ? $badgeImagePath : null,
            'certificate_syllabus' => $certificateSyllabus !== '' ? $certificateSyllabus : null,
            'certificate_workload_hours' => $certificateWorkloadHours > 0 ? $certificateWorkloadHours : null,
            'certificate_location' => $certificateLocation !== '' ? $certificateLocation : null,
            'is_paid' => $isPaid,
            'price_cents' => $isPaid ? $priceCents : null,
            'allow_plan_access_only' => $allowPlanAccessOnly,
            'allow_public_purchase' => $allowPublicPurchase,
            'is_active' => $isActive,
        ];

        if ($id > 0) {
            Course::update($id, $data);
            $courseId = $id;
        } else {
            $courseId = Course::create($data);
        }

        if ($ownerUserId) {
            $partner = CoursePartner::findByUserId((int)$ownerUserId);
            if ($partner && !empty($partner['id'])) {
                $partnerId = (int)$partner['id'];
                if ($partnerCommissionRaw === '') {
                    CoursePartnerCommission::deleteByPartnerAndCourse($partnerId, (int)$courseId);
                } elseif ($partnerCommissionPercent !== null) {
                    CoursePartnerCommission::setCommission($partnerId, (int)$courseId, (float)$partnerCommissionPercent);
                }
            }
        }

        header('Location: /admin/cursos');
        exit;
    }

    public function modules(): void
    {
        $this->ensureAdmin();
        $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
        $course = $courseId > 0 ? Course::findById($courseId) : null;
        if (!$course) {
            header('Location: /admin/cursos');
            exit;
        }

        $modules = CourseModule::allByCourse($courseId);
        $modulesWithExam = [];
        foreach ($modules as $m) {
            $mid = (int)($m['id'] ?? 0);
            if ($mid <= 0) {
                continue;
            }
            $m['exam'] = CourseModuleExam::findByModuleId($mid);
            $modulesWithExam[] = $m;
        }

        $this->view('admin/cursos/modules', [
            'pageTitle' => 'Módulos do curso: ' . (string)($course['title'] ?? ''),
            'course' => $course,
            'modules' => $modulesWithExam,
        ]);
    }

    public function moduleForm(): void
    {
        $this->ensureAdmin();
        $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $course = $courseId > 0 ? Course::findById($courseId) : null;
        if (!$course) {
            header('Location: /admin/cursos');
            exit;
        }

        $module = null;
        if ($id > 0) {
            $module = CourseModule::findById($id);
        }

        $this->view('admin/cursos/module_form', [
            'pageTitle' => $module ? 'Editar módulo' : 'Novo módulo',
            'course' => $course,
            'module' => $module,
        ]);
    }

    public function moduleSave(): void
    {
        $this->ensureAdmin();
        $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $course = $courseId > 0 ? Course::findById($courseId) : null;
        if (!$course) {
            header('Location: /admin/cursos');
            exit;
        }

        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $sortOrder = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;

        if ($title === '') {
            $_SESSION['admin_course_error'] = 'Preencha pelo menos o título do módulo.';
            $target = '/admin/cursos/modulos/novo?course_id=' . $courseId;
            if ($id > 0) {
                $target = '/admin/cursos/modulos/editar?course_id=' . $courseId . '&id=' . $id;
            }
            header('Location: ' . $target);
            exit;
        }

        $data = [
            'course_id' => $courseId,
            'title' => $title,
            'description' => $description !== '' ? $description : null,
            'sort_order' => $sortOrder,
        ];

        if ($id > 0) {
            CourseModule::update($id, $data);
        } else {
            CourseModule::create($data);
        }

        $_SESSION['admin_course_success'] = 'Módulo salvo com sucesso.';
        header('Location: /admin/cursos/modulos?course_id=' . $courseId);
        exit;
    }

    public function moduleDelete(): void
    {
        $this->ensureAdmin();
        $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id > 0) {
            CourseModule::delete($id);
        }
        $_SESSION['admin_course_success'] = 'Módulo excluído.';
        header('Location: /admin/cursos/modulos?course_id=' . $courseId);
        exit;
    }

    public function moduleExamForm(): void
    {
        $this->ensureAdmin();
        $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
        $moduleId = isset($_GET['module_id']) ? (int)$_GET['module_id'] : 0;
        $course = $courseId > 0 ? Course::findById($courseId) : null;
        if (!$course) {
            header('Location: /admin/cursos');
            exit;
        }
        $module = $moduleId > 0 ? CourseModule::findById($moduleId) : null;
        if (!$module) {
            header('Location: /admin/cursos/modulos?course_id=' . $courseId);
            exit;
        }

        $exam = CourseModuleExam::findByModuleId($moduleId);
        $examId = $exam && !empty($exam['id']) ? (int)$exam['id'] : 0;
        $questionRows = [];
        if ($examId > 0) {
            $rawQuestions = CourseExamQuestion::allByExam($examId);
            foreach ($rawQuestions as $q) {
                $qid = (int)($q['id'] ?? 0);
                if ($qid <= 0) {
                    continue;
                }
                $optionsRows = CourseExamOption::allByQuestion($qid);
                $options = [];
                $correctIndex = null;
                $idx = 0;
                foreach ($optionsRows as $opt) {
                    $options[] = (string)($opt['option_text'] ?? '');
                    if (!empty($opt['is_correct']) && $correctIndex === null) {
                        $correctIndex = $idx;
                    }
                    $idx++;
                }
                while (count($options) < 4) {
                    $options[] = '';
                }
                if ($correctIndex === null) {
                    $correctIndex = 0;
                }
                $questionRows[] = [
                    'id' => $qid,
                    'text' => (string)($q['question_text'] ?? ''),
                    'options' => $options,
                    'correct' => $correctIndex,
                ];
            }
        }
        while (count($questionRows) < 5) {
            $questionRows[] = [
                'id' => 0,
                'text' => '',
                'options' => ['', '', '', ''],
                'correct' => 0,
            ];
        }

        $this->view('admin/cursos/module_exam', [
            'pageTitle' => 'Prova do módulo: ' . (string)($module['title'] ?? ''),
            'course' => $course,
            'module' => $module,
            'exam' => $exam,
            'questions' => $questionRows,
        ]);
    }

    public function moduleExamSave(): void
    {
        $this->ensureAdmin();
        $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
        $moduleId = isset($_POST['module_id']) ? (int)$_POST['module_id'] : 0;
        $course = $courseId > 0 ? Course::findById($courseId) : null;
        if (!$course) {
            header('Location: /admin/cursos');
            exit;
        }
        $module = $moduleId > 0 ? CourseModule::findById($moduleId) : null;
        if (!$module) {
            header('Location: /admin/cursos/modulos?course_id=' . $courseId);
            exit;
        }

        $passScorePercent = isset($_POST['pass_score_percent']) ? (int)$_POST['pass_score_percent'] : 70;
        if ($passScorePercent < 0) {
            $passScorePercent = 0;
        } elseif ($passScorePercent > 100) {
            $passScorePercent = 100;
        }

        $maxAttempts = isset($_POST['max_attempts']) ? (int)$_POST['max_attempts'] : 3;
        if ($maxAttempts < 1) {
            $maxAttempts = 1;
        }

        $isActive = !empty($_POST['is_active']);

        $examId = CourseModuleExam::upsertForModule($moduleId, $passScorePercent, $maxAttempts, $isActive);

        $existingQuestions = CourseExamQuestion::allByExam($examId);
        $existingIds = [];
        foreach ($existingQuestions as $q) {
            $qid = (int)($q['id'] ?? 0);
            if ($qid > 0) {
                $existingIds[] = $qid;
            }
        }
        if (!empty($existingIds)) {
            CourseExamOption::deleteByQuestionIds($existingIds);
            CourseExamQuestion::deleteByExam($examId);
        }

        $questionsPost = $_POST['questions'] ?? [];
        $order = 0;
        $createdQuestions = 0;
        foreach ($questionsPost as $qData) {
            $text = trim((string)($qData['text'] ?? ''));
            if ($text === '') {
                continue;
            }
            $optionsPost = $qData['options'] ?? [];
            $correctIndex = isset($qData['correct']) ? (int)$qData['correct'] : -1;
            $questionId = CourseExamQuestion::create([
                'exam_id' => $examId,
                'question_text' => $text,
                'sort_order' => $order,
            ]);
            $order++;
            $createdQuestions++;

            $optOrder = 0;
            foreach ($optionsPost as $idx => $optText) {
                $optText = trim((string)$optText);
                if ($optText === '') {
                    continue;
                }
                $isCorrectOpt = ($correctIndex === (int)$idx);
                CourseExamOption::create([
                    'question_id' => $questionId,
                    'option_text' => $optText,
                    'is_correct' => $isCorrectOpt ? 1 : 0,
                    'sort_order' => $optOrder,
                ]);
                $optOrder++;
            }
        }

        if ($isActive && $createdQuestions === 0) {
            CourseModuleExam::upsertForModule($moduleId, $passScorePercent, $maxAttempts, false);
            $_SESSION['admin_course_error'] = 'Para ativar a prova, cadastre pelo menos uma pergunta com alternativas.';
            header('Location: /admin/cursos/modulos/prova?course_id=' . $courseId . '&module_id=' . $moduleId);
            exit;
        }

        $_SESSION['admin_course_success'] = 'Configurações da prova salvas com sucesso.';
        header('Location: /admin/cursos/modulos?course_id=' . $courseId);
        exit;
    }

    public function lessons(): void
    {
        $this->ensureAdmin();
        $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
        $course = $courseId > 0 ? Course::findById($courseId) : null;
        if (!$course) {
            header('Location: /admin/cursos');
            exit;
        }

        $lessons = CourseLesson::allByCourseId($courseId);

        $this->view('admin/cursos/lessons', [
            'pageTitle' => 'Aulas do curso: ' . (string)($course['title'] ?? ''),
            'course' => $course,
            'lessons' => $lessons,
        ]);
    }

    public function lessonForm(): void
    {
        $this->ensureAdmin();
        $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $course = $courseId > 0 ? Course::findById($courseId) : null;
        if (!$course) {
            header('Location: /admin/cursos');
            exit;
        }

        $lesson = null;
        if ($id > 0) {
            $lesson = CourseLesson::findById($id);
        }

        $modules = CourseModule::allByCourse($courseId);

        $this->view('admin/cursos/lesson_form', [
            'pageTitle' => $lesson ? 'Editar aula' : 'Nova aula',
            'course' => $course,
            'lesson' => $lesson,
            'modules' => $modules,
        ]);
    }

    public function lessonSave(): void
    {
        $this->ensureAdmin();
        $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $course = $courseId > 0 ? Course::findById($courseId) : null;
        if (!$course) {
            header('Location: /admin/cursos');
            exit;
        }

        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $videoUrl = trim($_POST['video_url'] ?? '');
        $sortOrder = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;
        $isPublished = !empty($_POST['is_published']) ? 1 : 0;
        $moduleId = isset($_POST['module_id']) ? (int)$_POST['module_id'] : 0;

        if (isset($_FILES['video_upload']) && !empty($_FILES['video_upload']['tmp_name'])) {
            $tmp = (string)($_FILES['video_upload']['tmp_name'] ?? '');
            $originalName = (string)($_FILES['video_upload']['name'] ?? '');
            $mime = (string)($_FILES['video_upload']['type'] ?? '');

            if ($tmp !== '' && is_uploaded_file($tmp)) {
                $defaultVideoEndpoint = defined('MEDIA_VIDEO_UPLOAD_ENDPOINT') ? MEDIA_VIDEO_UPLOAD_ENDPOINT : '';
                $endpoint = trim(Setting::get('media_video_upload_endpoint', $defaultVideoEndpoint));
                $remoteVideoUrl = MediaStorageService::uploadFileToEndpoint($tmp, $originalName, $mime, $endpoint);
                if ($remoteVideoUrl !== null) {
                    $videoUrl = $remoteVideoUrl;
                }
            }
        }

        if ($title === '' || $videoUrl === '') {
            $_SESSION['admin_course_error'] = 'Preencha o título e informe um link ou envie um arquivo de vídeo.';
            $target = '/admin/cursos/aulas/nova?course_id=' . $courseId;
            if ($id > 0) {
                $target = '/admin/cursos/aulas/editar?course_id=' . $courseId . '&id=' . $id;
            }
            header('Location: ' . $target);
            exit;
        }

        $data = [
            'course_id' => $courseId,
            'module_id' => $moduleId > 0 ? $moduleId : null,
            'title' => $title,
            'description' => $description !== '' ? $description : null,
            'video_url' => $videoUrl,
            'sort_order' => $sortOrder,
            'is_published' => $isPublished,
        ];

        if ($id > 0) {
            CourseLesson::update($id, $data);
        } else {
            CourseLesson::create($data);

            foreach (CourseEnrollment::allByCourse($courseId) as $en) {
                $user = User::findById((int)$en['user_id']);
                if (!$user || empty($user['email'])) {
                    continue;
                }
                $subject = 'Nova aula no curso: ' . (string)($course['title'] ?? '');
                $courseUrl = CourseController::buildCourseUrl($course);
                $safeName = htmlspecialchars($user['name'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safeCourseTitle = htmlspecialchars($course['title'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safeLessonTitle = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safeCourseUrl = htmlspecialchars($courseUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $baseUrl = $scheme . $host;
                $logoUrl = $baseUrl . '/public/favicon.png';
                $safeLogoUrl = htmlspecialchars($logoUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                $body = <<<HTML
<html>
<body style="margin:0; padding:0; background:#050509; font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color:#f5f5f5;">
  <div style="width:100%; padding:24px 0;">
    <div style="max-width:520px; margin:0 auto; background:#111118; border-radius:16px; border:1px solid #272727; padding:18px 20px;">
      <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
        <div style="width:32px; height:32px; border-radius:50%; overflow:hidden; background:#050509; box-shadow:0 0 18px rgba(229,57,53,0.8);">
          <img src="{$safeLogoUrl}" alt="Tuquinha" style="width:100%; height:100%; display:block; object-fit:cover;">
        </div>
        <div>
          <div style="font-weight:700; font-size:15px;">Resenha 2.0 - Tuquinha</div>
          <div style="font-size:11px; color:#b0b0b0;">Branding vivo na veia</div>
        </div>
      </div>

      <p style="font-size:14px; margin:0 0 10px 0;">Oi, {$safeName} 👋</p>
      <p style="font-size:14px; margin:0 0 10px 0;">Uma nova aula foi liberada no curso <strong>{$safeCourseTitle}</strong>:</p>
      <p style="font-size:14px; margin:0 0 10px 0;"><strong>{$safeLessonTitle}</strong></p>

      <div style="text-align:center; margin:14px 0 8px 0;">
        <a href="{$safeCourseUrl}" style="display:inline-block; padding:9px 18px; border-radius:999px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:600; font-size:13px; text-decoration:none;">Acessar curso</a>
      </div>

      <p style="font-size:12px; color:#777; margin:8px 0 0 0;">Se o botão não funcionar, copie e cole este link no navegador:<br>
        <a href="{$safeCourseUrl}" style="color:#ff6f60; text-decoration:none;">{$safeCourseUrl}</a>
      </p>
    </div>
  </div>
</body>
</html>
HTML;
                try {
                    MailService::send($user['email'], $user['name'] ?? '', $subject, $body);
                } catch (\Throwable $e) {
                }
            }
        }

        header('Location: /admin/cursos/aulas?course_id=' . $courseId);
        exit;
    }

    public function lessonDelete(): void
    {
        $this->ensureAdmin();
        $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id > 0) {
            CourseLesson::delete($id);
        }
        header('Location: /admin/cursos/aulas?course_id=' . $courseId);
        exit;
    }

    public function lives(): void
    {
        $this->ensureAdmin();
        $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
        $course = $courseId > 0 ? Course::findById($courseId) : null;
        if (!$course) {
            header('Location: /admin/cursos');
            exit;
        }

        $lives = CourseLive::allByCourse($courseId);

        $this->view('admin/cursos/lives', [
            'pageTitle' => 'Lives do curso: ' . (string)($course['title'] ?? ''),
            'course' => $course,
            'lives' => $lives,
        ]);
    }

    public function liveForm(): void
    {
        $this->ensureAdmin();
        $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $course = $courseId > 0 ? Course::findById($courseId) : null;
        if (!$course) {
            header('Location: /admin/cursos');
            exit;
        }

        $live = null;
        if ($id > 0) {
            $live = CourseLive::findById($id);
        }

        $this->view('admin/cursos/live_form', [
            'pageTitle' => $live ? 'Editar live' : 'Nova live',
            'course' => $course,
            'live' => $live,
        ]);
    }

    public function liveSave(): void
    {
        $this->ensureAdmin();
        $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $course = $courseId > 0 ? Course::findById($courseId) : null;
        if (!$course) {
            header('Location: /admin/cursos');
            exit;
        }

        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $scheduledAt = trim($_POST['scheduled_at'] ?? '');
        $meetLink = trim($_POST['meet_link'] ?? '');
        $recordingLink = trim($_POST['recording_link'] ?? '');
        $isPublished = !empty($_POST['is_published']) ? 1 : 0;

        if ($title === '' || $scheduledAt === '') {
            $_SESSION['admin_course_error'] = 'Preencha pelo menos título e data/horário da live.';
            $target = '/admin/cursos/lives/nova?course_id=' . $courseId;
            if ($id > 0) {
                $target = '/admin/cursos/lives/editar?course_id=' . $courseId . '&id=' . $id;
            }
            header('Location: ' . $target);
            exit;
        }

        $googleEventId = null;
        if ($meetLink === '') {
            $googleService = new GoogleCalendarService();
            if ($googleService->isConfigured()) {
                $startIso = date('c', strtotime($scheduledAt));
                $endIso = date('c', strtotime($scheduledAt . ' +60 minutes'));
                $summary = $title !== '' ? $title : ('Live do curso ' . (string)($course['title'] ?? ''));
                $desc = $description !== '' ? $description : 'Live do curso ' . (string)($course['title'] ?? '');

                $event = $googleService->createLiveEvent($summary, $desc, $startIso, $endIso);
                if ($event && !empty($event['meet_link'])) {
                    $meetLink = (string)$event['meet_link'];
                    $googleEventId = (string)($event['event_id'] ?? '');
                }
            }

            if ($meetLink === '') {
                $letters = 'abcdefghijklmnopqrstuvwxyz';

                $code = '';
                for ($i = 0; $i < 3; $i++) {
                    $code .= $letters[random_int(0, 25)];
                }
                $code .= '-';
                for ($i = 0; $i < 4; $i++) {
                    $code .= $letters[random_int(0, 25)];
                }
                $code .= '-';
                for ($i = 0; $i < 3; $i++) {
                    $code .= $letters[random_int(0, 25)];
                }

                $meetLink = 'https://meet.google.com/' . $code;
            }
        }

        $data = [
            'course_id' => $courseId,
            'title' => $title,
            'description' => $description !== '' ? $description : null,
            'scheduled_at' => $scheduledAt,
            'meet_link' => $meetLink,
            'recording_link' => $recordingLink !== '' ? $recordingLink : null,
            'recording_published_at' => null,
            'google_event_id' => $googleEventId ?: null,
            'is_published' => $isPublished,
        ];

        if ($id > 0) {
            $existing = CourseLive::findById($id);
            $hadRecording = !empty($existing['recording_link']);
            $willHaveRecording = $recordingLink !== '';

            if ($willHaveRecording && empty($data['recording_published_at'])) {
                $data['recording_published_at'] = date('Y-m-d H:i:s');
            } elseif (!$willHaveRecording) {
                $data['recording_published_at'] = null;
            }

            CourseLive::update($id, $data);

            if ($willHaveRecording && !$hadRecording) {
                $this->notifyRecordingPublished($course, CourseLive::findById($id));
            }
        } else {
            $liveId = CourseLive::create($data);

            if ($isPublished) {
                $enrollments = CourseEnrollment::allByCourse($courseId);
                foreach ($enrollments as $en) {
                    $user = User::findById((int)$en['user_id']);
                    if (!$user || empty($user['email'])) {
                        continue;
                    }

                    $when = '';
                    if ($scheduledAt !== '') {
                        $when = date('d/m/Y H:i', strtotime($scheduledAt));
                    }

                    $subject = 'Nova live no curso: ' . (string)($course['title'] ?? '');
                    $safeName = htmlspecialchars($user['name'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $safeCourseTitle = htmlspecialchars($course['title'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $safeWhen = htmlspecialchars($when, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
                    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                    $baseUrl = $scheme . $host;
                    $relativePath = CourseController::buildCourseUrl($course) . '#lives';
                    $courseUrl = $baseUrl . $relativePath;
                    $safeCourseUrl = htmlspecialchars($courseUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $logoUrl = $baseUrl . '/public/favicon.png';
                    $safeLogoUrl = htmlspecialchars($logoUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                    $whenParagraph = '';
                    if ($when !== '') {
                        $whenParagraph = '<p style="font-size:14px; margin:0 0 10px 0;">Data e horário: <strong>' . $safeWhen . '</strong></p>';
                    }

                    $body = <<<HTML
<html>
<body style="margin:0; padding:0; background:#050509; font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color:#f5f5f5;">
  <div style="width:100%; padding:24px 0;">
    <div style="max-width:520px; margin:0 auto; background:#111118; border-radius:16px; border:1px solid #272727; padding:18px 20px;">
      <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
        <div style="width:32px; height:32px; border-radius:50%; overflow:hidden; background:#050509; box-shadow:0 0 18px rgba(229,57,53,0.8);">
          <img src="{$safeLogoUrl}" alt="Tuquinha" style="width:100%; height:100%; display:block; object-fit:cover;">
        </div>
        <div>
          <div style="font-weight:700; font-size:15px;">Resenha 2.0 - Tuquinha</div>
          <div style="font-size:11px; color:#b0b0b0;">Branding vivo na veia</div>
        </div>
      </div>

      <p style="font-size:14px; margin:0 0 10px 0;">Oi, {$safeName} 👋</p>
      <p style="font-size:14px; margin:0 0 10px 0;">Uma nova live foi agendada para o curso <strong>{$safeCourseTitle}</strong>.</p>
      {$whenParagraph}

      <div style="text-align:center; margin:14px 0 8px 0;">
        <a href="{$safeCourseUrl}" style="display:inline-block; padding:9px 18px; border-radius:999px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:600; font-size:13px; text-decoration:none;">Ver live e se inscrever</a>
      </div>

      <p style="font-size:12px; color:#777; margin:8px 0 0 0;">Se o botão não funcionar, copie e cole este link no navegador:<br>
        <a href="{$safeCourseUrl}" style="color:#ff6f60; text-decoration:none;">{$safeCourseUrl}</a>
      </p>
    </div>
  </div>
</body>
</html>
HTML;

                    try {
                        MailService::send($user['email'], $user['name'] ?? '', $subject, $body);
                    } catch (\Throwable $e) {
                    }
                }
            }
        }

        header('Location: /admin/cursos/lives?course_id=' . $courseId);
        exit;
    }

    private function notifyRecordingPublished(array $course, ?array $live): void
    {
        if (!$live || empty($live['id']) || empty($live['recording_link'])) {
            return;
        }

        $liveId = (int)$live['id'];
        $participants = CourseLiveParticipant::allByLive($liveId);
        if (empty($participants)) {
            return;
        }

        $recordingLink = (string)$live['recording_link'];
        $when = '';
        if (!empty($live['scheduled_at'])) {
            $when = date('d/m/Y H:i', strtotime((string)$live['scheduled_at']));
        }

        $usersWithEmail = [];
        foreach ($participants as $p) {
            $user = User::findById((int)$p['user_id']);
            if (!$user || empty($user['email'])) {
                continue;
            }
            $usersWithEmail[] = $user;
        }

        if (empty($usersWithEmail)) {
            return;
        }

        $emails = [];
        foreach ($usersWithEmail as $user) {
            $emails[] = (string)$user['email'];
        }

        try {
            $googleService = new GoogleCalendarService();
            if ($googleService->isConfigured()) {
                $googleService->grantDriveFileAccessToEmails($recordingLink, $emails);
            }
        } catch (\Throwable $e) {
        }

        foreach ($usersWithEmail as $user) {
            $subject = 'Gravação disponível: live do curso ' . (string)($course['title'] ?? '');
            $safeName = htmlspecialchars($user['name'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeCourseTitle = htmlspecialchars($course['title'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeLiveTitle = htmlspecialchars($live['title'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeWhen = htmlspecialchars($when, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeRecordingLink = htmlspecialchars($recordingLink, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $baseUrl = $scheme . $host;
            $logoUrl = $baseUrl . '/public/favicon.png';
            $safeLogoUrl = htmlspecialchars($logoUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $whenParagraph = '';
            if ($when !== '') {
                $whenParagraph = '<p style="font-size:14px; margin:0 0 10px 0;">Live realizada em: <strong>' . $safeWhen . '</strong></p>';
            }

            $body = <<<HTML
<html>
<body style="margin:0; padding:0; background:#050509; font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color:#f5f5f5;">
  <div style="width:100%; padding:24px 0;">
    <div style="max-width:520px; margin:0 auto; background:#111118; border-radius:16px; border:1px solid #272727; padding:18px 20px;">
      <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
        <div style="width:32px; height:32px; border-radius:50%; overflow:hidden; background:#050509; box-shadow:0 0 18px rgba(229,57,53,0.8);">
          <img src="{$safeLogoUrl}" alt="Tuquinha" style="width:100%; height:100%; display:block; object-fit:cover;">
        </div>
        <div>
          <div style="font-weight:700; font-size:15px;">Resenha 2.0 - Tuquinha</div>
          <div style="font-size:11px; color:#b0b0b0;">Branding vivo na veia</div>
        </div>
      </div>

      <p style="font-size:14px; margin:0 0 10px 0;">Oi, {$safeName} 👋</p>
      <p style="font-size:14px; margin:0 0 10px 0;">A gravação da live <strong>{$safeLiveTitle}</strong> do curso <strong>{$safeCourseTitle}</strong> já está disponível.</p>
      {$whenParagraph}
      <p style="font-size:14px; margin:0 0 10px 0;">Você pode assistir pelo link abaixo:<br><a href="{$safeRecordingLink}" style="color:#ff6f60; text-decoration:none;">{$safeRecordingLink}</a></p>
    </div>
  </div>
</body>
</html>
HTML;

            try {
                MailService::send($user['email'], $user['name'] ?? '', $subject, $body);
            } catch (\Throwable $e) {
            }
        }
    }

    public function fetchLiveRecording(): void
    {
        $this->ensureAdmin();

        $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
        $liveId = isset($_POST['live_id']) ? (int)$_POST['live_id'] : 0;

        $course = $courseId > 0 ? Course::findById($courseId) : null;
        if (!$course) {
            header('Location: /admin/cursos');
            exit;
        }

        $live = $liveId > 0 ? CourseLive::findById($liveId) : null;
        if (!$live) {
            header('Location: /admin/cursos/lives?course_id=' . $courseId);
            exit;
        }

        if (!empty($live['recording_link'])) {
            $_SESSION['admin_course_success'] = 'Esta live já possui link de gravação cadastrado.';
            header('Location: /admin/cursos/lives?course_id=' . $courseId);
            exit;
        }

        if (empty($live['meet_link'])) {
            $_SESSION['admin_course_error'] = 'Não há link de reunião configurado para esta live. Não é possível buscar gravação automática.';
            header('Location: /admin/cursos/lives?course_id=' . $courseId);
            exit;
        }

        $googleService = new GoogleCalendarService();
        if (!$googleService->isConfigured()) {
            $_SESSION['admin_course_error'] = 'A API do Google ainda não está configurada nas Configurações do sistema.';
            header('Location: /admin/cursos/lives?course_id=' . $courseId);
            exit;
        }

        $recordingUrl = null;

        $eventId = (string)($live['google_event_id'] ?? '');
        if ($eventId !== '') {
            $recordingUrl = $googleService->findRecordingUrlByEventId($eventId);
        }

        if ($recordingUrl === null) {
            $recordingUrl = $googleService->findRecordingExportUriByMeetLink((string)($live['meet_link'] ?? ''));
        }
        if ($recordingUrl === null) {
            $_SESSION['admin_course_error'] = 'Não encontrei nenhuma gravação para esta reunião na API do Google. Aguarde alguns minutos após encerrar a gravação e tente novamente.';
            header('Location: /admin/cursos/lives?course_id=' . $courseId);
            exit;
        }

        $update = [
            'course_id' => (int)($live['course_id'] ?? $courseId),
            'title' => $live['title'] ?? '',
            'description' => $live['description'] ?? null,
            'scheduled_at' => $live['scheduled_at'] ?? '',
            'meet_link' => $live['meet_link'] ?? null,
            'recording_link' => $recordingUrl,
            'recording_published_at' => date('Y-m-d H:i:s'),
            'google_event_id' => $live['google_event_id'] ?? null,
            'is_published' => (int)($live['is_published'] ?? 1),
        ];

        CourseLive::update($liveId, $update);

        $updatedLive = CourseLive::findById($liveId);
        $this->notifyRecordingPublished($course, $updatedLive ?: $live);

        $_SESSION['admin_course_success'] = 'Gravação encontrada na API do Google e enviada para os participantes.';
        header('Location: /admin/cursos/lives?course_id=' . $courseId);
        exit;
    }

    public function sendLiveReminders(): void
    {
        $this->ensureAdmin();

        $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
        $liveId = isset($_POST['live_id']) ? (int)$_POST['live_id'] : 0;

        $course = $courseId > 0 ? Course::findById($courseId) : null;
        if (!$course) {
            header('Location: /admin/cursos');
            exit;
        }

        $live = $liveId > 0 ? CourseLive::findById($liveId) : null;
        if (!$live) {
            header('Location: /admin/cursos/lives?course_id=' . $courseId);
            exit;
        }

        $participants = CourseLiveParticipant::allByLive($liveId);
        if (!$participants) {
            header('Location: /admin/cursos/lives?course_id=' . $courseId);
            exit;
        }

        $when = '';
        if (!empty($live['scheduled_at'])) {
            $when = date('d/m/Y H:i', strtotime($live['scheduled_at']));
        }

        foreach ($participants as $p) {
            if (!empty($p['reminder_sent_at'])) {
                continue;
            }
            if (isset($p['status']) && $p['status'] !== 'confirmed') {
                continue;
            }

            $user = User::findById((int)$p['user_id']);
            if (!$user || empty($user['email'])) {
                continue;
            }

            $subject = 'Lembrete: live do curso ' . (string)($course['title'] ?? '');
            $safeName = htmlspecialchars($user['name'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeCourseTitle = htmlspecialchars($course['title'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeLiveTitle = htmlspecialchars($live['title'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeWhen = htmlspecialchars($when, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeMeetLink = htmlspecialchars((string)($live['meet_link'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $baseUrl = $scheme . $host;
            $logoUrl = $baseUrl . '/public/favicon.png';
            $safeLogoUrl = htmlspecialchars($logoUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $whenParagraph = '';
            if ($when !== '') {
                $whenParagraph = '<p style="font-size:14px; margin:0 0 10px 0;">Data e horário: <strong>' . $safeWhen . '</strong></p>';
            }

            $meetParagraph = '';
            if (!empty($live['meet_link'])) {
                $meetParagraph = '<p style="font-size:14px; margin:0 0 10px 0;">No horário da live, você poderá entrar pelo link abaixo:<br><a href="' . $safeMeetLink . '" style="color:#ff6f60; text-decoration:none;">' . $safeMeetLink . '</a></p>';
            }

            $body = <<<HTML
<html>
<body style="margin:0; padding:0; background:#050509; font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color:#f5f5f5;">
  <div style="width:100%; padding:24px 0;">
    <div style="max-width:520px; margin:0 auto; background:#111118; border-radius:16px; border:1px solid #272727; padding:18px 20px;">
      <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
        <div style="width:32px; height:32px; border-radius:50%; overflow:hidden; background:#050509; box-shadow:0 0 18px rgba(229,57,53,0.8);">
          <img src="{$safeLogoUrl}" alt="Tuquinha" style="width:100%; height:100%; display:block; object-fit:cover;">
        </div>
        <div>
          <div style="font-weight:700; font-size:15px;">Resenha 2.0 - Tuquinha</div>
          <div style="font-size:11px; color:#b0b0b0;">Branding vivo na veia</div>
        </div>
      </div>

      <p style="font-size:14px; margin:0 0 10px 0;">Oi, {$safeName} 👋</p>
      <p style="font-size:14px; margin:0 0 10px 0;">Este é um lembrete da live <strong>{$safeLiveTitle}</strong> do curso <strong>{$safeCourseTitle}</strong>.</p>
      {$whenParagraph}
      {$meetParagraph}
    </div>
  </div>
</body>
</html>
HTML;

            $sent = false;
            try {
                $sent = MailService::send($user['email'], $user['name'] ?? '', $subject, $body);
            } catch (\Throwable $e) {
            }

            if ($sent && !empty($p['id'])) {
                CourseLiveParticipant::markReminderSent((int)$p['id']);
            }
        }

        header('Location: /admin/cursos/lives?course_id=' . $courseId);
        exit;
    }
}
