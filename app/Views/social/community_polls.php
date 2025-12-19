<?php

$communityName = (string)($community['name'] ?? 'Comunidade');
$slug = (string)($community['slug'] ?? '');

$canClosePolls = !empty($canClosePolls);

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

    <section style="background:var(--surface-card); border-radius:16px; border:1px solid var(--border-subtle); padding:10px 12px;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:8px; flex-wrap:wrap;">
            <div style="font-size:13px; color:var(--text-secondary);">
                <a href="/comunidades" style="color:#ff6f60; text-decoration:none;">Comunidades</a>
                <span> / </span>
                <a href="/comunidades/ver?slug=<?= urlencode($slug) ?>" style="color:#ff6f60; text-decoration:none;">
                    <?= htmlspecialchars($communityName, ENT_QUOTES, 'UTF-8') ?>
                </a>
                <span> / Enquetes</span>
            </div>
            <a href="/comunidades/ver?slug=<?= urlencode($slug) ?>" style="font-size:12px; color:#ff6f60; text-decoration:none;">Voltar à comunidade</a>
        </div>
    </section>

    <section style="background:var(--surface-card); border-radius:16px; border:1px solid var(--border-subtle); padding:12px 14px; display:flex; flex-direction:column; gap:10px;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:8px; flex-wrap:wrap;">
            <h1 style="font-size:16px;">Enquetes de <?= htmlspecialchars($communityName, ENT_QUOTES, 'UTF-8') ?></h1>
            <?php if (!empty($canModerate)): ?>
                <span style="font-size:12px; color:var(--text-secondary);">Moderadores podem criar novas enquetes</span>
            <?php endif; ?>
        </div>

        <?php if (!empty($canModerate)): ?>
            <form action="/comunidades/enquetes/criar" method="post" style="margin-bottom:8px; display:flex; flex-direction:column; gap:6px;">
                <input type="hidden" name="community_id" value="<?= (int)($community['id'] ?? 0) ?>">
                <input type="text" name="question" placeholder="Pergunta da enquete" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--input-bg); color:var(--text-primary); font-size:13px;">
                <input type="text" name="option1" placeholder="Opção 1" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--input-bg); color:var(--text-primary); font-size:13px;">
                <input type="text" name="option2" placeholder="Opção 2" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--input-bg); color:var(--text-primary); font-size:13px;">
                <input type="text" name="option3" placeholder="Opção 3 (opcional)" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--input-bg); color:var(--text-primary); font-size:13px;">
                <input type="text" name="option4" placeholder="Opção 4 (opcional)" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--input-bg); color:var(--text-primary); font-size:13px;">
                <input type="text" name="option5" placeholder="Opção 5 (opcional)" style="width:100%; padding:6px 8px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--input-bg); color:var(--text-primary); font-size:13px;">
                <button type="submit" style="align-self:flex-end; border:none; border-radius:999px; padding:5px 10px; background:var(--surface-subtle); border:1px solid var(--border-subtle); color:var(--text-primary); font-size:12px; cursor:pointer;">Criar enquete</button>
            </form>
        <?php endif; ?>

        <?php if (empty($polls)): ?>
            <p style="font-size:13px; color:var(--text-secondary);">Nenhuma enquete criada ainda.</p>
        <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:10px;">
                <?php foreach ($polls as $item): ?>
                    <?php
                    $poll = $item['poll'] ?? [];
                    $options = $item['options'] ?? [];
                    $votes = $item['votes'] ?? [];
                    $totalVotes = (int)($item['total_votes'] ?? 0);
                    $userVote = $item['user_vote'] ?? null;
                    $pollId = (int)($poll['id'] ?? 0);
                    $isClosed = !empty($poll['closed_at']);
                    ?>
                    <div id="poll-<?= $pollId ?>" style="background:var(--surface-subtle); border-radius:14px; border:1px solid var(--border-subtle); padding:10px 12px; display:flex; flex-direction:column; gap:6px;">
                        <div style="font-size:13px; font-weight:600; color:var(--text-primary);">
                            <?= htmlspecialchars((string)($poll['question'] ?? 'Enquete'), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <?php if ($isClosed): ?>
                            <div style="font-size:11px; color:var(--text-secondary);">Enquete encerrada.</div>
                        <?php endif; ?>
                        <?php if ($totalVotes > 0): ?>
                            <div style="font-size:11px; color:var(--text-secondary); margin-bottom:2px;">
                                <?= $totalVotes ?> voto(s)
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($canModerate) && $canClosePolls): ?>
                            <form action="<?= $isClosed ? '/comunidades/enquetes/reabrir' : '/comunidades/enquetes/fechar' ?>" method="post" style="margin:0; display:flex; justify-content:flex-end;">
                                <input type="hidden" name="poll_id" value="<?= $pollId ?>">
                                <button type="submit" style="border:none; border-radius:999px; padding:4px 8px; background:var(--surface-card); border:1px solid var(--border-subtle); color:var(--text-primary); font-size:11px; cursor:pointer;">
                                    <?= $isClosed ? 'Reabrir' : 'Encerrar' ?>
                                </button>
                            </form>
                        <?php endif; ?>

                        <?php if (!empty($isMember)): ?>
                            <form action="/comunidades/enquetes/votar" method="post" style="display:flex; flex-direction:column; gap:4px;" <?= $isClosed ? 'onsubmit="return false;"' : '' ?>>
                                <input type="hidden" name="poll_id" value="<?= $pollId ?>">
                                <?php foreach ($options as $num => $label): ?>
                                    <?php
                                    $v = $votes[$num] ?? ['count' => 0, 'percentage' => 0];
                                    $count = (int)($v['count'] ?? 0);
                                    $pct = (int)($v['percentage'] ?? 0);
                                    $selected = ($userVote !== null && (int)$userVote === (int)$num);
                                    ?>
                                    <label style="display:flex; flex-direction:column; gap:2px; font-size:12px; color:var(--text-primary); cursor:pointer;">
                                        <span style="display:flex; align-items:center; gap:6px;">
                                            <input type="radio" name="option" value="<?= (int)$num ?>" <?= $selected ? 'checked' : '' ?> <?= $isClosed ? 'disabled' : '' ?> style="margin:0;">
                                            <?= htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8') ?>
                                            <span style="font-size:11px; color:var(--text-secondary); margin-left:auto;">
                                                <?= $count ?> voto(s) · <?= $pct ?>%
                                            </span>
                                        </span>
                                        <span style="display:block; width:100%; height:4px; border-radius:999px; background:var(--surface-card); overflow:hidden;">
                                            <span style="display:block; height:100%; width:<?= max(0, min(100, $pct)) ?>%; background:linear-gradient(135deg,#e53935,#ff6f60);"></span>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                                <button type="submit" <?= $isClosed ? 'disabled' : '' ?> style="margin-top:4px; align-self:flex-end; border:none; border-radius:999px; padding:4px 8px; background:var(--surface-card); border:1px solid var(--border-subtle); color:var(--text-primary); font-size:11px; cursor:pointer;">Votar</button>
                            </form>
                        <?php else: ?>
                            <p style="font-size:12px; color:var(--text-secondary);">Entre na comunidade para votar nas enquetes.</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
