<?php
/** @var array $user */
/** @var array $items */
/** @var string|null $success */
/** @var string|null $error */
/** @var int|null $ownerId */
/** @var bool|null $canShare */
/** @var array|null $collaborators */
/** @var array|null $pendingInvites */
/** @var array|null $editItem */
/** @var array|null $editBlocks */

$userId = (int)($user['id'] ?? 0);
$ownerUserId = isset($ownerId) ? (int)$ownerId : $userId;
$canShare = !empty($canShare);
$collaborators = is_array($collaborators ?? null) ? $collaborators : [];
$pendingInvites = is_array($pendingInvites ?? null) ? $pendingInvites : [];

$editBlocks = is_array($editBlocks ?? null) ? $editBlocks : [];

$editItem = is_array($editItem ?? null) ? $editItem : null;
$isEditing = !empty($editItem) && !empty($editItem['id']);
$editItemId = $isEditing ? (int)($editItem['id'] ?? 0) : 0;
$editTitle = $isEditing ? (string)($editItem['title'] ?? '') : '';
$editDescription = $isEditing ? (string)($editItem['description'] ?? '') : '';
$editExternalUrl = $isEditing ? (string)($editItem['external_url'] ?? '') : '';
$editProjectId = $isEditing ? (int)($editItem['project_id'] ?? 0) : 0;
?>
<style>
    @media (max-width: 900px) {
        #portfolioManageTop {
            flex-direction: column !important;
            align-items: flex-start !important;
        }
        #portfolioManageGrid {
            grid-template-columns: 1fr !important;
        }
    }
</style>

