<?php /** @var string|null $error */ ?>
<div style="max-width: 620px; margin: 0 auto;">
    <h1 style="font-size: 24px; margin-bottom: 10px;">Novo projeto</h1>

    <?php if (!empty($error)): ?>
        <div style="background:#311; border:1px solid #a33; color:#ffbaba; padding:8px 10px; border-radius:8px; font-size:13px; margin-bottom:12px;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form action="/projetos/criar" method="post" style="display:flex; flex-direction:column; gap:10px;">
        <div>
            <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Nome*</label>
            <input type="text" name="name" required style="width:100%; padding:8px 10px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:14px;">
        </div>
        <div>
            <label style="font-size:13px; color:#ddd; display:block; margin-bottom:4px;">Descrição</label>
            <textarea name="description" rows="4" style="width:100%; padding:8px 10px; border-radius:8px; border:1px solid #272727; background:#050509; color:#f5f5f5; font-size:14px; resize:vertical;"></textarea>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:10px;">
            <a href="/projetos" style="border:1px solid #272727; border-radius:999px; padding:9px 14px; background:#111118; color:#f5f5f5; font-weight:600; font-size:13px; text-decoration:none;">Cancelar</a>
            <button type="submit" style="border:none; border-radius:999px; padding:9px 14px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:600; font-size:13px; cursor:pointer;">Criar projeto</button>
        </div>
    </form>
</div>
