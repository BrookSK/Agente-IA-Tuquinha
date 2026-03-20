<?php
/** @var array $courses */
?>
<style>
    .courses-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    }
    
    @media (max-width: 768px) {
        .courses-grid {
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 16px;
        }
    }
    
    @media (max-width: 640px) {
        .courses-grid {
            grid-template-columns: 1fr;
            gap: 14px;
        }
    }
</style>
<div class="header">
    <div style="margin-bottom: 8px; font-size: 14px; color: var(--text-secondary);">
        Bem-vindo, <strong style="color: var(--text-primary);"><?= htmlspecialchars($user['name'] ?? 'Usuário', ENT_QUOTES, 'UTF-8') ?></strong>
    </div>
    <h1>Meus Cursos</h1>
    <p>Continue seus estudos</p>
</div>

<?php if (empty($courses)): ?>
    <div class="card" style="text-align: center; padding: 40px;">
        <div style="font-size: 48px; margin-bottom: 12px;">📖</div>
        <p style="font-size: 16px; color: var(--text-secondary); margin-bottom: 16px;">Você ainda não está inscrito em nenhum curso.</p>
        <a href="/painel-externo/cursos" class="btn">Ver cursos disponíveis</a>
    </div>
<?php else: ?>
    <div class="courses-grid">
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
                
                <a href="/painel-externo/curso?id=<?= (int)$course['id'] ?>" class="btn" style="width: 100%; text-align: center;">
                    Acessar curso
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
