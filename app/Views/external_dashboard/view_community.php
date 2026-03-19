<?php
/** @var array $community */
/** @var array $topics */
/** @var bool $isMember */

$communityName = trim((string)($community['name'] ?? ''));
$communityDescription = trim((string)($community['description'] ?? ''));
$communitySlug = trim((string)($community['slug'] ?? ''));
$coverImage = trim((string)($community['cover_image_path'] ?? ''));
?>

<?php if (empty($topics)): ?>
    <div style="text-align: center; padding: 40px;">
        <div style="font-size: 48px; margin-bottom: 12px;">💬</div>
        <p style="font-size: 14px; color: var(--text-secondary);">
            Ainda não há tópicos nesta comunidade. Seja o primeiro a criar um!
        </p>
    </div>
<?php else: ?>
    <div style="display: flex; flex-direction: column; gap: 16px;">
            <?php foreach ($topics as $topic): ?>
                <?php
                    $topicCoverUrl = trim((string)($topic['cover_image_url'] ?? ''));
                    $topicTitle = trim((string)($topic['title'] ?? ''));
                    $topicId = (int)($topic['id'] ?? 0);
                    $authorName = trim((string)($topic['author_name'] ?? 'Anônimo'));
                    $createdAt = $topic['created_at'] ?? '';
                    $repliesCount = (int)($topic['replies_count'] ?? 0);
                    $isPinned = !empty($topic['is_pinned']);
                ?>
                <?php if ($topicCoverUrl !== ''): ?>
                    <!-- Modern card layout for topics with cover -->
                    <div style="background: var(--surface-card); border-radius: 16px; border: 1px solid var(--border); overflow: hidden; transition: transform 0.2s, box-shadow 0.2s;">
                        <div style="width: 100%; aspect-ratio: 16/9; overflow: hidden; background: #000;">
                            <img src="<?= htmlspecialchars($topicCoverUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div style="padding: 16px;">
                            <?php if ($isPinned): ?>
                                <span style="font-size: 12px; margin-bottom: 4px; display: inline-block;">📌 Fixado</span>
                            <?php endif; ?>
                            <h3 style="font-size: 15px; font-weight: 700; color: var(--text-primary); margin: 0 0 4px 0; line-height: 1.3;">
                                <?= htmlspecialchars($topicTitle, ENT_QUOTES, 'UTF-8') ?>
                            </h3>
                            <p style="font-size: 12px; color: var(--text-secondary); margin: 0 0 12px 0;">
                                por <?= htmlspecialchars($authorName, ENT_QUOTES, 'UTF-8') ?>
                            </p>
                            <a href="/painel-externo/comunidade/topico?id=<?= $topicId ?>&slug=<?= urlencode($communitySlug) ?>" 
                               style="display: block; width: 100%; padding: 10px; background: linear-gradient(135deg, #ff6f60 0%, #e53935 100%); border: none; border-radius: 10px; color: #fff; font-size: 14px; font-weight: 600; text-align: center; text-decoration: none; cursor: pointer; transition: transform 0.2s;">
                                Ver tópico
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Simple list layout for topics without cover -->
                    <a href="/painel-externo/comunidade/topico?id=<?= $topicId ?>&slug=<?= urlencode($communitySlug) ?>" 
                       style="display: block; padding: 14px; border: 1px solid var(--border); border-radius: 12px; background: rgba(255,255,255,0.02); text-decoration: none; transition: transform 0.2s;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                            <?php if ($isPinned): ?>
                                <span style="font-size: 12px;">📌</span>
                            <?php endif; ?>
                            <h3 style="font-size: 15px; font-weight: 600; color: var(--text-primary); margin: 0;">
                                <?= htmlspecialchars($topicTitle, ENT_QUOTES, 'UTF-8') ?>
                            </h3>
                        </div>
                        <div style="font-size: 11px; color: var(--text-secondary);">
                            por <?= htmlspecialchars($authorName, ENT_QUOTES, 'UTF-8') ?>
                            <?php if ($repliesCount > 0): ?>
                                • 💬 <?= $repliesCount ?> <?= $repliesCount === 1 ? 'resposta' : 'respostas' ?>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endif; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
