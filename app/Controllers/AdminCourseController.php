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
use App\Models\User;
use App\Services\MailService;

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
        $partnerEmail = trim($_POST['partner_email'] ?? '');
        $isPaid = !empty($_POST['is_paid']) ? 1 : 0;
        $priceRaw = trim($_POST['price'] ?? '0');
        $partnerCommissionRaw = trim($_POST['partner_commission_percent'] ?? '');
        $allowPlanAccessOnly = !empty($_POST['allow_plan_access_only']) ? 1 : 0;
        $allowPublicPurchase = !empty($_POST['allow_public_purchase']) ? 1 : 0;
        $isActive = !empty($_POST['is_active']) ? 1 : 0;

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

        $data = [
            'owner_user_id' => $ownerUserId ?: null,
            'title' => $title,
            'slug' => $slug,
            'short_description' => $shortDescription !== '' ? $shortDescription : null,
            'description' => $description !== '' ? $description : null,
            'image_path' => $imagePath !== '' ? $imagePath : null,
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

        $this->view('admin/cursos/lesson_form', [
            'pageTitle' => $lesson ? 'Editar aula' : 'Nova aula',
            'course' => $course,
            'lesson' => $lesson,
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

        if ($title === '' || $videoUrl === '') {
            $_SESSION['admin_course_error'] = 'Preencha pelo menos título e link do vídeo.';
            $target = '/admin/cursos/aulas/nova?course_id=' . $courseId;
            if ($id > 0) {
                $target = '/admin/cursos/aulas/editar?course_id=' . $courseId . '&id=' . $id;
            }
            header('Location: ' . $target);
            exit;
        }

        $data = [
            'course_id' => $courseId,
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

                $body = <<<HTML
<html>
<body style="margin:0; padding:0; background:#050509; font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color:#f5f5f5;">
  <div style="width:100%; padding:24px 0;">
    <div style="max-width:520px; margin:0 auto; background:#111118; border-radius:16px; border:1px solid #272727; padding:18px 20px;">
      <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
        <div style="width:32px; height:32px; line-height:32px; border-radius:50%; background:radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%); text-align:center; font-weight:700; font-size:16px; color:#050509;">T</div>
        <div>
          <div style="font-weight:700; font-size:15px;">Agente IA - Tuquinha</div>
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

        if ($meetLink === '') {
            $meetLink = 'https://meet.google.com/' . substr(bin2hex(random_bytes(6)), 0, 10);
        }

        $data = [
            'course_id' => $courseId,
            'title' => $title,
            'description' => $description !== '' ? $description : null,
            'scheduled_at' => $scheduledAt,
            'meet_link' => $meetLink,
            'recording_link' => $recordingLink !== '' ? $recordingLink : null,
            'recording_published_at' => null,
            'google_event_id' => null,
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
                    $safeMeetLink = htmlspecialchars($meetLink, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                    $whenParagraph = '';
                    if ($when !== '') {
                        $whenParagraph = '<p style="font-size:14px; margin:0 0 10px 0;">Data e horário: <strong>' . $safeWhen . '</strong></p>';
                    }

                    $meetParagraph = '';
                    if ($meetLink !== '') {
                        $meetParagraph = '<p style="font-size:14px; margin:0 0 10px 0;">No dia e horário da live, você poderá entrar pelo link abaixo:<br><a href="' . $safeMeetLink . '" style="color:#ff6f60; text-decoration:none;">' . $safeMeetLink . '</a></p>';
                    }

                    $body = <<<HTML
<html>
<body style="margin:0; padding:0; background:#050509; font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color:#f5f5f5;">
  <div style="width:100%; padding:24px 0;">
    <div style="max-width:520px; margin:0 auto; background:#111118; border-radius:16px; border:1px solid #272727; padding:18px 20px;">
      <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
        <div style="width:32px; height:32px; line-height:32px; border-radius:50%; background:radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%); text-align:center; font-weight:700; font-size:16px; color:#050509;">T</div>
        <div>
          <div style="font-weight:700; font-size:15px;">Agente IA - Tuquinha</div>
          <div style="font-size:11px; color:#b0b0b0;">Branding vivo na veia</div>
        </div>
      </div>

      <p style="font-size:14px; margin:0 0 10px 0;">Oi, {$safeName} 👋</p>
      <p style="font-size:14px; margin:0 0 10px 0;">Uma nova live foi agendada para o curso <strong>{$safeCourseTitle}</strong>.</p>
      {$whenParagraph}
      {$meetParagraph}
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

        foreach ($participants as $p) {
            $user = User::findById((int)$p['user_id']);
            if (!$user || empty($user['email'])) {
                continue;
            }

            $subject = 'Gravação disponível: live do curso ' . (string)($course['title'] ?? '');
            $safeName = htmlspecialchars($user['name'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeCourseTitle = htmlspecialchars($course['title'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeLiveTitle = htmlspecialchars($live['title'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeWhen = htmlspecialchars($when, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeRecordingLink = htmlspecialchars($recordingLink, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

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
        <div style="width:32px; height:32px; line-height:32px; border-radius:50%; background:radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%); text-align:center; font-weight:700; font-size:16px; color:#050509;">T</div>
        <div>
          <div style="font-weight:700; font-size:15px;">Agente IA - Tuquinha</div>
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
        <div style="width:32px; height:32px; line-height:32px; border-radius:50%; background:radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%); text-align:center; font-weight:700; font-size:16px; color:#050509;">T</div>
        <div>
          <div style="font-weight:700; font-size:15px;">Agente IA - Tuquinha</div>
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
