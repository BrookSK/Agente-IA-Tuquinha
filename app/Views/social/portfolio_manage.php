<?php
/** @var array $user */
/** @var array $items */
/** @var string|null $success */
/** @var string|null $error */

$userId = (int)($user['id'] ?? 0);
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

    <section style="background:var(--surface-card); border-radius:16px; border:1px solid var(--border-subtle); padding:12px 14px;">
        <h2 style="font-size:16px; margin-bottom:8px;">Novo portfólio</h2>
        <form action="/perfil/portfolio/salvar" method="post" style="display:flex; flex-direction:column; gap:10px;">
            <input type="hidden" name="id" value="">
            <div>
                <label style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Título</label>
                <input name="title" type="text" maxlength="200" required style="width:100%; padding:8px 10px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
            </div>
            <div>
                <label style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Descrição</label>
                <textarea name="description" rows="3" style="width:100%; padding:8px 10px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px; resize:vertical;"></textarea>
            </div>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <div style="flex:1 1 240px; min-width:0;">
                    <label style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Link externo (opcional)</label>
                    <input name="external_url" type="url" maxlength="800" placeholder="https://..." style="width:100%; padding:8px 10px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                </div>
                <div style="flex:0 0 220px; min-width:0;">
                    <label style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:3px;">Projeto (opcional)</label>
                    <input name="project_id" type="number" min="0" placeholder="ID do projeto" style="width:100%; padding:8px 10px; border-radius:10px; border:1px solid var(--border-subtle); background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                </div>
            </div>
            <button type="submit" style="align-self:flex-end; border:none; border-radius:999px; padding:7px 12px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-size:12px; font-weight:650; cursor:pointer;">Criar</button>
        </form>
    </section>

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
                                <form action="/perfil/portfolio/excluir" method="post" style="margin:0;" onsubmit="return confirm('Excluir este portfólio?');">
                                    <input type="hidden" name="id" value="<?= $iid ?>">
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
