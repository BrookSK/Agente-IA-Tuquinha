<?php
/** @var array $communities */
?>
<div class="header">
    <div style="margin-bottom: 8px; font-size: 14px; color: var(--text-secondary);">
        Bem-vindo, <strong style="color: var(--text-primary);"><?= htmlspecialchars($user['name'] ?? 'Usuário', ENT_QUOTES, 'UTF-8') ?></strong>
    </div>
    <h1>Comunidade</h1>
    <p>Participe das discussões</p>
</div>

<?php if (empty($communities)): ?>
    <div class="card" style="text-align: center; padding: 40px;">
        <div style="font-size: 48px; margin-bottom: 12px;">👥</div>
        <p style="font-size: 16px; color: var(--text-secondary);">Você não tem acesso a nenhuma comunidade no momento.</p>
        <p style="font-size: 13px; color: var(--text-secondary); margin-top: 8px;">As comunidades são liberadas quando você se inscreve em cursos que permitem acesso.</p>
    </div>
<?php else: ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;">
        <?php foreach ($communities as $community): ?>
            <?php
                $communityImage = trim((string)($community['image_path'] ?? ''));
                $communityName = htmlspecialchars($community['name'] ?? '', ENT_QUOTES, 'UTF-8');
                $communityInitial = mb_strtoupper(mb_substr($community['name'] ?? 'C', 0, 1, 'UTF-8'), 'UTF-8');
            ?>
            <div class="card">
                <?php if (!empty($community['cover_image_path'])): ?>
                    <div style="width: 100%; height: 120px; border-radius: 10px; overflow: hidden; margin-bottom: 12px; background: rgba(255,255,255,0.05);">
                        <img src="<?= htmlspecialchars($community['cover_image_path'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= $communityName ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                <?php endif; ?>
                
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                    <div style="width: 50px; height: 50px; border-radius: 12px; overflow: hidden; background: linear-gradient(135deg, var(--accent), var(--accent2)); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <?php if ($communityImage !== ''): ?>
                            <img src="<?= htmlspecialchars($communityImage, ENT_QUOTES, 'UTF-8') ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <span style="font-size: 20px; font-weight: 700; color: var(--button-text);"><?= htmlspecialchars($communityInitial, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>
                    <h3 style="font-size: 18px; font-weight: 700; margin: 0; flex: 1;">
                        <?= $communityName ?>
                    </h3>
                </div>
                
                <?php if (!empty($community['description'])): ?>
                    <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 12px; line-height: 1.5;">
                        <?= htmlspecialchars(mb_substr($community['description'], 0, 120, 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>
                        <?= mb_strlen($community['description'], 'UTF-8') > 120 ? '...' : '' ?>
                    </p>
                <?php endif; ?>
                
                <div style="display: flex; gap: 12px; align-items: center; margin-bottom: 12px; font-size: 12px; color: var(--text-secondary);">
                    <span>👥 <?= number_format((int)($community['members_count'] ?? 0)) ?> membros</span>
                    <span>💬 <?= number_format((int)($community['topics_count'] ?? 0)) ?> tópicos</span>
                </div>
                
                <a href="/painel-externo/comunidade/ver?slug=<?= urlencode($community['slug'] ?? '') ?>" class="btn" style="width: 100%; text-align: center;">
                    Acessar comunidade
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
