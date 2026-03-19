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
        <div style="width:100%; height:300px; background:radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%);">
            <?php if ($coverImage !== ''): ?>
                <img src="<?= htmlspecialchars($coverImage, ENT_QUOTES, 'UTF-8') ?>" alt="Capa da comunidade" style="width:100%; height:100%; object-fit:contain; display:block;">
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
                <div id="createTopicForm" style="margin-bottom:16px; display:none;">
                    <div style="background:var(--surface-card); border-radius:12px; border:1px solid var(--border-subtle); padding:20px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                            <h3 style="font-size:18px; font-weight:700; color:var(--text-primary); margin:0;">Criar Novo Tópico</h3>
                            <button type="button" id="closeCreateTopicBtn" style="border:none; background:transparent; color:var(--text-secondary); font-size:24px; cursor:pointer; padding:0; line-height:1;">×</button>
                        </div>
                        
                        <form action="/comunidades/topicos/novo" method="post" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:16px;">
                            <input type="hidden" name="community_id" value="<?= (int)($community['id'] ?? 0) ?>">
                            
                            <!-- Título -->
                            <div>
                                <label for="topicTitle" style="display:block; font-size:13px; font-weight:600; color:var(--text-primary); margin-bottom:6px;">
                                    Título do Tópico <span style="color:#ff6f60;">*</span>
                                </label>
                                <input 
                                    id="topicTitle" 
                                    type="text" 
                                    name="title" 
                                    placeholder="Ex: Dúvida sobre a aula 5, Compartilhando meu projeto..." 
                                    required
                                    style="width:100%; padding:10px 14px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--input-bg); color:var(--text-primary); font-size:14px;"
                                >
                            </div>

                            <!-- Foto de Capa -->
                            <div>
                                <label style="display:block; font-size:13px; font-weight:600; color:var(--text-primary); margin-bottom:6px;">
                                    Foto de Capa (opcional)
                                </label>
                                <input id="topicCoverInput" type="file" name="cover_image" accept="image/*" style="display:none;">
                                <div id="topicCoverPreview" style="width:100%; aspect-ratio:21/9; border-radius:10px; border:2px dashed var(--border-subtle); background:var(--surface-subtle); display:flex; align-items:center; justify-content:center; cursor:pointer; overflow:hidden; position:relative;" onclick="document.getElementById('topicCoverInput').click();">
                                    <div id="topicCoverPlaceholder" style="text-align:center; color:var(--text-secondary);">
                                        <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 8px;">
                                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                            <circle cx="8.5" cy="8.5" r="1.5"/>
                                            <polyline points="21 15 16 10 5 21"/>
                                        </svg>
                                        <div style="font-size:13px; font-weight:600;">Clique para adicionar uma capa</div>
                                        <div style="font-size:11px; margin-top:4px;">Recomendado: 1200x500px • JPG, PNG • Até 5MB</div>
                                    </div>
                                    <img id="topicCoverImage" src="" alt="" style="display:none; width:100%; height:100%; object-fit:cover;">
                                    <button type="button" id="removeCoverBtn" style="display:none; position:absolute; top:10px; right:10px; background:rgba(0,0,0,0.7); border:none; color:#fff; width:32px; height:32px; border-radius:50%; cursor:pointer; font-size:20px; line-height:1;">×</button>
                                </div>
                            </div>

                            <!-- Mensagem -->
                            <div>
                                <label for="topicBody" style="display:block; font-size:13px; font-weight:600; color:var(--text-primary); margin-bottom:6px;">
                                    Mensagem Inicial (opcional)
                                </label>
                                <textarea 
                                    id="topicBody" 
                                    name="body" 
                                    rows="4" 
                                    placeholder="Descreva seu tópico, faça sua pergunta ou compartilhe seus pensamentos..."
                                    style="width:100%; padding:10px 14px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--input-bg); color:var(--text-primary); font-size:14px; resize:vertical; line-height:1.5;"
                                ></textarea>
                            </div>

                            <!-- Anexo de Mídia -->
                            <div>
                                <label style="display:block; font-size:13px; font-weight:600; color:var(--text-primary); margin-bottom:6px;">
                                    Anexar Arquivo (opcional)
                                </label>
                                <input id="communityTopicMediaInput" type="file" name="media" accept="image/*,video/*,application/pdf,.doc,.docx" style="display:none;">
                                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                                    <label for="communityTopicMediaInput" style="display:inline-flex; align-items:center; gap:8px; padding:8px 14px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px; cursor:pointer; user-select:none; transition:all 0.2s;">
                                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>
                                        </svg>
                                        <span>Escolher Arquivo</span>
                                    </label>
                                    <span id="communityTopicMediaName" style="font-size:12px; color:var(--text-secondary); flex:1;">Nenhum arquivo selecionado</span>
                                </div>
                                <div style="font-size:11px; color:var(--text-secondary); margin-top:6px;">
                                    📎 Imagens, vídeos, PDFs ou documentos • Até 20 MB
                                </div>
                            </div>

                            <!-- Botões -->
                            <div style="display:flex; gap:10px; justify-content:flex-end; padding-top:8px; border-top:1px solid var(--border-subtle);">
                                <button type="button" id="cancelCreateTopicBtn" style="padding:10px 20px; border-radius:8px; border:1px solid var(--border-subtle); background:transparent; color:var(--text-primary); font-size:14px; font-weight:600; cursor:pointer;">
                                    Cancelar
                                </button>
                                <button type="submit" style="padding:10px 24px; border-radius:8px; border:none; background:linear-gradient(135deg,#e53935,#ff6f60); color:#fff; font-size:14px; font-weight:600; cursor:pointer;">
                                    Publicar Tópico
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <script>
                (function(){
                    // Toggle create topic form
                    var toggleBtn = document.getElementById('toggleCreateTopicBtn');
                    var closeBtn = document.getElementById('closeCreateTopicBtn');
                    var cancelBtn = document.getElementById('cancelCreateTopicBtn');
                    var form = document.getElementById('createTopicForm');
                    
                    if (toggleBtn && form) {
                        toggleBtn.addEventListener('click', function(){
                            form.style.display = form.style.display === 'none' ? 'block' : 'none';
                            if (form.style.display === 'block') {
                                var titleInput = document.getElementById('topicTitle');
                                if (titleInput) titleInput.focus();
                            }
                        });
                    }
                    
                    if (closeBtn && form) {
                        closeBtn.addEventListener('click', function(){
                            form.style.display = 'none';
                        });
                    }
                    
                    if (cancelBtn && form) {
                        cancelBtn.addEventListener('click', function(){
                            form.style.display = 'none';
                        });
                    }

                    // Handle media attachment
                    var mediaInput = document.getElementById('communityTopicMediaInput');
                    var mediaNameEl = document.getElementById('communityTopicMediaName');
                    if (mediaInput && mediaNameEl) {
                        mediaInput.addEventListener('change', function(){
                            var f = mediaInput.files && mediaInput.files[0] ? mediaInput.files[0] : null;
                            mediaNameEl.textContent = f ? f.name : 'Nenhum arquivo selecionado';
                        });
                    }

                    // Handle cover image preview
                    var coverInput = document.getElementById('topicCoverInput');
                    var coverPreview = document.getElementById('topicCoverPreview');
                    var coverPlaceholder = document.getElementById('topicCoverPlaceholder');
                    var coverImage = document.getElementById('topicCoverImage');
                    var removeCoverBtn = document.getElementById('removeCoverBtn');

                    if (coverInput && coverPreview && coverPlaceholder && coverImage && removeCoverBtn) {
                        coverInput.addEventListener('change', function(){
                            var file = coverInput.files && coverInput.files[0] ? coverInput.files[0] : null;
                            if (file && file.type.startsWith('image/')) {
                                var reader = new FileReader();
                                reader.onload = function(e){
                                    coverImage.src = e.target.result;
                                    coverImage.style.display = 'block';
                                    coverPlaceholder.style.display = 'none';
                                    removeCoverBtn.style.display = 'block';
                                };
                                reader.readAsDataURL(file);
                            }
                        });

                        removeCoverBtn.addEventListener('click', function(e){
                            e.stopPropagation();
                            coverInput.value = '';
                            coverImage.src = '';
                            coverImage.style.display = 'none';
                            coverPlaceholder.style.display = 'block';
                            removeCoverBtn.style.display = 'none';
                        });
                    }
                })();
            </script>

            <?php if (empty($topics)): ?>
                <p style="font-size:13px; color:var(--text-secondary);">Nenhum tópico criado ainda. Comece o primeiro!</p>
            <?php else: ?>
                <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:16px;">
                    <?php foreach ($topics as $t): ?>
                        <?php
                        $topicCoverUrl = trim((string)($t['cover_image_url'] ?? ''));
                        $topicTitle = htmlspecialchars((string)($t['title'] ?? 'Tópico'), ENT_QUOTES, 'UTF-8');
                        $topicId = (int)($t['id'] ?? 0);
                        $topicAuthor = htmlspecialchars((string)($t['user_name'] ?? 'Usuário'), ENT_QUOTES, 'UTF-8');
                        ?>
                        <?php if ($topicCoverUrl !== ''): ?>
                            <!-- Modern card layout for topics with cover -->
                            <div style="background:var(--surface-card); border-radius:16px; border:1px solid var(--border-subtle); overflow:hidden; transition:transform 0.2s, box-shadow 0.2s;">
                                <div style="width:100%; aspect-ratio:16/9; overflow:hidden; background:#000;">
                                    <img src="<?= htmlspecialchars($topicCoverUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" style="width:100%; height:100%; object-fit:cover;">
                                </div>
                                <div style="padding:16px;">
                                    <h3 style="font-size:15px; font-weight:700; color:var(--text-primary); margin:0 0 4px 0; line-height:1.3;">
                                        <?= $topicTitle ?>
                                    </h3>
                                    <p style="font-size:12px; color:var(--text-secondary); margin:0 0 12px 0;">
                                        por <?= $topicAuthor ?>
                                    </p>
                                    <a href="/comunidades/topicos/ver?topic_id=<?= $topicId ?>" style="display:block; width:100%; padding:10px; background:linear-gradient(135deg, #ff6f60 0%, #e53935 100%); border:none; border-radius:10px; color:#fff; font-size:14px; font-weight:600; text-align:center; text-decoration:none; cursor:pointer; transition:transform 0.2s;">
                                        Ver tópico
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Simple list layout for topics without cover -->
                            <a href="/comunidades/topicos/ver?topic_id=<?= $topicId ?>" style="text-decoration:none;">
                                <div style="background:var(--surface-subtle); border-radius:12px; border:1px solid var(--border-subtle); padding:12px; transition:transform 0.2s;">
                                    <div style="font-size:14px; font-weight:600; color:var(--text-primary); margin-bottom:6px;">
                                        <?= $topicTitle ?>
                                    </div>
                                    <div style="font-size:11px; color:var(--text-secondary);">
                                        por <?= $topicAuthor ?>
                                    </div>
                                </div>
                            </a>
                        <?php endif; ?>
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
