<?php
/** @var array $sales */

$sales = is_array($sales ?? null) ? $sales : [];
?>
<div style="max-width: 1100px; margin: 0 auto;">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; gap:10px; flex-wrap:wrap; margin-bottom:12px;">
        <div>
            <h1 style="font-size: 22px; font-weight: 800; margin: 0 0 4px 0;">Vendas</h1>
            <p style="margin:0; font-size:13px; color:var(--text-secondary);">Últimas compras registradas nos seus cursos.</p>
        </div>
        <a href="/profissional" style="border-radius:999px; padding:9px 14px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-weight:700; text-decoration:none; font-size:13px;">Voltar</a>
    </div>

    <?php if (empty($sales)): ?>
        <div style="background: var(--surface-card); border: 1px solid var(--border-subtle); border-radius: 14px; padding: 14px 16px; color: var(--text-secondary); font-size:13px;">
            Nenhuma venda encontrada.
        </div>
    <?php else: ?>
        <div style="border:1px solid var(--border-subtle); border-radius:14px; overflow:hidden; background:var(--surface-card);">
            <table style="width:100%; border-collapse:collapse; font-size:13px;">
                <thead style="background:var(--surface-subtle);">
                    <tr>
                        <th style="text-align:left; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Curso</th>
                        <th style="text-align:left; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Aluno</th>
                        <th style="text-align:left; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">E-mail</th>
                        <th style="text-align:left; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Status</th>
                        <th style="text-align:right; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Valor</th>
                        <th style="text-align:left; padding:10px 12px; border-bottom:1px solid var(--border-subtle);">Data</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($sales as $row): ?>
                    <?php
                        $courseTitle = (string)($row['course_title'] ?? '');
                        $studentName = (string)($row['student_name'] ?? '');
                        $studentEmail = (string)($row['student_email'] ?? '');
                        $status = (string)($row['status'] ?? '');
                        $amountCents = isset($row['amount_cents']) ? (int)$row['amount_cents'] : 0;
                        $amount = $amountCents > 0 ? ('R$ ' . number_format($amountCents / 100, 2, ',', '.')) : '-';
                        $createdAt = (string)($row['created_at'] ?? '');
                    ?>
                    <tr style="border-top:1px solid var(--border-subtle);">
                        <td style="padding:10px 12px;"><?= htmlspecialchars($courseTitle, ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="padding:10px 12px; color:var(--text-secondary);"><?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="padding:10px 12px; color:var(--text-secondary);"><?= htmlspecialchars($studentEmail, ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="padding:10px 12px; color:var(--text-secondary);"><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="padding:10px 12px; text-align:right; color:var(--text-secondary);"><?= htmlspecialchars($amount, ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="padding:10px 12px; color:var(--text-secondary);"><?= htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
