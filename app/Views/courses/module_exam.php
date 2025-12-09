<?php
/** @var array $user */
/** @var array $course */
/** @var array $module */
/** @var array $exam */
/** @var array $questions */
/** @var int $attempts */
/** @var int $maxAttempts */
?>
<div style="max-width: 760px; margin: 0 auto;">
    <h1 style="font-size: 20px; margin-bottom: 8px; font-weight: 650;">
        Prova do módulo: <?= htmlspecialchars($module['title'] ?? '') ?>
    </h1>
    <p style="color:#b0b0b0; font-size:13px; margin-bottom:10px;">
        Responda às perguntas abaixo para avançar neste módulo do curso
        <strong><?= htmlspecialchars($course['title'] ?? '') ?></strong>.
    </p>

    <div style="margin-bottom:10px; font-size:12px; color:#b0b0b0; display:flex; justify-content:space-between; gap:8px; flex-wrap:wrap;">
        <span>Mínimo para aprovação: <strong><?= (int)($exam['pass_score_percent'] ?? 0) ?>%</strong></span>
        <span>
            Tentativas usadas: <strong><?= (int)$attempts ?></strong>
            <?php if ($maxAttempts > 0): ?>
                / <?= (int)$maxAttempts ?>
            <?php else: ?>
                (sem limite definido)
            <?php endif; ?>
        </span>
    </div>

    <?php if (!empty($_SESSION['courses_error'])): ?>
        <div style="background:#311; border:1px solid #a33; color:#ffbaba; padding:8px 10px; border-radius:8px; font-size:13px; margin-bottom:10px;">
            <?= htmlspecialchars($_SESSION['courses_error']) ?>
        </div>
        <?php unset($_SESSION['courses_error']); ?>
    <?php endif; ?>

    <form action="/cursos/modulos/prova" method="post" style="display:flex; flex-direction:column; gap:12px;">
        <input type="hidden" name="course_id" value="<?= (int)$course['id'] ?>">
        <input type="hidden" name="module_id" value="<?= (int)$module['id'] ?>">

        <?php foreach ($questions as $idx => $q): ?>
            <?php
                $qid = (int)($q['id'] ?? 0);
                $text = (string)($q['text'] ?? '');
                $options = $q['options'] ?? [];
            ?>
            <div style="border-radius:10px; border:1px solid #272727; background:#111118; padding:10px 12px;">
                <div style="font-size:13px; font-weight:600; margin-bottom:6px;">
                    Questão <?= $idx + 1 ?>
                </div>
                <div style="font-size:13px; color:#f5f5f5; margin-bottom:8px;">
                    <?= htmlspecialchars($text) ?>
                </div>

                <div style="display:flex; flex-direction:column; gap:6px;">
                    <?php foreach ($options as $opt): ?>
                        <?php $optId = (int)($opt['id'] ?? 0); ?>
                        <label style="display:flex; align-items:flex-start; gap:6px; font-size:13px; color:#ddd;">
                            <input type="radio" name="answers[<?= $qid ?>]" value="<?= $optId ?>" style="margin-top:2px;">
                            <span><?= htmlspecialchars($opt['option_text'] ?? '') ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div style="margin-top:12px; display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
            <button type="submit" style="
                border:none; border-radius:999px; padding:9px 18px;
                background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509;
                font-weight:600; font-size:13px; cursor:pointer;">
                Enviar respostas
            </button>
            <a href="<?= \App\Controllers\CourseController::buildCourseUrl($course) ?>" style="
                display:inline-flex; align-items:center; padding:8px 14px;
                border-radius:999px; border:1px solid #272727; color:#f5f5f5;
                font-size:13px; text-decoration:none;">
                Voltar para o curso
            </a>
        </div>
    </form>
</div>