<div style="max-width: 980px; margin: 0 auto; display:flex; flex-direction:column; gap:14px;">
    <section style="background:var(--surface-card); border-radius:16px; border:1px solid var(--border-subtle); padding:12px 14px;">
        <div id="portfolioManageTop" style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
            <div>
                <h1 style="font-size:18px; margin-bottom:2px; color:var(--text-primary);">Meu portfólio</h1>
                <div style="font-size:12px; color:var(--text-secondary);">Crie e gerencie seus portfólios para aparecer no seu perfil.</div>
            </div>
            <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                <a href="/perfil/portfolio" style="font-size:12px; color:#ff6f60; text-decoration:none;">Ver público</a>
                <a href="/perfil" style="font-size:12px; color:#ff6f60; text-decoration:none;">Voltar ao perfil</a>
            </div>
        </div>
    </section>

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

    <?php if ($canShare): ?>
        <section style="background:var(--surface-card); border-radius:16px; border:1px solid var(--border-subtle); padding:12px 14px;">
            <h2 style="font-size:16px; margin-bottom:8px;">Compartilhar</h2>
            <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                <select id="portfolioInviteItem" style="flex:1 1 220px; min-width:180px; max-width:100%; padding:10px 10px; border-radius:12px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px; outline:none;">
                    <option value="">Selecione um item</option>
                    <?php foreach (($items ?? []) as $it): ?>
                        <option value="<?= (int)($it['id'] ?? 0) ?>"><?= htmlspecialchars((string)($it['title'] ?? 'Item'), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
                <input id="portfolioInviteEmail" type="email" placeholder="Email do colaborador" style="flex:1 1 220px; min-width:180px; max-width:100%; padding:10px 12px; border-radius:12px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px; outline:none;" />
                <select id="portfolioInviteRole" style="flex:0 0 auto; min-width:140px; max-width:100%; padding:10px 10px; border-radius:12px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px; outline:none;">
                    <option value="read">Leitura</option>
                    <option value="edit">Edição</option>
                </select>
                <button type="button" id="sendPortfolioInviteBtn" style="border:none; border-radius:999px; padding:10px 14px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-size:12px; font-weight:750; cursor:pointer;">Convidar</button>
            </div>
            <div id="portfolioInviteFeedback" style="display:none; margin-top:8px; font-size:12px;"></div>

            <?php if (!empty($pendingInvites)): ?>
                <div style="margin-top:14px;">
                    <div style="font-size:12px; color:var(--text-secondary); margin-bottom:6px;">Convites pendentes</div>
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        <?php foreach ($pendingInvites as $inv): ?>
                            <?php
                                $rawRole = (string)($inv['role'] ?? 'read');
                                $roleLabel = $rawRole === 'edit' ? 'Edição' : 'Leitura';
                                $invItemTitle = trim((string)($inv['portfolio_item_title'] ?? ''));
                                if ($invItemTitle === '') { $invItemTitle = 'Item'; }
                            ?>
                            <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; border:1px solid var(--border-subtle); border-radius:12px; padding:10px 12px; background:var(--surface-subtle); flex-wrap:wrap;">
                                <div style="min-width:0;">
                                    <div style="font-size:12px; color:var(--text-primary); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars((string)($inv['invited_email'] ?? '')) ?></div>
                                    <div style="font-size:11px; color:var(--text-secondary); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">Item: <?= htmlspecialchars($invItemTitle, ENT_QUOTES, 'UTF-8') ?></div>
                                    <div style="font-size:11px; color:var(--text-secondary);">Permissão: <?= htmlspecialchars($roleLabel) ?></div>
                                </div>
                                <button type="button" class="revokePortfolioInviteBtn" data-invite-id="<?= (int)($inv['id'] ?? 0) ?>" style="border:1px solid var(--border-subtle); background:var(--surface-card); color:#ffbaba; border-radius:10px; padding:8px 10px; cursor:pointer;">Revogar</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($collaborators)): ?>
                <div style="margin-top:14px;">
                    <div style="font-size:12px; color:var(--text-secondary); margin-bottom:6px;">Colaboradores</div>
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        <?php foreach ($collaborators as $m): ?>
                            <?php
                                $label = trim((string)($m['user_preferred_name'] ?? ''));
                                if ($label === '') { $label = trim((string)($m['user_name'] ?? '')); }
                                if ($label === '') { $label = (string)($m['user_email'] ?? ''); }
                                $uid = (int)($m['collaborator_user_id'] ?? 0);
                                $role = (string)($m['role'] ?? 'read');
                                $itemId = (int)($m['portfolio_item_id'] ?? 0);
                                $itemTitle = trim((string)($m['portfolio_item_title'] ?? ''));
                                if ($itemTitle === '') { $itemTitle = 'Item'; }
                                $hasValidItem = $itemId > 0;
                            ?>
                            <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; border:1px solid var(--border-subtle); border-radius:12px; padding:10px 12px; background:var(--surface-subtle); flex-wrap:wrap;">
                                <div style="min-width:0;">
                                    <div style="font-size:12px; color:var(--text-primary); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars($label) ?></div>
                                    <div style="font-size:11px; color:var(--text-secondary); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars((string)($m['user_email'] ?? '')) ?></div>
                                    <div style="font-size:11px; color:var(--text-secondary); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">Item: <?= htmlspecialchars($itemTitle, ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php if (!$hasValidItem): ?>
                                        <div style="font-size:11px; color:var(--text-secondary);">Este compartilhamento é antigo e não está vinculado a um item.</div>
                                    <?php endif; ?>
                                </div>
                                <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                                    <select class="portfolioCollabRoleSelect" data-user-id="<?= $uid ?>" data-item-id="<?= (int)$itemId ?>" <?= !$hasValidItem ? 'disabled' : '' ?> style="padding:8px 10px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-card); color:var(--text-primary); font-size:12px; outline:none;">
                                        <option value="read" <?= $role === 'read' ? 'selected' : '' ?>>Leitura</option>
                                        <option value="edit" <?= $role === 'edit' ? 'selected' : '' ?>>Edição</option>
                                    </select>
                                    <button type="button" class="removePortfolioCollabBtn" data-user-id="<?= $uid ?>" data-item-id="<?= (int)$itemId ?>" <?= !$hasValidItem ? 'disabled' : '' ?> style="border:1px solid var(--border-subtle); background:var(--surface-card); color:#ffbaba; border-radius:10px; padding:8px 10px; cursor:pointer;">Remover</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <script>
                (function () {
                    var sendBtn = document.getElementById('sendPortfolioInviteBtn');
                    if (sendBtn) {
                        sendBtn.addEventListener('click', async function () {
                            var itemEl = document.getElementById('portfolioInviteItem');
                            var emailEl = document.getElementById('portfolioInviteEmail');
                            var roleEl = document.getElementById('portfolioInviteRole');
                            var fb = document.getElementById('portfolioInviteFeedback');
                            if (!itemEl || !emailEl || !roleEl) return;
                            var fd = new FormData();
                            fd.append('owner_user_id', '<?= (int)$ownerUserId ?>');
                            fd.append('portfolio_item_id', itemEl.value || '');
                            fd.append('email', emailEl.value || '');
                            fd.append('role', roleEl.value || 'read');
                            sendBtn.disabled = true;
                            try {
                                var res = await fetch('/perfil/portfolio/compartilhar/convidar', {
                                    method: 'POST',
                                    body: fd,
                                    credentials: 'same-origin',
                                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                                });
                                var json = await res.json().catch(function(){ return null; });
                                if (fb) {
                                    fb.style.display = 'block';
                                    fb.style.color = (json && json.ok) ? '#c8ffd4' : '#ffbaba';
                                    fb.textContent = (json && json.ok) ? 'Convite enviado.' : ((json && json.error) ? json.error : 'Não foi possível convidar.');
                                }
                                if (json && json.ok) {
                                    itemEl.value = '';
                                    emailEl.value = '';
                                    setTimeout(function(){ window.location.reload(); }, 600);
                                }
                            } catch (e) {
                                if (fb) {
                                    fb.style.display = 'block';
                                    fb.style.color = '#ffbaba';
                                    fb.textContent = 'Não foi possível convidar.';
                                }
                            } finally {
                                sendBtn.disabled = false;
                            }
                        });
                    }

                    document.querySelectorAll('.revokePortfolioInviteBtn').forEach(function (btn) {
                        btn.addEventListener('click', async function () {
                            var inviteId = btn.getAttribute('data-invite-id');
                            if (!inviteId) return;
                            var fd = new FormData();
                            fd.append('owner_user_id', '<?= (int)$ownerUserId ?>');
                            fd.append('invite_id', inviteId);
                            btn.disabled = true;
                            try {
                                var res = await fetch('/perfil/portfolio/compartilhar/revogar', {
                                    method: 'POST',
                                    body: fd,
                                    credentials: 'same-origin',
                                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                                });
                                var json = await res.json().catch(function(){ return null; });
                                if (json && json.ok) {
                                    window.location.reload();
                                }
                            } catch (e) {
                            } finally {
                                btn.disabled = false;
                            }
                        });
                    });

                    document.querySelectorAll('.portfolioCollabRoleSelect').forEach(function (sel) {
                        sel.addEventListener('change', async function () {
                            var uid = sel.getAttribute('data-user-id');
                            var itemId = sel.getAttribute('data-item-id');
                            var role = sel.value;
                            if (!uid || !itemId) return;
                            var fd = new FormData();
                            fd.append('owner_user_id', '<?= (int)$ownerUserId ?>');
                            fd.append('portfolio_item_id', itemId);
                            fd.append('user_id', uid);
                            fd.append('role', role);
                            sel.disabled = true;
                            try {
                                var res = await fetch('/perfil/portfolio/compartilhar/alterar-role', {
                                    method: 'POST',
                                    body: fd,
                                    credentials: 'same-origin',
                                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                                });
                                var json = await res.json().catch(function(){ return null; });
                                if (!json || !json.ok) {
                                    window.location.reload();
                                }
                            } catch (e) {
                                window.location.reload();
                            } finally {
                                sel.disabled = false;
                            }
                        });
                    });

                    document.querySelectorAll('.removePortfolioCollabBtn').forEach(function (btn) {
                        btn.addEventListener('click', async function () {
                            if (!confirm('Remover este colaborador deste item do portfólio?')) return;
                            var uid = btn.getAttribute('data-user-id');
                            var itemId = btn.getAttribute('data-item-id');
                            if (!uid || !itemId) return;
                            var fd = new FormData();
                            fd.append('owner_user_id', '<?= (int)$ownerUserId ?>');
                            fd.append('portfolio_item_id', itemId);
                            fd.append('user_id', uid);
                            btn.disabled = true;
                            try {
                                var res = await fetch('/perfil/portfolio/compartilhar/remover', {
                                    method: 'POST',
                                    body: fd,
                                    credentials: 'same-origin',
                                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                                });
                                var json = await res.json().catch(function(){ return null; });
                                if (json && json.ok) {
                                    window.location.reload();
                                }
                            } catch (e) {
                            } finally {
                                btn.disabled = false;
                            }
                        });
                    });
                })();
            </script>
        </section>
    <?php endif; ?>

    <section style="background:var(--surface-card); border-radius:16px; border:1px solid var(--border-subtle); padding:12px 14px;">
        <h2 style="font-size:16px; margin-bottom:8px;"><?= $isEditing ? 'Editar portfólio' : 'Novo portfólio' ?></h2>
        <form action="/perfil/portfolio/salvar" method="post" style="display:flex; flex-direction:column; gap:10px;">
            <input type="hidden" name="id" value="<?= $isEditing ? (int)$editItemId : '' ?>">
            <input type="hidden" name="owner_user_id" value="<?= (int)$ownerUserId ?>">
            <div>
                <label style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Título</label>
                <input name="title" type="text" maxlength="200" required value="<?= htmlspecialchars($editTitle, ENT_QUOTES, 'UTF-8') ?>" style="width:100%; padding:8px 10px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
            </div>
            <div>
                <label style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Descrição</label>
                <textarea name="description" rows="3" style="width:100%; padding:8px 10px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px; resize:vertical;"><?= htmlspecialchars($editDescription, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <div style="flex:1 1 240px; min-width:0;">
                    <label style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Link externo (opcional)</label>
                    <input name="external_url" type="url" maxlength="800" placeholder="https://..." value="<?= htmlspecialchars($editExternalUrl, ENT_QUOTES, 'UTF-8') ?>" style="width:100%; padding:8px 10px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                </div>
                <div style="flex:0 0 220px; min-width:0;">
                    <label style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Projeto (opcional)</label>
                    <input name="project_id" type="number" min="0" placeholder="ID do projeto" value="<?= $editProjectId > 0 ? (int)$editProjectId : '' ?>" style="width:100%; padding:8px 10px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                </div>
            </div>
            <div style="display:flex; gap:8px; justify-content:flex-end; flex-wrap:wrap;">
                <?php if ($isEditing): ?>
                    <a href="/perfil/portfolio/gerenciar?owner_user_id=<?= (int)$ownerUserId ?>" style="border-radius:999px; padding:7px 12px; border:1px solid var(--border-subtle); background:var(--surface-card); color:var(--text-primary); font-size:12px; text-decoration:none;">Cancelar</a>
                    <a href="/perfil/portfolio/editor?id=<?= (int)$editItemId ?>" style="border-radius:999px; padding:7px 12px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:12px; text-decoration:none;">Editar blocos</a>
                    <form action="/perfil/portfolio/publicar" method="post" style="margin:0;" onsubmit="return confirm('Publicar este projeto? Depois de publicado, ficará visível para todos.');">
                        <input type="hidden" name="item_id" value="<?= (int)$editItemId ?>" />
                        <button type="submit" style="border:none; border-radius:999px; padding:7px 12px; background:#1f6feb; color:#fff; font-size:12px; font-weight:800; cursor:pointer;">Publicar</button>
                    </form>
                <?php endif; ?>
                <button type="submit" style="border:none; border-radius:999px; padding:7px 12px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-size:12px; font-weight:650; cursor:pointer;"><?= $isEditing ? 'Salvar' : 'Criar' ?></button>
            </div>
        </form>
    </section>

    <?php if ($isEditing && $editItemId > 0): ?>
        <section style="background:var(--surface-card); border-radius:16px; border:1px solid var(--border-subtle); padding:12px 14px;">
            <h2 style="font-size:16px; margin-bottom:8px;">Blocos do projeto</h2>
            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:10px;">Abra o editor para montar o projeto com blocos (texto, imagem, grade, vídeo...).</div>
            <a href="/perfil/portfolio/editor?id=<?= (int)$editItemId ?>" style="display:inline-block; border-radius:999px; padding:8px 12px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-size:12px; font-weight:800; text-decoration:none;">Abrir editor de blocos</a>
        </section>
    <?php endif; ?>

    <section style="background:var(--surface-card); border-radius:16px; border:1px solid var(--border-subtle); padding:12px 14px;">
        <h2 style="font-size:16px; margin-bottom:8px;">Meus portfólios</h2>

        <?php if (empty($items)): ?>
            <div style="font-size:13px; color:var(--text-secondary);">Você ainda não criou nenhum portfólio.</div>
        <?php else: ?>
            <div id="portfolioManageGrid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(320px, 1fr)); gap:10px;">
                <?php foreach ($items as $it): ?>
                    <?php $iid = (int)($it['id'] ?? 0); ?>
                    <div style="border:1px solid var(--border-subtle); background:var(--surface-subtle); border-radius:14px; padding:10px 12px;">
                        <div style="display:flex; justify-content:space-between; gap:10px; align-items:flex-start;">
                            <div style="min-width:0;">
                                <div style="font-weight:650; font-size:13px; color:var(--text-primary); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                    <?= htmlspecialchars((string)($it['title'] ?? 'Portfólio'), ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <?php if (!empty($it['external_url'])): ?>
                                    <a href="<?= htmlspecialchars((string)$it['external_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" style="font-size:12px; color:#ff6f60; text-decoration:none;">Abrir link externo</a>
                                <?php endif; ?>
                            </div>
                            <div style="display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end;">
                                <a href="/perfil/portfolio/ver?id=<?= $iid ?>" style="border:1px solid var(--border-subtle); background:var(--surface-card); color:var(--text-primary); border-radius:999px; padding:5px 10px; font-size:12px; text-decoration:none;">Detalhes</a>
                                <a href="/perfil/portfolio/gerenciar?owner_user_id=<?= (int)$ownerUserId ?>&edit_id=<?= $iid ?>" style="border:1px solid var(--border-subtle); background:var(--surface-card); color:var(--text-primary); border-radius:999px; padding:5px 10px; font-size:12px; text-decoration:none;">Editar</a>
                                <form action="/perfil/portfolio/excluir" method="post" style="margin:0;" onsubmit="return confirm('Excluir este portfólio?');">
                                    <input type="hidden" name="id" value="<?= $iid ?>">
                                    <input type="hidden" name="owner_user_id" value="<?= (int)$ownerUserId ?>">
                                    <button type="submit" style="border:1px solid var(--border-subtle); background:var(--surface-card); color:#ffbaba; border-radius:999px; padding:5px 10px; font-size:12px; cursor:pointer;">Excluir</button>
                                </form>
                            </div>
                        </div>
                        <?php if (!empty($it['description'])): ?>
                            <div style="margin-top:6px; font-size:12px; color:var(--text-secondary); line-height:1.35;">
                                <?= nl2br(htmlspecialchars((string)$it['description'], ENT_QUOTES, 'UTF-8')) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
