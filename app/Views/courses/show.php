<?php
/** @var array|null $user */
/** @var array|null $plan */
/** @var array $course */
/** @var array $lessons */
/** @var array $lives */
/** @var array $commentsByLesson */
/** @var bool $isEnrolled */
/** @var bool $planAllowsCourses */
/** @var bool $canAccessContent */
/** @var string|null $success */
/** @var string|null $error */

use App\Controllers\CourseController;

$title = trim((string)($course['title'] ?? ''));
$short = trim((string)($course['short_description'] ?? ''));
$description = trim((string)($course['description'] ?? ''));
$image = trim((string)($course['image_path'] ?? ''));
$isPaid = !empty($course['is_paid']);
$priceCents = isset($course['price_cents']) ? (int)$course['price_cents'] : 0;
$allowPlanOnly = !empty($course['allow_plan_access_only']);
$allowPublicPurchase = !empty($course['allow_public_purchase']);
$courseUrl = CourseController::buildCourseUrl($course);

$completedLessonIds = $completedLessonIds ?? [];
$modulesData = $modulesData ?? [];
$hasPaidPurchase = $hasPaidPurchase ?? false;

$startLessonId = null;
$startLessonLabel = 'Começar curso';
$currentLessonId = null; // próxima aula recomendada para continuar
$hasCompletedAnyLesson = !empty($completedLessonIds);
$firstCompletedLessonId = null;

if (!empty($completedLessonIds)) {
    foreach ($lessons as $lessonRow) {
        $lid = (int)($lessonRow['id'] ?? 0);
        if ($lid > 0 && isset($completedLessonIds[$lid])) {
            $firstCompletedLessonId = $lid;
            break;
        }
    }
}

