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
                    $parentPostId = isset($post['parent_post_id']) ? (int)$post['parent_post_id'] : null;
                    
                    // Find parent post info if this is a reply
                    $parentAuthor = null;
                    if ($parentPostId) {
                        foreach ($posts as $p) {
                            if ((int)$p['id'] === $parentPostId) {
                                $parentAuthor = trim((string)($p['user_name'] ?? 'Anônimo'));
                                break;
                            }
                        }
                    }
                ?>
                <div style="padding: 14px; border: 1px solid var(--border); border-radius: 10px; background: rgba(255,255,255,0.02); <?= $parentPostId ? 'margin-left: 40px; border-left: 3px solid var(--accent);' : '' ?>">
                    <?php if ($parentPostId && $parentAuthor): ?>
                        <div style="font-size: 12px; color: var(--text-secondary); margin-bottom: 8px; padding: 6px 10px; background: rgba(255,255,255,0.03); border-radius: 6px;">
                            ↳ Respondendo a <strong style="color: var(--accent);"><?= htmlspecialchars($parentAuthor, ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                    <?php endif; ?>
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
                    
                    <?php
                        $postId = (int)($post['id'] ?? 0);
                        $likesCountForPost = $likesCount[$postId] ?? 0;
                        $isLikedByUser = isset($likedByUser[$postId]);
                    ?>
                    <div style="margin-top: 12px; display: flex; gap: 12px; align-items: center;">
                        <button onclick="toggleLike(<?= $postId ?>)" 
                                id="like-btn-<?= $postId ?>"
                                style="background: none; border: 1px solid var(--border); border-radius: 6px; padding: 6px 12px; cursor: pointer; display: flex; align-items: center; gap: 6px; color: <?= $isLikedByUser ? '#ff6b6b' : 'var(--text-secondary)' ?>; transition: all 0.2s;">
                            <span id="like-icon-<?= $postId ?>"><?= $isLikedByUser ? '❤️' : '🤍' ?></span>
                            <span id="like-count-<?= $postId ?>" style="font-size: 13px; font-weight: 500;"><?= $likesCountForPost ?></span>
                        </button>
                        <?php if ($isMember): ?>
                            <button onclick="showReplyForm(<?= $postId ?>, '<?= htmlspecialchars($postAuthor, ENT_QUOTES, 'UTF-8') ?>')" 
                                    style="background: none; border: 1px solid var(--border); border-radius: 6px; padding: 6px 12px; cursor: pointer; display: flex; align-items: center; gap: 6px; color: var(--text-secondary); transition: all 0.2s; font-size: 13px;">
                                💬 Responder
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($isMember): ?>
        <div style="border-top: 1px solid var(--border); padding-top: 20px; margin-top: 20px;">
            <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 12px;">Responder</h3>
            <div id="replyingTo" style="display: none; padding: 8px 12px; background: rgba(255,255,255,0.03); border-left: 3px solid var(--accent); border-radius: 4px; margin-bottom: 12px; font-size: 13px; color: var(--text-secondary);">
                Respondendo a <strong id="replyingToName"></strong>
                <button onclick="cancelReply()" style="background: none; border: none; color: var(--accent); cursor: pointer; margin-left: 8px; font-size: 12px;">✕ Cancelar</button>
            </div>
            <form id="mainReplyForm" action="/painel-externo/comunidade/topico/responder" method="post">
                <input type="hidden" name="topic_id" value="<?= $topicId ?>">
                <input type="hidden" id="parentPostId" name="parent_post_id" value="">
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

<script>
// User mention autocomplete (@username)
(function() {
    const textarea = document.getElementById('replyTextarea');
    if (!textarea) return;

    const communityId = <?= (int)($community['id'] ?? 0) ?>;
    let users = [];
    let userMentionStart = -1;
    let selectedUserIndex = 0;

    // Create dropdown for user mentions
    const userDropdown = document.createElement('div');
    userDropdown.id = 'userMentionDropdown';
    userDropdown.style.cssText = 'display: none; position: absolute; background: #111118; border: 1px solid #272727; border-radius: 8px; max-height: 200px; overflow-y: auto; z-index: 1001; box-shadow: 0 4px 12px rgba(0,0,0,0.5); min-width: 200px;';
    textarea.parentElement.appendChild(userDropdown);

    async function searchUsers(query) {
        try {
            const response = await fetch(`/api/comunidades/membros/buscar?community_id=${communityId}&q=${encodeURIComponent(query)}`);
            if (response.ok) {
                users = await response.json();
                showUserDropdown(users);
            }
        } catch (e) {
            console.error('Error searching users:', e);
        }
    }

    function showUserDropdown(filteredUsers) {
        if (filteredUsers.length === 0) {
            userDropdown.style.display = 'none';
            return;
        }

        selectedUserIndex = 0;
        userDropdown.innerHTML = filteredUsers.map((user, idx) => 
            `<div class="user-mention-item" data-user-id="${user.id}" data-user-name="${user.name}" data-index="${idx}" 
                  style="padding: 8px 12px; cursor: pointer; font-size: 13px; color: #f5f5f5; ${idx === 0 ? 'background: #1a1a24;' : ''}">
                ${user.name}
            </div>`
        ).join('');

        const rect = textarea.getBoundingClientRect();
        userDropdown.style.top = (rect.height + 4) + 'px';
        userDropdown.style.left = '0px';
        userDropdown.style.display = 'block';

        attachUserHandlers(filteredUsers);
    }

    function attachUserHandlers(filteredUsers) {
        userDropdown.querySelectorAll('.user-mention-item').forEach((item, idx) => {
            item.addEventListener('mouseenter', () => {
                selectedUserIndex = idx;
                updateUserSelection();
            });
            item.addEventListener('click', () => {
                insertUserMention(filteredUsers[idx]);
            });
        });
    }

    function updateUserSelection() {
        const items = userDropdown.querySelectorAll('.user-mention-item');
        items.forEach((item, idx) => {
            item.style.background = idx === selectedUserIndex ? '#1a1a24' : 'transparent';
        });
    }

    function insertUserMention(user) {
        const text = textarea.value;
        const beforeMention = text.substring(0, userMentionStart);
        const afterCaret = text.substring(textarea.selectionStart);
        const mention = `@${user.name}`;
        
        textarea.value = beforeMention + mention + ' ' + afterCaret;
        textarea.setSelectionRange(beforeMention.length + mention.length + 1, beforeMention.length + mention.length + 1);
        textarea.focus();
        
        userDropdown.style.display = 'none';
        userMentionStart = -1;
    }

    function getCurrentWord() {
        const pos = textarea.selectionStart;
        const text = textarea.value;
        let start = pos;
        while (start > 0 && text[start - 1] !== ' ' && text[start - 1] !== '\n') {
            start--;
        }
        return { start, word: text.substring(start, pos) };
    }

    textarea.addEventListener('input', function() {
        const { start, word } = getCurrentWord();
        
        // Check for user mention (@username)
        if (word.startsWith('@') && !word.includes('Aula')) {
            userMentionStart = start;
            const query = word.substring(1); // Can be empty string
            searchUsers(query);
        } else {
            userDropdown.style.display = 'none';
            userMentionStart = -1;
        }
    });

    textarea.addEventListener('keydown', function(e) {
        if (userDropdown.style.display === 'none') return;

        const items = userDropdown.querySelectorAll('.user-mention-item');
        if (items.length === 0) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            selectedUserIndex = (selectedUserIndex + 1) % items.length;
            updateUserSelection();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            selectedUserIndex = (selectedUserIndex - 1 + items.length) % items.length;
            updateUserSelection();
        } else if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            const userName = items[selectedUserIndex].getAttribute('data-user-name');
            const userId = items[selectedUserIndex].getAttribute('data-user-id');
            insertUserMention({ id: userId, name: userName });
        } else if (e.key === 'Escape') {
            userDropdown.style.display = 'none';
            userMentionStart = -1;
        }
    });

    document.addEventListener('click', function(e) {
        if (!textarea.contains(e.target) && !userDropdown.contains(e.target)) {
            userDropdown.style.display = 'none';
            userMentionStart = -1;
        }
    });
})();
</script>

