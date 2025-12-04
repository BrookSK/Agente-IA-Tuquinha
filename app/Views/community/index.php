<?php
/** @var array $user */
/** @var array|null $plan */
/** @var array $posts */
/** @var array $likesCount */
/** @var array $commentsCount */
/** @var array $likedByMe */
/** @var array $commentsByPost */
/** @var array|null $block */
/** @var string|null $success */
/** @var string|null $error */

$editingPostId = isset($_GET['edit_post_id']) ? (int)($_GET['edit_post_id']) : 0;
?>
<div style="max-width: 960px; margin: 0 auto;">
    <h1 style="font-size: 22px; margin-bottom: 8px; font-weight: 650;">Comunidade do Tuquinha</h1>
    <p style="color:#b0b0b0; font-size:13px; margin-bottom:12px;">
        Espaço para quem está estudando com o Tuquinha trocar dúvidas, processos, prints e aprendizados sobre branding e carreira criativa.
    </p>

    <?php if (!empty($success)): ?>
        <div style="background:#10330f; border:1px solid #3aa857; color:#c8ffd4; padding:8px 10px; border-radius:8px; font-size:13px; margin-bottom:10px;">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div style="background:#311; border:1px solid #a33; color:#ffbaba; padding:8px 10px; border-radius:8px; font-size:13px; margin-bottom:10px;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($block): ?>
        <div style="background:#311; border:1px solid #a33; color:#ffbaba; padding:8px 10px; border-radius:8px; font-size:13px; margin-bottom:10px;">
            <strong>Você está bloqueado na comunidade.</strong><br>
            <?php if (!empty($block['reason'])): ?>
                <span style="font-size:12px; color:#ffb74d;">Motivo: <?= nl2br(htmlspecialchars($block['reason'])) ?></span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div style="display:flex; flex-wrap:wrap; gap:18px; align-items:flex-start;">
        <div style="flex:2 1 320px; min-width:260px;">
            <div style="padding:10px 12px; border-radius:12px; background:#111118; border:1px solid #272727; margin-bottom:14px;">
                <h2 style="font-size:15px; margin-bottom:6px;">Novo post</h2>
                <?php if ($block): ?>
                    <p style="font-size:12px; color:#777;">Você não pode criar novos posts enquanto estiver bloqueado.</p>
                <?php else: ?>
                    <form action="/comunidade/postar" method="post" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:6px;">
                        <textarea name="body" rows="3" maxlength="4000" placeholder="Compartilhe uma dúvida, um aprendizado ou um print de processo..." style="
                            width:100%; padding:8px 10px; border-radius:8px; border:1px solid #272727;
                            background:#050509; color:#f5f5f5; font-size:13px; resize:vertical;"></textarea>
                        <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:center; font-size:11px; color:#b0b0b0;">
                            <label style="display:inline-flex; align-items:center; gap:4px; cursor:pointer;">
                                <span>📷</span>
                                <span>Imagem</span>
                                <input type="file" name="image" accept="image/*" style="display:none;">
                            </label>
                            <label style="display:inline-flex; align-items:center; gap:4px; cursor:pointer;">
                                <span>📎</span>
                                <span>Arquivo</span>
                                <input type="file" name="file" style="display:none;">
                            </label>
                            <span style="margin-left:auto;">Até 4000 caracteres</span>
                        </div>
                        <div style="display:flex; justify-content:flex-end; margin-top:4px;">
                            <button type="submit" style="
                                border:none; border-radius:999px; padding:7px 14px;
                                background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509;
                                font-weight:600; font-size:12px; cursor:pointer;">
                                Publicar
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>

            <?php if (empty($posts)): ?>
                <div style="color:#b0b0b0; font-size:13px;">Ainda não há posts na comunidade. Seja o primeiro a compartilhar algo. 🙂</div>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:10px;">
                    <?php foreach ($posts as $post): ?>
                        <?php
                            $postId = (int)$post['id'];
                            $author = trim((string)($post['user_name'] ?? ''));
                            $createdAt = $post['created_at'] ?? '';
                            $body = trim((string)($post['body'] ?? ''));
                            $image = trim((string)($post['image_path'] ?? ''));
                            $file = trim((string)($post['file_path'] ?? ''));
                            $repostId = (int)($post['repost_post_id'] ?? 0);
                            $isMine = (int)$post['user_id'] === (int)$user['id'];
                            $isAdmin = !empty($_SESSION['is_admin']);
                            $likes = $likesCount[$postId] ?? 0;
                            $commentsTotal = $commentsCount[$postId] ?? 0;
                            $isLiked = !empty($likedByMe[$postId]);
                            $postComments = $commentsByPost[$postId] ?? [];
                            $isEditing = $editingPostId === $postId && ($isMine || $isAdmin);
                        ?>
                        <div id="post-<?= $postId ?>" style="border-radius:12px; border:1px solid #272727; background:#111118; padding:8px 10px; font-size:13px;">
                            <div style="display:flex; justify-content:space-between; gap:8px; margin-bottom:4px;">
                                <div>
                                    <strong><?= htmlspecialchars($author) ?></strong>
                                </div>
                                <?php if ($createdAt): ?>
                                    <div style="font-size:11px; color:#777;">
                                        <?= htmlspecialchars($createdAt) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if ($repostId): ?>
                                <div style="font-size:11px; color:#b0b0b0; margin-bottom:4px;">🔁 Republicação de outro post</div>
                            <?php endif; ?>

                            <?php if ($isEditing): ?>
                                <form action="/comunidade/editar-post" method="post" style="margin-bottom:6px;">
                                    <input type="hidden" name="post_id" value="<?= $postId ?>">
                                    <textarea name="body" rows="3" maxlength="4000" style="
                                        width:100%; padding:6px 8px; border-radius:8px; border:1px solid #272727;
                                        background:#050509; color:#f5f5f5; font-size:13px; resize:vertical;"><?= htmlspecialchars($body) ?></textarea>
                                    <div style="margin-top:4px; display:flex; justify-content:flex-end; gap:6px; font-size:11px;">
                                        <a href="/comunidade#post-<?= $postId ?>" style="color:#b0b0b0; text-decoration:none;">Cancelar</a>
                                        <button type="submit" style="
                                            border:none; border-radius:999px; padding:5px 12px;
                                            background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509;
                                            font-weight:600; font-size:11px; cursor:pointer;">
                                            Salvar alterações
                                        </button>
                                    </div>
                                </form>
                            <?php elseif ($body !== ''): ?>
                                <div style="font-size:13px; color:#d0d0d0; white-space:pre-wrap; margin-bottom:6px;">
                                    <?= nl2br(htmlspecialchars($body)) ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($image !== ''): ?>
                                <div style="margin-bottom:6px;">
                                    <img src="<?= htmlspecialchars($image) ?>" alt="Imagem do post" style="max-width:100%; border-radius:10px; border:1px solid #272727;">
                                </div>
                            <?php endif; ?>

                            <?php if ($file !== ''): ?>
                                <div style="margin-bottom:6px; font-size:12px;">
                                    <a href="<?= htmlspecialchars($file) ?>" target="_blank" rel="noopener noreferrer" style="color:#ffcc80; text-decoration:none;">📎 Baixar arquivo</a>
                                </div>
                            <?php endif; ?>

                            <div style="display:flex; align-items:center; gap:10px; font-size:11px; color:#b0b0b0; margin-top:4px;">
                                <form action="/comunidade/curtir" method="post" style="display:inline;">
                                    <input type="hidden" name="post_id" value="<?= $postId ?>">
                                    <button type="submit" style="background:none; border:none; color:<?= $isLiked ? '#ff6f60' : '#b0b0b0' ?>; cursor:pointer; padding:0;">
                                        ❤️ <?= $likes ?>
                                    </button>
                                </form>

                                <span>💬 <?= $commentsTotal ?></span>

                                <form action="/comunidade/repostar" method="post" style="display:inline;">
                                    <input type="hidden" name="post_id" value="<?= $postId ?>">
                                    <button type="submit" style="background:none; border:none; color:#b0b0b0; cursor:pointer; padding:0;">
                                        🔁 Republicar
                                    </button>
                                </form>

                                <?php if ($isMine || !empty($_SESSION['is_admin'])): ?>
                                    <div style="margin-left:auto; display:flex; gap:8px; align-items:center;">
                                        <a href="/comunidade?edit_post_id=<?= $postId ?>#post-<?= $postId ?>" style="color:#b0b0b0; text-decoration:none;">Editar</a>
                                        <form action="/comunidade/excluir-post" method="post" style="display:inline;">
                                            <input type="hidden" name="post_id" value="<?= $postId ?>">
                                            <button type="submit" style="background:none; border:none; color:#ff8a65; cursor:pointer; padding:0;">Excluir</button>
                                        </form>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($_SESSION['is_admin']) && !$isMine): ?>
                                    <form action="/comunidade/bloquear-usuario" method="post" style="display:inline; margin-left:6px;">
                                        <input type="hidden" name="user_id" value="<?= (int)$post['user_id'] ?>">
                                        <input type="hidden" name="reason" value="Conteúdo inadequado em um post da comunidade.">
                                        <button type="submit" style="background:none; border:none; color:#ef5350; cursor:pointer; padding:0;">Bloquear autor</button>
                                    </form>
                                <?php endif; ?>
                            </div>

                            <div style="margin-top:6px; padding-top:6px; border-top:1px dashed #272727;">
                                <?php if (!empty($postComments)): ?>
                                    <div style="display:flex; flex-direction:column; gap:4px; margin-bottom:4px; max-height:160px; overflow:auto;">
                                        <?php foreach ($postComments as $comment): ?>
                                            <div style="border-radius:8px; border:1px solid #272727; background:#050509; padding:4px 6px; font-size:12px;">
                                                <div style="display:flex; justify-content:space-between; gap:8px; margin-bottom:2px;">
                                                    <span style="font-weight:600;">
                                                        <?= htmlspecialchars($comment['user_name'] ?? '') ?>
                                                    </span>
                                                    <?php if (!empty($comment['created_at'])): ?>
                                                        <span style="font-size:10px; color:#777;">
                                                            <?= htmlspecialchars($comment['created_at']) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div style="color:#d0d0d0; white-space:pre-wrap;">
                                                    <?= nl2br(htmlspecialchars($comment['body'] ?? '')) ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($block): ?>
                                    <div style="font-size:11px; color:#777;">Você não pode comentar enquanto estiver bloqueado.</div>
                                <?php else: ?>
                                    <form action="/comunidade/comentar" method="post" style="margin-top:4px;">
                                        <input type="hidden" name="post_id" value="<?= $postId ?>">
                                        <textarea name="body" rows="2" maxlength="2000" placeholder="Comente algo sobre este post..." style="
                                            width:100%; padding:6px 8px; border-radius:8px; border:1px solid #272727;
                                            background:#050509; color:#f5f5f5; font-size:12px; resize:vertical;"></textarea>
                                        <div style="margin-top:4px; display:flex; justify-content:flex-end;">
                                            <button type="submit" style="
                                                border:none; border-radius:999px; padding:5px 12px;
                                                background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509;
                                                font-weight:600; font-size:11px; cursor:pointer;">
                                                Comentar
                                            </button>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($_SESSION['is_admin'])): ?>
            <div style="flex:1 1 220px; min-width:220px;">
                <div style="padding:10px 12px; border-radius:12px; background:#111118; border:1px solid #272727; font-size:12px; color:#b0b0b0;">
                    <h2 style="font-size:14px; margin-bottom:6px;">Painel rápido do admin</h2>
                    <p style="margin-bottom:6px;">Como admin, você pode:</p>
                    <ul style="margin-left:16px; margin-bottom:6px;">
                        <li>Editar e excluir qualquer post</li>
                        <li>Bloquear usuários na comunidade</li>
                        <li>Desbloquear usuários via botão dedicado (a implementar em tela própria, se desejar)</li>
                    </ul>
                    <?php if (!empty($_SESSION['community_block_reason'])): ?>
                        <p style="font-size:11px; color:#ffb74d;">Motivo do último bloqueio ativo para este usuário está salvo na sessão.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
