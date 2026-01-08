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
                <?= nl2br(htmlspecialchars((string)$topic['body'], ENT_QUOTES, 'UTF-8')) ?>
            </div>
        <?php endif; ?>
        <?php if ($topicMediaUrl !== ''): ?>
            <div style="margin-top:6px;">
                <?php if ($topicMediaKind === 'image'): ?>
                    <img src="<?= htmlspecialchars($topicMediaUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" style="max-width:100%; border-radius:12px; border:1px solid #272727; display:block;">
                <?php elseif ($topicMediaKind === 'video'): ?>
                    <video controls style="width:100%; max-width:100%; border-radius:12px; border:1px solid #272727; display:block;">
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
            <form action="/comunidades/topicos/responder" method="post" enctype="multipart/form-data" style="margin-bottom:10px; display:flex; flex-direction:column; gap:6px;">
                <input type="hidden" name="topic_id" value="<?= (int)($topic['id'] ?? 0) ?>">
                <textarea name="body" rows="3" placeholder="Responda este tópico..." style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:13px; resize:vertical;"></textarea>
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
                    <div style="background:#050509; border-radius:12px; border:1px solid #272727; padding:8px 10px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:3px;">
                            <div style="font-size:13px; color:#f5f5f5; font-weight:500;">
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
                                ?>
                                <span style="display:inline-flex; align-items:center; gap:8px;">
                                    <span style="width:24px; height:24px; border-radius:50%; overflow:hidden; background:radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%); display:inline-flex; align-items:center; justify-content:center; font-size:12px; font-weight:800; color:#050509; flex:0 0 24px;">
                                        <?php if ($postAuthorAvatar !== ''): ?>
                                            <img src="<?= htmlspecialchars($postAuthorAvatar, ENT_QUOTES, 'UTF-8') ?>" alt="" style="width:100%; height:100%; object-fit:cover; display:block;">
                                        <?php else: ?>
                                            <?= htmlspecialchars($postAuthorInitial, ENT_QUOTES, 'UTF-8') ?>
                                        <?php endif; ?>
                                    </span>
                                    <span><?= htmlspecialchars($postAuthorName, ENT_QUOTES, 'UTF-8') ?></span>
                                </span>
                            </div>
                            <?php if (!empty($p['created_at'])): ?>
                                <div style="font-size:11px; color:#b0b0b0;">
                                    <?= htmlspecialchars(date('d/m/Y H:i', strtotime((string)$p['created_at'])), ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div style="font-size:13px; color:#f5f5f5;">
                            <?= nl2br(htmlspecialchars((string)($p['body'] ?? ''), ENT_QUOTES, 'UTF-8')) ?>
                        </div>
                        <?php if ($postMediaUrl !== ''): ?>
                            <div style="margin-top:6px;">
                                <?php if ($postMediaKind === 'image'): ?>
                                    <img src="<?= htmlspecialchars($postMediaUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" style="max-width:100%; border-radius:12px; border:1px solid #272727; display:block;">
                                <?php elseif ($postMediaKind === 'video'): ?>
                                    <video controls style="width:100%; max-width:100%; border-radius:12px; border:1px solid #272727; display:block;">
                                        <source src="<?= htmlspecialchars($postMediaUrl, ENT_QUOTES, 'UTF-8') ?>" type="<?= htmlspecialchars($postMediaMime !== '' ? $postMediaMime : 'video/mp4', ENT_QUOTES, 'UTF-8') ?>">
                                    </video>
                                <?php else: ?>
                                    <a href="<?= htmlspecialchars($postMediaUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" style="color:#ff6f60; text-decoration:none;">Ver arquivo anexado</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
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
</script>
