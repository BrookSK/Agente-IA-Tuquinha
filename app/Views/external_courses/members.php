<?php
/** @var array $course */
/** @var string $token */
/** @var int $firstLessonId */

$title = trim((string)($course['title'] ?? ''));
$desc = trim((string)($course['short_description'] ?? ''));

$isPartnerSite = !empty($isPartnerSite);
$slug = isset($slug) ? trim((string)$slug) : '';

$lessonHrefBase = '/';
$backHref = '/';
if ($slug !== '') {
    $lessonHrefBase = '/curso/' . urlencode($slug) . '/aula?lesson_id=';
    $backHref = '/curso/' . urlencode($slug);
}
?>

<h1 style="font-size:20px; font-weight:900; margin:0 0 8px 0;">Bem-vindo(a)!</h1>
<div class="hint" style="margin-bottom:12px;">Você já tem acesso à área de membros deste curso.</div>

<div style="border:1px solid var(--border); border-radius:16px; padding:14px 14px; background: rgba(255,255,255,0.02);">
    <div style="font-size:12px; color:var(--text-secondary); text-transform:uppercase; letter-spacing:0.12em;">Curso</div>
    <div style="font-size:18px; font-weight:900; margin-top:6px;"><?= htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
    <?php if ($desc !== ''): ?>
        <div class="hint" style="margin-top:6px;"><?= htmlspecialchars($desc, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
    <?php endif; ?>

    <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-top:12px;">
        <?php if ($firstLessonId > 0): ?>
            <a class="btn" href="<?= $lessonHrefBase ?><?= (int)$firstLessonId ?>">Acessar aulas</a>
        <?php else: ?>
            <span class="hint">Ainda não há aulas publicadas.</span>
        <?php endif; ?>
        <a class="btn-outline" href="<?= $backHref ?>">Voltar para a página do curso</a>
    </div>
</div>
