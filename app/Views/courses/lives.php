<?php
/** @var array $user */
/** @var array $plan */
/** @var array $course */
/** @var array $lives */
/** @var array $myLiveParticipation */
/** @var bool $isEnrolled */

use App\Controllers\CourseController;

$courseTitle = trim((string)($course['title'] ?? ''));
?>
<div style="max-width: 960px; margin: 0 auto; padding: 12px 8px 20px 8px;">
    <div style="margin-bottom:10px;">
        <a href="<?= CourseController::buildCourseUrl($course) ?>#lives" style="color:#ff6f60; font-size:12px; text-decoration:none;">&larr; Voltar para o curso</a>
    </div>

    <h1 style="font-size: 20px; margin: 0 0 4px 0; font-weight: 650;">Lives do curso: <?= htmlspecialchars($courseTitle) ?></h1>
    <p style="color:#b0b0b0; font-size:13px; margin:0 0 10px 0; max-width:640px;">
        Aqui você vê todas as lives deste curso. As próximas lives aparecem com opção para confirmar participação.
        Gravações só ficam disponíveis para quem participou de cada live.
    </p>

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

    <?php if (empty($lives)): ?>
        <div style="color:#b0b0b0; font-size:13px; margin-top:8px;">Nenhuma live cadastrada ainda para este curso.</div>
    <?php else: ?>
        <div style="margin-top:8px; border-radius:12px; border:1px solid #272727; background:#111118; overflow:hidden;">
            <?php foreach ($lives as $live): ?>
                <?php
                    $liveId = (int)($live['id'] ?? 0);
                    $ltitle = trim((string)($live['title'] ?? ''));
                    $ldesc = trim((string)($live['description'] ?? ''));
                    $scheduled = $live['scheduled_at'] ?? '';
                    $formatted = $scheduled ? date('d/m/Y H:i', strtotime($scheduled)) : '';
                    $recordingLink = trim((string)($live['recording_link'] ?? ''));
                    $scheduledTs = $scheduled ? strtotime((string)$scheduled) : null;
                    $nowTs = time();
                    $isFuture = $scheduledTs !== null && $scheduledTs >= $nowTs;
                    $isPast = $scheduledTs !== null && $scheduledTs < $nowTs;
                    $isParticipant = !empty($myLiveParticipation[$liveId] ?? false);
                    $hasRecordingAccess = $isParticipant && $recordingLink !== '';
                ?>
                <div style="padding:10px 12px; border-bottom:1px solid #272727;">
                    <div style="display:flex; justify-content:space-between; gap:8px; align-items:flex-start; flex-wrap:wrap;">
                        <div style="min-width:0;">
                            <div style="font-size:14px; font-weight:600; margin-bottom:2px;">
                                <?= htmlspecialchars($ltitle !== '' ? $ltitle : ('Live ' . $liveId)) ?>
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
                        </div>
                        <div style="text-align:right; font-size:11px; min-width:160px;">
                            <?php if ($isFuture): ?>
                                <div style="margin-bottom:4px;">
                                    <span style="
                                        display:inline-flex; align-items:center; gap:4px;
                                        border-radius:999px; padding:2px 8px;
                                        background:#102436; color:#90caf9;">
                                        Próxima live
                                    </span>
                                </div>
                                <?php if ($isParticipant): ?>
                                    <div style="color:#c8ffd4;">Participação confirmada.</div>
                                <?php else: ?>
                                    <form action="/cursos/lives/participar" method="post" style="display:inline-block; margin-top:2px;">
                                        <input type="hidden" name="live_id" value="<?= $liveId ?>">
                                        <button type="submit" style="
                                            border:none; border-radius:999px; padding:5px 12px;
                                            background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509;
                                            font-weight:600; font-size:11px; cursor:pointer;">
                                            Quero participar
                                        </button>
                                    </form>
                                <?php endif; ?>
                            <?php elseif ($isPast): ?>
                                <div style="margin-bottom:4px;">
                                    <span style="
                                        display:inline-flex; align-items:center; gap:4px;
                                        border-radius:999px; padding:2px 8px;
                                        background:#2a1b2e; color:#f48fb1;">
                                        Live passada
                                    </span>
                                </div>
                                <?php if ($hasRecordingAccess): ?>
                                    <a href="/cursos/lives/ver?live_id=<?= $liveId ?>" style="
                                        display:inline-flex; align-items:center; gap:4px;
                                        border-radius:999px; padding:5px 12px;
                                        background:#111118; border:1px solid #ffcc80; color:#ffcc80;
                                        font-weight:600; font-size:11px; text-decoration:none;">
                                        ▶ Assistir gravação
                                    </a>
                                <?php elseif ($recordingLink !== ''): ?>
                                    <div style="color:#b0b0b0;">Gravação disponível apenas para quem participou.</div>
                                <?php else: ?>
                                    <div style="color:#777;">Esta live ainda não tem gravação disponível.</div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div style="color:#777;">Live sem data definida.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
