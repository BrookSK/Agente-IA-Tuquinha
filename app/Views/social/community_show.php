<?php

$communityName = (string)($community['name'] ?? 'Comunidade');
$slug = (string)($community['slug'] ?? '');
$membersCount = is_array($members) ? count($members) : 0;
$topicsCount = is_array($topics) ? count($topics) : 0;

$languageCode = (string)($community['language'] ?? '');
$category = (string)($community['category'] ?? '');
$communityType = (string)($community['community_type'] ?? 'public');
$postingPolicy = (string)($community['posting_policy'] ?? 'any_member');
$forumType = (string)($community['forum_type'] ?? 'non_anonymous');
$coverImage = (string)($community['cover_image_path'] ?? '');
$profileImage = (string)($community['image_path'] ?? '');
$communityInitial = 'C';
$tmpCommunityName = trim($communityName);
if ($tmpCommunityName !== '') {
    $communityInitial = mb_strtoupper(mb_substr($tmpCommunityName, 0, 1, 'UTF-8'), 'UTF-8');
}

if ($communityType !== 'private') {
    $communityType = 'public';
}
if (!in_array($postingPolicy, ['any_member', 'owner_moderators'], true)) {
    $postingPolicy = 'any_member';
}
if (!in_array($forumType, ['non_anonymous', 'anonymous'], true)) {
    $forumType = 'non_anonymous';
}

// Rótulos amigáveis
$languageLabel = '';
if ($languageCode === 'pt-BR') {
    $languageLabel = 'Português (Brasil)';
} elseif ($languageCode === 'en') {
    $languageLabel = 'Inglês';
} elseif ($languageCode === 'es') {
    $languageLabel = 'Espanhol';
} elseif ($languageCode !== '') {
    $languageLabel = $languageCode;
}

$typeLabel = $communityType === 'private' ? 'Privada (apenas com convite)' : 'Pública';
$postingLabel = $postingPolicy === 'owner_moderators'
    ? 'Apenas dono e moderadores postam'
    : 'Qualquer membro pode postar';
$forumLabel = $forumType === 'anonymous'
    ? 'Anônimo para membros'
    : 'Não-anônimo (mostra o nome)';

// Dono e moderadores (pelos membros carregados)
$ownerId = (int)($community['owner_user_id'] ?? 0);
$ownerName = null;
$moderatorNames = [];
if (is_array($members)) {
    foreach ($members as $m) {
        $mid = (int)($m['user_id'] ?? 0);
        $mname = (string)($m['user_name'] ?? '');
        $role = (string)($m['role'] ?? 'member');
        if ($mid === $ownerId && $mname !== '') {
            $ownerName = $mname;
        }
        if ($role === 'moderator' && $mname !== '') {
            $moderatorNames[] = $mname;
        }
    }
}
$moderatorsText = !empty($moderatorNames) ? implode(', ', $moderatorNames) : '';

$canModerate = !empty($canModerate);

?>
<style>
    @media (max-width: 900px) {
        #communityTwoColGrid {
            grid-template-columns: minmax(0, 1fr) !important;
        }
    }
