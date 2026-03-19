<?php

$communityName = (string)($community['name'] ?? 'Comunidade');
$topicTitle = (string)($topic['title'] ?? 'Tópico');
$slug = (string)($community['slug'] ?? '');

?>
<div style="max-width: 980px; margin: 0 auto; display:flex; flex-direction:column; gap:14px;">
    <?php if (!empty($error)): ?>
        <div style="background:#311; border:1px solid #a33; color:#ffbaba; padding:8px 10px; border-radius:10px; font-size:13px;">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div style="background:#10330f; border:1px solid #3aa857; color:#c8ffd4; padding:8px 10px; border-radius:10px; font-size:13px;">
            <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <section style="background:#111118; border-radius:16px; border:1px solid #272727; padding:10px 12px;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:8px;">
            <div style="font-size:13px; color:#b0b0b0;">
                <a href="/comunidades" style="color:#ff6f60; text-decoration:none;">Comunidades</a>
                <span> / </span>
                <a href="/comunidades/ver?slug=<?= urlencode($slug) ?>" style="color:#ff6f60; text-decoration:none;">
                    <?= htmlspecialchars($communityName, ENT_QUOTES, 'UTF-8') ?>
                </a>
            </div>
            <?php if ($isMember): ?>
                <span style="font-size:11px; color:#8bc34a;">Você é membro desta comunidade</span>
            <?php endif; ?>
        </div>
    </section>

    <section style="background:#111118; border-radius:16px; border:1px solid #272727; padding:12px 14px; display:flex; flex-direction:column; gap:8px;">
        <div style="font-size:12px; color:#b0b0b0; display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
            <?php
                $topicAuthorName = (string)($topic['user_name'] ?? 'Usuário');
                $topicAuthorAvatar = trim((string)($topic['user_avatar_path'] ?? ''));
                $topicAuthorInitial = 'U';
                $tmpName = trim($topicAuthorName);
                if ($tmpName !== '') {
                    $topicAuthorInitial = mb_strtoupper(mb_substr($tmpName, 0, 1, 'UTF-8'), 'UTF-8');
                }
                $topicMediaUrl = trim((string)($topic['media_url'] ?? ''));
                $topicMediaMime = trim((string)($topic['media_mime'] ?? ''));
                $topicMediaKind = trim((string)($topic['media_kind'] ?? ''));
            ?>
            <span style="display:inline-flex; align-items:center; gap:6px;">
                <span style="width:18px; height:18px; border-radius:50%; overflow:hidden; background:radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%); display:inline-flex; align-items:center; justify-content:center; font-size:11px; font-weight:800; color:#050509; flex:0 0 18px;">
                    <?php if ($topicAuthorAvatar !== ''): ?>
                        <img src="<?= htmlspecialchars($topicAuthorAvatar, ENT_QUOTES, 'UTF-8') ?>" alt="" style="width:100%; height:100%; object-fit:cover; display:block;">
                    <?php else: ?>
                        <?= htmlspecialchars($topicAuthorInitial, ENT_QUOTES, 'UTF-8') ?>
                    <?php endif; ?>
                </span>
                <span>por <?= htmlspecialchars($topicAuthorName, ENT_QUOTES, 'UTF-8') ?></span>
            </span>
            <?php if (!empty($topic['created_at'])): ?>
                <span style="opacity:0.9;">·</span>
                <span><?= htmlspecialchars(date('d/m/Y H:i', strtotime((string)$topic['created_at'])), ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>
        <h1 style="font-size:18px;">
            <?= htmlspecialchars($topicTitle, ENT_QUOTES, 'UTF-8') ?>
        </h1>
        <?php if (!empty($topic['body'])): ?>
            <div style="font-size:13px; color:#f5f5f5; margin-top:4px;">
                <?= nl2br(\App\Controllers\CommunitiesController::renderLessonMentions((string)$topic['body'])) ?>
            </div>
        <?php endif; ?>
        <?php if ($topicMediaUrl !== ''): ?>
            <div style="margin-top:6px;">
                <?php if ($topicMediaKind === 'image'): ?>
                    <img src="<?= htmlspecialchars($topicMediaUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" style="max-width:100%; border-radius:12px; border:1px solid #272727; display:block;">
                <?php elseif ($topicMediaKind === 'video'): ?>
                    <video controls controlsList="nodownload" oncontextmenu="return false;" style="width:100%; max-width:100%; border-radius:12px; border:1px solid #272727; display:block;">
                        <source src="<?= htmlspecialchars($topicMediaUrl, ENT_QUOTES, 'UTF-8') ?>" type="<?= htmlspecialchars($topicMediaMime !== '' ? $topicMediaMime : 'video/mp4', ENT_QUOTES, 'UTF-8') ?>">
                    </video>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($topicMediaUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" style="color:#ff6f60; text-decoration:none;">Ver arquivo anexado</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>

    <section style="background:#111118; border-radius:16px; border:1px solid #272727; padding:12px 14px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
            <h2 style="font-size:16px;">Respostas</h2>
            <span style="font-size:12px; color:#b0b0b0;">Converse como em um fórum entre amigos</span>
        </div>

        <?php if ($isMember): ?>
            <div id="replyingTo" style="display:none; padding:6px 10px; background:#1a1a24; border-left:3px solid #ff6f60; border-radius:4px; margin-bottom:8px; font-size:12px; color:#b0b0b0;">
                Respondendo a <strong id="replyingToName" style="color:#f5f5f5;"></strong>
                <button onclick="cancelReply()" style="background:none; border:none; color:#ff6f60; cursor:pointer; margin-left:8px; font-size:11px;">✕ Cancelar</button>
            </div>
            <form id="mainReplyForm" action="/comunidades/topicos/responder" method="post" enctype="multipart/form-data" style="margin-bottom:10px; display:flex; flex-direction:column; gap:6px;">
                <input type="hidden" name="topic_id" value="<?= (int)($topic['id'] ?? 0) ?>">
                <input type="hidden" id="parentPostId" name="parent_post_id" value="">
                <div style="position: relative;">
                    <textarea id="replyTextarea" name="body" rows="3" placeholder="Responda este tópico... (use @ para mencionar uma aula)" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:13px; resize:vertical;"></textarea>
                    <div id="lessonMentionDropdown" style="display: none; position: absolute; background: #111118; border: 1px solid #272727; border-radius: 8px; max-height: 200px; overflow-y: auto; z-index: 1000; box-shadow: 0 4px 12px rgba(0,0,0,0.5); min-width: 250px;"></div>
                </div>
                <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:center; justify-content:space-between;">
                    <div style="display:flex; flex-direction:column; gap:6px;">
                        <input id="communityReplyMediaInput" type="file" name="media" accept="image/*,video/*" style="display:none;">
                        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                            <label for="communityReplyMediaInput" style="display:inline-flex; align-items:center; gap:8px; padding:6px 10px; border-radius:999px; border:1px solid #272727; background:#111118; color:#f5f5f5; font-size:12px; cursor:pointer; user-select:none;">
                                <span style="width:18px; height:18px; border-radius:6px; display:inline-flex; align-items:center; justify-content:center; background:rgba(255,111,96,0.12); border:1px solid rgba(255,111,96,0.28);">
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="#ff6f60" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M21 15V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v8" />
                                        <path d="M3 17l4-4 4 4 4-4 6 6" />
                                        <path d="M14 10a2 2 0 1 1-4 0 2 2 0 0 1 4 0z" />
                                    </svg>
                                </span>
                                <span>Anexar mídia</span>
                            </label>
                            <span id="communityReplyMediaName" style="font-size:12px; color:#b0b0b0;">Nenhum arquivo selecionado</span>
                        </div>
                        <div style="font-size:11px; color:#b0b0b0;">Imagem/vídeo/arquivo (opcional) · Até 20 MB.</div>
                    </div>
                    <button type="submit" style="align-self:flex-end; border:none; border-radius:999px; padding:5px 10px; background:#111118; border:1px solid #272727; color:#f5f5f5; font-size:12px; cursor:pointer;">Enviar resposta</button>
                </div>
            </form>
        <?php else: ?>
            <p style="font-size:13px; color:#b0b0b0;">Entre na comunidade para responder neste tópico.</p>
        <?php endif; ?>

        <?php if (empty($posts)): ?>
            <p style="font-size:13px; color:#b0b0b0;">Ninguém respondeu ainda. Puxe a conversa!</p>
        <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:8px;">
                <?php foreach ($posts as $p): ?>
                    <?php
                        $postAuthorName = (string)($p['user_name'] ?? 'Usuário');
                        $postAuthorAvatar = trim((string)($p['user_avatar_path'] ?? ''));
                        $postAuthorInitial = 'U';
                        $tmpName2 = trim($postAuthorName);
                        if ($tmpName2 !== '') {
                            $postAuthorInitial = mb_strtoupper(mb_substr($tmpName2, 0, 1, 'UTF-8'), 'UTF-8');
                        }
                        $postMediaUrl = trim((string)($p['media_url'] ?? ''));
                        $postMediaMime = trim((string)($p['media_mime'] ?? ''));
                        $postMediaKind = trim((string)($p['media_kind'] ?? ''));
                        $parentPostId = isset($p['parent_post_id']) ? (int)$p['parent_post_id'] : null;
                        
                        // Find parent post info if this is a reply
                        $parentAuthor = null;
                        if ($parentPostId) {
                            foreach ($posts as $pp) {
                                if ((int)$pp['id'] === $parentPostId) {
                                    $parentAuthor = trim((string)($pp['user_name'] ?? 'Usuário'));
                                    break;
                                }
                            }
                        }
                    ?>
                    <div style="background:#050509; border-radius:12px; border:1px solid #272727; padding:10px 12px; <?= $parentPostId ? 'margin-left:40px; border-left:3px solid #ff6f60;' : '' ?>">
                        <?php if ($parentPostId && $parentAuthor): ?>
                            <div style="font-size:11px; color:#b0b0b0; margin-bottom:8px; padding:4px 8px; background:#1a1a24; border-radius:4px;">
                                ↳ Respondendo a <strong style="color:#ff6f60;"><?= htmlspecialchars($parentAuthor, ENT_QUOTES, 'UTF-8') ?></strong>
                            </div>
                        <?php endif; ?>
                        <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
                            <div style="width:36px; height:36px; border-radius:50%; overflow:hidden; background:radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                <?php if ($postAuthorAvatar !== ''): ?>
                                    <img src="<?= htmlspecialchars($postAuthorAvatar, ENT_QUOTES, 'UTF-8') ?>" alt="" style="width:100%; height:100%; object-fit:cover;">
                                <?php else: ?>
                                    <span style="font-size:14px; font-weight:800; color:#050509;"><?= htmlspecialchars($postAuthorInitial, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>
                            <div style="flex:1; min-width:0;">
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <span style="font-size:13px; color:#f5f5f5; font-weight:500;"><?= htmlspecialchars($postAuthorName, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php if (!empty($p['created_at'])): ?>
                                        <span style="font-size:11px; color:#b0b0b0;">
                                            <?= htmlspecialchars(date('d/m/Y H:i', strtotime((string)$p['created_at'])), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div style="font-size:13px; color:#f5f5f5;">
                            <?= nl2br(\App\Controllers\CommunitiesController::renderLessonMentions((string)($p['body'] ?? ''))) ?>
                        </div>
                        <?php if ($postMediaUrl !== ''): ?>
                            <div style="margin-top:6px;">
                                <?php if ($postMediaKind === 'image'): ?>
                                    <img src="<?= htmlspecialchars($postMediaUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" style="max-width:100%; border-radius:12px; border:1px solid #272727; display:block;">
                                <?php elseif ($postMediaKind === 'video'): ?>
                                    <video controls controlsList="nodownload" oncontextmenu="return false;" style="width:100%; max-width:100%; border-radius:12px; border:1px solid #272727; display:block;">
                                        <source src="<?= htmlspecialchars($postMediaUrl, ENT_QUOTES, 'UTF-8') ?>" type="<?= htmlspecialchars($postMediaMime !== '' ? $postMediaMime : 'video/mp4', ENT_QUOTES, 'UTF-8') ?>">
                                    </video>
                                <?php else: ?>
                                    <a href="<?= htmlspecialchars($postMediaUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" style="color:#ff6f60; text-decoration:none;">Ver arquivo anexado</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php
                            $postId = (int)($p['id'] ?? 0);
                            $likesCountForPost = $likesCount[$postId] ?? 0;
                            $isLikedByUser = isset($likedByUser[$postId]);
                        ?>
                        <div style="margin-top:8px; display:flex; gap:12px; align-items:center;">
                            <button onclick="toggleLike(<?= $postId ?>)" 
                                    id="like-btn-<?= $postId ?>"
                                    style="background:none; border:1px solid #272727; border-radius:6px; padding:4px 10px; cursor:pointer; display:flex; align-items:center; gap:6px; color:<?= $isLikedByUser ? '#ff6b6b' : '#b0b0b0' ?>; transition:all 0.2s; font-size:12px;">
                                <span id="like-icon-<?= $postId ?>"><?= $isLikedByUser ? '❤️' : '🤍' ?></span>
                                <span id="like-count-<?= $postId ?>" style="font-weight:500;"><?= $likesCountForPost ?></span>
                            </button>
                            <?php if ($isMember): ?>
                                <button onclick="showReplyForm(<?= $postId ?>, '<?= htmlspecialchars($postAuthorName, ENT_QUOTES, 'UTF-8') ?>')" 
                                        style="background:none; border:1px solid #272727; border-radius:6px; padding:4px 10px; cursor:pointer; display:flex; align-items:center; gap:6px; color:#b0b0b0; transition:all 0.2s; font-size:12px;">
                                    💬 Responder
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<script>
    (function(){
        var input = document.getElementById('communityReplyMediaInput');
        var nameEl = document.getElementById('communityReplyMediaName');
        if (!input || !nameEl) return;
        input.addEventListener('change', function(){
            var f = input.files && input.files[0] ? input.files[0] : null;
            nameEl.textContent = f ? f.name : 'Nenhum arquivo selecionado';
        });
    })();

    // Lesson Mention Autocomplete - Hierarchical (Course > Lesson)
    (function(){
        const textarea = document.getElementById('replyTextarea');
        const dropdown = document.getElementById('lessonMentionDropdown');
        console.log('Lesson mention autocomplete initializing...');
        if (!textarea || !dropdown) {
            console.error('Textarea or dropdown not found!');
            return;
        }
        console.log('Autocomplete initialized successfully');

        let courses = [];
        let currentCourse = null;
        let currentLessons = [];
        let mentionStart = -1;
        let selectedIndex = 0;
        let mode = 'course'; // 'course' or 'lesson'

        // Fetch available courses
        async function fetchCourses() {
            try {
                console.log('Fetching enrolled courses...');
                const response = await fetch('/api/courses/enrolled');
                console.log('Response status:', response.status);
                if (response.ok) {
                    courses = await response.json();
                    console.log('Courses loaded:', courses);
                } else {
                    console.error('Failed to fetch courses, status:', response.status);
                }
            } catch (e) {
                console.error('Failed to fetch courses:', e);
            }
        }
        fetchCourses();

        // Fetch lessons for a specific course
        async function fetchLessonsForCourse(courseId) {
            try {
                console.log('Fetching lessons for course:', courseId);
                const response = await fetch('/api/courses/' + courseId + '/lessons');
                console.log('Lessons response status:', response.status);
                if (response.ok) {
                    const lessons = await response.json();
                    console.log('Lessons loaded:', lessons);
                    return lessons;
                } else {
                    console.error('Failed to fetch lessons, status:', response.status);
                }
            } catch (e) {
                console.error('Failed to fetch lessons:', e);
            }
            return [];
        }

        function getCaretPosition() {
            return textarea.selectionStart;
        }

        function setCaretPosition(pos) {
            textarea.setSelectionRange(pos, pos);
            textarea.focus();
        }

        function getCurrentWord() {
            const pos = getCaretPosition();
            const text = textarea.value;
            let start = pos;
            while (start > 0 && text[start - 1] !== ' ' && text[start - 1] !== '\n') {
                start--;
            }
            return { start, word: text.substring(start, pos) };
        }

        function showCourseDropdown(filteredCourses) {
            if (filteredCourses.length === 0) {
                dropdown.style.display = 'none';
                return;
            }

            mode = 'course';
            selectedIndex = 0;
            dropdown.innerHTML = `<div style="padding: 6px 12px; font-size: 11px; color: #b0b0b0; border-bottom: 1px solid #272727;">Selecione o curso:</div>` +
                filteredCourses.map((course, idx) => 
                    `<div class="course-mention-item" data-course-id="${course.id}" data-index="${idx}" style="padding: 8px 12px; cursor: pointer; font-size: 13px; color: #f5f5f5; ${idx === 0 ? 'background: #1a1a24;' : ''}">
                        <div style="font-weight: 600;">${course.title}</div>
                        <div style="font-size: 11px; color: #b0b0b0;">→ Ver aulas</div>
                    </div>`
                ).join('');

            const rect = textarea.getBoundingClientRect();
            dropdown.style.top = (rect.height + 4) + 'px';
            dropdown.style.left = '0px';
            dropdown.style.display = 'block';

            attachCourseHandlers(filteredCourses);
        }

        function showLessonDropdown(lessons) {
            if (lessons.length === 0) {
                dropdown.innerHTML = '<div style="padding: 12px; font-size: 12px; color: #b0b0b0;">Nenhuma aula encontrada</div>';
                return;
            }

            mode = 'lesson';
            selectedIndex = 0;
            dropdown.innerHTML = `<div style="padding: 6px 12px; font-size: 11px; color: #b0b0b0; border-bottom: 1px solid #272727; display: flex; justify-content: space-between; align-items: center;">
                    <span>Selecione a aula:</span>
                    <button onclick="event.stopPropagation(); document.getElementById('lessonMentionDropdown').style.display='none';" style="background: none; border: none; color: #ff6f60; cursor: pointer; font-size: 11px;">← Voltar</button>
                </div>` +
                lessons.map((lesson, idx) => 
                    `<div class="lesson-mention-item" data-lesson='${JSON.stringify(lesson)}' data-index="${idx}" style="padding: 8px 12px; cursor: pointer; font-size: 13px; color: #f5f5f5; ${idx === 0 ? 'background: #1a1a24;' : ''}">
                        <div style="font-weight: 600;">${lesson.title}</div>
                    </div>`
                ).join('');

            attachLessonHandlers(lessons);
        }

        function attachCourseHandlers(filteredCourses) {

            dropdown.querySelectorAll('.course-mention-item').forEach((item, idx) => {
                item.addEventListener('mouseenter', () => {
                    selectedIndex = idx;
                    updateSelection();
                });
                item.addEventListener('click', async () => {
                    const courseId = item.getAttribute('data-course-id');
                    currentCourse = filteredCourses[idx];
                    currentLessons = await fetchLessonsForCourse(courseId);
                    showLessonDropdown(currentLessons);
                });
            });
        }

        function attachLessonHandlers(lessons) {
            dropdown.querySelectorAll('.lesson-mention-item').forEach((item, idx) => {
                item.addEventListener('mouseenter', () => {
                    selectedIndex = idx;
                    updateSelection();
                });
                item.addEventListener('click', () => {
                    insertMention(lessons[idx]);
                });
            });
        }

        function updateSelection() {
            const selector = mode === 'course' ? '.course-mention-item' : '.lesson-mention-item';
            const items = dropdown.querySelectorAll(selector);
            items.forEach((item, idx) => {
                item.style.background = idx === selectedIndex ? '#1a1a24' : 'transparent';
            });
        }

        function insertMention(lesson) {
            const text = textarea.value;
            const beforeMention = text.substring(0, mentionStart);
            const afterCaret = text.substring(getCaretPosition());
            const mention = `@${lesson.title}`;
            
            textarea.value = beforeMention + mention + ' ' + afterCaret;
            setCaretPosition(beforeMention.length + mention.length + 1);
            
            dropdown.style.display = 'none';
            mentionStart = -1;
        }

        textarea.addEventListener('input', function() {
            const { start, word } = getCurrentWord();
            console.log('Input detected, word:', word, 'courses count:', courses.length);
            
            if (word === '@') {
                console.log('@ detected, showing courses');
                mentionStart = start;
                showCourseDropdown(courses);
            } else if (word.startsWith('@') && word.length > 1) {
                mentionStart = start;
                const query = word.substring(1).toLowerCase();
                const filtered = courses.filter(c => 
                    c.title.toLowerCase().includes(query)
                ).slice(0, 8);
                console.log('Filtered courses:', filtered);
                showCourseDropdown(filtered);
            } else {
                dropdown.style.display = 'none';
                mentionStart = -1;
                mode = 'course';
            }
        });

        textarea.addEventListener('keydown', function(e) {
            if (dropdown.style.display === 'none') return;

            const selector = mode === 'course' ? '.course-mention-item' : '.lesson-mention-item';
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
                if (mode === 'course') {
                    const courseId = items[selectedIndex].getAttribute('data-course-id');
                    const course = courses.find(c => c.id == courseId);
                    if (course) {
                        currentCourse = course;
                        fetchLessonsForCourse(courseId).then(lessons => {
                            currentLessons = lessons;
                            showLessonDropdown(lessons);
                        });
                    }
                } else {
                    const lessonData = JSON.parse(items[selectedIndex].getAttribute('data-lesson') || '{}');
                    if (lessonData.title) {
                        insertMention(lessonData);
                    }
                }
            } else if (e.key === 'Escape') {
                dropdown.style.display = 'none';
                mentionStart = -1;
                mode = 'course';
            }
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!textarea.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.style.display = 'none';
                mentionStart = -1;
            }
        });
    })();

    // Like functionality
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
                    btn.style.color = '#b0b0b0';
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

    // Reply functionality
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
