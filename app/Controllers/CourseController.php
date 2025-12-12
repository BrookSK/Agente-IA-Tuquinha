<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseLesson;
use App\Models\CourseLessonComment;
use App\Models\CourseLessonProgress;
use App\Models\CourseLive;
use App\Models\CourseLiveParticipant;
use App\Models\CourseModule;
use App\Models\CourseModuleExam;
use App\Models\CourseExamQuestion;
use App\Models\CourseExamOption;
use App\Models\CourseExamAttempt;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Community;
use App\Models\CommunityMember;
use App\Models\CoursePurchase;
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

    private function userCanAccessCourseContent(array $course, ?array $user, ?array $plan, bool $isEnrolled): bool
    {
        $isAdmin = !empty($_SESSION['is_admin']);
        if ($isAdmin) {
            return true;
        }

        $planAllowsCourses = !empty($plan['allow_courses'] ?? false);
        if ($planAllowsCourses) {
            return true;
        }

        $priceCents = isset($course['price_cents']) ? (int)$course['price_cents'] : 0;
        $allowPublicPurchase = !empty($course['allow_public_purchase']);
        $isPaidFlag = !empty($course['is_paid']);

        $isPaid = $isPaidFlag && $priceCents > 0;

        // Cursos sem preço (gratuitos): para usuários comuns, exigem inscrição para liberar o conteúdo
        if (!$isPaid) {
            if (!$user || empty($user['id'])) {
                return false;
            }
            return $isEnrolled;
        }

        // Curso pago sem plano: precisa ter compra avulsa paga registrada.
        if ($allowPublicPurchase && $user && !empty($user['id']) && !empty($course['id'])) {
            $userId = (int)$user['id'];
            $courseId = (int)$course['id'];
            if (CoursePurchase::userHasPaidPurchase($userId, $courseId)) {
                return true;
            }
        }

        return false;
    }

    private function buildModulesData(int $courseId, ?array $user, bool $isEnrolled, array $lessons, array $completedLessonIds): array
    {
        $modules = CourseModule::allByCourse($courseId);

        if (empty($modules)) {
            return [
                'modules' => [],
                'unassigned_lessons' => $lessons,
            ];
        }

        $lessonsByModule = [];
        $unassignedLessons = [];
        foreach ($lessons as $lesson) {
            $mid = (int)($lesson['module_id'] ?? 0);
            if ($mid > 0) {
                if (!isset($lessonsByModule[$mid])) {
                    $lessonsByModule[$mid] = [];
                }
                $lessonsByModule[$mid][] = $lesson;
            } else {
                $unassignedLessons[] = $lesson;
            }
        }

        $userId = $user && !empty($user['id']) ? (int)$user['id'] : 0;
        $resultModules = [];
        $blocked = false;

        foreach ($modules as $module) {
            $mid = (int)($module['id'] ?? 0);
            if ($mid <= 0) {
                continue;
            }

            $moduleLessons = $lessonsByModule[$mid] ?? [];
            $totalLessons = count($moduleLessons);
            $doneLessons = 0;
            foreach ($moduleLessons as $lessonRow) {
                $lid = (int)($lessonRow['id'] ?? 0);
                if ($lid > 0 && isset($completedLessonIds[$lid])) {
                    $doneLessons++;
                }
            }
            $moduleProgressPercent = 0;
            if ($totalLessons > 0) {
                $moduleProgressPercent = (int)floor(($doneLessons / $totalLessons) * 100);
            }

            $exam = CourseModuleExam::findByModuleId($mid);
            $examId = $exam && !empty($exam['id']) ? (int)$exam['id'] : 0;
            $hasExam = $examId > 0 && !empty($exam['is_active']);

            $examAttempts = 0;
            $hasPassedExam = false;
            $lastAttempt = null;
            if ($userId > 0 && $isEnrolled && $examId > 0) {
                $examAttempts = CourseExamAttempt::countAttemptsForUser($examId, $userId);
                $hasPassedExam = CourseExamAttempt::hasPassed($examId, $userId);
                $lastAttempt = CourseExamAttempt::findLastForUser($examId, $userId);
            }

            $isLocked = ($userId > 0 && $isEnrolled) ? $blocked : false;

            $canTakeExam = false;
            $maxAttempts = $exam && isset($exam['max_attempts']) ? (int)$exam['max_attempts'] : 0;
            if ($userId > 0 && $isEnrolled && $hasExam && !$isLocked && !$hasPassedExam) {
                if ($maxAttempts <= 0 || $examAttempts < $maxAttempts) {
                    $canTakeExam = true;
                }
            }

            if ($userId > 0 && $isEnrolled && $hasExam && !$hasPassedExam) {
                $blocked = true;
            }

            $resultModules[] = [
                'module' => $module,
                'lessons' => $moduleLessons,
                'progress_percent' => $moduleProgressPercent,
                'exam' => $exam,
                'exam_attempts' => $examAttempts,
                'last_attempt' => $lastAttempt,
                'has_passed_exam' => $hasPassedExam,
                'can_take_exam' => $canTakeExam,
                'is_locked' => $isLocked,
            ];
        }

        return [
            'modules' => $resultModules,
            'unassigned_lessons' => $unassignedLessons,
        ];
    }

    private function isLessonLockedByModules(array $course, array $lesson, ?array $user): bool
    {
        $courseId = (int)($course['id'] ?? 0);
        $lessonModuleId = (int)($lesson['module_id'] ?? 0);
        if ($courseId <= 0 || $lessonModuleId <= 0) {
            return false;
        }

        if (!$user || empty($user['id'])) {
            return false;
        }
        $userId = (int)$user['id'];
        if ($userId <= 0) {
            return false;
        }

        $modules = CourseModule::allByCourse($courseId);
        if (empty($modules)) {
            return false;
        }

        $blocked = false;
        foreach ($modules as $module) {
            $mid = (int)($module['id'] ?? 0);
            if ($mid <= 0) {
                continue;
            }

            if ($mid === $lessonModuleId) {
                return $blocked;
            }

            $exam = CourseModuleExam::findByModuleId($mid);
            $examId = $exam && !empty($exam['id']) ? (int)$exam['id'] : 0;
            if ($examId > 0 && !empty($exam['is_active'])) {
                $hasPassed = CourseExamAttempt::hasPassed($examId, $userId);
                if (!$hasPassed) {
                    $blocked = true;
                }
            }
        }

        return false;
    }

    private function ensureCourseCommunityMembership(array $course, int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        $community = Community::findOrCreateForCourse($course);
        if (!$community || empty($community['id'])) {
            return;
        }

        CommunityMember::join((int)$community['id'], $userId);
    }

    private function removeCourseCommunityMembership(array $course, int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        $community = Community::findForCourse($course);
        if (!$community || empty($community['id'])) {
            return;
        }

        CommunityMember::leave((int)$community['id'], $userId);
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
        $rawLives = CourseLive::allByCourse($courseId);

        $completedLessonIds = [];
        $courseProgressPercent = 0;
        if ($user) {
            $completedLessonIds = CourseLessonProgress::completedLessonIdsByUserAndCourse($courseId, (int)$user['id']);
            $totalLessons = count($lessons);
            if ($totalLessons > 0) {
                $doneLessons = 0;
                foreach ($lessons as $lessonRow) {
                    $lid = (int)($lessonRow['id'] ?? 0);
                    if ($lid > 0 && isset($completedLessonIds[$lid])) {
                        $doneLessons++;
                    }
                }
                $courseProgressPercent = (int)floor(($doneLessons / $totalLessons) * 100);
            }
        }

        $isEnrolled = false;
        $myLiveParticipation = [];
        $hasPaidPurchase = false;
        if ($user) {
            $userId = (int)$user['id'];
            $isEnrolled = CourseEnrollment::isEnrolled($courseId, $userId);
            $myLiveParticipation = CourseLiveParticipant::liveIdsByUser($userId);
            $hasPaidPurchase = CoursePurchase::userHasPaidPurchase($userId, $courseId);
        }

        $canAccessContent = $this->userCanAccessCourseContent($course, $user, $plan, $isEnrolled);

        // Lives futuras e publicadas para o painel do curso
        $lives = [];
        if ($isEnrolled || $isAdmin) {
            $nowTs = time();
            foreach ($rawLives as $live) {
                if (empty($live['is_published'])) {
                    continue;
                }
                $scheduled = $live['scheduled_at'] ?? null;
                if (!$scheduled) {
                    continue;
                }
                $scheduledTs = strtotime((string)$scheduled);
                if ($scheduledTs === false) {
                    continue;
                }
                if ($scheduledTs >= $nowTs) {
                    $lives[] = $live;
                }
            }
        }

        $modulesData = $this->buildModulesData($courseId, $user, $isEnrolled, $lessons, $completedLessonIds);

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
            'hasPaidPurchase' => $hasPaidPurchase,
            'canAccessContent' => $canAccessContent,
            'planAllowsCourses' => $planAllowsCourses,
            'completedLessonIds' => $completedLessonIds,
            'courseProgressPercent' => $courseProgressPercent,
            'modulesData' => $modulesData['modules'] ?? [],
            'unassignedLessons' => $modulesData['unassigned_lessons'] ?? [],
            'success' => $success,
            'error' => $error,
        ]);
    }

    public function watchLesson(): void
    {
        $lessonId = isset($_GET['lesson_id']) ? (int)$_GET['lesson_id'] : 0;
        if ($lessonId <= 0) {
            header('Location: /cursos');
            exit;
        }

        $lesson = CourseLesson::findById($lessonId);
        if (!$lesson || empty($lesson['is_published'])) {
            $_SESSION['courses_error'] = 'Aula não encontrada.';
            header('Location: /cursos');
            exit;
        }

        $course = Course::findById((int)$lesson['course_id']);
        if (!$course || empty($course['is_active'])) {
            $_SESSION['courses_error'] = 'Curso desta aula não foi encontrado.';
            header('Location: /cursos');
            exit;
        }

        $courseId = (int)$course['id'];
        $isEnrolled = false;
        $user = $this->getCurrentUser();
        $plan = $this->resolvePlanForUser($user);

        $completedLessonIds = [];
        $isLessonCompleted = false;
        $hasPaidPurchase = false;
        if ($user) {
            $userId = (int)$user['id'];
            $isEnrolled = CourseEnrollment::isEnrolled($courseId, $userId);
            $completedLessonIds = CourseLessonProgress::completedLessonIdsByUserAndCourse($courseId, $userId);
            if (!empty($completedLessonIds[$lessonId])) {
                $isLessonCompleted = true;
            }
            $hasPaidPurchase = CoursePurchase::userHasPaidPurchase($userId, $courseId);
        }

        $canAccessContent = $this->userCanAccessCourseContent($course, $user, $plan, $isEnrolled);
        if (!$canAccessContent) {
            $_SESSION['courses_error'] = 'Você precisa concluir a compra deste curso ou ter um plano que libera cursos para assistir às aulas.';
            header('Location: ' . self::buildCourseUrl($course));
            exit;
        }

        // Se o usuário comprou avulso e cancelou a inscrição, ele só pode rever aulas já concluídas
        if ($user && $hasPaidPurchase && !$isEnrolled && !$isLessonCompleted) {
            $_SESSION['courses_error'] = 'Você cancelou sua inscrição neste curso. Você ainda pode rever as aulas que já concluiu, mas para assistir novas aulas precisa se inscrever novamente.';
            header('Location: ' . self::buildCourseUrl($course));
            exit;
        }

        if ($user && $isEnrolled && $this->isLessonLockedByModules($course, $lesson, $user)) {
            $_SESSION['courses_error'] = 'Este módulo está bloqueado até você passar na prova do módulo anterior.';
            header('Location: ' . self::buildCourseUrl($course));
            exit;
        }

        $lessons = CourseLesson::allByCourseId($courseId);

        // Navegação: aula anterior, próxima aula desbloqueada ou prova do módulo
        $prevUrl = null;
        $nextUrl = null;
        $nextIsExam = false;

        if ($user && $canAccessContent) {
            $countLessons = count($lessons);
            $currentIndex = null;

            for ($i = 0; $i < $countLessons; $i++) {
                $lid = (int)($lessons[$i]['id'] ?? 0);
                if ($lid === $lessonId) {
                    $currentIndex = $i;
                    break;
                }
            }

            if ($currentIndex !== null) {
                if ($currentIndex - 1 >= 0) {
                    $prevLesson = $lessons[$currentIndex - 1];
                    $prevLessonId = (int)($prevLesson['id'] ?? 0);
                    if ($prevLessonId > 0) {
                        $prevUrl = '/cursos/aulas/ver?lesson_id=' . $prevLessonId;
                    }
                }

                if ($currentIndex + 1 < $countLessons) {
                    $nextLesson = $lessons[$currentIndex + 1];
                    if (!$this->isLessonLockedByModules($course, $nextLesson, $user)) {
                        $nextLessonId = (int)($nextLesson['id'] ?? 0);
                        if ($nextLessonId > 0) {
                            $nextUrl = '/cursos/aulas/ver?lesson_id=' . $nextLessonId;
                        }
                    }
                }
            }

            // Se não houver próxima aula desbloqueada, tenta mandar para a prova do módulo atual
            $currentModuleId = (int)($lesson['module_id'] ?? 0);
            if (!$nextUrl && $currentModuleId > 0) {
                $exam = CourseModuleExam::findByModuleId($currentModuleId);
                if ($exam && !empty($exam['is_active'])) {
                    $nextUrl = '/cursos/modulos/prova?course_id=' . $courseId . '&module_id=' . $currentModuleId;
                    $nextIsExam = true;
                }
            }
        }

        $lessonComments = CourseLessonComment::allByLessonWithUser($lessonId);

        $this->view('courses/lesson_player', [
            'pageTitle' => 'Aula: ' . (string)($lesson['title'] ?? ''),
            'user' => $user,
            'course' => $course,
            'lesson' => $lesson,
            'lessons' => $lessons,
            'lessonComments' => $lessonComments,
            'isEnrolled' => $isEnrolled,
            'isLessonCompleted' => $isLessonCompleted,
            'canAccessContent' => $canAccessContent,
            'prevUrl' => $prevUrl,
            'nextUrl' => $nextUrl,
            'nextIsExam' => $nextIsExam,
        ]);
    }

    public function completeLesson(): void
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            header('Location: /login');
            exit;
        }

        $lessonId = isset($_POST['lesson_id']) ? (int)$_POST['lesson_id'] : 0;
        $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;

        if ($lessonId <= 0 || $courseId <= 0) {
            header('Location: /cursos');
            exit;
        }

        $lesson = CourseLesson::findById($lessonId);
        $course = Course::findById($courseId);

        if (
            !$lesson ||
            !$course ||
            empty($course['is_active']) ||
            empty($lesson['is_published']) ||
            (int)($lesson['course_id'] ?? 0) !== $courseId
        ) {
            $_SESSION['courses_error'] = 'Aula não encontrada para este curso.';
            header('Location: /cursos');
            exit;
        }

        $plan = $this->resolvePlanForUser($user);
        $userId = (int)$user['id'];
        $isEnrolled = CourseEnrollment::isEnrolled($courseId, $userId);

        $canAccessContent = $this->userCanAccessCourseContent($course, $user, $plan, $isEnrolled);
        if (!$canAccessContent) {
            $_SESSION['courses_error'] = 'Você precisa concluir a compra deste curso ou ter um plano que libera cursos para marcar esta aula como concluída.';
            header('Location: ' . self::buildCourseUrl($course));
            exit;
        }

        $hasPaidPurchase = CoursePurchase::userHasPaidPurchase($userId, $courseId);
        $completedLessonIds = CourseLessonProgress::completedLessonIdsByUserAndCourse($courseId, $userId);
        $isLessonAlreadyCompleted = !empty($completedLessonIds[$lessonId]);

        // Usuário que comprou avulso mas cancelou a inscrição não pode marcar novas aulas como concluídas
        if ($hasPaidPurchase && !$isEnrolled && !$isLessonAlreadyCompleted) {
            $_SESSION['courses_error'] = 'Você cancelou sua inscrição neste curso. Você ainda pode rever as aulas que já concluiu, mas para concluir novas aulas precisa se inscrever novamente.';
            header('Location: ' . self::buildCourseUrl($course));
            exit;
        }

        if ($this->isLessonLockedByModules($course, $lesson, $user)) {
            $_SESSION['courses_error'] = 'Este módulo está bloqueado até você passar na prova do módulo anterior.';
            header('Location: ' . self::buildCourseUrl($course));
            exit;
        }

        CourseLessonProgress::markCompleted($courseId, $lessonId, (int)$user['id']);

        $_SESSION['courses_success'] = 'Marcamos esta aula como concluída para você.';
        header('Location: /cursos/aulas/ver?lesson_id=' . $lessonId);
        exit;
    }

    public function moduleExam(): void
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            header('Location: /login');
            exit;
        }

        $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
        $moduleId = isset($_GET['module_id']) ? (int)$_GET['module_id'] : 0;

        $course = $courseId > 0 ? Course::findById($courseId) : null;
        if (!$course || empty($course['is_active'])) {
            $_SESSION['courses_error'] = 'Curso não encontrado.';
            header('Location: /cursos');
            exit;
        }

        $userId = (int)$user['id'];
        $isEnrolled = CourseEnrollment::isEnrolled($courseId, $userId);
        if (!$isEnrolled) {
            $_SESSION['courses_error'] = 'Você precisa estar inscrito neste curso para fazer a prova deste módulo.';
            header('Location: ' . self::buildCourseUrl($course));
            exit;
        }

        $module = $moduleId > 0 ? CourseModule::findById($moduleId) : null;
        if (!$module || (int)($module['course_id'] ?? 0) !== $courseId) {
            $_SESSION['courses_error'] = 'Módulo não encontrado neste curso.';
            header('Location: ' . self::buildCourseUrl($course));
            exit;
        }

        $dummyLesson = ['module_id' => $moduleId];
        if ($this->isLessonLockedByModules($course, $dummyLesson, $user)) {
            $_SESSION['courses_error'] = 'Este módulo está bloqueado até você passar na prova do módulo anterior.';
            header('Location: ' . self::buildCourseUrl($course));
            exit;
        }

        $exam = CourseModuleExam::findByModuleId($moduleId);
        if (!$exam || empty($exam['is_active'])) {
            $_SESSION['courses_error'] = 'Este módulo não possui uma prova ativa configurada.';
            header('Location: ' . self::buildCourseUrl($course));
            exit;
        }

        $examId = (int)$exam['id'];
        $attempts = CourseExamAttempt::countAttemptsForUser($examId, $userId);
        $maxAttempts = isset($exam['max_attempts']) ? (int)$exam['max_attempts'] : 0;
        if ($maxAttempts > 0 && $attempts >= $maxAttempts) {
            $_SESSION['courses_error'] = 'Você já atingiu o limite de tentativas para esta prova.';
            header('Location: ' . self::buildCourseUrl($course));
            exit;
        }

        if (CourseExamAttempt::hasPassed($examId, $userId)) {
            $_SESSION['courses_success'] = 'Você já foi aprovado nesta prova de módulo.';
            header('Location: ' . self::buildCourseUrl($course));
            exit;
        }

        $rawQuestions = CourseExamQuestion::allByExam($examId);
        $questions = [];
        foreach ($rawQuestions as $q) {
            $qid = (int)($q['id'] ?? 0);
            if ($qid <= 0) {
                continue;
            }
            $options = CourseExamOption::allByQuestion($qid);
            if (empty($options)) {
                continue;
            }
            $questions[] = [
                'id' => $qid,
                'text' => (string)($q['question_text'] ?? ''),
                'options' => $options,
            ];
        }

        if (empty($questions)) {
            $_SESSION['courses_error'] = 'Esta prova ainda não possui perguntas configuradas.';
            header('Location: ' . self::buildCourseUrl($course));
            exit;
        }

        $this->view('courses/module_exam', [
            'pageTitle' => 'Prova do módulo: ' . (string)($module['title'] ?? ''),
            'user' => $user,
            'course' => $course,
            'module' => $module,
            'exam' => $exam,
            'questions' => $questions,
            'attempts' => $attempts,
            'maxAttempts' => $maxAttempts,
        ]);
    }

    public function moduleExamSubmit(): void
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            header('Location: /login');
            exit;
        }

        $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
        $moduleId = isset($_POST['module_id']) ? (int)$_POST['module_id'] : 0;

        $course = $courseId > 0 ? Course::findById($courseId) : null;
        if (!$course || empty($course['is_active'])) {
            $_SESSION['courses_error'] = 'Curso não encontrado.';
            header('Location: /cursos');
            exit;
        }

        $userId = (int)$user['id'];
        $isEnrolled = CourseEnrollment::isEnrolled($courseId, $userId);
        if (!$isEnrolled) {
            $_SESSION['courses_error'] = 'Você precisa estar inscrito neste curso para fazer a prova deste módulo.';
            header('Location: ' . self::buildCourseUrl($course));
            exit;
        }

        $module = $moduleId > 0 ? CourseModule::findById($moduleId) : null;
        if (!$module || (int)($module['course_id'] ?? 0) !== $courseId) {
            $_SESSION['courses_error'] = 'Módulo não encontrado neste curso.';
            header('Location: ' . self::buildCourseUrl($course));
            exit;
        }

        $dummyLesson = ['module_id' => $moduleId];
        if ($this->isLessonLockedByModules($course, $dummyLesson, $user)) {
            $_SESSION['courses_error'] = 'Este módulo está bloqueado até você passar na prova do módulo anterior.';
            header('Location: ' . self::buildCourseUrl($course));
            exit;
        }

        $exam = CourseModuleExam::findByModuleId($moduleId);
        if (!$exam || empty($exam['is_active'])) {
            $_SESSION['courses_error'] = 'Este módulo não possui uma prova ativa configurada.';
            header('Location: ' . self::buildCourseUrl($course));
            exit;
        }

        $examId = (int)$exam['id'];
        $attemptsBefore = CourseExamAttempt::countAttemptsForUser($examId, $userId);
        $maxAttempts = isset($exam['max_attempts']) ? (int)$exam['max_attempts'] : 0;
        if ($maxAttempts > 0 && $attemptsBefore >= $maxAttempts) {
            $_SESSION['courses_error'] = 'Você já atingiu o limite de tentativas para esta prova.';
            header('Location: ' . self::buildCourseUrl($course));
            exit;
        }

        $answers = $_POST['answers'] ?? [];

        $rawQuestions = CourseExamQuestion::allByExam($examId);
        $totalQuestions = 0;
        $correctAnswers = 0;

        foreach ($rawQuestions as $q) {
            $qid = (int)($q['id'] ?? 0);
            if ($qid <= 0) {
                continue;
            }

            $options = CourseExamOption::allByQuestion($qid);
            if (empty($options)) {
                continue;
            }

            $totalQuestions++;

            $selectedOptionId = isset($answers[$qid]) ? (int)$answers[$qid] : 0;
            if ($selectedOptionId <= 0) {
                continue;
            }

            foreach ($options as $opt) {
                if ((int)($opt['id'] ?? 0) === $selectedOptionId && !empty($opt['is_correct'])) {
                    $correctAnswers++;
                    break;
                }
            }
        }

        if ($totalQuestions === 0) {
            $_SESSION['courses_error'] = 'Esta prova ainda não possui perguntas configuradas.';
            header('Location: ' . self::buildCourseUrl($course));
            exit;
        }

        $scorePercent = (int)floor(($correctAnswers / $totalQuestions) * 100);
        $passScore = isset($exam['pass_score_percent']) ? (int)$exam['pass_score_percent'] : 0;
        $isPassed = $scorePercent >= $passScore;

        CourseExamAttempt::create([
            'exam_id' => $examId,
            'user_id' => $userId,
            'score_percent' => $scorePercent,
            'is_passed' => $isPassed ? 1 : 0,
        ]);

        $attemptsAfter = $attemptsBefore + 1;

        if ($isPassed) {
            $_SESSION['courses_success'] = 'Você foi aprovado na prova deste módulo com ' . $scorePercent . "%.";
        } else {
            $message = 'Você não atingiu a nota mínima nesta prova. Sua nota foi ' . $scorePercent . '% (mínimo ' . $passScore . '%).';
            if ($maxAttempts > 0 && $attemptsAfter >= $maxAttempts) {
                CourseLessonProgress::clearByCourseModuleAndUser($courseId, $moduleId, $userId);
                CourseExamAttempt::resetAttemptsForUser($examId, $userId);
                $message .= ' Você atingiu o limite de tentativas para este módulo. Para tentar novamente, refaça todas as aulas deste módulo; elas foram marcadas como não concluídas para você.';
            }
            $_SESSION['courses_error'] = $message;
        }

        header('Location: ' . self::buildCourseUrl($course));
        exit;
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
        $priceCents = isset($course['price_cents']) ? (int)$course['price_cents'] : 0;
        $isPaid = !empty($course['is_paid']) && $priceCents > 0;

        // Admins e assinantes de planos com cursos sempre podem se inscrever
        if (!$isAdmin && !$planAllowsCourses) {
            // Usuário sem plano que libera cursos
            if (!$allowPublicPurchase) {
                $_SESSION['courses_error'] = 'Seu plano atual não permite inscrição neste curso.';
                header('Location: /planos');
                exit;
            }

            // Curso pago com compra avulsa liberada: só redireciona para compra se ainda não houver compra paga
            if ($isPaid) {
                $userIdForPurchase = (int)$user['id'];
                if (!CoursePurchase::userHasPaidPurchase($userIdForPurchase, $courseId)) {
                    header('Location: /cursos/comprar?course_id=' . $courseId);
                    exit;
                }
                // Se já houver compra paga, segue para inscrição normal sem novo pagamento
            }
            // Curso gratuito com visibilidade pública: segue para inscrição normal
        }

        $userId = (int)$user['id'];
        CourseEnrollment::enroll($courseId, $userId);
        $this->ensureCourseCommunityMembership($course, $userId);

        $subject = 'Inscrição confirmada no curso: ' . (string)($course['title'] ?? 'Curso do Tuquinha');

        $coursePath = self::buildCourseUrl($course);
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $courseUrl = $scheme . $host . $coursePath;
        $logoUrl = $scheme . $host . '/public/favicon.png';
        $safeName = htmlspecialchars($user['name'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeCourseTitle = htmlspecialchars($course['title'] ?? 'Curso do Tuquinha', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeCourseUrl = htmlspecialchars($courseUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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

    public function unenroll(): void
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            header('Location: /login');
            exit;
        }

        $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
        $course = $courseId > 0 ? Course::findById($courseId) : null;
        if (!$course || empty($course['is_active'])) {
            $_SESSION['courses_error'] = 'Curso não encontrado.';
            header('Location: /cursos');
            exit;
        }

        $userId = (int)$user['id'];
        if (!CourseEnrollment::isEnrolled($courseId, $userId)) {
            $_SESSION['courses_error'] = 'Você não está inscrito neste curso.';
            header('Location: ' . self::buildCourseUrl($course));
            exit;
        }

        CourseEnrollment::unenroll($courseId, $userId);
        $this->removeCourseCommunityMembership($course, $userId);

        $subject = 'Inscrição cancelada no curso: ' . (string)($course['title'] ?? 'Curso do Tuquinha');

        $coursePath = self::buildCourseUrl($course);
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $courseUrl = $scheme . $host . $coursePath;
        $logoUrl = $scheme . $host . '/public/favicon.png';
        $safeName = htmlspecialchars($user['name'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeCourseTitle = htmlspecialchars($course['title'] ?? 'Curso do Tuquinha', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeCourseUrl = htmlspecialchars($courseUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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
          <div style="font-weight:700; font-size:15px;">Agente IA - Tuquinha</div>
          <div style="font-size:11px; color:#b0b0b0;">Branding vivo na veia</div>
        </div>
      </div>

      <p style="font-size:14px; margin:0 0 10px 0;">Oi, {$safeName} 👋</p>
      <p style="font-size:14px; margin:0 0 10px 0;">Sua inscrição no curso <strong>{$safeCourseTitle}</strong> foi cancelada.</p>
      <p style="font-size:14px; margin:0 0 10px 0;">Você não receberá mais e-mails sobre novas aulas e lives deste curso. Se mudar de ideia, é só acessar a página do curso e se inscrever novamente.</p>

      <div style="text-align:center; margin:14px 0 8px 0;">
        <a href="{$safeCourseUrl}" style="display:inline-block; padding:9px 18px; border-radius:999px; background:#222230; color:#f5f5f5; font-weight:600; font-size:13px; text-decoration:none; border:1px solid #444;">Ver curso</a>
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

        $_SESSION['courses_success'] = 'Sua inscrição neste curso foi cancelada.';
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
        $userId = (int)$user['id'];
        CourseEnrollment::enroll($courseId, $userId);
        $this->ensureCourseCommunityMembership($course, $userId);

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
        $logoUrl = $scheme . $host . '/public/favicon.png';
        $safeName = htmlspecialchars($user['name'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeCourseTitle = htmlspecialchars($course['title'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeLiveTitle = htmlspecialchars($live['title'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeCourseUrl = htmlspecialchars($courseUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeMeetLink = htmlspecialchars($meetLink, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeLogoUrl = htmlspecialchars($logoUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

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
        <div style="width:32px; height:32px; border-radius:50%; overflow:hidden; background:#050509; box-shadow:0 0 18px rgba(229,57,53,0.8);">
          <img src="{$safeLogoUrl}" alt="Tuquinha" style="width:100%; height:100%; display:block; object-fit:cover;">
        </div>
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

    public function watchLive(): void
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            header('Location: /login');
            exit;
        }

        $liveId = isset($_GET['live_id']) ? (int)$_GET['live_id'] : 0;
        if ($liveId <= 0) {
            $_SESSION['courses_error'] = 'Live não encontrada.';
            header('Location: /cursos');
            exit;
        }

        $live = CourseLive::findById($liveId);
        if (!$live) {
            $_SESSION['courses_error'] = 'Live não encontrada.';
            header('Location: /cursos');
            exit;
        }

        $course = Course::findById((int)$live['course_id']);
        if (!$course || empty($course['is_active'])) {
            $_SESSION['courses_error'] = 'Curso desta live não foi encontrado.';
            header('Location: /cursos');
            exit;
        }

        $courseId = (int)$course['id'];
        $userId = (int)$user['id'];

        // Garante que o usuário esteja inscrito no curso para assistir à live/gravação
        $isEnrolled = CourseEnrollment::isEnrolled($courseId, $userId);
        if (!$isEnrolled) {
            $_SESSION['courses_error'] = 'Você precisa estar inscrito neste curso para acessar esta live.';
            header('Location: ' . self::buildCourseUrl($course));
            exit;
        }

        // Gravação disponível apenas para quem participou da live
        if (!CourseLiveParticipant::isParticipant($liveId, $userId)) {
            $_SESSION['courses_error'] = 'A gravação desta live está disponível apenas para quem participou.';
            header('Location: ' . self::buildCourseUrl($course) . '#lives');
            exit;
        }

        $recordingLink = trim((string)($live['recording_link'] ?? ''));
        if ($recordingLink === '') {
            $_SESSION['courses_error'] = 'Esta live ainda não possui gravação disponível.';
            header('Location: ' . self::buildCourseUrl($course) . '#lives');
            exit;
        }

        // Carrega todas as lives do curso para a coluna lateral
        $lives = CourseLive::allByCourse($courseId);

        // Comentários desta live
        $liveComments = CourseLessonComment::allByLiveWithUser($liveId);

        $this->view('courses/live_player', [
            'pageTitle' => 'Live: ' . (string)($live['title'] ?? ''),
            'user' => $user,
            'course' => $course,
            'live' => $live,
            'lives' => $lives,
            'liveComments' => $liveComments,
        ]);
    }

    public function lives(): void
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            header('Location: /login');
            exit;
        }

        $plan = $this->resolvePlanForUser($user);
        $isAdmin = !empty($_SESSION['is_admin']);
        $planAllowsCourses = !empty($plan['allow_courses']);

        $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
        $course = $courseId > 0 ? Course::findById($courseId) : null;
        if (!$course || empty($course['is_active'])) {
            $_SESSION['courses_error'] = 'Curso não encontrado.';
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
        $userId = (int)$user['id'];
        $isEnrolled = CourseEnrollment::isEnrolled($courseId, $userId);
        if (!$isEnrolled && !$isAdmin) {
            $_SESSION['courses_error'] = 'Você precisa estar inscrito neste curso para ver as lives.';
            header('Location: ' . self::buildCourseUrl($course));
            exit;
        }

        $allLives = CourseLive::allByCourse($courseId);
        $lives = [];
        foreach ($allLives as $live) {
            if (!empty($live['is_published'])) {
                $lives[] = $live;
            }
        }

        $myLiveParticipation = CourseLiveParticipant::liveIdsByUser($userId);

        $success = $_SESSION['courses_success'] ?? null;
        $error = $_SESSION['courses_error'] ?? null;
        unset($_SESSION['courses_success'], $_SESSION['courses_error']);

        $this->view('courses/lives', [
            'pageTitle' => 'Lives do curso: ' . (string)($course['title'] ?? ''),
            'user' => $user,
            'plan' => $plan,
            'course' => $course,
            'lives' => $lives,
            'myLiveParticipation' => $myLiveParticipation,
            'isEnrolled' => $isEnrolled || $isAdmin,
            'success' => $success,
            'error' => $error,
        ]);
    }

    public function commentLive(): void
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            header('Location: /login');
            exit;
        }

        $liveId = isset($_POST['live_id']) ? (int)$_POST['live_id'] : 0;
        $body = trim((string)($_POST['body'] ?? ''));
        $parentId = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0;

        if ($liveId <= 0) {
            $_SESSION['courses_error'] = 'Live inválida para comentar.';
            header('Location: /cursos');
            exit;
        }

        $live = CourseLive::findById($liveId);
        if (!$live) {
            $_SESSION['courses_error'] = 'Live não encontrada.';
            header('Location: /cursos');
            exit;
        }

        $course = Course::findById((int)$live['course_id']);
        if (!$course || empty($course['is_active'])) {
            $_SESSION['courses_error'] = 'Curso desta live não foi encontrado.';
            header('Location: /cursos');
            exit;
        }

        $courseId = (int)$course['id'];
        $userId = (int)$user['id'];

        if ($body === '') {
            $_SESSION['courses_error'] = 'Escreva um comentário antes de enviar.';
            header('Location: /cursos/lives/ver?live_id=' . $liveId);
            exit;
        }

        if (strlen($body) > 2000) {
            $_SESSION['courses_error'] = 'O comentário pode ter no máximo 2000 caracteres.';
            header('Location: /cursos/lives/ver?live_id=' . $liveId);
            exit;
        }

        // Permissões: precisa estar inscrito no curso e ter participado da live
        $isEnrolled = CourseEnrollment::isEnrolled($courseId, $userId);
        $isParticipant = CourseLiveParticipant::isParticipant($liveId, $userId);
        if (!$isEnrolled || !$isParticipant) {
            $_SESSION['courses_error'] = 'Apenas participantes desta live podem comentar aqui.';
            header('Location: ' . self::buildCourseUrl($course) . '#lives');
            exit;
        }

        $parentCommentId = null;
        if ($parentId > 0) {
            $parent = CourseLessonComment::findById($parentId);
            if ($parent && (int)($parent['live_id'] ?? 0) === $liveId) {
                $parentCommentId = $parentId;
            }
        }

        CourseLessonComment::create([
            'course_id' => $courseId,
            'lesson_id' => null,
            'live_id' => $liveId,
            'user_id' => $userId,
            'parent_id' => $parentCommentId,
            'body' => $body,
        ]);

        $_SESSION['courses_success'] = 'Comentário enviado com sucesso.';
        header('Location: /cursos/lives/ver?live_id=' . $liveId);
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
