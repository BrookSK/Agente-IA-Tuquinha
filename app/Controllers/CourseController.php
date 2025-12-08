<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseLesson;
use App\Models\CourseLessonComment;
use App\Models\CourseLive;
use App\Models\CourseLiveParticipant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\MailService;
use App\Services\GoogleCalendarService;

class CourseController extends Controller
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

    private function resolvePlanForUser(?array $user): ?array
    {
        $plan = null;
        if ($user && !empty($user['email'])) {
            $sub = Subscription::findLastByEmail($user['email']);
            if ($sub && !empty($sub['plan_id'])) {
                $plan = Plan::findById((int)$sub['plan_id']);
            }
        }
        if (!$plan) {
            $plan = Plan::findBySessionSlug($_SESSION['plan_slug'] ?? null) ?: Plan::findBySlug('free');
        }
        return $plan;
    }

    public function index(): void
    {
        $user = $this->getCurrentUser();
        $plan = $this->resolvePlanForUser($user);
        $isAdmin = !empty($_SESSION['is_admin']);
        $planAllowsCourses = !empty($plan['allow_courses']);

        $courses = Course::allActive();

        $enrolledIds = [];
        if ($user) {
            foreach (CourseEnrollment::allByUser((int)$user['id']) as $en) {
                $enrolledIds[(int)$en['course_id']] = true;
            }
        }

        $visibleCourses = [];
        foreach ($courses as $course) {
            $cid = (int)($course['id'] ?? 0);
            $allowPlanOnly = !empty($course['allow_plan_access_only']);
            $allowPublicPurchase = !empty($course['allow_public_purchase']);

            $canSee = $isAdmin || $planAllowsCourses || $allowPublicPurchase;
            if (!$canSee) {
                continue;
            }

            $course['is_enrolled'] = isset($enrolledIds[$cid]);
            $course['can_access_by_plan'] = $planAllowsCourses || $isAdmin;
            $course['allow_public_purchase'] = $allowPublicPurchase;
            $course['allow_plan_access_only'] = $allowPlanOnly;
            $visibleCourses[] = $course;
        }

        $success = $_SESSION['courses_success'] ?? null;
        $error = $_SESSION['courses_error'] ?? null;
        unset($_SESSION['courses_success'], $_SESSION['courses_error']);

        $this->view('courses/index', [
            'pageTitle' => 'Cursos do Tuquinha',
            'user' => $user,
            'plan' => $plan,
            'courses' => $visibleCourses,
            'success' => $success,
            'error' => $error,
        ]);
    }

    public function show(): void
    {
        $user = $this->getCurrentUser();
        $plan = $this->resolvePlanForUser($user);
        $isAdmin = !empty($_SESSION['is_admin']);
        $planAllowsCourses = !empty($plan['allow_courses']);

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $slug = isset($_GET['slug']) ? trim((string)$_GET['slug']) : '';

        $course = null;
        if ($id > 0) {
            $course = Course::findById($id);
        } elseif ($slug !== '') {
            $course = Course::findBySlug($slug);
        }

        if (!$course || empty($course['is_active'])) {
            header('Location: /cursos');
            exit;
        }

        $allowPlanOnly = !empty($course['allow_plan_access_only']);
        $allowPublicPurchase = !empty($course['allow_public_purchase']);

        $canSee = $isAdmin || $planAllowsCourses || $allowPublicPurchase;
        if (!$canSee) {
            header('Location: /planos');
            exit;
        }

        $courseId = (int)$course['id'];
        $lessons = CourseLesson::allByCourseId($courseId);
        $lives = CourseLive::allByCourse($courseId);

        $isEnrolled = false;
        $myLiveParticipation = [];
        if ($user) {
            $userId = (int)$user['id'];
            $isEnrolled = CourseEnrollment::isEnrolled($courseId, $userId);
            $myLiveParticipation = CourseLiveParticipant::liveIdsByUser($userId);
        }

        $commentsByLesson = [];
        $lessonComments = CourseLessonComment::allByCourseWithUser($courseId);
        foreach ($lessonComments as $comment) {
            $lid = (int)($comment['lesson_id'] ?? 0);
            if ($lid <= 0) {
                continue;
            }
            if (!isset($commentsByLesson[$lid])) {
                $commentsByLesson[$lid] = [];
            }
            $commentsByLesson[$lid][] = $comment;
        }

        $success = $_SESSION['courses_success'] ?? null;
        $error = $_SESSION['courses_error'] ?? null;
        unset($_SESSION['courses_success'], $_SESSION['courses_error']);

        $this->view('courses/show', [
            'pageTitle' => 'Curso: ' . (string)($course['title'] ?? ''),
            'user' => $user,
            'plan' => $plan,
            'course' => $course,
            'lessons' => $lessons,
            'lives' => $lives,
            'commentsByLesson' => $commentsByLesson,
            'isEnrolled' => $isEnrolled,
            'myLiveParticipation' => $myLiveParticipation,
            'planAllowsCourses' => $planAllowsCourses,
            'success' => $success,
            'error' => $error,
        ]);
    }

    public function enroll(): void
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            header('Location: /login');
            exit;
        }

        $plan = $this->resolvePlanForUser($user);
        $isAdmin = !empty($_SESSION['is_admin']);
        $planAllowsCourses = !empty($plan['allow_courses']);

        $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
        $course = $courseId > 0 ? Course::findById($courseId) : null;
        if (!$course || empty($course['is_active'])) {
            $_SESSION['courses_error'] = 'Curso não encontrado.';
            header('Location: /cursos');
            exit;
        }

        $allowPlanOnly = !empty($course['allow_plan_access_only']);
        $allowPublicPurchase = !empty($course['allow_public_purchase']);
        $isPaid = !empty($course['is_paid']);

        // Admins e assinantes de planos com cursos sempre podem se inscrever
        if (!$isAdmin && !$planAllowsCourses) {
            // Usuário sem plano que libera cursos
            if (!$allowPublicPurchase) {
                $_SESSION['courses_error'] = 'Seu plano atual não permite inscrição neste curso.';
                header('Location: /planos');
                exit;
            }

            // Curso pago com compra avulsa liberada: redireciona para fluxo de compra
            if ($isPaid) {
                header('Location: /cursos/comprar?course_id=' . $courseId);
                exit;
            }
            // Curso gratuito com visibilidade pública: segue para inscrição normal
        }

        CourseEnrollment::enroll($courseId, (int)$user['id']);

        $subject = 'Inscrição confirmada no curso: ' . (string)($course['title'] ?? 'Curso do Tuquinha');

        $coursePath = self::buildCourseUrl($course);
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $courseUrl = $scheme . $host . $coursePath;
        $safeName = htmlspecialchars($user['name'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeCourseTitle = htmlspecialchars($course['title'] ?? 'Curso do Tuquinha', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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
      <p style="font-size:14px; margin:0 0 10px 0;">Sua inscrição no curso <strong>{$safeCourseTitle}</strong> foi confirmada com sucesso.</p>
      <p style="font-size:14px; margin:0 0 10px 0;">À medida que novas aulas forem liberadas ou lives forem agendadas para este curso, você receberá avisos por e-mail.</p>

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
            if (!empty($user['email'])) {
                MailService::send($user['email'], $user['name'] ?? '', $subject, $body);
            }
        } catch (\Throwable $e) {
        }

        $_SESSION['courses_success'] = 'Inscrição realizada com sucesso.';
        header('Location: ' . self::buildCourseUrl($course));
        exit;
    }

    public function joinLive(): void
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            header('Location: /login');
            exit;
        }

        $liveId = isset($_POST['live_id']) ? (int)$_POST['live_id'] : 0;
        $live = $liveId > 0 ? CourseLive::findById($liveId) : null;
        if (!$live) {
            $_SESSION['courses_error'] = 'Live não encontrada.';
            header('Location: /cursos');
            exit;
        }

        $course = Course::findById((int)$live['course_id']);
        if (!$course) {
            $_SESSION['courses_error'] = 'Curso desta live não foi encontrado.';
            header('Location: /cursos');
            exit;
        }

        $courseId = (int)$course['id'];
        CourseEnrollment::enroll($courseId, (int)$user['id']);

        $alreadyParticipant = CourseLiveParticipant::isParticipant($liveId, (int)$user['id']);
        if ($alreadyParticipant) {
            $_SESSION['courses_success'] = 'Sua participação nesta live já está confirmada.';
            header('Location: ' . self::buildCourseUrl($course) . '#lives');
            exit;
        }

        CourseLiveParticipant::addParticipant($liveId, (int)$user['id']);

        $googleEventId = (string)($live['google_event_id'] ?? '');
        if ($googleEventId !== '' && !empty($user['email'])) {
            try {
                $googleService = new GoogleCalendarService();
                if ($googleService->isConfigured()) {
                    $googleService->addAttendeeToEvent($googleEventId, (string)$user['email'], (string)($user['name'] ?? ''));
                }
            } catch (\Throwable $e) {
            }
        }

        $meetLink = (string)($live['meet_link'] ?? '');

        $subject = 'Confirmação de participação na live do curso: ' . (string)($course['title'] ?? '');

        $coursePath = self::buildCourseUrl($course);
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $courseUrl = $scheme . $host . $coursePath;
        $safeName = htmlspecialchars($user['name'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeCourseTitle = htmlspecialchars($course['title'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeLiveTitle = htmlspecialchars($live['title'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeCourseUrl = htmlspecialchars($courseUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeMeetLink = htmlspecialchars($meetLink, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $when = '';
        if (!empty($live['scheduled_at'])) {
            $when = date('d/m/Y H:i', strtotime((string)$live['scheduled_at']));
        }
        $safeWhen = htmlspecialchars($when, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $meetParagraph = '';
        if ($meetLink !== '') {
            $meetParagraph = '<p style="font-size:14px; margin:0 0 10px 0;">No dia e horário da live, você poderá entrar pelo link abaixo:<br><a href="' . $safeMeetLink . '" style="color:#ff6f60; text-decoration:none;">' . $safeMeetLink . '</a></p>';
        }

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
        <div style="width:32px; height:32px; line-height:32px; border-radius:50%; background:radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%); text-align:center; font-weight:700; font-size:16px; color:#050509;">T</div>
        <div>
          <div style="font-weight:700; font-size:15px;">Agente IA - Tuquinha</div>
          <div style="font-size:11px; color:#b0b0b0;">Branding vivo na veia</div>
        </div>
      </div>

      <p style="font-size:14px; margin:0 0 10px 0;">Oi, {$safeName} 👋</p>
      <p style="font-size:14px; margin:0 0 10px 0;">Sua participação na live <strong>{$safeLiveTitle}</strong> do curso <strong>{$safeCourseTitle}</strong> foi confirmada.</p>
      {$whenParagraph}
      {$meetParagraph}

      <div style="text-align:center; margin:14px 0 8px 0;">
        <a href="{$safeCourseUrl}#lives" style="display:inline-block; padding:9px 18px; border-radius:999px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:600; font-size:13px; text-decoration:none;">Ver detalhes da live</a>
      </div>

      <p style="font-size:12px; color:#777; margin:8px 0 0 0;">Se o botão não funcionar, copie e cole este link no navegador:<br>
        <a href="{$safeCourseUrl}#lives" style="color:#ff6f60; text-decoration:none;">{$safeCourseUrl}#lives</a>
      </p>
    </div>
  </div>
</body>
</html>
HTML;

        try {
            if (!empty($user['email'])) {
                MailService::send($user['email'], $user['name'] ?? '', $subject, $body);
            }
        } catch (\Throwable $e) {
        }

        $_SESSION['courses_success'] = 'Sua participação na live foi registrada. Você receberá um e-mail com os detalhes.';
        header('Location: ' . self::buildCourseUrl($course) . '#lives');
        exit;
    }

    public function commentLesson(): void
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            header('Location: /login');
            exit;
        }

        $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
        $lessonId = isset($_POST['lesson_id']) ? (int)$_POST['lesson_id'] : 0;
        $body = trim((string)($_POST['body'] ?? ''));
        $parentId = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0;

        if ($courseId <= 0 || $lessonId <= 0) {
            $_SESSION['courses_error'] = 'Aula ou curso inválido para comentar.';
            header('Location: /cursos');
            exit;
        }

        if ($body === '') {
            $_SESSION['courses_error'] = 'Escreva um comentário antes de enviar.';
            $course = Course::findById($courseId);
            $target = $course ? self::buildCourseUrl($course) : '/cursos';
            header('Location: ' . $target);
            exit;
        }

        if (strlen($body) > 2000) {
            $_SESSION['courses_error'] = 'O comentário pode ter no máximo 2000 caracteres.';
            $course = Course::findById($courseId);
            $target = $course ? self::buildCourseUrl($course) : '/cursos';
            header('Location: ' . $target);
            exit;
        }

        $course = Course::findById($courseId);
        if (!$course || empty($course['is_active'])) {
            $_SESSION['courses_error'] = 'Curso não encontrado.';
            header('Location: /cursos');
            exit;
        }

        $lesson = CourseLesson::findById($lessonId);
        if (!$lesson || (int)($lesson['course_id'] ?? 0) !== $courseId || empty($lesson['is_published'])) {
            $_SESSION['courses_error'] = 'Aula não encontrada para este curso.';
            header('Location: ' . self::buildCourseUrl($course));
            exit;
        }

        $isAdmin = !empty($_SESSION['is_admin']);
        $isOwner = !empty($course['owner_user_id']) && (int)$course['owner_user_id'] === (int)$user['id'];
        $isEnrolled = CourseEnrollment::isEnrolled($courseId, (int)$user['id']);

        if (!$isAdmin && !$isOwner && !$isEnrolled) {
            $_SESSION['courses_error'] = 'Você precisa estar inscrito neste curso para comentar.';
            header('Location: ' . self::buildCourseUrl($course));
            exit;
        }

        $parentCommentId = null;
        if ($parentId > 0) {
            $parent = CourseLessonComment::findById($parentId);
            if ($parent && (int)($parent['lesson_id'] ?? 0) === $lessonId) {
                $parentCommentId = $parentId;
            }
        }

        CourseLessonComment::create([
            'course_id' => $courseId,
            'lesson_id' => $lessonId,
            'user_id' => (int)$user['id'],
            'parent_id' => $parentCommentId,
            'body' => $body,
        ]);

        $_SESSION['courses_success'] = 'Comentário enviado com sucesso.';
        $target = self::buildCourseUrl($course) . '#lesson-' . $lessonId;
        header('Location: ' . $target);
        exit;
    }

    public static function buildCourseUrl(array $course): string
    {
        if (!empty($course['slug'])) {
            return '/cursos/ver?slug=' . urlencode((string)$course['slug']);
        }
        return '/cursos/ver?id=' . (int)($course['id'] ?? 0);
    }
}
