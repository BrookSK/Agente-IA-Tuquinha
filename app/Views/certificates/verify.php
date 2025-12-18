<?php
/** @var array|null $badge */
/** @var array|null $course */
/** @var array|null $student */
/** @var string $issuerName */

$isValid = !empty($badge) && !empty($course) && !empty($student);
?>

<div style="max-width: 720px; margin: 0 auto;">
    <h1 style="font-size:22px; font-weight:700; margin-bottom:10px;">Verificação de certificado</h1>

    <?php if (!$isValid): ?>
        <div style="background:#311; border:1px solid #a33; color:#ffbaba; padding:10px 12px; border-radius:12px; font-size:13px;">
            Certificado não encontrado ou inválido.
        </div>
    <?php else: ?>
        <div style="background:#10330f; border:1px solid #3aa857; color:#c8ffd4; padding:10px 12px; border-radius:12px; font-size:13px;">
            Certificado válido ✅
        </div>

        <div style="margin-top:12px; border-radius:14px; border:1px solid var(--border-subtle); background:var(--surface-card); padding:12px 12px;">
            <div style="display:flex; flex-direction:column; gap:8px; font-size:13px;">
                <div><strong>Aluno:</strong> <?= htmlspecialchars((string)($student['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                <div><strong>Curso:</strong> <?= htmlspecialchars((string)($course['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                <div><strong>Emissor:</strong> <?= htmlspecialchars((string)$issuerName, ENT_QUOTES, 'UTF-8') ?></div>
                <div><strong>Carga horária:</strong> <?= !empty($course['certificate_workload_hours']) ? (int)$course['certificate_workload_hours'] . 'h' : '-' ?></div>
                <div><strong>Período:</strong> <?= !empty($badge['started_at']) ? htmlspecialchars((string)$badge['started_at']) : '-' ?> até <?= !empty($badge['finished_at']) ? htmlspecialchars((string)$badge['finished_at']) : '-' ?></div>
                <div style="font-size:12px; color:var(--text-secondary);"><strong>Código:</strong> <?= htmlspecialchars((string)($badge['certificate_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>
    <?php endif; ?>
</div>
