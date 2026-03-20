<?php
/** @var array $students */

$students = is_array($students ?? null) ? $students : [];
?>
<div style="max-width: 1100px; margin: 0 auto;">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; gap:10px; flex-wrap:wrap; margin-bottom:12px;">
        <div>
            <h1 style="font-size: 22px; font-weight: 800; margin: 0 0 4px 0;">Alunos</h1>
            <p style="margin:0; font-size:13px; color:var(--text-secondary);">Lista de alunos matriculados nos seus cursos.</p>
        </div>
        <a href="/profissional" style="border-radius:999px; padding:9px 14px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-weight:700; text-decoration:none; font-size:13px;">Voltar</a>
    </div>

    <?php if (empty($students)): ?>
        <div style="background: var(--surface-card); border: 1px solid var(--border-subtle); border-radius: 14px; padding: 14px 16px; color: var(--text-secondary); font-size:13px;">
            Nenhum aluno encontrado ainda.
        </div>
    <?php else: ?>
        <div style="border:1px solid var(--border-subtle); border-radius:14px; overflow:hidden; background:var(--surface-card);">
            <table style="width:100%; border-collapse:collapse; font-size:13px;">
                <thead style="background:var(--surface-subtle);">
                    <tr>
                        <th style="text-align:left; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Aluno</th>
                        <th style="text-align:left; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">E-mail</th>
                        <th style="text-align:left; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Curso</th>
                        <th style="text-align:left; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Inscrito em</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($students as $s): ?>
                    <?php
                        $name = (string)($s['name'] ?? '');
                        $email = (string)($s['email'] ?? '');
                        $courseTitle = (string)($s['course_title'] ?? '');
                        $enrolledAt = (string)($s['enrolled_at'] ?? '');
                    ?>
                    <tr style="border-top:1px solid var(--border-subtle);">
                        <td style="padding:10px 12px;"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="padding:10px 12px; color:var(--text-secondary);"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="padding:10px 12px; color:var(--text-secondary);"><?= htmlspecialchars($courseTitle, ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="padding:10px 12px; color:var(--text-secondary);"><?= htmlspecialchars($enrolledAt, ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
