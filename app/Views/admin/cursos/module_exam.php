<?php
/** @var array $course */
/** @var array $module */
/** @var array|null $exam */
/** @var array $questions */
$passScore = isset($exam['pass_score_percent']) ? (int)$exam['pass_score_percent'] : 70;
$maxAttempts = isset($exam['max_attempts']) ? (int)$exam['max_attempts'] : 3;
$isActive = !empty($exam['is_active']);
?>
<div style="max-width: 900px; margin: 0 auto;">
    <h1 style="font-size: 20px; margin-bottom: 8px; font-weight: 650;">
        Prova do módulo: <?= htmlspecialchars($module['title'] ?? '') ?>
    </h1>
    <p style="color:#b0b0b0; font-size:13px; margin-bottom:10px;">
        Defina a taxa de aprovação, o número máximo de tentativas e as perguntas de múltipla escolha para este módulo.
    </p>

    <div style="margin-bottom:12px; display:flex; justify-content:space-between; gap:8px; align-items:center;">
        <a href="/admin/cursos/modulos?course_id=<?= (int)$course['id'] ?>" style="font-size:12px; color:#b0b0b0; text-decoration:none;">&larr; Voltar para módulos</a>
        <span style="font-size:11px; color:#777;">Curso: <?= htmlspecialchars($course['title'] ?? '') ?></span>
    </div>

    <?php if (!empty($_SESSION['admin_course_error'])): ?>
        <div style="background:#311; border:1px solid #a33; color:#ffbaba; padding:8px 10px; border-radius:8px; font-size:13px; margin-bottom:10px;">
            <?= htmlspecialchars($_SESSION['admin_course_error']) ?>
        </div>
        <?php unset($_SESSION['admin_course_error']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['admin_course_success'])): ?>
        <div style="background:#10330f; border:1px solid #3aa857; color:#c8ffd4; padding:8px 10px; border-radius:8px; font-size:13px; margin-bottom:10px;">
            <?= htmlspecialchars($_SESSION['admin_course_success']) ?>
        </div>
        <?php unset($_SESSION['admin_course_success']); ?>
    <?php endif; ?>

    <form action="/admin/cursos/modulos/prova" method="post" style="display:flex; flex-direction:column; gap:12px;">
        <input type="hidden" name="course_id" value="<?= (int)$course['id'] ?>">
        <input type="hidden" name="module_id" value="<?= (int)$module['id'] ?>">

        <div style="display:flex; gap:14px; flex-wrap:wrap;">
            <div style="flex:1 1 160px;">
                <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Taxa de aprovação mínima (%)</label>
                <input type="number" name="pass_score_percent" min="0" max="100" value="<?= $passScore ?>" style="
                    width:120px; padding:8px 10px; border-radius:8px; border:1px solid #272727;
                    background:#050509; color:#f5f5f5; font-size:13px;">
                <div style="font-size:11px; color:#777; margin-top:3px;">Ex: 70 significa que o aluno precisa acertar 70% das questões.</div>
            </div>
            <div style="flex:1 1 160px;">
                <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Máximo de tentativas</label>
                <input type="number" name="max_attempts" min="1" value="<?= $maxAttempts ?>" style="
                    width:120px; padding:8px 10px; border-radius:8px; border:1px solid #272727;
                    background:#050509; color:#f5f5f5; font-size:13px;">
                <div style="font-size:11px; color:#777; margin-top:3px;">Depois de atingir esse limite sem passar, o módulo ficará travado.</div>
            </div>
            <div style="flex:1 1 180px; display:flex; align-items:flex-end;">
                <label style="display:flex; align-items:center; gap:5px; font-size:13px; color:#ddd;">
                    <input type="checkbox" name="is_active" value="1" <?= $isActive ? 'checked' : '' ?>>
                    <span>Ativar prova deste módulo</span>
                </label>
            </div>
        </div>

        <div style="margin-top:8px; padding:10px 12px; border-radius:10px; border:1px solid #272727; background:#0b0b10;">
            <div style="font-size:13px; color:#ddd; margin-bottom:8px; font-weight:600;">Perguntas</div>
            <div style="font-size:11px; color:#777; margin-bottom:10px;">
                Cada pergunta terá até 4 alternativas, com apenas uma correta. Deixe perguntas em branco se não for usar todas.
            </div>

            <?php foreach ($questions as $i => $q): ?>
                <?php
                    $qText = (string)($q['text'] ?? '');
                    $opts = $q['options'] ?? ['', '', '', ''];
                    $correct = (int)($q['correct'] ?? 0);
                ?>
                <div style="margin-bottom:12px; padding:10px; border-radius:8px; border:1px solid #272727; background:#111118;">
                    <div style="font-size:12px; color:#b0b0b0; margin-bottom:6px;">Pergunta <?= $i + 1 ?></div>
                    <textarea name="questions[<?= $i ?>][text]" rows="2" style="
                        width:100%; padding:6px 8px; border-radius:6px; border:1px solid #272727;
                        background:#050509; color:#f5f5f5; font-size:13px; resize:vertical; margin-bottom:8px;">
<?= htmlspecialchars($qText) ?></textarea>

                    <div style="display:flex; flex-wrap:wrap; gap:8px;">
                        <?php for ($j = 0; $j < 4; $j++): ?>
                            <div style="flex:1 1 200px;">
                                <label style="font-size:11px; color:#999; display:block; margin-bottom:3px;">Alternativa <?= chr(65 + $j) ?></label>
                                <input type="text" name="questions[<?= $i ?>][options][<?= $j ?>]" value="<?= htmlspecialchars($opts[$j] ?? '') ?>" style="
                                    width:100%; padding:6px 8px; border-radius:6px; border:1px solid #272727;
                                    background:#050509; color:#f5f5f5; font-size:13px;">
                            </div>
                        <?php endfor; ?>
                    </div>

                    <div style="margin-top:8px; font-size:12px; color:#ddd; display:flex; align-items:center; gap:6px;">
                        <span>Alternativa correta:</span>
                        <select name="questions[<?= $i ?>][correct]" style="
                            padding:4px 8px; border-radius:999px; border:1px solid #272727;
                            background:#050509; color:#f5f5f5; font-size:12px;">
                            <?php for ($j = 0; $j < 4; $j++): ?>
                                <option value="<?= $j ?>" <?= $correct === $j ? 'selected' : '' ?>><?= chr(65 + $j) ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="margin-top:12px; display:flex; gap:8px;">
            <button type="submit" style="
                border:none; border-radius:999px; padding:8px 16px;
                background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509;
                font-weight:600; font-size:13px; cursor:pointer;">
                Salvar prova do módulo
            </button>
            <a href="/admin/cursos/modulos?course_id=<?= (int)$course['id'] ?>" style="
                display:inline-flex; align-items:center; padding:8px 14px;
                border-radius:999px; border:1px solid #272727; color:#f5f5f5;
                font-size:13px; text-decoration:none;">
                Cancelar
            </a>
        </div>
    </form>
</div>
