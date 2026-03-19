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
        <?= nl2br(\App\Controllers\CommunitiesController::renderLessonMentions($topicBody)) ?>
    </div>
    
    <?php
    $topicMediaUrl = trim((string)($topic['media_url'] ?? ''));
    $topicMediaKind = trim((string)($topic['media_kind'] ?? ''));
    $topicMediaMime = trim((string)($topic['media_mime'] ?? ''));
    ?>
    <?php if ($topicMediaUrl !== ''): ?>
        <div style="margin-top: 16px;">
            <?php if ($topicMediaKind === 'image'): ?>
                <img src="<?= htmlspecialchars($topicMediaUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" style="max-width: 100%; border-radius: 12px; border: 1px solid var(--border); display: block;">
            <?php elseif ($topicMediaKind === 'video'): ?>
                <video controls style="width: 100%; max-width: 100%; border-radius: 12px; border: 1px solid var(--border); display: block;">
                    <source src="<?= htmlspecialchars($topicMediaUrl, ENT_QUOTES, 'UTF-8') ?>" type="<?= htmlspecialchars($topicMediaMime !== '' ? $topicMediaMime : 'video/mp4', ENT_QUOTES, 'UTF-8') ?>">
                </video>
            <?php else: ?>
                <a href="<?= htmlspecialchars($topicMediaUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" style="color: var(--accent); text-decoration: none;">Ver arquivo anexado</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($topic['poll_question'])): ?>
        <div style="margin-top: 20px; padding: 16px; background: rgba(255,255,255,0.03); border: 1px solid var(--border); border-radius: 12px;">
            <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 12px; color: var(--text-primary);">
                📊 <?= htmlspecialchars($topic['poll_question'], ENT_QUOTES, 'UTF-8') ?>
            </h3>
            <?php
            $pollOptions = !empty($topic['poll_options']) ? json_decode($topic['poll_options'], true) : [];
            $pollVotes = !empty($topic['poll_votes']) ? json_decode($topic['poll_votes'], true) : [];
            $totalVotes = array_sum($pollVotes);
            ?>
            <?php if (!empty($pollOptions) && is_array($pollOptions)): ?>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <?php foreach ($pollOptions as $idx => $option): ?>
                        <?php
                        $votes = isset($pollVotes[$idx]) ? (int)$pollVotes[$idx] : 0;
                        $percentage = $totalVotes > 0 ? round(($votes / $totalVotes) * 100, 1) : 0;
                        ?>
                        <div style="position: relative; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; background: rgba(255,255,255,0.02); overflow: hidden;">
                            <div style="position: absolute; left: 0; top: 0; bottom: 0; width: <?= $percentage ?>%; background: linear-gradient(90deg, var(--accent), transparent); opacity: 0.2; transition: width 0.3s;"></div>
                            <div style="position: relative; display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 14px; color: var(--text-primary);"><?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8') ?></span>
                                <span style="font-size: 13px; color: var(--text-secondary); font-weight: 600;"><?= $percentage ?>% (<?= $votes ?>)</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div style="margin-top: 10px; font-size: 12px; color: var(--text-secondary); text-align: right;">
                    Total de votos: <?= $totalVotes ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
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
                        <?= nl2br(\App\Controllers\CommunitiesController::renderLessonMentions($postBody)) ?>
                    </div>
                    <?php
                    $postMediaUrl = trim((string)($post['media_url'] ?? ''));
                    $postMediaKind = trim((string)($post['media_kind'] ?? ''));
                    $postMediaMime = trim((string)($post['media_mime'] ?? ''));
                    ?>
                    <?php if ($postMediaUrl !== ''): ?>
                        <div style="margin-top: 12px;">
                            <?php if ($postMediaKind === 'image'): ?>
                                <img src="<?= htmlspecialchars($postMediaUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" style="max-width: 100%; border-radius: 10px; border: 1px solid var(--border); display: block;">
                            <?php elseif ($postMediaKind === 'video'): ?>
                                <video controls style="width: 100%; max-width: 100%; border-radius: 10px; border: 1px solid var(--border); display: block;">
                                    <source src="<?= htmlspecialchars($postMediaUrl, ENT_QUOTES, 'UTF-8') ?>" type="<?= htmlspecialchars($postMediaMime !== '' ? $postMediaMime : 'video/mp4', ENT_QUOTES, 'UTF-8') ?>">
                                </video>
                            <?php else: ?>
                                <a href="<?= htmlspecialchars($postMediaUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" style="color: var(--accent); text-decoration: none;">Ver arquivo anexado</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($isMember): ?>
        <div style="border-top: 1px solid var(--border); padding-top: 20px; margin-top: 20px;">
            <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 12px;">Responder</h3>
            <form action="/painel-externo/comunidade/topico/responder" method="post">
                <input type="hidden" name="topic_id" value="<?= $topicId ?>">
                <div style="position: relative;">
                    <textarea id="replyTextarea" name="body" rows="4" required placeholder="Escreva sua resposta... (use @ para mencionar uma aula)" 
                              style="width: 100%; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid var(--border); border-radius: 10px; color: var(--text-primary); font-size: 14px; resize: vertical;"></textarea>
                    <div id="lessonMentionDropdown" style="display: none; position: absolute; background: #111118; border: 1px solid #272727; border-radius: 8px; max-height: 200px; overflow-y: auto; z-index: 1000; box-shadow: 0 4px 12px rgba(0,0,0,0.5); min-width: 250px;"></div>
                </div>
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

<script src="/app/Views/external_dashboard/view_topic_autocomplete.js"></script>
