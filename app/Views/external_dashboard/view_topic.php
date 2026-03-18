<?php
/** @var array $community */
/** @var array $topic */
/** @var array $posts */
/** @var bool $isMember */

$communityName = trim((string)($community['name'] ?? ''));
$communitySlug = trim((string)($community['slug'] ?? ''));
$topicTitle = trim((string)($topic['title'] ?? ''));
$topicBody = trim((string)($topic['body'] ?? ''));
$topicId = (int)($topic['id'] ?? 0);
$authorName = trim((string)($topic['author_name'] ?? 'Anônimo'));
$createdAt = $topic['created_at'] ?? '';
?>

<div class="header">
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
        <a href="/painel-externo/comunidade/ver?slug=<?= urlencode($communitySlug) ?>" style="color: var(--text-secondary); text-decoration: none; font-size: 14px;">
            ← Voltar para <?= htmlspecialchars($communityName, ENT_QUOTES, 'UTF-8') ?>
        </a>
    </div>
    
    <h1 style="font-size: 24px; font-weight: 700; margin-bottom: 8px;"><?= htmlspecialchars($topicTitle, ENT_QUOTES, 'UTF-8') ?></h1>
    <p style="color: var(--text-secondary); font-size: 14px;">
        Por <?= htmlspecialchars($authorName, ENT_QUOTES, 'UTF-8') ?>
        <?php if ($createdAt): ?>
            • <?= htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') ?>
        <?php endif; ?>
    </p>
</div>

<div class="card" style="margin-bottom: 20px;">
    <div style="font-size: 15px; line-height: 1.6; color: var(--text-primary); white-space: pre-line;">
        <?= nl2br(htmlspecialchars($topicBody, ENT_QUOTES, 'UTF-8')) ?>
    </div>
</div>

<div class="card">
    <h2 style="font-size: 18px; font-weight: 700; margin-bottom: 16px;">
        Respostas (<?= count($posts) ?>)
    </h2>
    
    <?php if (empty($posts)): ?>
        <div style="text-align: center; padding: 40px;">
            <div style="font-size: 48px; margin-bottom: 12px;">💬</div>
            <p style="font-size: 14px; color: var(--text-secondary);">
                Ainda não há respostas. Seja o primeiro a responder!
            </p>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 20px;">
            <?php foreach ($posts as $post): ?>
                <?php
                    $postBody = trim((string)($post['body'] ?? ''));
                    $postAuthor = trim((string)($post['user_name'] ?? 'Anônimo'));
                    $postCreatedAt = $post['created_at'] ?? '';
                ?>
                <div style="padding: 14px; border: 1px solid var(--border); border-radius: 10px; background: rgba(255,255,255,0.02);">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                        <span style="font-weight: 600; color: var(--text-primary);">
                            <?= htmlspecialchars($postAuthor, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <?php if ($postCreatedAt): ?>
                            <span style="font-size: 12px; color: var(--text-secondary);">
                                <?= htmlspecialchars($postCreatedAt, ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div style="font-size: 14px; line-height: 1.6; color: var(--text-primary); white-space: pre-line;">
                        <?= nl2br(htmlspecialchars($postBody, ENT_QUOTES, 'UTF-8')) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($isMember): ?>
        <div style="border-top: 1px solid var(--border); padding-top: 20px; margin-top: 20px;">
            <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 12px;">Responder</h3>
            <form action="/painel-externo/comunidade/topico/responder" method="post">
                <input type="hidden" name="topic_id" value="<?= $topicId ?>">
                <textarea name="body" rows="4" required placeholder="Escreva sua resposta..." 
                          style="width: 100%; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid var(--border); border-radius: 10px; color: var(--text-primary); font-size: 14px; resize: vertical;"></textarea>
                <div style="margin-top: 12px; display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn" style="padding: 10px 24px;">
                        Enviar Resposta
                    </button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <div style="border-top: 1px solid var(--border); padding-top: 20px; margin-top: 20px; text-align: center;">
            <p style="color: var(--text-secondary); font-size: 14px;">
                Você precisa ser membro da comunidade para responder.
            </p>
        </div>
    <?php endif; ?>
</div>
