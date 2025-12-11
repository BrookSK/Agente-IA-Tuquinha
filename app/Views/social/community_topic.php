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
        <h1 style="font-size:18px;">
            <?= htmlspecialchars($topicTitle, ENT_QUOTES, 'UTF-8') ?>
        </h1>
        <div style="font-size:12px; color:#b0b0b0;">
            por <?= htmlspecialchars((string)($topic['user_name'] ?? 'Usuário'), ENT_QUOTES, 'UTF-8') ?>
            <?php if (!empty($topic['created_at'])): ?>
                · <?= htmlspecialchars(date('d/m/Y H:i', strtotime((string)$topic['created_at'])), ENT_QUOTES, 'UTF-8') ?>
            <?php endif; ?>
        </div>
        <?php if (!empty($topic['body'])): ?>
            <div style="font-size:13px; color:#f5f5f5; margin-top:4px;">
                <?= nl2br(htmlspecialchars((string)$topic['body'], ENT_QUOTES, 'UTF-8')) ?>
            </div>
        <?php endif; ?>
    </section>

    <section style="background:#111118; border-radius:16px; border:1px solid #272727; padding:12px 14px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
            <h2 style="font-size:16px;">Respostas</h2>
            <span style="font-size:12px; color:#b0b0b0;">Converse como em um fórum entre amigos</span>
        </div>

        <?php if ($isMember): ?>
            <form action="/comunidades/topicos/responder" method="post" style="margin-bottom:10px; display:flex; flex-direction:column; gap:6px;">
                <input type="hidden" name="topic_id" value="<?= (int)($topic['id'] ?? 0) ?>">
                <textarea name="body" rows="3" placeholder="Responda este tópico..." style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:13px; resize:vertical;"></textarea>
                <button type="submit" style="align-self:flex-end; border:none; border-radius:999px; padding:5px 10px; background:#111118; border:1px solid #272727; color:#f5f5f5; font-size:12px; cursor:pointer;">Enviar resposta</button>
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
                                <?= htmlspecialchars((string)($p['user_name'] ?? 'Usuário'), ENT_QUOTES, 'UTF-8') ?>
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
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
