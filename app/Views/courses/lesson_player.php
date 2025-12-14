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

// Detecta se o link é um arquivo de vídeo direto (ex.: .mp4, .webm, .ogg).
$isDirectVideoFile = false;
if ($videoUrl !== '') {
    if (preg_match('~\.(mp4|webm|ogg)(?:\?.*)?$~i', $videoUrl)) {
        $isDirectVideoFile = true;
    }
}

// Para links que não são arquivo direto, mantém o comportamento de embed (Drive, YouTube embed, etc.).
$embedUrl = $videoUrl;
if (!$isDirectVideoFile && $embedUrl !== '' && strpos($embedUrl, 'drive.google.com') !== false) {
    if (preg_match('~https?://drive\.google\.com/file/d/([^/]+)/~', $embedUrl, $m)) {
        $embedUrl = 'https://drive.google.com/file/d/' . $m[1] . '/preview';
    } elseif (preg_match('~https?://drive\.google\.com/open\?id=([^&]+)~', $embedUrl, $m)) {
        $embedUrl = 'https://drive.google.com/file/d/' . $m[1] . '/preview';
    }
}
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
                <?php if ($isDirectVideoFile): ?>
                    <div style="position:relative; padding-bottom:56.25%; height:0; overflow:hidden; border-radius:10px; background:#000;">
                        <video
                            id="lesson-video"
                            src="<?= htmlspecialchars($videoUrl) ?>"
                            preload="metadata"
                            playsinline
                            style="position:absolute; top:0; left:0; width:100%; height:100%; background:#000;">
                        </video>
                    </div>

                    <div style="margin-top:8px; display:flex; flex-wrap:wrap; align-items:center; gap:10px;">
                        <button
                            type="button"
                            id="lesson-play-pause"
                            style="
                                border:none; border-radius:999px; padding:6px 14px;
                                background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509;
                                font-weight:600; font-size:12px; cursor:pointer;">
                            Reproduzir
                        </button>

                        <div style="display:flex; align-items:center; gap:6px; font-size:12px; color:#b0b0b0;">
                            <span>Volume</span>
                            <input
                                type="range"
                                id="lesson-volume"
                                min="0"
                                max="1"
                                step="0.05"
                                value="1"
                                style="width:140px; cursor:pointer;">
                        </div>
                    </div>

                    <script>
                        (function () {
                            var video = document.getElementById('lesson-video');
                            var btn = document.getElementById('lesson-play-pause');
                            var vol = document.getElementById('lesson-volume');

                            if (!video || !btn || !vol) {
                                return;
                            }

                            function updateButton() {
                                btn.textContent = video.paused ? 'Reproduzir' : 'Pausar';
                            }

                            btn.addEventListener('click', function () {
                                if (video.paused) {
                                    video.play();
                                } else {
                                    video.pause();
                                }
                            });

                            video.addEventListener('play', updateButton);
                            video.addEventListener('pause', updateButton);

                            vol.addEventListener('input', function () {
                                var value = parseFloat(this.value);
                                if (!isNaN(value)) {
                                    video.volume = value;
                                }
                            });

                            // Volume inicial
                            video.volume = parseFloat(vol.value) || 1;
                            updateButton();
                        })();
                    </script>
                <?php else: ?>
                    <div style="position:relative; padding-bottom:56.25%; height:0; overflow:hidden; border-radius:10px; background:#000;">
                        <iframe src="<?= htmlspecialchars($embedUrl) ?>" frameborder="0" allow="autoplay; encrypted-media" style="position:absolute; top:0; left:0; width:100%; height:100%;"></iframe>
                    </div>
                <?php endif; ?>

                <?php if (!empty($user) && !empty($canAccessContent)): ?>
                    <div style="margin-top:10px; padding-top:8px; border-top:1px dashed #272727; display:flex; flex-wrap:wrap; gap:10px; align-items:center; justify-content:space-between;">
                        <div style="font-size:12px; color:#b0b0b0;">
                            <?php if (!empty($isLessonCompleted)): ?>
                                Esta aula já está marcada como concluída.
                            <?php else: ?>
                                Esta aula ainda não foi marcada como concluída.
                            <?php endif; ?>
                        </div>
                        <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:center;">
                            <?php if (empty($isLessonCompleted)): ?>
                                <form action="/cursos/aulas/concluir" method="post" style="display:inline;">
                                    <input type="hidden" name="course_id" value="<?= (int)($course['id'] ?? 0) ?>">
                                    <input type="hidden" name="lesson_id" value="<?= $currentLessonId ?>">
                                    <button type="submit" style="
                                        border:none; border-radius:999px; padding:6px 14px;
                                        background:#16351f; color:#6be28d;
                                        font-weight:600; font-size:12px; cursor:pointer;">
                                        Marcar como concluída
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if (!empty($prevUrl)): ?>
                                <a href="<?= htmlspecialchars($prevUrl) ?>" style="
                                    display:inline-flex; align-items:center; gap:6px; padding:6px 14px;
                                    border-radius:999px; border:1px solid #272727;
                                    background:#050509; color:#f5f5f5;
                                    font-weight:500; font-size:12px; text-decoration:none;">
                                    ← Aula anterior
                                </a>
                            <?php endif; ?>

                            <?php if (!empty($nextUrl)): ?>
                                <a href="<?= htmlspecialchars($nextUrl) ?>" style="
                                    display:inline-flex; align-items:center; gap:6px; padding:6px 14px;
                                    border-radius:999px; border:1px solid #ff6f60;
                                    background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509;
                                    font-weight:600; font-size:12px; text-decoration:none;">
                                    <?php if (!empty($nextIsExam)): ?>
                                        Ir para a prova do módulo
                                    <?php else: ?>
                                        Ir para a próxima aula
                                    <?php endif; ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
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
