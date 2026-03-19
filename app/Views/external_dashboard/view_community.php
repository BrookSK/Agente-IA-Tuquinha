<?php
/** @var array $community */
/** @var array $topics */
/** @var bool $isMember */

$communityName = trim((string)($community['name'] ?? ''));
$communityDescription = trim((string)($community['description'] ?? ''));
$communitySlug = trim((string)($community['slug'] ?? ''));
$coverImage = trim((string)($community['cover_image_path'] ?? ''));
?>

<div class="header">
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
        <a href="/painel-externo/comunidade" style="color: var(--text-secondary); text-decoration: none; font-size: 14px;">
            ← Voltar para comunidades
        </a>
    </div>
    
    <?php if ($coverImage !== ''): ?>
        <div style="width: 100%; max-width: 1200px; height: 300px; border-radius: 14px; overflow: hidden; margin-bottom: 20px; background: rgba(255,255,255,0.05);">
            <img src="<?= htmlspecialchars($coverImage, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($communityName, ENT_QUOTES, 'UTF-8') ?>" style="width: 100%; height: 100%; object-fit: contain;">
        </div>
    <?php endif; ?>
    
    <h1><?= htmlspecialchars($communityName, ENT_QUOTES, 'UTF-8') ?></h1>
    
    <?php if ($communityDescription !== ''): ?>
        <p style="margin-top: 8px;"><?= htmlspecialchars($communityDescription, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
</div>

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
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php foreach ($topics as $topic): ?>
                <?php
                    $topicTitle = trim((string)($topic['title'] ?? ''));
                    $topicId = (int)($topic['id'] ?? 0);
                    $authorName = trim((string)($topic['author_name'] ?? 'Anônimo'));
                    $createdAt = $topic['created_at'] ?? '';
                    $repliesCount = (int)($topic['replies_count'] ?? 0);
                    $isPinned = !empty($topic['is_pinned']);
                ?>
                <a href="/painel-externo/comunidade/topico?id=<?= $topicId ?>&slug=<?= urlencode($communitySlug) ?>" 
                   style="display: block; padding: 14px; border: 1px solid var(--border); border-radius: 10px; background: rgba(255,255,255,0.02); text-decoration: none; transition: background 0.2s;"
                   onmouseover="this.style.background='rgba(255,255,255,0.05)'" 
                   onmouseout="this.style.background='rgba(255,255,255,0.02)'">
                    <div style="display: flex; justify-content: space-between; align-items: start; gap: 12px;">
                        <div style="flex: 1; min-width: 0;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                <?php if ($isPinned): ?>
                                    <span style="font-size: 12px;">📌</span>
                                <?php endif; ?>
                                <h3 style="font-size: 16px; font-weight: 600; color: var(--text-primary); margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?= htmlspecialchars($topicTitle, ENT_QUOTES, 'UTF-8') ?>
                                </h3>
                            </div>
                            <div style="font-size: 12px; color: var(--text-secondary);">
                                Por <?= htmlspecialchars($authorName, ENT_QUOTES, 'UTF-8') ?>
                                <?php if ($createdAt): ?>
                                    • <?= htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="font-size: 12px; color: var(--text-secondary); white-space: nowrap;">
                            💬 <?= $repliesCount ?> <?= $repliesCount === 1 ? 'resposta' : 'respostas' ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