if (!empty($user) && !empty($canAccessContent) && !empty($lessons)) {
    $lockedModuleIds = [];
    foreach ($modulesData as $mData) {
        $mod = $mData['module'] ?? [];
        if (!empty($mData['is_locked']) && !empty($mod['id'])) {
            $lockedModuleIds[(int)$mod['id']] = true;
        }
    }

    foreach ($lessons as $lessonRow) {
        $lid = (int)($lessonRow['id'] ?? 0);
        if ($lid <= 0) {
            continue;
        }
        $mid = (int)($lessonRow['module_id'] ?? 0);
        if ($mid > 0 && isset($lockedModuleIds[$mid])) {
            continue;
        }

        if ($hasCompletedAnyLesson && isset($completedLessonIds[$lid])) {
            continue;
        }

        $currentLessonId = $lid;
        break;
    }

    if ($currentLessonId !== null) {
        $startLessonId = $currentLessonId;
        $startLessonLabel = $hasCompletedAnyLesson ? 'Continuar curso' : 'Começar curso';
    } else {
        foreach ($lessons as $lessonRow) {
            $lid = (int)($lessonRow['id'] ?? 0);
            if ($lid <= 0) {
                continue;
            }
            $mid = (int)($lessonRow['module_id'] ?? 0);
            if ($mid > 0 && isset($lockedModuleIds[$mid])) {
                continue;
            }
            $startLessonId = $lid;
            break;
        }

        if (!empty($completedLessonIds)) {
            $startLessonLabel = 'Rever curso';
        }
    }
}
?>
<div style="max-width: 960px; margin: 0 auto;">
    <div style="display:flex; flex-wrap:wrap; gap:20px; margin-bottom:18px;">
        <div style="flex:1 1 260px; min-width:260px; max-width:360px; border-radius:20px; overflow:hidden; border:1px solid var(--border-subtle); background:var(--surface-card); box-shadow:0 18px 35px rgba(0,0,0,0.25);">
            <div style="width:100%; height:220px; overflow:hidden; background:var(--surface-subtle);">
                <?php if ($image !== ''): ?>
                    <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($title) ?>" style="width:100%; height:100%; object-fit:cover; display:block;">
                <?php else: ?>
                    <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:32px; background:radial-gradient(circle at top left,#e53935 0,#050509 60%);">
                        🎓
                    </div>
                <?php endif; ?>
            </div>
            <div style="padding:10px 12px 12px 12px; font-size:12px;">
                <div style="font-size:16px; font-weight:650; margin-bottom:4px;">
                    <?= htmlspecialchars($title) ?>
                </div>
                <?php if ($short !== ''): ?>
                    <div style="color:var(--text-secondary); line-height:1.4; margin-bottom:6px;">
                        <?= htmlspecialchars($short) ?>
                    </div>
                <?php endif; ?>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:4px;">
                    <div style="font-size:11px; color:#ffcc80;">
                        <?php if ($isPaid && $priceCents > 0): ?>
                            R$ <?= number_format(max($priceCents,0)/100, 2, ',', '.') ?>
                        <?php else: ?>
                            Gratuito
                        <?php endif; ?>
                    </div>
                    <div style="font-size:11px; color:var(--text-secondary); text-align:right;">
                        <?php if ($allowPlanOnly): ?>
                            <div>Planos com flag de cursos</div>
                        <?php endif; ?>
                        <?php if ($allowPublicPurchase): ?>
                            <div>Disponível para compra avulsa</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div style="flex:2 1 320px; min-width:260px;">
            <?php if (!empty($success)): ?>
                <div style="background:#10330f; border:1px solid #3aa857; color:#c8ffd4; padding:8px 10px; border-radius:8px; font-size:13px; margin-bottom:10px;">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div style="background:#311; border:1px solid #a33; color:#ffbaba; padding:8px 10px; border-radius:8px; font-size:13px; margin-bottom:10px;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <h1 style="font-size:22px; margin-bottom:8px; font-weight:650;">Curso: <?= htmlspecialchars($title) ?></h1>

            <?php if ($user && !empty($canAccessContent)): ?>
                <div style="font-size:12px; color:var(--text-secondary); margin-bottom:6px;">
                    Progresso no curso: <?= isset($courseProgressPercent) ? (int)$courseProgressPercent : 0 ?>%
                </div>
            <?php endif; ?>

            <?php if ($description !== ''): ?>
                <div style="font-size:13px; color:var(--text-secondary); line-height:1.5; margin-bottom:10px; white-space:pre-line;">
                    <?= htmlspecialchars($description) ?>
                </div>
            <?php endif; ?>

            <div style="margin-top:8px; display:flex; flex-wrap:wrap; gap:8px; align-items:center;">
                <?php if (!$user): ?>
                    <a href="/login" style="
                        display:inline-flex; align-items:center; gap:6px; padding:8px 16px;
                        border-radius:999px; border:1px solid #ff6f60;
                        background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509;
                        font-size:13px; font-weight:600; text-decoration:none;">
                        Entrar para se inscrever
                    </a>
                <?php else: ?>
                    <?php if ($isEnrolled): ?>
                        <?php if (!empty($canAccessContent) && !empty($startLessonId)): ?>
                            <a href="/cursos/aulas/ver?lesson_id=<?= (int)$startLessonId ?>" style="
                                display:inline-flex; align-items:center; gap:6px; padding:8px 16px;
                                border-radius:999px; border:1px solid #ff6f60;
                                background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509;
                                font-size:13px; font-weight:600; text-decoration:none;">
                                <?= htmlspecialchars($startLessonLabel) ?>
                            </a>
                        <?php endif; ?>

                        <span style="
                            display:inline-flex; align-items:center; gap:6px; padding:8px 16px;
                            border-radius:999px; border:1px solid #3aa857;
                            background:#10330f; color:#c8ffd4; font-size:13px;">
                            Você já está inscrito neste curso
                        </span>
                        <form action="/cursos/cancelar-inscricao" method="post" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja cancelar sua inscrição neste curso?');">
                            <input type="hidden" name="course_id" value="<?= (int)($course['id'] ?? 0) ?>">
                            <button type="submit" style="
                                border:none; border-radius:999px; padding:8px 16px;
                                background:#311; color:#ffbaba;
                                font-weight:600; font-size:12px; cursor:pointer;">
                                Cancelar inscrição
                            </button>
                        </form>
                    <?php else: ?>
                        <?php if ($hasPaidPurchase): ?>
                            <?php if (!empty($firstCompletedLessonId)): ?>
                                <a href="/cursos/aulas/ver?lesson_id=<?= (int)$firstCompletedLessonId ?>" style="
                                    display:inline-flex; align-items:center; gap:6px; padding:8px 16px;
                                    border-radius:999px; border:1px solid #ff6f60;
                                    background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509;
                                    font-size:13px; font-weight:600; text-decoration:none;">
                                    Rever aulas concluídas
                                </a>
                            <?php endif; ?>
                            <form action="/cursos/inscrever" method="post" style="display:inline;">
                                <input type="hidden" name="course_id" value="<?= (int)($course['id'] ?? 0) ?>">
                                <button type="submit" style="
                                    border:none; border-radius:999px; padding:8px 16px;
                                    background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509;
                                    font-weight:600; font-size:13px; cursor:pointer;">
                                    Reinscrever neste curso
                                </button>
                            </form>
                        <?php else: ?>
                            <?php if (empty($canAccessContent)): ?>
                                <?php if ($isPaid && $priceCents > 0 && $allowPublicPurchase && !$planAllowsCourses): ?>
                                    <a href="/cursos/comprar?course_id=<?= (int)($course['id'] ?? 0) ?>" style="
                                        display:inline-flex; align-items:center; gap:6px; padding:8px 16px;
                                        border-radius:999px; border:1px solid #ff6f60;
                                        background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509;
                                        font-size:13px; font-weight:600; text-decoration:none;">
                                        Comprar curso avulso
                                    </a>
                                <?php else: ?>
                                    <form action="/cursos/inscrever" method="post" style="display:inline;">
                                        <input type="hidden" name="course_id" value="<?= (int)($course['id'] ?? 0) ?>">
                                        <button type="submit" style="
                                            border:none; border-radius:999px; padding:8px 16px;
                                            background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509;
                                            font-weight:600; font-size:13px; cursor:pointer;">
                                            Quero fazer este curso
                                        </button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div style="margin-top:16px; display:flex; flex-wrap:wrap; gap:24px;">
        <div style="flex:2 1 360px; min-width:260px;">
            <h2 style="font-size:16px; margin-bottom:8px;">Aulas do curso</h2>
            <?php
                $modulesData = $modulesData ?? [];
                $unassignedLessons = $unassignedLessons ?? [];
            ?>
            <?php if (empty($user) || empty($canAccessContent)): ?>
                <div style="color:var(--text-secondary); font-size:13px;">
                    <?php if (empty($user)): ?>
                        Entre ou faça login para ver as aulas deste curso.
                    <?php else: ?>
                        Para ver as aulas, você precisa ter um plano que libera cursos ou concluir a compra avulsa deste curso.
                    <?php endif; ?>
                </div>
            <?php elseif (empty($modulesData) && empty($unassignedLessons)): ?>
                <div style="color:var(--text-secondary); font-size:13px;">Nenhuma aula cadastrada ainda.</div>
            <?php else: ?>
                <div style="border-radius:12px; border:1px solid var(--border-subtle); background:var(--surface-card); overflow:hidden;">
                    <?php if (!empty($modulesData)): ?>
                        <?php foreach ($modulesData as $mIndex => $mData): ?>
                            <?php
                                $module = $mData['module'] ?? [];
                                $moduleLessons = $mData['lessons'] ?? [];
                                $moduleProgress = isset($mData['progress_percent']) ? (int)$mData['progress_percent'] : 0;
                                $exam = $mData['exam'] ?? null;
                                $hasExam = $exam && !empty($exam['is_active']);
                                $hasPassedExam = !empty($mData['has_passed_exam']);
                                $canTakeExam = !empty($mData['can_take_exam']);
                                $isLocked = !empty($mData['is_locked']);
                                $examAttempts = (int)($mData['exam_attempts'] ?? 0);
                                $maxAttempts = $exam && isset($exam['max_attempts']) ? (int)$exam['max_attempts'] : 0;
                            ?>
                            <div style="padding:10px 12px; border-bottom:1px solid #272727;">
                                <div style="display:flex; justify-content:space-between; gap:8px; align-items:flex-start; flex-wrap:wrap;">
                                    <div style="min-width:0;">
                                        <div style="font-size:14px; font-weight:600; margin-bottom:4px;">
                                            Módulo <?= $mIndex + 1 ?>: <?= htmlspecialchars($module['title'] ?? '') ?>
                                        </div>
                                        <?php if (!empty($module['description'])): ?>
                                            <div style="font-size:12px; color:#b0b0b0; margin-bottom:4px; line-height:1.4;">
                                                <?= htmlspecialchars($module['description']) ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($user): ?>
                                            <div style="font-size:11px; color:#b0b0b0;">
                                                Progresso do módulo: <?= $moduleProgress ?>%
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($user && $isEnrolled && $isLocked): ?>
                                            <div style="margin-top:4px;">
                                                <span style="
                                                    display:inline-flex; align-items:center; gap:4px;
                                                    border-radius:999px; padding:2px 8px; font-size:10px;
                                                    background:#332020; color:#ff8a80;">
                                                    Módulo bloqueado até passar na prova anterior
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div style="text-align:right; font-size:11px; min-width:150px;">
                                        <?php if ($hasExam): ?>
                                            <?php if ($user && $isEnrolled): ?>
                                                <?php if ($hasPassedExam): ?>
                                                    <div style="margin-bottom:4px;">
                                                        <span style="
                                                            display:inline-flex; align-items:center; gap:4px;
                                                            border-radius:999px; padding:2px 8px; font-size:10px;
                                                            background:#16351f; color:#6be28d;">
                                                            Prova concluída
                                                        </span>
                                                    </div>
                                                <?php elseif ($canTakeExam): ?>
                                                    <div style="margin-bottom:4px;">
                                                        <a href="/cursos/modulos/prova?course_id=<?= (int)$course['id'] ?>&module_id=<?= (int)($module['id'] ?? 0) ?>" style="
                                                            display:inline-flex; align-items:center; gap:4px;
                                                            border-radius:999px; padding:5px 10px;
                                                            background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509;
                                                            font-weight:600; font-size:11px; text-decoration:none;">
                                                            Fazer prova do módulo
                                                        </a>
                                                    </div>
                                                <?php else: ?>
                                                    <div style="margin-bottom:4px;">
                                                        <span style="
                                                            display:inline-flex; align-items:center; gap:4px;
                                                            border-radius:999px; padding:2px 8px; font-size:10px;
                                                            background:#331b1b; color:#ffbaba;">
                                                            Limite de tentativas atingido
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                                <div style="color:#b0b0b0;">
                                                    Tentativas: <?= $examAttempts ?>
                                                    <?php if ($maxAttempts > 0): ?>
                                                        / <?= $maxAttempts ?>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <div style="color:#b0b0b0;">
                                                    Prova disponível após se inscrever no curso.
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if (!empty($moduleLessons)): ?>
                                    <div style="margin-top:8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--surface-subtle); overflow:hidden;">
                                        <?php foreach ($moduleLessons as $idx => $lesson): ?>
                                            <?php
                                                $ltitle = trim((string)($lesson['title'] ?? ''));
                                                $ldesc = trim((string)($lesson['description'] ?? ''));
                                                $video = trim((string)($lesson['video_url'] ?? ''));
                                                $number = $idx + 1;
                                                $lessonId = (int)($lesson['id'] ?? 0);
                                                $lessonComments = $commentsByLesson[$lessonId] ?? [];
                                                $isAdmin = !empty($_SESSION['is_admin']);
                                                $isOwner = $user && !empty($course['owner_user_id']) && (int)$course['owner_user_id'] === (int)$user['id'];
                                                $canCommentLesson = $user && ($isEnrolled || $isOwner || $isAdmin);

                                                $isCompleted = $user && !empty($completedLessonIds[$lessonId] ?? false);
                                                $isCurrent = $user && !empty($canAccessContent) && $currentLessonId !== null && $lessonId === $currentLessonId && !$isCompleted;
                                                $actionLabel = '';
                                                if ($isCompleted) {
                                                    $actionLabel = 'Assistir novamente';
                                                } elseif ($isCurrent) {
                                                    $actionLabel = 'Continuar de onde parei';
                                                } else {
                                                    $actionLabel = 'Assistir';
                                                }
                                            ?>
                                            <div id="lesson-<?= $lessonId ?>" style="padding:8px 10px; border-bottom:1px solid var(--border-subtle);">
                                                <div style="display:flex; justify-content:space-between; gap:8px; align-items:center;">
                                                    <div style="font-size:13px; font-weight:600; display:flex; align-items:center; gap:6px; min-width:0;">
                                                        <span style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">Aula <?= $number ?>: <?= htmlspecialchars($ltitle) ?></span>
                                                        <?php if ($isCompleted): ?>
                                                            <span style="font-size:11px; color:#6be28d;">✔ concluída</span>
                                                        <?php elseif ($isCurrent): ?>
                                                            <span style="font-size:11px; color:#ffcc80;">Próxima aula</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if ($video !== '' && $lessonId > 0 && !empty($canAccessContent)): ?>
                                                        <a href="/cursos/aulas/ver?lesson_id=<?= $lessonId ?>" style="
                                                            display:inline-flex; align-items:center; gap:6px; padding:4px 10px;
                                                            border-radius:999px; border:1px solid #ff6f60;
                                                            background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509;
                                                            font-size:11px; font-weight:600; text-decoration:none;">
                                                            <span style="font-size:12px;">▶</span>
                                                            <span><?= htmlspecialchars($actionLabel) ?></span>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($ldesc !== ''): ?>
                                                    <div style="margin-top:4px; font-size:12px; color:var(--text-secondary); line-height:1.4;">
                                                        <?= htmlspecialchars($ldesc) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div style="margin-top:6px; font-size:12px; color:#b0b0b0;">Nenhuma aula cadastrada neste módulo.</div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if (!empty($unassignedLessons)): ?>
                        <div style="padding:10px 12px;">
                            <div style="font-size:14px; font-weight:600; margin-bottom:4px;">Aulas sem módulo</div>
                            <div style="border-radius:8px; border:1px solid var(--border-subtle); background:var(--surface-subtle); overflow:hidden;">
                                <?php foreach ($unassignedLessons as $idx => $lesson): ?>
                                    <?php
                                        $ltitle = trim((string)($lesson['title'] ?? ''));
                                        $ldesc = trim((string)($lesson['description'] ?? ''));
                                        $video = trim((string)($lesson['video_url'] ?? ''));
                                        $number = $idx + 1;
                                        $lessonId = (int)($lesson['id'] ?? 0);
                                        $lessonComments = $commentsByLesson[$lessonId] ?? [];
                                        $isAdmin = !empty($_SESSION['is_admin']);
                                        $isOwner = $user && !empty($course['owner_user_id']) && (int)$course['owner_user_id'] === (int)$user['id'];
                                        $canCommentLesson = $user && ($isEnrolled || $isOwner || $isAdmin);

                                        $isCompleted = $user && !empty($completedLessonIds[$lessonId] ?? false);
                                        $isCurrent = $user && !empty($canAccessContent) && $currentLessonId !== null && $lessonId === $currentLessonId && !$isCompleted;
                                        $actionLabel = '';
                                        if ($isCompleted) {
                                            $actionLabel = 'Assistir novamente';
                                        } elseif ($isCurrent) {
                                            $actionLabel = 'Continuar de onde parei';
                                        } else {
                                            $actionLabel = 'Assistir';
                                        }
                                    ?>
                                    <div id="lesson-<?= $lessonId ?>" style="padding:8px 10px; border-bottom:1px solid #272727;">
                                        <div style="display:flex; justify-content:space-between; gap:8px; align-items:center;">
                                            <div style="font-size:13px; font-weight:600; display:flex; align-items:center; gap:6px; min-width:0;">
                                                <span style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">Aula <?= $number ?>: <?= htmlspecialchars($ltitle) ?></span>
                                                <?php if ($isCompleted): ?>
                                                    <span style="font-size:11px; color:#6be28d;">✔ concluída</span>
                                                <?php elseif ($isCurrent): ?>
                                                    <span style="font-size:11px; color:#ffcc80;">Próxima aula</span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($video !== '' && $lessonId > 0 && !empty($canAccessContent)): ?>
                                                <a href="/cursos/aulas/ver?lesson_id=<?= $lessonId ?>" style="
                                                    display:inline-flex; align-items:center; gap:6px; padding:4px 10px;
                                                    border-radius:999px; border:1px solid #ff6f60;
                                                    background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509;
                                                    font-size:11px; font-weight:600; text-decoration:none;">
                                                    <span style="font-size:12px;">▶</span>
                                                    <span><?= htmlspecialchars($actionLabel) ?></span>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($ldesc !== ''): ?>
                                            <div style="margin-top:4px; font-size:12px; color:var(--text-secondary); line-height:1.4;">
                                                <?= htmlspecialchars($ldesc) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div id="lives" style="flex:1 1 260px; min-width:240px;">
            <h2 style="font-size:16px; margin-bottom:8px;">Lives deste curso</h2>
            <?php if (!$user): ?>
                <div style="color:var(--text-secondary); font-size:13px;">Entre na sua conta e inscreva-se neste curso para ver o calendário de lives.</div>
            <?php elseif (empty($canAccessContent) && empty($_SESSION['is_admin'])): ?>
                <div style="color:var(--text-secondary); font-size:13px;">Para ver e participar das lives, você precisa ter um plano com cursos ou concluir a compra deste curso.</div>
            <?php elseif (!$isEnrolled && empty($_SESSION['is_admin'])): ?>
                <div style="color:var(--text-secondary); font-size:13px;">Inscreva-se neste curso para ver e participar das lives ao vivo.</div>
            <?php else: ?>
                <?php if (empty($lives)): ?>
                    <div style="color:var(--text-secondary); font-size:13px;">Nenhuma próxima live agendada no momento.</div>
                <?php else: ?>
                    <div style="border-radius:12px; border:1px solid var(--border-subtle); background:var(--surface-card); overflow:hidden;">
                        <?php foreach ($lives as $live): ?>
                            <?php
                                $ltitle = trim((string)($live['title'] ?? ''));
                                $ldesc = trim((string)($live['description'] ?? ''));
                                $scheduled = $live['scheduled_at'] ?? '';
                                $formatted = $scheduled ? date('d/m/Y H:i', strtotime($scheduled)) : '';
                                $recordingLink = trim((string)($live['recording_link'] ?? ''));
                                $liveId = (int)($live['id'] ?? 0);
                                $hasRecordingAccess = $user && $recordingLink !== '' && !empty($myLiveParticipation[$liveId] ?? false);
                            ?>
                            <div style="padding:8px 10px; border-bottom:1px solid #272727;">
                                <div style="font-size:13px; font-weight:600; margin-bottom:2px;">
                                    <?= htmlspecialchars($ltitle) ?>
                                </div>
                                <?php if ($formatted !== ''): ?>
                                    <div style="font-size:12px; color:#ffcc80; margin-bottom:4px;">
                                        <?= htmlspecialchars($formatted) ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($ldesc !== ''): ?>
                                    <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px; line-height:1.4;">
                                        <?= htmlspecialchars($ldesc) ?>
                                    </div>
                                <?php endif; ?>
                                <div style="margin-top:4px;">
                                    <?php if (!empty($myLiveParticipation[$liveId] ?? false)): ?>
                                        <span style="font-size:11px; color:#c8ffd4;">Você já está inscrito nesta live.</span>
                                    <?php else: ?>
                                        <form action="/cursos/lives/participar" method="post" style="display:inline;">
                                            <input type="hidden" name="live_id" value="<?= $liveId ?>">
                                            <button type="submit" style="
                                                border:none; border-radius:999px; padding:5px 12px;
                                                background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509;
                                                font-weight:600; font-size:11px; cursor:pointer;">
                                                Quero participar da live
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                                <?php if ($hasRecordingAccess): ?>
                                    <div style="margin-top:4px; font-size:11px; color:#b0b0b0;">
                                        <a href="/cursos/lives/ver?live_id=<?= $liveId ?>" style="color:#ffcc80; text-decoration:none;">▶ Assistir gravação desta live</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div style="margin-top:6px; font-size:11px; text-align:right;">
                    <a href="/cursos/lives?course_id=<?= (int)$course['id'] ?>" style="color:#ff6f60; text-decoration:none;">Ver todas as lives deste curso &rarr;</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div style="margin-top:18px; font-size:12px; color:#777;">
        <a href="/cursos" style="color:#ff6f60; text-decoration:none;">&larr; Voltar para lista de cursos</a>
    </div>
</div>
