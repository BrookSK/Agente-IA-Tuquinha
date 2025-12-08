<?php
/** @var array|null $user */
/** @var array $course */
/** @var array $lesson */
/** @var array $lessons */
/** @var array $lessonComments */
/** @var bool $isEnrolled */

use App\Controllers\CourseController;

$courseTitle = trim((string)($course['title'] ?? ''));
$lessonTitle = trim((string)($lesson['title'] ?? ''));
$lessonDescription = trim((string)($lesson['description'] ?? ''));
$videoUrl = trim((string)($lesson['video_url'] ?? ''));
$currentLessonId = (int)($lesson['id'] ?? 0);

$courseUrl = CourseController::buildCourseUrl($course);

$isAdmin = !empty($_SESSION['is_admin']);
$isOwner = $user && !empty($course['owner_user_id']) && (int)$course['owner_user_id'] === (int)($user['id'] ?? 0);
$canCommentLesson = $user && ($isEnrolled || $isOwner || $isAdmin);
?>
<div style="max-width: 1120px; margin: 0 auto; display:flex; gap:18px;">
    <aside style="flex:0 0 220px; border-radius:16px; border:1px solid #272727; background:#050509; padding:10px 8px; max-height:80vh; overflow:auto;">
        <div style="font-size:13px; font-weight:600; margin-bottom:8px; color:#f5f5f5;">Aulas do curso</div>
        <?php if (empty($lessons)): ?>
            <div style="font-size:12px; color:#777;">Nenhuma aula cadastrada.</div>
        <?php else: ?>
            <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:6px;">
                <?php foreach ($lessons as $idx => $item): ?>
                    <?php
                        $lid = (int)($item['id'] ?? 0);
                        $ltitle = trim((string)($item['title'] ?? ''));
                        $isCurrent = $lid === $currentLessonId;
                        $label = $ltitle !== '' ? $ltitle : ('Aula ' . ($idx + 1));
                    ?>
                    <li>
                        <a href="/cursos/aulas/ver?lesson_id=<?= $lid ?>" style="
                            display:flex; align-items:center; gap:8px; padding:6px 8px; border-radius:999px;
                            text-decoration:none; font-size:12px;
                            background:<?= $isCurrent ? '#111118' : 'transparent' ?>;
                            color:<?= $isCurrent ? '#ffcc80' : '#f5f5f5' ?>;
                            border:1px solid <?= $isCurrent ? '#ff6f60' : 'transparent' ?>;
                        ">
                            <span style="width:10px; height:10px; border-radius:50%; border:2px solid #7cb342; background:<?= $isCurrent ? '#7cb342' : 'transparent' ?>;"></span>
                            <span style="flex:1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                <?= htmlspecialchars($label) ?>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </aside>

    <main style="flex:1 1 auto; display:flex; flex-direction:column; gap:12px;">
        <header style="margin-bottom:4px;">
            <div style="font-size:13px; color:#b0b0b0; margin-bottom:2px;">
                Curso: <?= htmlspecialchars($courseTitle) ?>
            </div>
            <h1 style="font-size:20px; margin:0; font-weight:650;">Aula: <?= htmlspecialchars($lessonTitle) ?></h1>
        </header>

        <section style="border-radius:14px; border:1px solid #272727; background:#111118; padding:8px; min-height:260px;">
            <div style="font-size:12px; color:#b0b0b0; margin-bottom:6px;">Player</div>
            <?php if ($videoUrl === ''): ?>
                <div style="font-size:13px; color:#b0b0b0; padding:18px 12px;">
                    Nenhum vídeo foi configurado para esta aula ainda.
                </div>
            <?php else: ?>
                <div style="position:relative; padding-bottom:56.25%; height:0; overflow:hidden; border-radius:10px; background:#000;">
                    <iframe src="<?= htmlspecialchars($videoUrl) ?>" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen style="position:absolute; top:0; left:0; width:100%; height:100%;"></iframe>
                </div>
                <div style="margin-top:6px; font-size:11px; color:#777;">
                    Se o player não carregar corretamente, você pode <a href="<?= htmlspecialchars($videoUrl) ?>" target="_blank" rel="noopener noreferrer" style="color:#ff6f60; text-decoration:none;">abrir o vídeo em outra aba</a>.
                </div>
            <?php endif; ?>
        </section>

        <section style="border-radius:14px; border:1px solid #272727; background:#111118; padding:10px 12px; min-height:120px;">
            <div style="font-size:12px; color:#b0b0b0; margin-bottom:6px;">Sobre esta aula</div>
            <?php if ($lessonDescription !== ''): ?>
                <div style="font-size:13px; color:#d0d0d0; line-height:1.5; white-space:pre-line;">
                    <?= htmlspecialchars($lessonDescription) ?>
                </div>
            <?php else: ?>
                <div style="font-size:13px; color:#b0b0b0;">
                    O professor ainda não adicionou uma descrição detalhada para esta aula.
                </div>
            <?php endif; ?>
        </section>

        <section style="border-radius:14px; border:1px solid #272727; background:#111118; padding:10px 12px; min-height:140px;">
            <div style="font-size:12px; color:#b0b0b0; margin-bottom:6px;">Comentários da aula</div>

            <?php if (empty($lessonComments)): ?>
                <div style="font-size:12px; color:#555; margin-bottom:6px;">Ainda não há comentários nesta aula.</div>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:6px; margin-bottom:8px; max-height:260px; overflow:auto;">
                    <?php foreach ($lessonComments as $comment): ?>
                        <?php
                            $author = trim((string)($comment['user_name'] ?? ''));
                            $createdAt = $comment['created_at'] ?? '';
                        ?>
                        <div style="border-radius:8px; border:1px solid #272727; background:#050509; padding:6px 8px; font-size:12px;">
                            <div style="display:flex; justify-content:space-between; gap:8px; margin-bottom:2px;">
                                <span style="font-weight:600;">
                                    <?= htmlspecialchars($author) ?>
                                </span>
                                <?php if ($createdAt): ?>
                                    <span style="font-size:10px; color:#777;">
                                        <?= htmlspecialchars($createdAt) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div style="color:#d0d0d0; margin:0;">
                                <?= nl2br(htmlspecialchars($comment['body'] ?? '')) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($user)): ?>
                <?php if ($canCommentLesson): ?>
                    <form action="/cursos/aulas/comentar" method="post" style="margin-top:4px;">
                        <input type="hidden" name="course_id" value="<?= (int)($course['id'] ?? 0) ?>">
                        <input type="hidden" name="lesson_id" value="<?= $currentLessonId ?>">
                        <textarea name="body" rows="2" maxlength="2000" placeholder="Escreva um comentário sobre esta aula..." style="
                            width:100%; padding:6px 8px; border-radius:8px; border:1px solid #272727;
                            background:#050509; color:#f5f5f5; font-size:12px; resize:vertical;"></textarea>
                        <div style="margin-top:4px; display:flex; justify-content:flex-end;">
                            <button type="submit" style="
                                border:none; border-radius:999px; padding:5px 12px;
                                background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509;
                                font-weight:600; font-size:11px; cursor:pointer;">
                                Enviar comentário
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div style="margin-top:4px; font-size:11px; color:#777;">
                        Você precisa estar inscrito neste curso para comentar.
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div style="margin-top:4px; font-size:11px; color:#777;">
                    <a href="/login" style="color:#ff6f60; text-decoration:none;">Entre na sua conta para comentar esta aula.</a>
                </div>
            <?php endif; ?>
        </section>

        <div style="margin-top:4px; font-size:12px;">
            <a href="<?= $courseUrl ?>#lesson-<?= $currentLessonId ?>" style="color:#ff6f60; text-decoration:none;">&larr; Voltar para aulas do curso</a>
        </div>
    </main>
</div>
