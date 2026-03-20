<?php
/** @var array|null $user */
/** @var array $course */
/** @var array $lesson */
/** @var array $lessons */
/** @var array $lessonComments */
/** @var bool $isEnrolled */
/** @var array|null $completedLessonIds */
/** @var int|null $currentModuleId */
/** @var bool|null $hasModuleExam */
/** @var bool|null $canTakeModuleExam */
/** @var bool|null $showExamPrompt */
/** @var string|null $prevUrl */
/** @var string|null $nextUrl */
/** @var bool|null $nextIsExam */
/** @var bool $isLessonCompleted */
/** @var bool $canAccessContent */

$courseTitle = trim((string)($course['title'] ?? ''));
$lessonTitle = trim((string)($lesson['title'] ?? ''));
$lessonDescription = trim((string)($lesson['description'] ?? ''));
$videoUrl = trim((string)($lesson['video_url'] ?? ''));

$isDirectVideoFile = false;
if ($videoUrl !== '') {
    if (preg_match('~\.(mp4|webm|ogg)(?:\?.*)?$~i', $videoUrl)) {
        $isDirectVideoFile = true;
    }
}

$embedUrl = $videoUrl;
if (!$isDirectVideoFile && $embedUrl !== '' && strpos($embedUrl, 'drive.google.com') !== false) {
    if (preg_match('~https?://drive\.google\.com/file/d/([^/]+)/~', $embedUrl, $m)) {
        $embedUrl = 'https://drive.google.com/file/d/' . $m[1] . '/preview';
    } elseif (preg_match('~https?://drive\.google\.com/open\?id=([^&]+)~', $embedUrl, $m)) {
        $embedUrl = 'https://drive.google.com/file/d/' . $m[1] . '/preview';
    }
}
$currentLessonId = (int)($lesson['id'] ?? 0);

$completedLessonIds = is_array($completedLessonIds ?? null) ? $completedLessonIds : [];

$currentModuleId = isset($currentModuleId) ? (int)$currentModuleId : (int)($lesson['module_id'] ?? 0);
$hasModuleExam = !empty($hasModuleExam);
$canTakeModuleExam = !empty($canTakeModuleExam);
$showExamPrompt = !empty($showExamPrompt);

$moduleLessons = [];
foreach ($lessons as $l) {
    if (empty($l['is_published'])) {
        continue;
    }
    if ((int)($l['module_id'] ?? 0) !== $currentModuleId) {
        continue;
    }
    $moduleLessons[] = $l;
}

