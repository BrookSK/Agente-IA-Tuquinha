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
$coverImage = (string)($community['cover_image_path'] ?? $community['image_path'] ?? '');

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

    <section style="background:var(--surface-card); border-radius:16px; border:1px solid var(--border-subtle); padding:12px 14px; display:flex; gap:12px; align-items:flex-start; flex-wrap:wrap;">
        <div style="width:64px; height:64px; border-radius:14px; overflow:hidden; background:radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%);">
            <?php if ($coverImage !== ''): ?>
                <img src="<?= htmlspecialchars($coverImage, ENT_QUOTES, 'UTF-8') ?>" alt="Capa da comunidade" style="width:100%; height:100%; object-fit:cover; display:block;">
            <?php endif; ?>
        </div>
        <div style="flex:1 1 200px; min-width:0;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:8px;">
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
                <div style="text-align:right; font-size:12px; color:var(--text-secondary);">
                    <div><?= (int)$membersCount ?> membro(s)</div>
                    <div><?= (int)$topicsCount ?> tópico(s)</div>
                </div>
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
                <a href="/comunidades" style="font-size:12px; color:#ff6f60; text-decoration:none;">Voltar para lista de comunidades</a>
                <?php if ($isMember): ?>
                    <span style="font-size:12px; color:#8bc34a;">Você é membro desta comunidade.</span>
                <?php else: ?>
                    <form action="/comunidades/entrar" method="post" style="margin:0;">
                        <input type="hidden" name="community_id" value="<?= (int)($community['id'] ?? 0) ?>">
                        <button type="submit" style="border:none; border-radius:999px; padding:5px 10px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-size:12px; font-weight:600; cursor:pointer;">Participar da comunidade</button>
                    </form>
                <?php endif; ?>

                <a href="#topics-section" style="font-size:12px; padding:4px 9px; border-radius:999px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); text-decoration:none;">Ver fóruns/tópicos</a>
                <a href="/comunidades/membros?slug=<?= urlencode($slug) ?>" style="font-size:12px; padding:4px 9px; border-radius:999px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); text-decoration:none;">Ver todos os membros</a>
                <a href="/comunidades/enquetes?slug=<?= urlencode($slug) ?>" style="font-size:12px; padding:4px 9px; border-radius:999px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); text-decoration:none;">Enquetes da comunidade</a>
            </div>
        </div>
    </section>

    <div style="display:grid; grid-template-columns:minmax(0,2fr) minmax(0,1.1fr); gap:12px; align-items:flex-start;">
        <section id="topics-section" style="background:var(--surface-card); border-radius:16px; border:1px solid var(--border-subtle); padding:12px 14px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                <h2 style="font-size:16px;">Tópicos</h2>
                <?php if ($isMember): ?>
                    <span style="font-size:12px; color:var(--text-secondary);">Crie um novo assunto para conversar</span>
                <?php else: ?>
                    <span style="font-size:12px; color:var(--text-secondary);">Entre na comunidade para criar tópicos</span>
                <?php endif; ?>
            </div>

            <?php if ($isMember): ?>
                <form action="/comunidades/topicos/novo" method="post" style="margin-bottom:10px; display:flex; flex-direction:column; gap:6px;">
                    <input type="hidden" name="community_id" value="<?= (int)($community['id'] ?? 0) ?>">
                    <input type="text" name="title" placeholder="Título do tópico" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--input-bg); color:var(--text-primary); font-size:13px;">
                    <textarea name="body" rows="3" placeholder="Mensagem inicial do tópico (opcional)" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--input-bg); color:var(--text-primary); font-size:13px; resize:vertical;"></textarea>
                    <button type="submit" style="align-self:flex-end; border:none; border-radius:999px; padding:5px 10px; background:var(--surface-subtle); border:1px solid var(--border-subtle); color:var(--text-primary); font-size:12px; cursor:pointer;">Criar tópico</button>
                </form>
            <?php endif; ?>

            <?php if (empty($topics)): ?>
                <p style="font-size:13px; color:var(--text-secondary);">Nenhum tópico criado ainda. Comece o primeiro!</p>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:6px;">
                    <?php foreach ($topics as $t): ?>
                        <a href="/comunidades/topicos/ver?topic_id=<?= (int)($t['id'] ?? 0) ?>" style="text-decoration:none;">
                            <div style="background:var(--surface-subtle); border-radius:12px; border:1px solid var(--border-subtle); padding:8px 10px;">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:3px;">
                                    <div style="font-size:13px; font-weight:600; color:var(--text-primary);">
                                        <?= htmlspecialchars((string)($t['title'] ?? 'Tópico'), ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <div style="font-size:11px; color:var(--text-secondary);">
                                        <?php if (!empty($t['created_at'])): ?>
                                            <?= htmlspecialchars(date('d/m/Y H:i', strtotime((string)$t['created_at'])), ENT_QUOTES, 'UTF-8') ?>
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
            <h3 style="font-size:14px; margin-bottom:6px;">Membros</h3>
            <?php if (empty($members)): ?>
                <p style="font-size:12px; color:var(--text-secondary);">Nenhum membro listado ainda.</p>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:6px;">
                    <?php foreach ($members as $m): ?>
                        <?php
                        $memberId = (int)($m['user_id'] ?? 0);
                        $name = (string)($m['user_name'] ?? 'Usuário');
                        $initial = mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8');
                        ?>
                        <a href="/perfil?user_id=<?= $memberId ?>" style="text-decoration:none;">
                            <div style="display:flex; align-items:center; gap:8px; padding:4px 6px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-subtle);">
                                <div style="width:24px; height:24px; border-radius:50%; background:radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%); display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; color:#050509;">
                                    <?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?>
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
