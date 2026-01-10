<?php
/** @var array $user */
/** @var array $news */
/** @var bool $emailEnabled */
/** @var bool $fetchedNow */
/** @var string|null $lastFetchedAt */

$hero = null;
if (!empty($news) && is_array($news[0] ?? null)) {
    $hero = $news[0];
}

$grid = [];
if (is_array($news)) {
    $grid = array_slice($news, 1, 12);
}
?>

<div style="max-width: 1200px; margin: 0 auto;">
    <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:14px; margin-bottom: 14px;">
        <div>
            <div style="font-size: 34px; font-weight: 750; letter-spacing: -0.02em;">Discover</div>
            <div style="color: var(--text-secondary); font-size: 13px; margin-top: 4px;">Notícias de marketing no Brasil, atualizadas pela IA.</div>
        </div>

        <div style="display:flex; gap:10px; align-items:center;">
            <form action="/noticias/email" method="post" style="display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:12px; border:1px solid var(--border-subtle); background: var(--bg-secondary);">
                <div style="font-size:12px; color: var(--text-secondary);">Notificar por e-mail</div>
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="checkbox" name="email_enabled" value="1" <?= !empty($emailEnabled) ? 'checked' : '' ?> style="transform: translateY(1px);">
                    <span style="font-size:13px; font-weight:600;">Ativado</span>
                </label>
                <button type="submit" style="padding:8px 12px; border-radius:999px; border:1px solid var(--border-subtle); background: linear-gradient(135deg, var(--accent), var(--accent-soft)); color:#050509; font-weight:700; font-size:12px; cursor:pointer;">Salvar</button>
            </form>
        </div>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 330px; gap: 18px; align-items:start;">
        <div>
            <?php if (!empty($hero)): ?>
                <?php
                    $heroTitle = (string)($hero['title'] ?? '');
                    $heroId = (int)($hero['id'] ?? 0);
                    $heroSummary = (string)($hero['summary'] ?? '');
                    $heroImg = (string)($hero['image_url'] ?? '');
                    $heroSource = (string)($hero['source_name'] ?? '');
                    $heroPublished = (string)($hero['published_at'] ?? '');
                ?>
                <a href="/noticias/ver?id=<?= (int)$heroId ?>" style="display:block;">
                    <div style="display:grid; grid-template-columns: 1.1fr 0.9fr; gap:14px; padding:16px; border-radius:16px; border:1px solid var(--border-subtle); background: var(--bg-secondary);">
                        <div>
                            <div style="font-size: 28px; font-weight: 760; line-height: 1.08; letter-spacing: -0.02em;"><?= htmlspecialchars($heroTitle, ENT_QUOTES, 'UTF-8') ?></div>
                            <?php if ($heroPublished !== ''): ?>
                                <div style="margin-top:10px; color: var(--text-secondary); font-size: 12px;">Publicado em <?= htmlspecialchars($heroPublished, ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                            <?php if ($heroSummary !== ''): ?>
                                <div style="margin-top:10px; color: var(--text-secondary); font-size: 13px; line-height: 1.45;"><?= htmlspecialchars($heroSummary, ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                            <?php if ($heroSource !== ''): ?>
                                <div style="margin-top:12px; color: var(--text-secondary); font-size: 12px;">Fonte: <?= htmlspecialchars($heroSource, ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                        <div style="border-radius:14px; overflow:hidden; border:1px solid rgba(255,255,255,0.06); background: rgba(255,255,255,0.04); min-height: 180px;">
                            <?php if ($heroImg !== ''): ?>
                                <img src="<?= htmlspecialchars($heroImg, ENT_QUOTES, 'UTF-8') ?>" alt="" style="width:100%; height:100%; display:block; object-fit:cover;">
                            <?php else: ?>
                                <div style="height:100%; display:flex; align-items:center; justify-content:center; color: var(--text-secondary); font-size: 12px;">Sem imagem</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            <?php endif; ?>

            <div style="margin-top:14px; display:grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px;">
                <?php foreach ($grid as $it): ?>
                    <?php
                        if (!is_array($it)) {
                            continue;
                        }
                        $id = (int)($it['id'] ?? 0);
                        $t = (string)($it['title'] ?? '');
                        $img = (string)($it['image_url'] ?? '');
                        $src = (string)($it['source_name'] ?? '');
                        $pub = (string)($it['published_at'] ?? '');
                    ?>
                    <a href="/noticias/ver?id=<?= (int)$id ?>" style="display:block;">
                        <div style="border-radius:14px; border:1px solid var(--border-subtle); background: var(--bg-secondary); overflow:hidden;">
                            <div style="height: 130px; background: rgba(255,255,255,0.04);">
                                <?php if ($img !== ''): ?>
                                    <img src="<?= htmlspecialchars($img, ENT_QUOTES, 'UTF-8') ?>" alt="" style="width:100%; height:100%; display:block; object-fit:cover;">
                                <?php else: ?>
                                    <div style="height:100%; display:flex; align-items:center; justify-content:center; color: var(--text-secondary); font-size: 12px;">Sem imagem</div>
                                <?php endif; ?>
                            </div>
                            <div style="padding: 10px 10px 12px 10px;">
                                <div style="font-size: 13px; font-weight: 700; line-height: 1.25; min-height: 34px;"><?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8') ?></div>
                                <div style="margin-top: 8px; display:flex; align-items:center; justify-content:space-between; gap: 10px; color: var(--text-secondary); font-size: 11px;">
                                    <div style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width: 70%;"><?= htmlspecialchars($src !== '' ? $src : 'Fonte', ENT_QUOTES, 'UTF-8') ?></div>
                                    <div style="white-space:nowrap;"><?= htmlspecialchars($pub !== '' ? $pub : 'Agora', ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div>
            <div style="border-radius:16px; border:1px solid var(--border-subtle); background: var(--bg-secondary); padding: 14px;">
                <div style="font-weight: 750; font-size: 14px; margin-bottom: 10px;">Make it yours</div>
                <div style="color: var(--text-secondary); font-size: 12px; line-height:1.35; margin-bottom: 12px;">As notícias são filtradas para marketing/branding no Brasil e atualizam automaticamente quando você abre essa aba.</div>

                <div style="display:flex; flex-wrap:wrap; gap: 8px; margin-bottom: 12px;">
                    <div style="padding:6px 10px; border-radius:999px; border:1px solid var(--border-subtle); background: rgba(255,255,255,0.03); font-size:12px;">Marketing</div>
                    <div style="padding:6px 10px; border-radius:999px; border:1px solid var(--border-subtle); background: rgba(255,255,255,0.03); font-size:12px;">Branding</div>
                    <div style="padding:6px 10px; border-radius:999px; border:1px solid var(--border-subtle); background: rgba(255,255,255,0.03); font-size:12px;">Social media</div>
                    <div style="padding:6px 10px; border-radius:999px; border:1px solid var(--border-subtle); background: rgba(255,255,255,0.03); font-size:12px;">E-commerce</div>
                    <div style="padding:6px 10px; border-radius:999px; border:1px solid var(--border-subtle); background: rgba(255,255,255,0.03); font-size:12px;">Mídia & Ads</div>
                </div>

                <div style="padding: 12px; border-radius: 14px; border: 1px solid var(--border-subtle); background: rgba(255,255,255,0.02);">
                    <div style="display:flex; justify-content:space-between; gap:10px;">
                        <div style="color: var(--text-secondary); font-size: 12px;">Atualização</div>
                        <div style="font-weight:700; font-size: 12px;"><?= !empty($fetchedNow) ? 'Atualizado agora' : 'Cache' ?></div>
                    </div>
                    <div style="margin-top:6px; color: var(--text-secondary); font-size: 11px;">
                        Última busca: <?= htmlspecialchars($lastFetchedAt ?? '—', ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
