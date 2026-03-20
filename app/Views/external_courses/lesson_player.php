<?php
/** @var array $course */
/** @var array $lesson */
/** @var array $lessons */
/** @var array $lessonComments */
/** @var array $completedLessonIds */
/** @var string $token */
/** @var int $prevLessonId */
/** @var int $nextLessonId */

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
$isLessonCompleted = !empty($completedLessonIds[$currentLessonId] ?? false);

$isPartnerSite = !empty($isPartnerSite);
$slug = isset($slug) ? trim((string)$slug) : '';

$completeAction = '/';
$commentAction = '/';
$membersHref = '/';
$lessonHrefBase = '/';

if ($slug !== '') {
    $completeAction = '/curso/' . urlencode($slug) . '/aula/concluir';
    $commentAction = '/curso/' . urlencode($slug) . '/aula/comentar';
    $membersHref = '/curso/' . urlencode($slug) . '/membros';
    $lessonHrefBase = '/curso/' . urlencode($slug) . '/aula?lesson_id=';
}

$publishedLessons = [];
foreach ($lessons as $l) {
    if (empty($l['is_published'])) continue;
    $publishedLessons[] = $l;
}
?>

<div style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-start;">
    <div style="flex:1 1 520px; min-width:280px;">
        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Curso: <?= htmlspecialchars($courseTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
        <h1 style="font-size:18px; font-weight:900; margin:0 0 10px 0;">Aula: <?= htmlspecialchars($lessonTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h1>

        <div style="border:1px solid var(--border); border-radius:16px; padding:10px; background: rgba(0,0,0,0.35);">
            <?php if ($videoUrl === ''): ?>
                <div class="hint" style="padding:16px 10px;">Nenhum vídeo foi configurado para esta aula.</div>
            <?php else: ?>
                <?php if ($isDirectVideoFile): ?>
                    <div style="position:relative; padding-bottom:56.25%; height:0; overflow:hidden; border-radius:12px; background:#000;">
                        <video src="<?= htmlspecialchars($videoUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" preload="metadata" playsinline controls controlsList="nodownload" oncontextmenu="return false;" style="position:absolute; inset:0; width:100%; height:100%; background:#000;"></video>
                    </div>
                <?php else: ?>
                    <div style="position:relative; padding-bottom:56.25%; height:0; overflow:hidden; border-radius:12px; background:#000;">
                        <iframe src="<?= htmlspecialchars($embedUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" frameborder="0" allow="autoplay; encrypted-media" style="position:absolute; inset:0; width:100%; height:100%;"></iframe>
                    </div>
                <?php endif; ?>

                <div style="margin-top:10px; display:flex; flex-wrap:wrap; gap:10px; align-items:center; justify-content:space-between; border-top:1px dashed var(--border); padding-top:10px;">
                    <div class="hint">
                        <?= $isLessonCompleted ? 'Esta aula já está concluída.' : 'Marque como concluída quando terminar.' ?>
                    </div>
                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                        <?php if (!$isLessonCompleted): ?>
                            <form action="<?= $completeAction ?>" method="post" style="margin:0;">
                                <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                                <input type="hidden" name="lesson_id" value="<?= (int)$currentLessonId ?>">
                                <button type="submit" class="btn">Marcar como concluída</button>
                            </form>
                        <?php endif; ?>

                        <?php if ($prevLessonId > 0): ?>
                            <a class="btn-outline" href="<?= $lessonHrefBase ?><?= (int)$prevLessonId ?>">← Anterior</a>
                        <?php endif; ?>
                        <?php if ($nextLessonId > 0): ?>
                            <a class="btn" href="<?= $lessonHrefBase ?><?= (int)$nextLessonId ?>">Próxima →</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div style="margin-top:12px; border:1px solid var(--border); border-radius:16px; padding:12px 12px; background: rgba(255,255,255,0.02);">
            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:6px;">Sobre esta aula</div>
            <?php if ($lessonDescription !== ''): ?>
                <div class="hint" style="white-space:pre-line;"><?= htmlspecialchars($lessonDescription, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
            <?php else: ?>
                <div class="hint">Sem descrição.</div>
            <?php endif; ?>
        </div>

        <div style="margin-top:12px; border:1px solid var(--border); border-radius:16px; padding:12px 12px; background: rgba(255,255,255,0.02);">
            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:8px;">Comentários</div>

            <?php if (empty($lessonComments)): ?>
                <div class="hint" style="margin-bottom:8px;">Ainda não há comentários.</div>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:8px; max-height:260px; overflow:auto; margin-bottom:10px;">
                    <?php foreach ($lessonComments as $c): ?>
                        <div style="border:1px solid var(--border); border-radius:12px; padding:8px 10px; background: rgba(0,0,0,0.30);">
                            <div style="display:flex; justify-content:space-between; gap:10px; margin-bottom:4px;">
                                <div style="font-weight:800; font-size:12px;"><?= htmlspecialchars((string)($c['user_name'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
                                <div style="font-size:10px; color:var(--text-secondary);"><?= htmlspecialchars((string)($c['created_at'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
                            </div>
                            <div class="hint" style="white-space:pre-line; color: var(--text-primary); opacity:0.9;">
                                <?= htmlspecialchars((string)($c['body'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form action="<?= $commentAction ?>" method="post" style="margin:0;">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                <input type="hidden" name="lesson_id" value="<?= (int)$currentLessonId ?>">
                <label>Escreva um comentário</label>
                <input name="body" required placeholder="Seu comentário..." />
                <div style="display:flex; justify-content:flex-end; margin-top:10px;">
                    <button type="submit" class="btn">Enviar</button>
                </div>
            </form>
        </div>

        <div style="margin-top:12px;">
            <a class="btn-outline" href="<?= $membersHref ?>">Voltar para área de membros</a>
        </div>
    </div>

    <div style="flex:0 0 280px; width:280px; border:1px solid var(--border); border-radius:16px; padding:12px 12px; background: rgba(255,255,255,0.02);">
        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:8px;">Aulas</div>
        <div style="display:flex; flex-direction:column; gap:6px; max-height:540px; overflow:auto;">
            <?php foreach ($publishedLessons as $idx => $l): ?>
                <?php
                    $lid = (int)($l['id'] ?? 0);
                    $lt = trim((string)($l['title'] ?? ''));
                    $label = $lt !== '' ? $lt : ('Aula ' . ($idx + 1));
                    $isCur = $lid === $currentLessonId;
                    $isDone = !empty($completedLessonIds[$lid] ?? false);
                ?>
                <a href="<?= $lessonHrefBase ?><?= $lid ?>" style="
                    display:flex; gap:10px; align-items:center; padding:8px 10px; border-radius:12px;
                    border:1px solid <?= $isCur ? 'var(--accent)' : 'transparent' ?>;
                    background: <?= $isCur ? 'rgba(255,255,255,0.05)' : 'transparent' ?>;
                ">
                    <span style="width:10px; height:10px; border-radius:999px; border:2px solid <?= $isDone ? '#6be28d' : 'var(--border)' ?>; background: <?= $isDone ? '#6be28d' : 'transparent' ?>;"></span>
                    <span style="font-size:12px; font-weight:800; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; min-width:0; flex:1;"><?= htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
