<?php
/** @var array $community */
/** @var array $topics */
/** @var bool $isMember */

$communityName = trim((string)($community['name'] ?? ''));
$communityDescription = trim((string)($community['description'] ?? ''));
$communitySlug = trim((string)($community['slug'] ?? ''));
$coverImage = trim((string)($community['cover_image_path'] ?? ''));
?>

<?php if ($coverImage !== ''): ?>
    <div style="width: 100%; height: 300px; overflow: hidden; margin-bottom: 20px; background: rgba(255,255,255,0.05); margin-left: -30px; margin-right: -30px; width: calc(100% + 60px);">
        <img src="<?= htmlspecialchars($coverImage, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($communityName, ENT_QUOTES, 'UTF-8') ?>" style="width: 100%; height: 100%; object-fit: contain;">
    </div>
<?php endif; ?>

<div class="card">
    <h2 style="font-size: 20px; font-weight: 700; margin-bottom: 16px;">Tópicos da Comunidade</h2>
    
    <?php if (empty($topics)): ?>
        <div style="text-align: center; padding: 40px;">
            <div style="font-size: 48px; margin-bottom: 12px;">💬</div>
            <p style="font-size: 14px; color: var(--text-secondary);">
                Ainda não há tópicos nesta comunidade. Seja o primeiro a criar um!
            </p>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 1px;">
            <?php foreach ($topics as $topic): ?>
                <?php
                    $topicCoverUrl = trim((string)($topic['cover_image_url'] ?? ''));
                    $topicTitle = trim((string)($topic['title'] ?? ''));
                    $topicId = (int)($topic['id'] ?? 0);
                    $authorName = trim((string)($topic['author_name'] ?? 'Anônimo'));
                    $authorAvatar = trim((string)($topic['author_avatar'] ?? ''));
                    $createdAt = $topic['created_at'] ?? '';
                    $repliesCount = (int)($topic['replies_count'] ?? 0);
                    $isPinned = !empty($topic['is_pinned']);
                    $authorInitial = mb_strtoupper(mb_substr($authorName, 0, 1, 'UTF-8'), 'UTF-8');
                ?>
                <a href="/painel-externo/comunidade/topico?id=<?= $topicId ?>&slug=<?= urlencode($communitySlug) ?>" 
                   style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: var(--surface-card); border-bottom: 1px solid var(--border); text-decoration: none; transition: background 0.2s;"
                   onmouseover="this.style.background='rgba(255,255,255,0.05)'" 
                   onmouseout="this.style.background='var(--surface-card)'">
                    
                    <!-- User Avatar -->
                    <div style="width: 40px; height: 40px; border-radius: 50%; overflow: hidden; background: linear-gradient(135deg, #ff6f60 0%, #e53935 100%); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <?php if ($authorAvatar !== ''): ?>
                            <img src="<?= htmlspecialchars($authorAvatar, ENT_QUOTES, 'UTF-8') ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <span style="font-size: 16px; font-weight: 700; color: #fff;"><?= htmlspecialchars($authorInitial, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Cover Thumbnail (if exists) -->
                    <?php if ($topicCoverUrl !== ''): ?>
                        <div style="width: 60px; height: 40px; border-radius: 6px; overflow: hidden; background: #000; flex-shrink: 0;">
                            <img src="<?= htmlspecialchars($topicCoverUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                    <?php endif; ?>
                    
                    <!-- Topic Info -->
                    <div style="flex: 1; min-width: 0;">
                        <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 2px;">
                            <?php if ($isPinned): ?>
                                <span style="font-size: 12px;">📌</span>
                            <?php endif; ?>
                            <h3 style="font-size: 14px; font-weight: 600; color: var(--text-primary); margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <?= htmlspecialchars($topicTitle, ENT_QUOTES, 'UTF-8') ?>
                            </h3>
                        </div>
                        <div style="font-size: 11px; color: var(--text-secondary);">
                            por <?= htmlspecialchars($authorName, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>
                    
                    <!-- Stats -->
                    <div style="display: flex; align-items: center; gap: 16px; flex-shrink: 0;">
                        <div style="text-align: center; min-width: 40px;">
                            <div style="font-size: 12px; font-weight: 600; color: var(--text-primary);"><?= $repliesCount ?></div>
                            <div style="font-size: 10px; color: var(--text-secondary);">respostas</div>
                        </div>
                        <div style="font-size: 11px; color: var(--text-secondary); min-width: 80px; text-align: right;">
                            <?= htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