$canCommentLesson = $user && $isEnrolled;
?>
<style>
    .lesson-container {
        width: 100%;
        padding: 0 20px;
        margin: 0 auto;
        display: flex;
        gap: 18px;
        box-sizing: border-box;
    }
    
    .lesson-sidebar {
        flex: 0 0 220px;
        border-radius: 16px;
        border: 1px solid var(--border);
        background: var(--bg-card);
        padding: 10px 8px;
        max-height: 80vh;
        overflow: auto;
    }
    
    .lesson-main {
        flex: 1 1 auto;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    
    @media (max-width: 768px) {
        .lesson-container {
            flex-direction: column;
            padding: 0 12px;
            gap: 16px;
        }
        
        .lesson-sidebar {
            flex: 1 1 auto;
            max-height: none;
            order: 2;
        }
        
        .lesson-main {
            order: 1;
        }
        
        .lesson-sidebar ul {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 8px !important;
        }
        
        .lesson-sidebar li a {
            font-size: 11px !important;
            padding: 8px 10px !important;
            border-radius: 8px !important;
        }
    }
    
    @media (max-width: 640px) {
        .lesson-container {
            padding: 0 8px;
            gap: 12px;
        }
        
        .lesson-sidebar {
            padding: 8px 6px;
        }
        
        .lesson-sidebar ul {
            grid-template-columns: 1fr;
        }
    }
</style>
<div class="lesson-container">
    <aside class="lesson-sidebar">
        <div style="font-size:13px; font-weight:600; margin-bottom:8px; color:var(--text-primary);">Aulas do curso</div>
        <?php if (empty($moduleLessons)): ?>
            <div style="font-size:12px; color:var(--text-secondary);">Nenhuma aula cadastrada.</div>
        <?php else: ?>
            <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:6px;">
                <?php foreach ($moduleLessons as $idx => $item): ?>
                    <?php
                        $lid = (int)($item['id'] ?? 0);
                        $ltitle = trim((string)($item['title'] ?? ''));
                        $isCurrent = $lid === $currentLessonId;
                        $isCompleted = $lid > 0 && !empty($completedLessonIds[$lid] ?? false);
                        $label = $ltitle !== '' ? $ltitle : ('Aula ' . ($idx + 1));
                    ?>
                    <li>
                        <a href="/painel-externo/aula?id=<?= $lid ?>&course_id=<?= (int)$course['id'] ?>" style="
                            display:flex; align-items:center; gap:8px; padding:6px 8px; border-radius:999px;
                            text-decoration:none; font-size:12px;
                            background:<?= $isCurrent ? 'var(--bg-card)' : 'transparent' ?>;
                            color:<?= $isCurrent ? 'var(--accent)' : 'var(--text-primary)' ?>;
                            border:1px solid <?= $isCurrent ? 'var(--accent)' : 'transparent' ?>;
                        ">
                            <span style="width:10px; height:10px; border-radius:50%; border:2px solid #7cb342; background:<?= ($isCompleted || $isCurrent) ? '#7cb342' : 'transparent' ?>;"></span>
                            <span style="flex:1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <?php if ($isCompleted): ?>
                                <span style="font-size:10px; color:#6be28d;">feita</span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </aside>

    <main class="lesson-main">
        <header style="margin-bottom:4px;">
            <div style="font-size:13px; color:var(--text-secondary); margin-bottom:2px;">
                Curso: <?= htmlspecialchars($courseTitle, ENT_QUOTES, 'UTF-8') ?>
            </div>
            <h1 style="font-size:20px; margin:0; font-weight:650; color:var(--text-primary);">Aula: <?= htmlspecialchars($lessonTitle, ENT_QUOTES, 'UTF-8') ?></h1>
        </header>

        <section class="card" style="min-height:260px;">
            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:6px;">Player</div>
            <?php if ($videoUrl === ''): ?>
                <div style="font-size:13px; color:var(--text-secondary); padding:18px 12px;">
                    Nenhum vídeo foi configurado para esta aula ainda.
                </div>
            <?php else: ?>
                <?php if ($isDirectVideoFile): ?>
                    <div style="position:relative; padding-bottom:56.25%; height:0; overflow:hidden; border-radius:10px; background:#000;">
                        <video
                            id="lesson-video"
                            src="<?= htmlspecialchars($videoUrl, ENT_QUOTES, 'UTF-8') ?>"
                            preload="metadata"
                            playsinline
                            controls
                            controlsList="nodownload noplaybackrate noremoteplayback"
                            disablePictureInPicture
                            oncontextmenu="return false;"
                            style="position:absolute; top:0; left:0; width:100%; height:100%; background:#000;">
                        </video>
                    </div>
                <?php else: ?>
                    <div style="position:relative; padding-bottom:56.25%; height:0; overflow:hidden; border-radius:10px; background:#000;">
                        <iframe src="<?= htmlspecialchars($embedUrl, ENT_QUOTES, 'UTF-8') ?>" frameborder="0" allow="autoplay; encrypted-media" style="position:absolute; top:0; left:0; width:100%; height:100%;"></iframe>
                    </div>
                <?php endif; ?>

                <?php if (!empty($user) && !empty($canAccessContent)): ?>
                    <div style="margin-top:10px; padding-top:8px; border-top:1px dashed var(--border); display:flex; flex-wrap:wrap; gap:10px; align-items:center; justify-content:space-between;">
                        <div style="font-size:12px; color:var(--text-secondary);">
                            <?php if (!empty($isLessonCompleted)): ?>
                                Esta aula já está marcada como concluída.
                            <?php else: ?>
                                Esta aula ainda não foi marcada como concluída.
                            <?php endif; ?>
                        </div>
                        <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:center;">
                            <?php if (empty($isLessonCompleted)): ?>
                                <form action="/painel-externo/aula/concluir" method="post" style="display:inline;">
                                    <input type="hidden" name="course_id" value="<?= (int)($course['id'] ?? 0) ?>">
                                    <input type="hidden" name="lesson_id" value="<?= $currentLessonId ?>">
                                    <button type="submit" class="btn" style="padding:6px 14px; font-size:12px; background:#16351f; color:#6be28d;">
                                        Marcar como concluída
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if (!empty($prevUrl)): ?>
                                <a href="<?= htmlspecialchars($prevUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn-outline" style="padding:6px 14px; font-size:12px;">
                                    ← Aula anterior
                                </a>
                            <?php endif; ?>

                            <?php if (!empty($nextUrl)): ?>
                                <a href="<?= htmlspecialchars($nextUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn" style="padding:6px 14px; font-size:12px;">
                                    <?php if (!empty($nextIsExam)): ?>
                                        Ir para a próxima aula
                                    <?php else: ?>
                                        Próxima aula →
                                    <?php endif; ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        <section class="card" style="min-height:120px;">
            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:6px;">Sobre esta aula</div>
            <?php if ($lessonDescription !== ''): ?>
                <div style="font-size:13px; color:var(--text-primary); line-height:1.5; white-space:pre-line;">
                    <?= htmlspecialchars($lessonDescription, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php else: ?>
                <div style="font-size:13px; color:var(--text-secondary);">
                    O professor ainda não adicionou uma descrição detalhada para esta aula.
                </div>
            <?php endif; ?>
        </section>

        <section class="card" style="min-height:140px;">
            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:6px;">Comentários da aula</div>

            <?php if (empty($lessonComments)): ?>
                <div style="font-size:12px; color:var(--text-secondary); margin-bottom:6px;">Ainda não há comentários nesta aula.</div>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:6px; margin-bottom:8px; max-height:260px; overflow:auto;">
                    <?php foreach ($lessonComments as $comment): ?>
                        <?php
                            $author = trim((string)($comment['user_name'] ?? ''));
                            $createdAt = $comment['created_at'] ?? '';
                        ?>
                        <div style="border-radius:8px; border:1px solid var(--border); background:var(--bg-main); padding:6px 8px; font-size:12px;">
                            <div style="display:flex; justify-content:space-between; gap:8px; margin-bottom:2px;">
                                <span style="font-weight:600; color:var(--text-primary);">
                                    <?= htmlspecialchars($author, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <?php if ($createdAt): ?>
                                    <span style="font-size:10px; color:var(--text-secondary);">
                                        <?= htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div style="color:var(--text-primary); margin:0;">
                                <?= nl2br(htmlspecialchars($comment['body'] ?? '', ENT_QUOTES, 'UTF-8')) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($user)): ?>
                <?php if ($canCommentLesson): ?>
                    <form action="/painel-externo/aula/comentar" method="post" style="margin-top:4px;">
                        <input type="hidden" name="course_id" value="<?= (int)($course['id'] ?? 0) ?>">
                        <input type="hidden" name="lesson_id" value="<?= $currentLessonId ?>">
                        <textarea name="body" rows="2" maxlength="2000" placeholder="Escreva um comentário sobre esta aula..." style="
                            width:100%; padding:6px 8px; border-radius:8px; border:1px solid var(--border);
                            background:var(--bg-main); color:var(--text-primary); font-size:12px; resize:vertical;"></textarea>
                        <div style="margin-top:4px; display:flex; justify-content:flex-end;">
                            <button type="submit" class="btn" style="padding:5px 12px; font-size:11px;">
                                Enviar comentário
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div style="margin-top:4px; font-size:11px; color:var(--text-secondary);">
                        Você precisa estar inscrito neste curso para comentar.
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        <div style="margin-top:4px; font-size:12px;">
            <a href="/painel-externo/curso?id=<?= (int)$course['id'] ?>" style="color:var(--accent); text-decoration:none;">&larr; Voltar para o curso</a>
        </div>
    </main>
</div>