</style>
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

    <section style="background:var(--surface-card); border-radius:16px; border:1px solid var(--border-subtle); padding:10px 12px;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:8px; flex-wrap:wrap;">
            <div style="font-size:13px; color:var(--text-secondary);">
                <a href="/comunidades" style="color:#ff6f60; text-decoration:none;">Comunidades</a>
                <span> / </span>
                <span><?= htmlspecialchars($communityName, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <a href="/comunidades" style="font-size:12px; color:#ff6f60; text-decoration:none;">Voltar para lista de comunidades</a>
        </div>
    </section>

    <section style="background:var(--surface-card); border-radius:16px; border:1px solid var(--border-subtle); overflow:hidden;">
        <div style="width:100%; height:220px; background:radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%);">
            <?php if ($coverImage !== ''): ?>
                <img src="<?= htmlspecialchars($coverImage, ENT_QUOTES, 'UTF-8') ?>" alt="Capa da comunidade" style="width:100%; height:100%; object-fit:cover; display:block;">
            <?php else: ?>
                <div style="width:100%; height:100%; display:flex; align-items:flex-end; padding:14px;">
                    <div style="background:rgba(0,0,0,0.45); border:1px solid rgba(255,255,255,0.12); color:#fff; padding:8px 10px; border-radius:12px; font-size:14px; font-weight:700;">
                        <?= htmlspecialchars($communityName, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section style="background:var(--surface-card); border-radius:16px; border:1px solid var(--border-subtle); padding:12px 14px; display:flex; gap:12px; align-items:flex-start; flex-wrap:wrap;">
        <div style="width:64px; height:64px; border-radius:14px; overflow:hidden; background:radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%); display:flex; align-items:center; justify-content:center; font-size:22px; font-weight:800; color:#050509;">
            <?php if ($profileImage !== ''): ?>
                <img src="<?= htmlspecialchars($profileImage, ENT_QUOTES, 'UTF-8') ?>" alt="Imagem de perfil da comunidade" style="width:100%; height:100%; object-fit:cover; display:block;">
            <?php else: ?>
                <?= htmlspecialchars($communityInitial, ENT_QUOTES, 'UTF-8') ?>
            <?php endif; ?>
        </div>
        <div style="flex:1 1 200px; min-width:0;">
            <div>
                <h1 style="font-size:18px; margin-bottom:4px;">
                    <?= htmlspecialchars($communityName, ENT_QUOTES, 'UTF-8') ?>
                </h1>
                <?php if (!empty($community['description'])): ?>
                    <p style="font-size:13px; color:var(--text-secondary);">
                        <?= nl2br(htmlspecialchars((string)$community['description'], ENT_QUOTES, 'UTF-8')) ?>
                    </p>
                <?php endif; ?>
            </div>

            <div style="margin-top:8px; display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:6px; font-size:12px; color:var(--text-secondary);">
                <div>
                    <div style="font-size:11px; text-transform:uppercase; letter-spacing:0.08em; opacity:0.8;">Idioma</div>
                    <div><?= $languageLabel !== '' ? htmlspecialchars($languageLabel, ENT_QUOTES, 'UTF-8') : 'Não informado' ?></div>
                </div>
                <div>
                    <div style="font-size:11px; text-transform:uppercase; letter-spacing:0.08em; opacity:0.8;">Categoria</div>
                    <div><?= $category !== '' ? htmlspecialchars($category, ENT_QUOTES, 'UTF-8') : 'Sem categoria' ?></div>
                </div>
                <div>
                    <div style="font-size:11px; text-transform:uppercase; letter-spacing:0.08em; opacity:0.8;">Tipo</div>
                    <div><?= htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <div>
                    <div style="font-size:11px; text-transform:uppercase; letter-spacing:0.08em; opacity:0.8;">Privacidade do conteúdo</div>
                    <div><?= htmlspecialchars($postingLabel, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <div>
                    <div style="font-size:11px; text-transform:uppercase; letter-spacing:0.08em; opacity:0.8;">Fórum</div>
                    <div><?= htmlspecialchars($forumLabel, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <div>
                    <div style="font-size:11px; text-transform:uppercase; letter-spacing:0.08em; opacity:0.8;">Dono</div>
                    <div>
                        <?php if ($ownerName !== null): ?>
                            <?= htmlspecialchars($ownerName, ENT_QUOTES, 'UTF-8') ?>
                        <?php else: ?>
                            Não informado
                        <?php endif; ?>
                    </div>
                </div>
                <div>
                    <div style="font-size:11px; text-transform:uppercase; letter-spacing:0.08em; opacity:0.8;">Moderadores</div>
                    <div>
                        <?php if ($moderatorsText !== ''): ?>
                            <?= htmlspecialchars($moderatorsText, ENT_QUOTES, 'UTF-8') ?>
                        <?php else: ?>
                            Nenhum moderador definido
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div style="margin-top:8px; display:flex; flex-wrap:wrap; gap:8px; align-items:center;">
                <?php if ($canModerate): ?>
                    <a href="/comunidades/editar?slug=<?= urlencode($slug) ?>" style="font-size:12px; color:#ff6f60; text-decoration:none;">Editar comunidade</a>
                <?php endif; ?>
                <?php if ($isMember): ?>
                    <span style="font-size:12px; color:#8bc34a;">Você é membro desta comunidade.</span>
                <?php else: ?>
                    <form action="/comunidades/entrar" method="post" style="margin:0;">
                        <input type="hidden" name="community_id" value="<?= (int)($community['id'] ?? 0) ?>">
                        <button type="submit" style="border:none; border-radius:999px; padding:5px 10px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-size:12px; font-weight:600; cursor:pointer;">Participar da comunidade</button>
                    </form>
                <?php endif; ?>
                <a href="#topics-section" style="font-size:12px; padding:4px 9px; border-radius:999px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); text-decoration:none;">Ver fóruns/tópicos</a>
                <a href="/comunidades/enquetes?slug=<?= urlencode($slug) ?>" style="font-size:12px; padding:4px 9px; border-radius:999px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); text-decoration:none;">Enquetes da comunidade</a>
            </div>
        </div>
    </section>

    <div id="communityTwoColGrid" style="display:grid; grid-template-columns:minmax(0,2fr) minmax(0,1.1fr); gap:12px; align-items:flex-start;">
        <section id="topics-section" style="background:var(--surface-card); border-radius:16px; border:1px solid var(--border-subtle); padding:12px 14px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px; gap:10px; flex-wrap:wrap;">
                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                    <h2 style="font-size:16px;">Tópicos</h2>
                    <?php if ($isMember): ?>
                        <button type="button" id="toggleCreateTopicBtn" style="border:none; border-radius:999px; padding:5px 10px; background:var(--surface-subtle); border:1px solid var(--border-subtle); color:var(--text-primary); font-size:12px; cursor:pointer;">Criar tópico</button>
                    <?php endif; ?>
                </div>
                <div style="display:flex; align-items:center; gap:10px;">
                    <span style="font-size:12px; color:var(--text-secondary);"><?= (int)$topicsCount ?> tópico(s)</span>
                    <?php if (!$isMember): ?>
                        <span style="font-size:12px; color:var(--text-secondary);">Entre na comunidade para criar tópicos</span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($isMember): ?>
                <form id="createTopicForm" action="/comunidades/topicos/novo" method="post" enctype="multipart/form-data" style="margin-bottom:10px; display:none; flex-direction:column; gap:6px;">
                    <input type="hidden" name="community_id" value="<?= (int)($community['id'] ?? 0) ?>">
                    <input type="text" name="title" placeholder="Título do tópico" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--input-bg); color:var(--text-primary); font-size:13px;">
                    <textarea name="body" rows="3" placeholder="Mensagem inicial do tópico (opcional)" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--input-bg); color:var(--text-primary); font-size:13px; resize:vertical;"></textarea>
                    <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:center; justify-content:space-between;">
                        <div style="display:flex; flex-direction:column; gap:6px;">
                            <input id="communityTopicMediaInput" type="file" name="media" accept="image/*,video/*" style="display:none;">
                            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                <label for="communityTopicMediaInput" style="display:inline-flex; align-items:center; gap:8px; padding:6px 10px; border-radius:999px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:12px; cursor:pointer; user-select:none;">
                                    <span style="width:18px; height:18px; border-radius:6px; display:inline-flex; align-items:center; justify-content:center; background:rgba(255,111,96,0.12); border:1px solid rgba(255,111,96,0.28);">
                                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="#ff6f60" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M21 15V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v8" />
                                            <path d="M3 17l4-4 4 4 4-4 6 6" />
                                            <path d="M14 10a2 2 0 1 1-4 0 2 2 0 0 1 4 0z" />
                                        </svg>
                                    </span>
                                    <span>Anexar mídia</span>
                                </label>
                                <span id="communityTopicMediaName" style="font-size:12px; color:var(--text-secondary);">Nenhum arquivo selecionado</span>
                            </div>
                            <div style="font-size:11px; color:var(--text-secondary);">Imagem/vídeo/arquivo (opcional) · Até 20 MB.</div>
                        </div>
                        <button type="submit" style="align-self:flex-end; border:none; border-radius:999px; padding:5px 10px; background:var(--surface-subtle); border:1px solid var(--border-subtle); color:var(--text-primary); font-size:12px; cursor:pointer;">Criar tópico</button>
                    </div>
                </form>
            <?php endif; ?>

            <script>
                (function(){
                    var input = document.getElementById('communityTopicMediaInput');
                    var nameEl = document.getElementById('communityTopicMediaName');
                    if (!input || !nameEl) return;
                    input.addEventListener('change', function(){
                        var f = input.files && input.files[0] ? input.files[0] : null;
                        nameEl.textContent = f ? f.name : 'Nenhum arquivo selecionado';
                    });
                })();
            </script>

            <?php if (empty($topics)): ?>
                <p style="font-size:13px; color:var(--text-secondary);">Nenhum tópico criado ainda. Comece o primeiro!</p>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:6px;">
                    <?php foreach ($topics as $t): ?>
                        <?php
                        $topicMediaUrl = trim((string)($t['media_url'] ?? ''));
                        $topicAttachmentsCount = $topicMediaUrl !== '' ? 1 : 0;
                        $topicAttachmentsLabel = $topicAttachmentsCount === 1 ? '1 anexo' : ($topicAttachmentsCount . ' anexos');
                        ?>
                        <a href="/comunidades/topicos/ver?topic_id=<?= (int)($t['id'] ?? 0) ?>" style="text-decoration:none;">
                            <div style="background:var(--surface-subtle); border-radius:12px; border:1px solid var(--border-subtle); padding:8px 10px;">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:3px;">
                                    <div style="font-size:13px; font-weight:600; color:var(--text-primary);">
                                        <?= htmlspecialchars((string)($t['title'] ?? 'Tópico'), ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <div style="text-align:right;">
                                        <?php if (!empty($t['created_at'])): ?>
                                            <div style="font-size:11px; color:var(--text-secondary);">
                                                <?= htmlspecialchars(date('d/m/Y H:i', strtotime((string)$t['created_at'])), ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($topicAttachmentsCount > 0): ?>
                                            <div style="margin-top:2px; font-size:10px; color:var(--text-secondary); opacity:0.9;">
                                                <?= htmlspecialchars($topicAttachmentsLabel, ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div style="font-size:11px; color:#b0b0b0;">
                                    por <?= htmlspecialchars((string)($t['user_name'] ?? 'Usuário'), ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <aside id="members-section" style="background:var(--surface-card); border-radius:16px; border:1px solid var(--border-subtle); padding:12px 14px;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:6px;">
                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                    <h3 style="font-size:14px;">Membros</h3>
                    <a href="/comunidades/membros?slug=<?= urlencode($slug) ?>" style="font-size:12px; padding:4px 9px; border-radius:999px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); text-decoration:none;">Ver todos os membros</a>
                </div>
                <span style="font-size:12px; color:var(--text-secondary);"><?= (int)$membersCount ?> membro(s)</span>
            </div>
            <?php if (empty($members)): ?>
                <p style="font-size:12px; color:var(--text-secondary);">Nenhum membro listado ainda.</p>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:6px;">
                    <?php foreach ($members as $m): ?>
                        <?php
                        $memberId = (int)($m['user_id'] ?? 0);
                        $name = (string)($m['user_name'] ?? 'Usuário');
                        $initial = mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8');
                        $avatar = trim((string)($m['user_avatar_path'] ?? ''));
                        ?>
                        <a href="/perfil?user_id=<?= $memberId ?>" style="text-decoration:none;">
                            <div style="display:flex; align-items:center; gap:8px; padding:4px 6px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-subtle);">
                                <div style="width:24px; height:24px; border-radius:50%; overflow:hidden; background:radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%); display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; color:#050509;">
                                    <?php if ($avatar !== ''): ?>
                                        <img src="<?= htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8') ?>" alt="" style="width:100%; height:100%; object-fit:cover; display:block;">
                                    <?php else: ?>
                                        <?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?>
                                    <?php endif; ?>
                                </div>
                                <span style="font-size:12px; color:var(--text-primary);">
                                    <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </aside>
    </div>
</div>

<script>
    (function(){
        var btn = document.getElementById('toggleCreateTopicBtn');
        var form = document.getElementById('createTopicForm');
        if (!btn || !form) return;
        btn.addEventListener('click', function(){
            var isOpen = form.style.display !== 'none';
            form.style.display = isOpen ? 'none' : 'flex';
            if (!isOpen) {
                var titleInput = form.querySelector('input[name="title"]');
                if (titleInput) titleInput.focus();
            }
        });
    })();
</script>
