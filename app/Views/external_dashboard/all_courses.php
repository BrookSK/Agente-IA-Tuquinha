<?php
/** @var array $courses */
?>
<div class="header">
    <div style="margin-bottom: 8px; font-size: 14px; color: var(--text-secondary);">
        Bem-vindo, <strong style="color: var(--text-primary);"><?= htmlspecialchars($user['name'] ?? 'Usuário', ENT_QUOTES, 'UTF-8') ?></strong>
    </div>
    <h1>Cursos Disponíveis</h1>
    <p>Todos os cursos que você pode acessar</p>
</div>

<?php if (empty($courses)): ?>
    <div class="card" style="text-align: center; padding: 40px;">
        <div style="font-size: 48px; margin-bottom: 12px;">📚</div>
        <p style="font-size: 16px; color: var(--text-secondary);">Nenhum curso disponível no momento.</p>
    </div>
<?php else: ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
        <?php foreach ($courses as $course): ?>
            <div class="card">
                <?php if (!empty($course['image_path'])): ?>
                    <div style="width: 100%; height: 160px; border-radius: 10px; overflow: hidden; margin-bottom: 12px; background: rgba(255,255,255,0.05);">
                        <img src="<?= htmlspecialchars($course['image_path'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($course['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                <?php endif; ?>
                
                <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 8px;">
                    <?= htmlspecialchars($course['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                </h3>
                
                <?php if (!empty($course['short_description'])): ?>
                    <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 12px;">
                        <?= htmlspecialchars($course['short_description'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                <?php endif; ?>
                
                <div style="display: flex; gap: 8px; align-items: center; margin-top: auto;">
                    <?php 
                    $hasAccess = !empty($course['user_has_access']);
                    if (!$hasAccess && !empty($course['is_paid']) && !empty($course['price_cents'])): 
                    ?>
                        <span style="font-size: 16px; font-weight: 700; color: var(--accent);">
                            R$ <?= number_format($course['price_cents'] / 100, 2, ',', '.') ?>
                        </span>
                    <?php elseif (!$hasAccess): ?>
                        <span style="font-size: 14px; font-weight: 600; color: #6be28d;">Gratuito</span>
                    <?php endif; ?>
                    
                    <?php if ($hasAccess): ?>
                        <a href="/painel-externo/curso?id=<?= (int)$course['id'] ?>" class="btn" style="margin-left: auto;">
                            Acessar curso
                        </a>
                    <?php else: ?>
                        <?php 
                        $courseToken = !empty($course['external_token']) ? $course['external_token'] : '';
                        $courseLink = $courseToken !== '' ? '/curso-externo?token=' . urlencode($courseToken) : '/painel-externo/curso?id=' . (int)$course['id'];
                        ?>
                        <a href="<?= $courseLink ?>" class="btn" style="margin-left: auto;">
                            Ver curso
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
