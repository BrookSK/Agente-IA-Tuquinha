<?php
/** @var array|null $user */
/** @var array|null $plan */
/** @var array $course */
/** @var array $lessons */
/** @var array $lives */
/** @var array $commentsByLesson */
/** @var bool $isEnrolled */
/** @var bool $planAllowsCourses */
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
?>
<div style="max-width: 960px; margin: 0 auto;">
    <div style="display:flex; flex-wrap:wrap; gap:20px; margin-bottom:18px;">
        <div style="flex:1 1 260px; min-width:260px; max-width:360px; border-radius:20px; overflow:hidden; border:1px solid #272727; background:#050509; box-shadow:0 18px 35px rgba(0,0,0,0.55);">
            <div style="width:100%; height:220px; overflow:hidden; background:#111118;">
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
                    <div style="color:#b0b0b0; line-height:1.4; margin-bottom:6px;">
                        <?= htmlspecialchars($short) ?>
                    </div>
                <?php endif; ?>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:4px;">
                    <div style="font-size:11px; color:#ffcc80;">
                        <?php if ($isPaid): ?>
                            R$ <?= number_format(max($priceCents,0)/100, 2, ',', '.') ?>
                        <?php else: ?>
                            Gratuito para planos com cursos
                        <?php endif; ?>
                    </div>
                    <div style="font-size:11px; color:#b0b0b0; text-align:right;">
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

            <?php if ($description !== ''): ?>
                <div style="font-size:13px; color:#d0d0d0; line-height:1.5; margin-bottom:10px; white-space:pre-line;">
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
                        <span style="
                            display:inline-flex; align-items:center; gap:6px; padding:8px 16px;
                            border-radius:999px; border:1px solid #3aa857;
                            background:#10330f; color:#c8ffd4; font-size:13px;">
                            Você já está inscrito neste curso
                        </span>
                    <?php else: ?>
                        <?php if ($isPaid && $allowPublicPurchase && !$planAllowsCourses): ?>
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
            </div>
        </div>
    </div>

    <div style="margin-top:16px; display:flex; flex-wrap:wrap; gap:24px;">
        <div style="flex:2 1 360px; min-width:260px;">
            <h2 style="font-size:16px; margin-bottom:8px;">Aulas do curso</h2>
            <?php if (empty($lessons)): ?>
                <div style="color:#b0b0b0; font-size:13px;">Nenhuma aula cadastrada ainda.</div>
            <?php else: ?>
                <div style="border-radius:12px; border:1px solid #272727; background:#111118; overflow:hidden;">
                    <?php foreach ($lessons as $idx => $lesson): ?>
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
                        ?>
                        <div id="lesson-<?= $lessonId ?>" style="padding:8px 10px; border-bottom:1px solid #272727;">
                            <div style="display:flex; justify-content:space-between; gap:8px; align-items:center;">
                                <div style="font-size:13px; font-weight:600;">
                                    Aula <?= $number ?>: <?= htmlspecialchars($ltitle) ?>
                                </div>
                                <?php if ($video !== ''): ?>
                                    <a href="<?= htmlspecialchars($video) ?>" target="_blank" rel="noopener noreferrer" style="font-size:11px; color:#ff6f60; text-decoration:none;">Assistir</a>
                                <?php endif; ?>
                            </div>
                            <?php if ($ldesc !== ''): ?>
                                <div style="margin-top:4px; font-size:12px; color:#b0b0b0; line-height:1.4;">
                                    <?= htmlspecialchars($ldesc) ?>
                                </div>
                            <?php endif; ?>

                            <div style="margin-top:6px; padding-top:6px; border-top:1px dashed #272727;">
                                <div style="font-size:12px; color:#b0b0b0; margin-bottom:4px;">Comentários da aula</div>

                                <?php if (empty($lessonComments)): ?>
                                    <div style="font-size:12px; color:#555;">Ainda não há comentários nesta aula.</div>
                                <?php else: ?>
                                    <div style="display:flex; flex-direction:column; gap:6px; margin-bottom:6px; max-height:220px; overflow:auto;">
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

                                <?php if ($user): ?>
                                    <?php if ($canCommentLesson): ?>
                                        <form action="/cursos/aulas/comentar" method="post" style="margin-top:4px;">
                                            <input type="hidden" name="course_id" value="<?= (int)($course['id'] ?? 0) ?>">
                                            <input type="hidden" name="lesson_id" value="<?= $lessonId ?>">
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
                                            Faça login e inscreva-se no curso para comentar.
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div style="margin-top:4px; font-size:11px; color:#777;">
                                        <a href="/login" style="color:#ff6f60; text-decoration:none;">Entre na sua conta para comentar esta aula.</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div id="lives" style="flex:1 1 260px; min-width:240px;">
            <h2 style="font-size:16px; margin-bottom:8px;">Lives deste curso</h2>
            <?php if (empty($lives)): ?>
                <div style="color:#b0b0b0; font-size:13px;">Nenhuma live agendada ainda.</div>
            <?php else: ?>
                <div style="border-radius:12px; border:1px solid #272727; background:#111118; overflow:hidden;">
                    <?php foreach ($lives as $live): ?>
                        <?php
                            $ltitle = trim((string)($live['title'] ?? ''));
                            $ldesc = trim((string)($live['description'] ?? ''));
                            $scheduled = $live['scheduled_at'] ?? '';
                            $formatted = $scheduled ? date('d/m/Y H:i', strtotime($scheduled)) : '';
                            $meetLink = trim((string)($live['meet_link'] ?? ''));
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
                                <div style="font-size:12px; color:#b0b0b0; margin-bottom:4px; line-height:1.4;">
                                    <?= htmlspecialchars($ldesc) ?>
                                </div>
                            <?php endif; ?>
                            <div style="margin-top:4px;">
                                <?php if (!$user): ?>
                                    <a href="/login" style="font-size:11px; color:#ff6f60; text-decoration:none;">Entrar para participar</a>
                                <?php elseif (!$isEnrolled): ?>
                                    <span style="font-size:11px; color:#b0b0b0;">Inscreva-se no curso para participar desta live.</span>
                                <?php else: ?>
                                    <?php if (!empty($myLiveParticipation[$liveId] ?? false)): ?>
                                        <span style="font-size:11px; color:#c8ffd4;">Você já está inscrito nesta live.</span>
                                        <?php if ($recordingLink !== ''): ?>
                                            <div style="margin-top:4px; font-size:11px; color:#b0b0b0;">
                                                Gravação disponível apenas para quem participou desta live.
                                            </div>
                                        <?php elseif ($meetLink !== ''): ?>
                                            <div style="margin-top:4px; font-size:11px; color:#b0b0b0;">
                                                No horário da live, você receberá o link por e-mail.
                                            </div>
                                        <?php endif; ?>
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
                                        <?php if ($recordingLink !== ''): ?>
                                            <div style="margin-top:4px; font-size:11px; color:#b0b0b0;">
                                                Gravação disponível apenas para quem participou desta live.
                                            </div>
                                        <?php elseif ($meetLink !== ''): ?>
                                            <div style="margin-top:4px; font-size:11px; color:#b0b0b0;">
                                                Link será enviado por e-mail após a confirmação.
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <?php if ($hasRecordingAccess): ?>
                                <div style="margin-top:4px; font-size:11px; color:#b0b0b0;">
                                    <a href="<?= htmlspecialchars($recordingLink) ?>" target="_blank" rel="noopener noreferrer" style="color:#ffcc80; text-decoration:none;">▶ Assistir gravação desta live</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div style="margin-top:18px; font-size:12px; color:#777;">
        <a href="/cursos" style="color:#ff6f60; text-decoration:none;">&larr; Voltar para lista de cursos</a>
    </div>
</div>
