<?php
/** @var array $user */
/** @var array $news */
/** @var bool $emailEnabled */
/** @var bool $fetchedNow */
/** @var string|null $lastFetchedAt */

$grid = [];
if (is_array($news)) {
    $grid = array_slice($news, 0, 30);
}
?>

<style>
    #news-grid {
        display: grid;
        grid-template-columns: repeat(12, minmax(0, 1fr));
        gap: 12px;
    }
    .news-grid-item {
        grid-column: span 4;
        min-width: 0;
    }
    .news-grid-item.span-6 {
        grid-column: span 6;
    }
    .news-grid-item.span-12 {
        grid-column: span 12;
    }

    .news-card {
        border-radius: 14px;
        border: 1px solid var(--border-subtle);
        background: var(--bg-secondary);
        overflow: hidden;
        height: 230px;
        display: flex;
        flex-direction: column;
    }
    .news-card-img {
        height: 130px;
        background: rgba(255,255,255,0.04);
        flex: 0 0 130px;
    }
    .news-card-body {
        padding: 10px 10px 12px 10px;
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
        min-height: 0;
    }
    .news-card-title {
        font-size: 13px;
        font-weight: 700;
        line-height: 1.25;
        display: -webkit-box;
        -webkit-box-orient: vertical;
        line-clamp: 3;
        -webkit-line-clamp: 3;
        overflow: hidden;
        min-height: 49px;
    }
    .news-card.news-card-full {
        height: 290px;
    }
    .news-card.news-card-full .news-card-img {
        height: 180px;
        flex: 0 0 180px;
    }
    .news-card-meta {
        margin-top: auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        color: var(--text-secondary);
        font-size: 11px;
    }
    @media (max-width: 900px) {
        #news-page-header {
            flex-direction: column;
            align-items: stretch !important;
        }
        #news-email-form {
            width: 100%;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        #news-email-form button {
            width: 100%;
        }
        #news-layout {
            grid-template-columns: 1fr !important;
        }
        #news-hero {
            grid-template-columns: 1fr !important;
        }
        #news-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }
        #news-sidebar {
            margin-top: 14px;
        }

        .news-grid-item,
        .news-grid-item.span-6,
        .news-grid-item.span-12 {
            grid-column: span 1;
        }
        .news-card,
        .news-card.news-card-full {
            height: 230px;
        }
        .news-card.news-card-full .news-card-img {
            height: 130px;
            flex: 0 0 130px;
        }
    }

    @media (max-width: 520px) {
        #news-grid {
            grid-template-columns: 1fr !important;
        }
        #news-title {
            font-size: 26px !important;
        }
        #news-hero-title {
            font-size: 20px !important;
            line-height: 1.15 !important;
        }
        #news-hero-img {
            min-height: 160px !important;
        }
        #news-email-form {
            gap: 8px !important;
        }

        .news-grid-item,
        .news-grid-item.span-6,
        .news-grid-item.span-12 {
            grid-column: span 1;
        }
    }
</style>

<div style="max-width: 1200px; margin: 0 auto;">
    <div id="news-page-header" style="display:flex; align-items:flex-start; justify-content:space-between; gap:14px; margin-bottom: 14px;">
        <div>
            <div id="news-title" style="font-size: 34px; font-weight: 750; letter-spacing: -0.02em;">Discover</div>
            <div style="color: var(--text-secondary); font-size: 13px; margin-top: 4px;">Notícias de marketing no Brasil, atualizadas pela IA.</div>
        </div>

        <div style="display:flex; gap:10px; align-items:center;">
            <form id="news-email-form" action="/noticias/email" method="post" style="display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:12px; border:1px solid var(--border-subtle); background: var(--bg-secondary);">
                <div style="font-size:12px; color: var(--text-secondary);">Notificar por e-mail</div>
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="checkbox" name="email_enabled" value="1" <?= !empty($emailEnabled) ? 'checked' : '' ?> style="transform: translateY(1px);">
                    <span style="font-size:13px; font-weight:600;">Ativado</span>
                </label>
                <button type="submit" style="padding:8px 12px; border-radius:999px; border:1px solid var(--border-subtle); background: linear-gradient(135deg, var(--accent), var(--accent-soft)); color:#050509; font-weight:700; font-size:12px; cursor:pointer;">Salvar</button>
            </form>
        </div>
    </div>

    <div id="news-layout" style="display:grid; grid-template-columns: 1fr 330px; gap: 18px; align-items:start;">
        <div>
            <div id="news-grid">
                <?php foreach ($grid as $idx => $it): ?>
                    <?php
                        if (!is_array($it)) {
                            continue;
                        }
                        $id = (int)($it['id'] ?? 0);
                        $t = (string)($it['title'] ?? '');
                        $img = (string)($it['image_url'] ?? '');
                        $src = (string)($it['source_name'] ?? '');
                        $pub = (string)($it['published_at'] ?? '');

                        $cyclePos = ((int)$idx) % 9;
                        $spanClass = 'span-4';
                        $isFull = false;
                        if ($cyclePos >= 3 && $cyclePos <= 4) {
                            $spanClass = 'span-6';
                        } elseif ($cyclePos === 8) {
                            $spanClass = 'span-12';
                            $isFull = true;
                        }
                    ?>
                    <a href="/noticias/ver?id=<?= (int)$id ?>" class="news-grid-item <?= htmlspecialchars($spanClass, ENT_QUOTES, 'UTF-8') ?>" style="display:block;">
                        <div class="news-card<?= $isFull ? ' news-card-full' : '' ?>">
                            <div class="news-card-img">
                                <?php if ($img !== ''): ?>
                                    <img src="<?= htmlspecialchars($img, ENT_QUOTES, 'UTF-8') ?>" alt="" style="width:100%; height:100%; display:block; object-fit:cover;">
                                <?php else: ?>
                                    <div style="height:100%; display:flex; align-items:center; justify-content:center; color: var(--text-secondary); font-size: 12px;">Sem imagem</div>
                                <?php endif; ?>
                            </div>
                            <div class="news-card-body">
                                <div class="news-card-title"><?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="news-card-meta">
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
