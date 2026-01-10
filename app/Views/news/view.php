<?php
/** @var array $user */
/** @var array $newsItem */
/** @var array|null $content */

$title = (string)($newsItem['title'] ?? '');
$url = (string)($newsItem['url'] ?? '');
$summary = (string)($newsItem['summary'] ?? '');
$img = (string)($newsItem['image_url'] ?? '');
$source = (string)($newsItem['source_name'] ?? '');
$published = (string)($newsItem['published_at'] ?? '');

$exTitle = is_array($content) ? (string)($content['extracted_title'] ?? '') : '';
$exDesc = is_array($content) ? (string)($content['extracted_description'] ?? '') : '';
$exText = is_array($content) ? (string)($content['extracted_text'] ?? '') : '';

$displayTitle = trim($exTitle) !== '' ? $exTitle : $title;
?>

<div style="max-width: 900px; margin: 0 auto;">
    <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; align-items:center; margin-bottom: 12px;">
        <a href="/noticias" style="text-decoration:none; color:var(--text-secondary); font-size:13px;">← Voltar</a>
        <a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" style="text-decoration:none; color:var(--accent-soft); font-size:13px;">Ver fonte original</a>
    </div>

    <div style="border-radius:18px; border:1px solid var(--border-subtle); background: var(--bg-secondary); overflow:hidden;">
        <?php if (trim($img) !== ''): ?>
            <div style="height: 280px; background: rgba(255,255,255,0.04);">
                <img src="<?= htmlspecialchars($img, ENT_QUOTES, 'UTF-8') ?>" alt="" style="width:100%; height:100%; object-fit:cover; display:block;">
            </div>
        <?php endif; ?>

        <div style="padding: 16px 16px 18px 16px;">
            <div style="font-size: 26px; font-weight: 780; line-height: 1.12; letter-spacing: -0.02em;">
                <?= htmlspecialchars($displayTitle, ENT_QUOTES, 'UTF-8') ?>
            </div>

            <div style="margin-top:10px; display:flex; gap:10px; flex-wrap:wrap; align-items:center; color: var(--text-secondary); font-size: 12px;">
                <?php if (trim($source) !== ''): ?>
                    <div>Fonte: <?= htmlspecialchars($source, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <?php if (trim($published) !== ''): ?>
                    <div>Publicado em <?= htmlspecialchars($published, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
            </div>

            <?php if (trim($summary) !== ''): ?>
                <div style="margin-top: 12px; color: var(--text-secondary); font-size: 14px; line-height: 1.55;">
                    <?= htmlspecialchars($summary, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <?php if (trim($exDesc) !== ''): ?>
                <div style="margin-top: 12px; color: var(--text-secondary); font-size: 14px; line-height: 1.55;">
                    <?= htmlspecialchars($exDesc, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <?php if (trim($exText) !== ''): ?>
                <div style="margin-top: 14px; border-top: 1px solid var(--border-subtle); padding-top: 14px;">
                    <?php foreach (preg_split('/\n\n+/', $exText) as $p): ?>
                        <?php $p = trim((string)$p); if ($p === '') continue; ?>
                        <p style="margin: 0 0 12px 0; color: var(--text-primary); font-size: 15px; line-height: 1.65;">
                            <?= htmlspecialchars($p, ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="margin-top: 14px; border-top: 1px solid var(--border-subtle); padding-top: 14px; color: var(--text-secondary); font-size: 13px;">
                    Não foi possível extrair o texto completo desta notícia. Use “Ver fonte original”.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
