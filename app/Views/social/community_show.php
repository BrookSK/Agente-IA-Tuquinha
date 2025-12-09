<?php

$communityName = (string)($community['name'] ?? 'Comunidade');
$slug = (string)($community['slug'] ?? '');
$membersCount = is_array($members) ? count($members) : 0;
$topicsCount = is_array($topics) ? count($topics) : 0;

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

    <section style="background:#111118; border-radius:16px; border:1px solid #272727; padding:12px 14px; display:flex; gap:12px; align-items:flex-start; flex-wrap:wrap;">
        <div style="width:64px; height:64px; border-radius:14px; background:radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%);"></div>
        <div style="flex:1 1 200px; min-width:0;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:8px;">
                <div>
                    <h1 style="font-size:18px; margin-bottom:4px;">
                        <?= htmlspecialchars($communityName, ENT_QUOTES, 'UTF-8') ?>
                    </h1>
                    <?php if (!empty($community['description'])): ?>
                        <p style="font-size:13px; color:#b0b0b0;">
                            <?= nl2br(htmlspecialchars((string)$community['description'], ENT_QUOTES, 'UTF-8')) ?>
                        </p>
                    <?php endif; ?>
                </div>
                <div style="text-align:right; font-size:12px; color:#b0b0b0;">
                    <div><?= (int)$membersCount ?> membro(s)</div>
                    <div><?= (int)$topicsCount ?> tópico(s)</div>
                </div>
            </div>
            <div style="margin-top:8px; display:flex; gap:8px; align-items:center;">
                <a href="/comunidades" style="font-size:12px; color:#ff6f60; text-decoration:none;">Voltar para lista de comunidades</a>
                <?php if ($isMember): ?>
                    <span style="font-size:12px; color:#8bc34a;">Você é membro desta comunidade.</span>
                <?php else: ?>
                    <form action="/comunidades/entrar" method="post" style="margin:0;">
                        <input type="hidden" name="community_id" value="<?= (int)($community['id'] ?? 0) ?>">
                        <button type="submit" style="border:none; border-radius:999px; padding:5px 10px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-size:12px; font-weight:600; cursor:pointer;">Participar da comunidade</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <div style="display:grid; grid-template-columns:minmax(0,2fr) minmax(0,1.1fr); gap:12px; align-items:flex-start;">
        <section style="background:#111118; border-radius:16px; border:1px solid #272727; padding:12px 14px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                <h2 style="font-size:16px;">Tópicos</h2>
                <?php if ($isMember): ?>
                    <span style="font-size:12px; color:#b0b0b0;">Crie um novo assunto para conversar</span>
                <?php else: ?>
                    <span style="font-size:12px; color:#b0b0b0;">Entre na comunidade para criar tópicos</span>
                <?php endif; ?>
            </div>

            <?php if ($isMember): ?>
                <form action="/comunidades/topicos/novo" method="post" style="margin-bottom:10px; display:flex; flex-direction:column; gap:6px;">
                    <input type="hidden" name="community_id" value="<?= (int)($community['id'] ?? 0) ?>">
                    <input type="text" name="title" placeholder="Título do tópico" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:13px;">
                    <textarea name="body" rows="3" placeholder="Mensagem inicial do tópico (opcional)" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:13px; resize:vertical;"></textarea>
                    <button type="submit" style="align-self:flex-end; border:none; border-radius:999px; padding:5px 10px; background:#111118; border:1px solid #272727; color:#f5f5f5; font-size:12px; cursor:pointer;">Criar tópico</button>
                </form>
            <?php endif; ?>

            <?php if (empty($topics)): ?>
                <p style="font-size:13px; color:#b0b0b0;">Nenhum tópico criado ainda. Comece o primeiro!</p>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:6px;">
                    <?php foreach ($topics as $t): ?>
                        <a href="/comunidades/topicos/ver?topic_id=<?= (int)($t['id'] ?? 0) ?>" style="text-decoration:none;">
                            <div style="background:#050509; border-radius:12px; border:1px solid #272727; padding:8px 10px;">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:3px;">
                                    <div style="font-size:13px; font-weight:600; color:#f5f5f5;">
                                        <?= htmlspecialchars((string)($t['title'] ?? 'Tópico'), ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <div style="font-size:11px; color:#b0b0b0;">
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

        <aside style="background:#111118; border-radius:16px; border:1px solid #272727; padding:12px 14px;">
            <h3 style="font-size:14px; margin-bottom:6px;">Membros</h3>
            <?php if (empty($members)): ?>
                <p style="font-size:12px; color:#b0b0b0;">Nenhum membro listado ainda.</p>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:6px;">
                    <?php foreach ($members as $m): ?>
                        <?php
                        $memberId = (int)($m['user_id'] ?? 0);
                        $name = (string)($m['user_name'] ?? 'Usuário');
                        $initial = mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8');
                        ?>
                        <a href="/perfil?user_id=<?= $memberId ?>" style="text-decoration:none;">
                            <div style="display:flex; align-items:center; gap:8px; padding:4px 6px; border-radius:10px; border:1px solid #272727; background:#050509;">
                                <div style="width:24px; height:24px; border-radius:50%; background:radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%); display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; color:#050509;">
                                    <?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <span style="font-size:12px; color:#f5f5f5;">
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
