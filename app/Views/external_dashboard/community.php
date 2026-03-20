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
        <p style="font-size: 16px; color: var(--text-secondary);">Nenhuma comunidade disponível no momento.</p>
        <p style="font-size: 13px; color: var(--text-secondary); margin-top: 8px;">As comunidades são vinculadas aos cursos.</p>
    </div>
<?php else: ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;">
        <?php foreach ($communities as $community): ?>
            <?php
                $communityImage = trim((string)($community['image_path'] ?? ''));
                $communityName = htmlspecialchars($community['name'] ?? '', ENT_QUOTES, 'UTF-8');
                $communityInitial = mb_strtoupper(mb_substr($community['name'] ?? 'C', 0, 1, 'UTF-8'), 'UTF-8');
                $hasAccess = !empty($community['user_has_access']);
                $courseTitle = htmlspecialchars($community['course_title'] ?? 'o curso', ENT_QUOTES, 'UTF-8');
                $courseSlug = trim((string)($community['course_slug'] ?? ''));
            ?>
            <div class="card" style="<?= !$hasAccess ? 'opacity: 0.7; position: relative;' : '' ?>">
                <?php if (!$hasAccess): ?>
                    <div style="position: absolute; top: 12px; right: 12px; background: rgba(255, 193, 7, 0.2); border: 1px solid rgba(255, 193, 7, 0.5); border-radius: 8px; padding: 4px 10px; font-size: 11px; font-weight: 600; color: #ffc107; display: flex; align-items: center; gap: 4px;">
                        🔒 Bloqueado
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($community['cover_image_path'])): ?>
                    <div style="width: 100%; height: 120px; border-radius: 10px; overflow: hidden; margin-bottom: 12px; background: rgba(255,255,255,0.05);">
                        <img src="<?= htmlspecialchars($community['cover_image_path'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= $communityName ?>" style="width: 100%; height: 100%; object-fit: cover; <?= !$hasAccess ? 'filter: grayscale(50%);' : '' ?>">
                    </div>
                <?php endif; ?>
                
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                    <div style="width: 50px; height: 50px; border-radius: 12px; overflow: hidden; background: linear-gradient(135deg, var(--accent), var(--accent2)); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <?php if ($communityImage !== ''): ?>
                            <img src="<?= htmlspecialchars($communityImage, ENT_QUOTES, 'UTF-8') ?>" alt="" style="width: 100%; height: 100%; object-fit: cover; <?= !$hasAccess ? 'filter: grayscale(50%);' : '' ?>">
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
                
                <?php if (!$hasAccess): ?>
                    <div style="background: rgba(255, 193, 7, 0.1); border: 1px solid rgba(255, 193, 7, 0.3); border-radius: 10px; padding: 12px; margin-bottom: 12px;">
                        <div style="display: flex; align-items: start; gap: 8px;">
                            <span style="font-size: 16px; flex-shrink: 0;">⚠️</span>
                            <p style="font-size: 12px; color: #ffc107; margin: 0; line-height: 1.4;">
                                Você precisa participar do curso <strong><?= $courseTitle ?></strong> para ter acesso a esta comunidade.
                            </p>
                        </div>
                    </div>
                    <?php if ($courseSlug !== ''): ?>
                        <a href="/painel-externo/cursos?highlight=<?= urlencode($courseSlug) ?>" class="btn" style="width: 100%; text-align: center; background: rgba(255, 193, 7, 0.2); color: #ffc107; border: 1px solid rgba(255, 193, 7, 0.3);">
                            Ver curso necessário
                        </a>
                    <?php else: ?>
                        <button class="btn" style="width: 100%; text-align: center; opacity: 0.5; cursor: not-allowed;" disabled>
                            Acesso bloqueado
                        </button>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="/painel-externo/comunidade/ver?slug=<?= urlencode($community['slug'] ?? '') ?>" class="btn" style="width: 100%; text-align: center;">
                        Acessar comunidade
                    </a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
