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
                <video controls controlsList="nodownload" oncontextmenu="return false;" style="width: 100%; max-width: 100%; border-radius: 12px; border: 1px solid var(--border); display: block;">
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
                    $postAvatar = trim((string)($post['user_avatar_path'] ?? ''));
                    $postAuthorInitial = mb_strtoupper(mb_substr($postAuthor, 0, 1, 'UTF-8'), 'UTF-8');
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
                <div id="post-<?= (int)($post['id'] ?? 0) ?>" style="padding: 14px; border: 1px solid var(--border); border-radius: 10px; background: rgba(255,255,255,0.02); <?= $parentPostId ? 'margin-left: 40px; border-left: 3px solid var(--accent);' : '' ?>">
                    <?php if ($parentPostId && $parentAuthor): ?>
                        <div style="font-size: 12px; color: var(--text-secondary); margin-bottom: 8px; padding: 6px 10px; background: rgba(255,255,255,0.03); border-radius: 6px;">
                            ↳ Respondendo a <strong style="color: var(--accent);"><?= htmlspecialchars($parentAuthor, ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                    <?php endif; ?>
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                        <div style="width: 40px; height: 40px; border-radius: 50%; overflow: hidden; background: linear-gradient(135deg, var(--accent) 0%, #6366f1 100%); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <?php if ($postAvatar !== ''): ?>
                                <img src="<?= htmlspecialchars($postAvatar, ENT_QUOTES, 'UTF-8') ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <span style="font-size: 16px; font-weight: 700; color: white;"><?= htmlspecialchars($postAuthorInitial, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-weight: 600; color: var(--text-primary);">
                                    <?= htmlspecialchars($postAuthor, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <?php if ($postCreatedAt): ?>
                                    <span style="font-size: 12px; color: var(--text-secondary);">
                                        <?= htmlspecialchars($postCreatedAt, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
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
                                <video controls controlsList="nodownload" oncontextmenu="return false;" style="width: 100%; max-width: 100%; border-radius: 10px; border: 1px solid var(--border); display: block;">
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
            <form id="mainReplyForm" action="/painel-externo/comunidade/topico/responder" method="post" enctype="multipart/form-data">
                <input type="hidden" name="topic_id" value="<?= $topicId ?>">
                <input type="hidden" id="parentPostId" name="parent_post_id" value="">
                <div style="position: relative;">
                    <textarea id="replyTextarea" name="body" rows="4" required placeholder="Escreva sua resposta... (use @ para mencionar)" 
                              style="width: 100%; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid var(--border); border-radius: 10px; color: var(--text-primary); font-size: 14px; resize: vertical;"></textarea>
                    <div id="lessonMentionDropdown" style="display: none; position: absolute; background: #111118; border: 1px solid #272727; border-radius: 8px; max-height: 200px; overflow-y: auto; z-index: 1000; box-shadow: 0 4px 12px rgba(0,0,0,0.5); min-width: 250px;"></div>
                </div>
                
                <input type="file" id="mediaInput" name="media" accept="image/*,video/*" style="display: none;">
                <div id="mediaPreview" style="display: none; margin-top: 12px; padding: 12px; background: rgba(255,255,255,0.03); border: 1px solid var(--border); border-radius: 8px; position: relative;">
                    <button type="button" onclick="clearMedia()" style="position: absolute; top: 8px; right: 8px; background: rgba(0,0,0,0.6); border: none; color: white; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 16px;">×</button>
                    <img id="imagePreview" style="max-width: 100%; max-height: 200px; border-radius: 6px; display: none;">
                    <video id="videoPreview" controls controlsList="nodownload" oncontextmenu="return false;" style="max-width: 100%; max-height: 200px; border-radius: 6px; display: none;"></video>
                    <div id="fileInfo" style="font-size: 13px; color: var(--text-secondary);"></div>
                </div>
                
                <div style="margin-top: 12px; display: flex; justify-content: space-between; align-items: center;">
                    <button type="button" onclick="document.getElementById('mediaInput').click()" 
                            style="background: none; border: 1px solid var(--border); border-radius: 6px; padding: 8px 14px; cursor: pointer; display: flex; align-items: center; gap: 6px; color: var(--text-secondary); transition: all 0.2s; font-size: 13px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path>
                        </svg>
                        Anexar Imagem/Vídeo
                    </button>
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
// Unified @ mention autocomplete (users and lessons)
(function() {
    const textarea = document.getElementById('replyTextarea');
    if (!textarea) return;

    const communityId = <?= (int)($community['id'] ?? 0) ?>;
    let mentionStart = -1;
    let selectedIndex = 0;
    let currentMode = 'menu'; // 'menu', 'users', 'courses'
    let users = [];
    let courses = [];

    // Create single unified dropdown
    const dropdown = document.createElement('div');
    dropdown.id = 'unifiedMentionDropdown';
    dropdown.style.cssText = 'display: none; position: absolute; background: #111118; border: 1px solid #272727; border-radius: 8px; max-height: 250px; overflow-y: auto; z-index: 2000; box-shadow: 0 4px 12px rgba(0,0,0,0.5); min-width: 250px;';
    textarea.parentElement.appendChild(dropdown);

    async function searchUsers(query) {
        try {
            const response = await fetch(`/api/comunidades/membros/buscar?community_id=${communityId}&q=${encodeURIComponent(query)}`);
            if (response.ok) {
                users = await response.json();
            }
        } catch (e) {
            console.error('Error searching users:', e);
        }
    }

    async function fetchCourses() {
        try {
            const response = await fetch('/api/courses/enrolled');
            if (response.ok) {
                courses = await response.json();
            }
        } catch (e) {
            console.error('Error fetching courses:', e);
        }
    }

    function showMainMenu() {
        currentMode = 'menu';
        selectedIndex = 0;
        
        dropdown.innerHTML = `
            <div style="padding: 6px 12px; font-size: 11px; color: #b0b0b0; border-bottom: 1px solid #272727;">
                Mencionar:
            </div>
            <div class="mention-option" data-type="users" data-index="0" 
                 style="padding: 10px 12px; cursor: pointer; font-size: 13px; color: #f5f5f5; background: #1a1a24; display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 16px;">👤</span>
                <div>
                    <div style="font-weight: 600;">Usuários</div>
                    <div style="font-size: 11px; color: #b0b0b0;">Mencionar membros da comunidade</div>
                </div>
            </div>
            <div class="mention-option" data-type="courses" data-index="1" 
                 style="padding: 10px 12px; cursor: pointer; font-size: 13px; color: #f5f5f5; display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 16px;">📚</span>
                <div>
                    <div style="font-weight: 600;">Aulas</div>
                    <div style="font-size: 11px; color: #b0b0b0;">Mencionar aulas dos cursos</div>
                </div>
            </div>
        `;

        const rect = textarea.getBoundingClientRect();
        dropdown.style.top = (rect.height + 4) + 'px';
        dropdown.style.left = '0px';
        dropdown.style.display = 'block';

        attachMenuHandlers();
    }

    function showUsersList() {
        currentMode = 'users';
        selectedIndex = 0;
        
        if (users.length === 0) {
            dropdown.innerHTML = '<div style="padding: 12px; font-size: 12px; color: #b0b0b0;">Nenhum usuário encontrado</div>';
            return;
        }

        dropdown.innerHTML = `
            <div style="padding: 6px 12px; font-size: 11px; color: #b0b0b0; border-bottom: 1px solid #272727; display: flex; justify-content: space-between;">
                <span>Selecione o usuário:</span>
                <button onclick="event.stopPropagation(); this.closest('#unifiedMentionDropdown').style.display='none';" 
                        style="background: none; border: none; color: #ff6f60; cursor: pointer; font-size: 11px;">← Voltar</button>
            </div>
            ${users.map((user, idx) => 
                `<div class="user-item" data-user-id="${user.id}" data-user-name="${user.name}" data-index="${idx}" 
                      style="padding: 8px 12px; cursor: pointer; font-size: 13px; color: #f5f5f5; ${idx === 0 ? 'background: #1a1a24;' : ''}">
                    👤 ${user.name}
                </div>`
            ).join('')}
        `;

        attachUserHandlers();
    }

    function attachMenuHandlers() {
        dropdown.querySelectorAll('.mention-option').forEach((item, idx) => {
            item.addEventListener('mouseenter', () => {
                selectedIndex = idx;
                updateSelection();
            });
            item.addEventListener('click', async () => {
                const type = item.getAttribute('data-type');
                if (type === 'users') {
                    await searchUsers('');
                    showUsersList();
                } else if (type === 'courses') {
                    await fetchCourses();
                    showCoursesList();
                }
            });
        });
    }

    function attachUserHandlers() {
        dropdown.querySelectorAll('.user-item').forEach((item, idx) => {
            item.addEventListener('mouseenter', () => {
                selectedIndex = idx;
                updateSelection();
            });
            item.addEventListener('click', () => {
                const userName = item.getAttribute('data-user-name');
                insertMention(`@${userName}`);
            });
        });
    }

    function showCoursesList() {
        currentMode = 'courses';
        selectedIndex = 0;
        
        if (courses.length === 0) {
            dropdown.innerHTML = '<div style="padding: 12px; font-size: 12px; color: #b0b0b0;">Nenhum curso encontrado</div>';
            return;
        }

        dropdown.innerHTML = `
            <div style="padding: 6px 12px; font-size: 11px; color: #b0b0b0; border-bottom: 1px solid #272727; display: flex; justify-content: space-between;">
                <span>Selecione o curso:</span>
                <button onclick="event.stopPropagation(); this.closest('#unifiedMentionDropdown').style.display='none';" 
                        style="background: none; border: none; color: #ff6f60; cursor: pointer; font-size: 11px;">← Voltar</button>
            </div>
            ${courses.map((course, idx) => 
                `<div class="course-item" data-course-id="${course.id}" data-index="${idx}" 
                      style="padding: 8px 12px; cursor: pointer; font-size: 13px; color: #f5f5f5; ${idx === 0 ? 'background: #1a1a24;' : ''}">
                    📚 ${course.title}
                </div>`
            ).join('')}
        `;

        attachCourseHandlers();
    }

    function attachCourseHandlers() {
        dropdown.querySelectorAll('.course-item').forEach((item, idx) => {
            item.addEventListener('mouseenter', () => {
                selectedIndex = idx;
                updateSelection();
            });
            item.addEventListener('click', async () => {
                const courseId = item.getAttribute('data-course-id');
                await showLessonsForCourse(courseId);
            });
        });
    }

    async function showLessonsForCourse(courseId) {
        try {
            const response = await fetch(`/api/courses/${courseId}/lessons`);
            if (response.ok) {
                const lessons = await response.json();
                showLessonsList(lessons);
            }
        } catch (e) {
            console.error('Error fetching lessons:', e);
        }
    }

    function showLessonsList(lessons) {
        currentMode = 'lessons';
        selectedIndex = 0;
        
        if (lessons.length === 0) {
            dropdown.innerHTML = '<div style="padding: 12px; font-size: 12px; color: #b0b0b0;">Nenhuma aula encontrada</div>';
            return;
        }

        dropdown.innerHTML = `
            <div style="padding: 6px 12px; font-size: 11px; color: #b0b0b0; border-bottom: 1px solid #272727; display: flex; justify-content: space-between;">
                <span>Selecione a aula:</span>
                <button onclick="event.stopPropagation(); this.closest('#unifiedMentionDropdown').style.display='none';" 
                        style="background: none; border: none; color: #ff6f60; cursor: pointer; font-size: 11px;">← Voltar</button>
            </div>
            ${lessons.map((lesson, idx) => 
                `<div class="lesson-item" data-lesson='${JSON.stringify(lesson)}' data-index="${idx}" 
                      style="padding: 8px 12px; cursor: pointer; font-size: 13px; color: #f5f5f5; ${idx === 0 ? 'background: #1a1a24;' : ''}">
                    📖 ${lesson.title}
                </div>`
            ).join('')}
        `;

        attachLessonHandlers();
    }

    function attachLessonHandlers() {
        dropdown.querySelectorAll('.lesson-item').forEach((item, idx) => {
            item.addEventListener('mouseenter', () => {
                selectedIndex = idx;
                updateSelection();
            });
            item.addEventListener('click', () => {
                const lesson = JSON.parse(item.getAttribute('data-lesson'));
                insertMention(`@${lesson.title}`);
            });
        });
    }

    function updateSelection() {
        const selector = currentMode === 'menu' ? '.mention-option' : 
                        currentMode === 'users' ? '.user-item' :
                        currentMode === 'courses' ? '.course-item' : '.lesson-item';
        const items = dropdown.querySelectorAll(selector);
        items.forEach((item, idx) => {
            item.style.background = idx === selectedIndex ? '#1a1a24' : 'transparent';
        });
    }

    function insertMention(mention) {
        const text = textarea.value;
        const beforeMention = text.substring(0, mentionStart);
        const afterCaret = text.substring(textarea.selectionStart);
        
        textarea.value = beforeMention + mention + ' ' + afterCaret;
        textarea.setSelectionRange(beforeMention.length + mention.length + 1, beforeMention.length + mention.length + 1);
        textarea.focus();
        
        dropdown.style.display = 'none';
        mentionStart = -1;
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
        
        if (word === '@') {
            mentionStart = start;
            showMainMenu();
        } else {
            dropdown.style.display = 'none';
            mentionStart = -1;
        }
    });

    textarea.addEventListener('keydown', function(e) {
        if (dropdown.style.display === 'none') return;

        const selector = currentMode === 'menu' ? '.mention-option' : 
                        currentMode === 'users' ? '.user-item' :
                        currentMode === 'courses' ? '.course-item' : '.lesson-item';
        const items = dropdown.querySelectorAll(selector);
        if (items.length === 0) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            selectedIndex = (selectedIndex + 1) % items.length;
            updateSelection();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            selectedIndex = (selectedIndex - 1 + items.length) % items.length;
            updateSelection();
        } else if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            items[selectedIndex].click();
        } else if (e.key === 'Escape') {
            dropdown.style.display = 'none';
            mentionStart = -1;
        }
    });

    document.addEventListener('click', function(e) {
        if (!textarea.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
            mentionStart = -1;
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

// Media upload preview
document.getElementById('mediaInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;
    
    const preview = document.getElementById('mediaPreview');
    const imagePreview = document.getElementById('imagePreview');
    const videoPreview = document.getElementById('videoPreview');
    const fileInfo = document.getElementById('fileInfo');
    
    preview.style.display = 'block';
    imagePreview.style.display = 'none';
    videoPreview.style.display = 'none';
    
    const fileSize = (file.size / 1024 / 1024).toFixed(2);
    fileInfo.textContent = `${file.name} (${fileSize} MB)`;
    
    if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
            imagePreview.src = e.target.result;
            imagePreview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else if (file.type.startsWith('video/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
            videoPreview.src = e.target.result;
            videoPreview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
});

function clearMedia() {
    document.getElementById('mediaInput').value = '';
    document.getElementById('mediaPreview').style.display = 'none';
    document.getElementById('imagePreview').src = '';
    document.getElementById('videoPreview').src = '';
}

// Scroll to post if hash is present in URL (from notifications)
window.addEventListener('load', function() {
    if (window.location.hash) {
        const hash = window.location.hash;
        const element = document.querySelector(hash);
        if (element) {
            setTimeout(function() {
                element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                // Add highlight effect
                element.style.transition = 'all 0.3s';
                element.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.3)';
                setTimeout(function() {
                    element.style.boxShadow = '';
                }, 2000);
            }, 300);
        }
    }
});
</script>
