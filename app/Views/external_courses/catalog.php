<?php
$companyName = isset($branding) && is_array($branding) ? trim((string)($branding['company_name'] ?? '')) : '';
?>

<div class="container" style="max-width: 1100px;">
    <div style="text-align:center; margin-bottom: 2rem;">
        <h1 style="font-size: 2.25rem; font-weight: 900; margin-bottom: 0.5rem;">
            <?= htmlspecialchars($companyName !== '' ? $companyName : 'Cursos', ENT_QUOTES, 'UTF-8') ?>
        </h1>
        <p style="color: var(--text-secondary); font-size: 1.05rem;">
            Escolha um curso para continuar
        </p>
    </div>

    <?php if (empty($courses) || !is_array($courses)): ?>
        <div class="card" style="max-width: 560px; margin: 0 auto; text-align:center;">
            <div style="font-weight: 800; margin-bottom: 0.25rem;">Nenhum curso disponível</div>
            <div style="color: var(--text-secondary); font-size: 0.95rem;">Volte mais tarde.</div>
        </div>
    <?php else: ?>
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem;">
            <?php foreach ($courses as $course): ?>
                <?php
                    $slug = trim((string)($course['slug'] ?? ''));
                    $title = trim((string)($course['title'] ?? 'Curso'));
                    $desc = trim((string)($course['short_description'] ?? ''));
                    $imagePath = trim((string)($course['image_path'] ?? ''));
                ?>
                <div class="card" style="display:flex; flex-direction:column; gap: 0.75rem;">
                    <?php if ($imagePath !== ''): ?>
                        <img src="<?= htmlspecialchars($imagePath, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" alt="<?= htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" style="width: 100%; height: 160px; object-fit: cover; border-radius: 14px; border: 1px solid var(--border);" />
                    <?php endif; ?>

                    <div style="font-size: 1.1rem; font-weight: 900;">
                        <?= htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                    </div>

                    <?php if ($desc !== ''): ?>
                        <div style="color: var(--text-secondary); font-size: 0.95rem; line-height: 1.45;">
                            <?= htmlspecialchars($desc, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>

                    <div style="margin-top: auto; display:flex; justify-content:flex-end;">
                        <?php if ($slug !== ''): ?>
                            <a class="btn" href="/curso/<?= urlencode($slug) ?>" style="text-decoration:none;">Ver curso</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
