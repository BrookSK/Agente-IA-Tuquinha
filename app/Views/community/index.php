<?php
/** @var array $user */
/** @var array|null $plan */
/** @var array $posts */
/** @var array $likesCount */
/** @var array $commentsCount */
/** @var array $likedByMe */
/** @var array $commentsByPost */
/** @var array $originalPosts */
/** @var array|null $block */
/** @var string|null $success */
/** @var string|null $error */

$editingPostId = isset($_GET['edit_post_id']) ? (int)($_GET['edit_post_id']) : 0;
$activeTab = isset($_GET['tab']) ? trim((string)$_GET['tab']) : 'scraps';
$allowedTabs = ['perfil', 'scraps', 'amigos', 'comunidades'];
if (!in_array($activeTab, $allowedTabs, true)) {
    $activeTab = 'scraps';
}

// Contagem simples de "amigos" baseada nos autores que aparecem no feed
$myId = (int)($user['id'] ?? 0);
$friendIds = [];
$friendsPreview = [];
$myPostCount = 0;
foreach ($posts as $p) {
    $uid = (int)($p['user_id'] ?? 0);
    $uname = trim((string)($p['user_name'] ?? ''));
    if ($uid === $myId) {
        $myPostCount++;
        continue;
    }
    if ($uid > 0 && $uname !== '' && !isset($friendIds[$uid])) {
        $friendIds[$uid] = true;
        $friendsPreview[] = [
            'id' => $uid,
            'name' => $uname,
        ];
        if (count($friendsPreview) >= 12) {
            break;
        }
    }
}
$friendsCount = count($friendIds);
$communitiesCount = 1; // por enquanto, apenas a comunidade global do Tuquinha
?>
<div style="max-width: 980px; margin: 0 auto; padding: 0 4px 16px 4px;">
    <div style="display:flex; flex-wrap:wrap; gap:18px; align-items:flex-start;">
        <aside style="flex:0 0 220px; min-width:220px; max-width:240px;">
            <div style="border-radius:16px; border:1px solid #272727; background:#111118; padding:10px 12px; margin-bottom:10px;">
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
                    <?php
                        $name = trim((string)($user['preferred_name'] ?? $user['name'] ?? 'Usuário'));
                        $initial = mb_strtoupper(mb_substr($name, 0, 1));
                    ?>
                    <div style="width:56px; height:56px; border-radius:50%; background:radial-gradient(circle at 30% 20%, #fff 0, #ffb74d 30%, #e53935 65%, #050509 100%); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:24px; color:#050509; box-shadow:0 0 18px rgba(229,57,53,0.7);">
                        <?= htmlspecialchars($initial) ?>
                    </div>
                    <div style="min-width:0;">
                        <div style="font-size:14px; font-weight:650; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                            <?= htmlspecialchars($name) ?>
                        </div>
                        <div style="font-size:11px; color:#b0b0b0; margin-top:2px;">
                            Membro da comunidade do Tuquinha
                        </div>
                    </div>
                </div>
                <div style="display:flex; gap:6px; font-size:11px; color:#b0b0b0; margin-top:4px;">
                    <div style="flex:1 1 0; text-align:center;">
                        <div style="font-weight:600; color:#ffcc80;"><?= (int)$friendsCount ?></div>
                        <div>amigos*</div>
                    </div>
                    <div style="flex:1 1 0; text-align:center;">
                        <div style="font-weight:600; color:#90caf9;"><?= (int)$communitiesCount ?></div>
                        <div>comunidades</div>
                    </div>
                    <div style="flex:1 1 0; text-align:center;">
                        <div style="font-weight:600; color:#c5e1a5;"><?= (int)$myPostCount ?></div>
                        <div>posts</div>
                    </div>
                </div>
                <div style="margin-top:6px; font-size:10px; color:#777;">
                    *Amigos aqui são outros alunos que já apareceram com você no feed.
                </div>
            </div>

            <div style="border-radius:16px; border:1px solid #272727; background:#111118; padding:8px 10px;">
                <div style="font-size:12px; font-weight:600; margin-bottom:6px;">Navegação</div>
                <?php
                    $tabs = [
                        'perfil' => 'Perfil',
                        'scraps' => 'Scraps / mural',
                        'amigos' => 'Amigos',
                        'comunidades' => 'Comunidades',
                    ];
                ?>
                <nav style="display:flex; flex-direction:column; gap:4px; font-size:12px;">
                    <?php foreach ($tabs as $tabKey => $tabLabel): ?>
                        <?php
                            $isActive = $activeTab === $tabKey;
                        ?>
                        <a href="/comunidade?tab=<?= urlencode($tabKey) ?>" style="
                            display:flex; align-items:center; gap:6px; padding:6px 8px; border-radius:999px;
                            text-decoration:none; border:1px solid <?= $isActive ? '#ff6f60' : 'transparent' ?>;
                            background:<?= $isActive ? '#111118' : 'transparent' ?>;
                            color:<?= $isActive ? '#ffcc80' : '#f5f5f5' ?>;">
                            <span style="width:8px; height:8px; border-radius:50%; border:2px solid #7cb342; background:<?= $isActive ? '#7cb342' : 'transparent' ?>;"></span>
                            <span><?= htmlspecialchars($tabLabel) ?></span>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </div>
        </aside>

        <main style="flex:2 1 320px; min-width:260px;">
            <h1 style="font-size: 20px; margin-bottom: 4px; font-weight: 650;">Comunidade do Tuquinha</h1>
            <p style="color:#b0b0b0; font-size:13px; margin-bottom:10px;">
                Espaço para trocar dúvidas, processos e histórias com outros alunos do Tuquinha. Inspirado no Orkut, mas com o jeitinho atual da plataforma.
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

            <?php if ($activeTab === 'perfil'): ?>
                <div style="border-radius:16px; border:1px solid #272727; background:#111118; padding:12px 14px; font-size:13px;">
                    <h2 style="font-size:15px; margin-bottom:6px;">Sobre você</h2>
                    <div style="display:flex; flex-wrap:wrap; gap:10px;">
                        <div style="flex:1 1 180px; min-width:180px;">
                            <div style="font-size:12px; color:#b0b0b0; margin-bottom:4px;">Nome completo</div>
                            <div style="font-weight:600; margin-bottom:6px;"><?= htmlspecialchars($user['name'] ?? '') ?></div>
                            <div style="font-size:12px; color:#b0b0b0; margin-bottom:4px;">Como o Tuquinha te chama</div>
                            <div style="margin-bottom:6px;">
                                <?= htmlspecialchars($user['preferred_name'] ?? ($user['name'] ?? '')) ?>
                            </div>
                            <div style="font-size:12px; color:#b0b0b0; margin-bottom:4px;">E-mail</div>
                            <div style="margin-bottom:6px; color:#d0d0d0;">
                                <?= htmlspecialchars($user['email'] ?? '') ?>
                            </div>
                        </div>
                        <div style="flex:1 1 220px; min-width:200px;">
                            <div style="font-size:12px; color:#b0b0b0; margin-bottom:4px;">Memórias globais</div>
                            <div style="font-size:12px; color:#d0d0d0; border-radius:8px; border:1px solid #272727; background:#050509; padding:6px 8px; min-height:40px;">
                                <?= nl2br(htmlspecialchars($user['global_memory'] ?? 'Sem memórias globais cadastradas ainda.')) ?>
                            </div>
                            <div style="font-size:12px; color:#b0b0b0; margin:8px 0 4px;">Regras globais para o Tuquinha</div>
                            <div style="font-size:12px; color:#d0d0d0; border-radius:8px; border:1px solid #272727; background:#050509; padding:6px 8px; min-height:40px;">
                                <?= nl2br(htmlspecialchars($user['global_instructions'] ?? 'Sem regras globais personalizadas ainda.')) ?>
                            </div>
                        </div>
                    </div>
                    <div style="margin-top:10px; font-size:11px; color:#8d8d8d;">
                        Quer editar esses dados? Vá em <a href="/conta" style="color:#ff6f60; text-decoration:none;">Minha conta</a>.
                    </div>
                </div>
            <?php elseif ($activeTab === 'amigos'): ?>
                <div style="border-radius:16px; border:1px solid #272727; background:#111118; padding:12px 14px; font-size:13px;">
                    <h2 style="font-size:15px; margin-bottom:6px;">Seus amigos na comunidade</h2>
                    <p style="font-size:12px; color:#b0b0b0; margin-bottom:8px;">
                        Aqui aparecem outras pessoas que já publicaram ou interagiram com você na Comunidade do Tuquinha.
                    </p>
                    <?php if (empty($friendsPreview)): ?>
                        <div style="font-size:12px; color:#b0b0b0;">Assim que outras pessoas começarem a participar dos mesmos posts que você, elas vão aparecer aqui. 🙂</div>
                    <?php else: ?>
                        <div style="display:flex; flex-wrap:wrap; gap:8px;">
                            <?php foreach ($friendsPreview as $f): ?>
                                <?php
                                    $fname = trim((string)$f['name']);
                                    $finitial = mb_strtoupper(mb_substr($fname, 0, 1));
                                ?>
                                <div style="flex:0 0 48%; min-width:150px; border-radius:12px; border:1px solid #272727; background:#050509; padding:6px 8px; display:flex; align-items:center; gap:8px;">
                                    <div style="width:32px; height:32px; border-radius:50%; background:#111118; display:flex; align-items:center; justify-content:center; font-size:14px; font-weight:600; color:#ffcc80;">
                                        <?= htmlspecialchars($finitial) ?>
                                    </div>
                                    <div style="min-width:0;">
                                        <div style="font-size:13px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                            <?= htmlspecialchars($fname) ?>
                                        </div>
                                        <div style="font-size:11px; color:#777;">Companheiro(a) de comunidade</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php elseif ($activeTab === 'comunidades'): ?>
                <div style="border-radius:16px; border:1px solid #272727; background:#111118; padding:12px 14px; font-size:13px;">
                    <h2 style="font-size:15px; margin-bottom:6px;">Suas comunidades</h2>
                    <p style="font-size:12px; color:#b0b0b0; margin-bottom:8px;">
                        Por enquanto, tudo acontece dentro da <strong>Comunidade do Tuquinha</strong>. No futuro, novas comunidades temáticas podem aparecer aqui.
                    </p>
                    <div style="display:flex; flex-wrap:wrap; gap:10px;">
                        <div style="flex:1 1 220px; min-width:200px; border-radius:14px; border:1px solid #272727; background:radial-gradient(circle at 0 0, #283593 0, #050509 45%, #111118 100%); padding:10px 12px; color:#f5f5f5;">
                            <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                                <div style="width:40px; height:40px; border-radius:8px; background:#050509; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:18px; color:#ffcc80; box-shadow:0 0 14px rgba(66,165,245,0.7);">
                                    T
                                </div>
                                <div>
                                    <div style="font-size:14px; font-weight:650;">Comunidade do Tuquinha</div>
                                    <div style="font-size:11px; color:#c5cae9;">Branding vivo na veia</div>
                                </div>
                            </div>
                            <div style="font-size:12px; color:#e0e0e0; margin-bottom:6px;">
                                Mural para compartilhar dúvidas, processos, resultados e histórias de quem está estudando com o Tuquinha.
                            </div>
                            <div style="display:flex; justify-content:space-between; align-items:center; font-size:11px; color:#c5e1a5;">
                                <span><?= (int)$friendsCount + 1 ?> membros recentes</span>
                                <a href="/comunidade" style="color:#ffcc80; text-decoration:none;">Entrar &rarr;</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div style="padding:10px 12px; border-radius:12px; background:#111118; border:1px solid #272727; margin-bottom:14px;">
                    <h2 style="font-size:15px; margin-bottom:6px;">Novo scrap</h2>
                    <?php if ($block): ?>
                        <p style="font-size:12px; color:#777;">Você não pode criar novos scraps enquanto estiver bloqueado.</p>
                    <?php else: ?>
                        <form action="/comunidade/postar" method="post" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:6px;">
                            <textarea name="body" rows="3" maxlength="4000" placeholder="Deixe um recado para a comunidade..." style="
                                width:100%; padding:8px 10px; border-radius:8px; border:1px solid #272727;
                                background:#050509; color:#f5f5f5; font-size:13px; resize:vertical;"></textarea>
                            <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:center; font-size:11px; color:#b0b0b0;">
                                <label style="display:inline-flex; align-items:center; gap:4px; cursor:pointer;">
                                    <span>📷</span>
                                    <span>Imagem</span>
                                    <input type="file" name="image" accept="image/*" style="display:none;" id="community-image-input">
                                </label>
                                <label style="display:inline-flex; align-items:center; gap:4px; cursor:pointer;">
                                    <span>📎</span>
                                    <span>Arquivo</span>
                                    <input type="file" name="file" style="display:none;" id="community-file-input">
                                </label>
                                <span style="margin-left:auto;">Até 4000 caracteres</span>
                            </div>
                            <div id="community-attachment-preview" style="margin-top:4px; display:none; padding:6px 8px; border-radius:8px; border:1px dashed #272727; background:#050509;">
                                <div id="community-image-preview" style="display:none; margin-bottom:4px;">
                                    <img src="" alt="Pré-visualização da imagem" style="max-width:100%; max-height:180px; border-radius:8px; border:1px solid #272727; object-fit:cover;">
                                </div>
                                <div id="community-file-preview" style="display:none; font-size:11px; color:#b0b0b0;"></div>
                            </div>
                            <div style="display:flex; justify-content:flex-end; margin-top:4px;">
                                <button type="submit" style="
                                    border:none; border-radius:999px; padding:7px 14px;
                                    background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509;
                                    font-weight:600; font-size:12px; cursor:pointer;">
                                    Publicar scrap
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>

                <?php if (empty($posts)): ?>
                    <div style="color:#b0b0b0; font-size:13px;">Ainda não há scraps na comunidade. Seja o primeiro a deixar um recado. 🙂</div>
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

                            $original = null;
                            if ($repostId > 0 && !empty($originalPosts[$repostId])) {
                                $original = $originalPosts[$repostId];
                            }
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
                                <?php if ($original): ?>
                                    <?php
                                        $origAuthor = trim((string)($original['user_name'] ?? ''));
                                        $origCreated = $original['created_at'] ?? '';
                                        $origBody = trim((string)($original['body'] ?? ''));
                                        $origImage = trim((string)($original['image_path'] ?? ''));
                                        $origFile = trim((string)($original['file_path'] ?? ''));
                                    ?>
                                    <div style="border-radius:10px; border:1px solid #272727; background:#050509; padding:6px 8px; font-size:12px; margin-bottom:4px;">
                                        <div style="display:flex; justify-content:space-between; gap:6px; margin-bottom:2px;">
                                            <span style="font-weight:600;">
                                                <?= htmlspecialchars($origAuthor) ?>
                                            </span>
                                            <?php if ($origCreated): ?>
                                                <span style="font-size:10px; color:#777;">
                                                    <?= htmlspecialchars($origCreated) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($origBody !== ''): ?>
                                            <div style="color:#d0d0d0; font-size:12px; margin:0 0 2px 0;">
                                                <?= nl2br(htmlspecialchars($origBody)) ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($origImage !== '' || $origFile !== ''): ?>
                                            <div style="margin-top:2px;">
                                                <?php if ($origImage !== ''): ?>
                                                    <div style="margin-bottom:4px;">
                                                        <img src="<?= htmlspecialchars($origImage) ?>" alt="Imagem do post original" style="max-width:100%; border-radius:8px; border:1px solid #272727;">
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($origFile !== ''): ?>
                                                    <div style="font-size:11px;">
                                                        <a href="<?= htmlspecialchars($origFile) ?>" target="_blank" rel="noopener noreferrer" style="color:#ffcc80; text-decoration:none;">📎 Baixar arquivo original</a>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div style="font-size:11px; color:#777; margin-bottom:4px;">Post original não está mais disponível.</div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if ($image !== '' || $file !== ''): ?>
                                <div style="margin-bottom:6px;">
                                    <?php if ($image !== ''): ?>
                                        <div style="margin-bottom:4px;">
                                            <img src="<?= htmlspecialchars($image) ?>" alt="Imagem do post" style="max-width:100%; border-radius:10px; border:1px solid #272727;">
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($file !== ''): ?>
                                        <div style="font-size:12px;">
                                            <a href="<?= htmlspecialchars($file) ?>" target="_blank" rel="noopener noreferrer" style="color:#ffcc80; text-decoration:none;">📎 Baixar arquivo</a>
                                        </div>
                                    <?php endif; ?>
                                </div>
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
                                <div style="font-size:13px; color:#d0d0d0; margin:0 0 2px 0;">
                                    <?= nl2br(htmlspecialchars($body)) ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($image !== '' || $file !== ''): ?>
                                <div style="margin-top:2px;">
                                    <?php if ($image !== ''): ?>
                                        <div style="margin-bottom:4px;">
                                            <img src="<?= htmlspecialchars($image) ?>" alt="Imagem do post" style="max-width:100%; border-radius:10px; border:1px solid #272727;">
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($file !== ''): ?>
                                        <div style="font-size:12px;">
                                            <a href="<?= htmlspecialchars($file) ?>" target="_blank" rel="noopener noreferrer" style="color:#ffcc80; text-decoration:none;">📎 Baixar arquivo</a>
                                        </div>
                                    <?php endif; ?>
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
                                                <div style="color:#d0d0d0;">
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
            <?php endif; ?>
        </main>

        <aside style="flex:1 1 220px; min-width:220px; max-width:260px;">
            <div style="padding:10px 12px; border-radius:16px; background:#111118; border:1px solid #272727; font-size:12px; color:#b0b0b0; margin-bottom:10px;">
                <h2 style="font-size:14px; margin-bottom:6px;">Resumo rápido</h2>
                <div style="display:flex; flex-direction:column; gap:4px;">
                    <div>
                        <strong><?= (int)$friendsCount ?></strong> pessoas diferentes já apareceram nos mesmos posts que você.
                    </div>
                    <div>
                        <strong><?= (int)count($posts) ?></strong> scraps recentes no mural.
                    </div>
                </div>
            </div>

            <?php if (!empty($friendsPreview)): ?>
                <div style="padding:10px 12px; border-radius:16px; background:#111118; border:1px solid #272727; font-size:12px; color:#b0b0b0; margin-bottom:10px;">
                    <h2 style="font-size:14px; margin-bottom:6px;">Amigos em destaque</h2>
                    <div style="display:flex; flex-wrap:wrap; gap:6px;">
                        <?php foreach ($friendsPreview as $f): ?>
                            <?php
                                $fname = trim((string)$f['name']);
                                $finitial = mb_strtoupper(mb_substr($fname, 0, 1));
                            ?>
                            <div style="flex:0 0 48%; min-width:120px; border-radius:10px; border:1px solid #272727; background:#050509; padding:4px 6px; display:flex; align-items:center; gap:6px;">
                                <div style="width:26px; height:26px; border-radius:50%; background:#111118; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:600; color:#ffcc80;">
                                    <?= htmlspecialchars($finitial) ?>
                                </div>
                                <div style="font-size:11px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    <?= htmlspecialchars($fname) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($_SESSION['is_admin'])): ?>
                <div style="padding:10px 12px; border-radius:16px; background:#111118; border:1px solid #272727; font-size:12px; color:#b0b0b0;">
                    <h2 style="font-size:14px; margin-bottom:6px;">Painel rápido do admin</h2>
                    <p style="margin-bottom:6px;">Como admin, você pode:</p>
                    <ul style="margin-left:16px; margin-bottom:6px;">
                        <li>Editar e excluir qualquer post</li>
                        <li>Bloquear usuários na comunidade</li>
                        <li>Desbloquear usuários via página de bloqueios</li>
                    </ul>
                </div>
            <?php endif; ?>
        </aside>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var imageInput = document.getElementById('community-image-input');
    var fileInput = document.getElementById('community-file-input');
    var previewBox = document.getElementById('community-attachment-preview');
    var imageWrapper = document.getElementById('community-image-preview');
    var imageTag = imageWrapper ? imageWrapper.querySelector('img') : null;
    var filePreview = document.getElementById('community-file-preview');

    function updateVisibility() {
        var hasImage = imageWrapper && imageWrapper.style.display !== 'none';
        var hasFile = filePreview && filePreview.style.display !== 'none';
        if (previewBox) {
            previewBox.style.display = hasImage || hasFile ? 'block' : 'none';
        }
    }

    if (imageInput && imageWrapper && imageTag) {
        imageInput.addEventListener('change', function () {
            var file = imageInput.files && imageInput.files[0];
            if (!file) {
                imageWrapper.style.display = 'none';
                if (imageTag) {
                    imageTag.src = '';
                }
                updateVisibility();
                return;
            }

            var reader = new FileReader();
            reader.onload = function (e) {
                imageTag.src = e.target && e.target.result ? e.target.result : '';
                imageWrapper.style.display = imageTag.src ? 'block' : 'none';
                updateVisibility();
            };
            reader.readAsDataURL(file);
        });
    }

    if (fileInput && filePreview) {
        fileInput.addEventListener('change', function () {
            var file = fileInput.files && fileInput.files[0];
            if (!file) {
                filePreview.textContent = '';
                filePreview.style.display = 'none';
                updateVisibility();
                return;
            }

            filePreview.textContent = '📎 ' + (file.name || 'Arquivo selecionado');
            filePreview.style.display = 'block';
            updateVisibility();
        });
    }
});
</script>