<script>
async function toggleLike(postId) {
    const btn = document.getElementById('like-btn-' + postId);
    const icon = document.getElementById('like-icon-' + postId);
    const count = document.getElementById('like-count-' + postId);
    
    btn.disabled = true;
    
    try {
        const formData = new FormData();
        formData.append('post_id', postId);
        
        const response = await fetch('/comunidades/topicos/post/curtir', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            count.textContent = data.likes_count;
            if (data.is_liked) {
                icon.textContent = '❤️';
                btn.style.color = '#ff6b6b';
            } else {
                icon.textContent = '🤍';
                btn.style.color = 'var(--text-secondary)';
            }
        } else {
            console.error('Error toggling like:', data.error);
        }
    } catch (error) {
        console.error('Error toggling like:', error);
    } finally {
        btn.disabled = false;
    }
}

function showReplyForm(postId, authorName) {
    const replyingTo = document.getElementById('replyingTo');
    const replyingToName = document.getElementById('replyingToName');
    const parentPostId = document.getElementById('parentPostId');
    const textarea = document.getElementById('replyTextarea');
    
    replyingToName.textContent = authorName;
    parentPostId.value = postId;
    replyingTo.style.display = 'block';
    
    // Scroll to form
    document.getElementById('mainReplyForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
    textarea.focus();
}

function cancelReply() {
    const replyingTo = document.getElementById('replyingTo');
    const parentPostId = document.getElementById('parentPostId');
    
    replyingTo.style.display = 'none';
    parentPostId.value = '';
}
</script>
