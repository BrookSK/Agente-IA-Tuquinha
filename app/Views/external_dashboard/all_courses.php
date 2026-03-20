<?php
/** @var array $courses */
$highlightSlug = isset($_GET['highlight']) ? trim((string)$_GET['highlight']) : '';
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
    
    .course-highlight {
        animation: highlightPulse 2s ease-in-out 3;
        border: 2px solid var(--accent) !important;
        box-shadow: 0 0 30px rgba(255, 193, 7, 0.4) !important;
        position: relative;
    }
    
    .course-highlight::before {
        content: '⭐ Curso Necessário';
        position: absolute;
        top: -12px;
        left: 50%;
        transform: translateX(-50%);
        background: var(--accent);
        color: var(--button-text);
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        white-space: nowrap;
        z-index: 10;
    }
    
    @keyframes highlightPulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.02); }
    }
</style>
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
    <div class="courses-grid">
        <?php foreach ($courses as $course): ?>
            <?php 
            $courseSlugCurrent = !empty($course['slug']) ? (string)$course['slug'] : '';
            $isHighlighted = ($highlightSlug !== '' && $courseSlugCurrent === $highlightSlug);
            ?>
            <div class="card<?= $isHighlighted ? ' course-highlight' : '' ?>" <?= $isHighlighted ? 'id="highlighted-course"' : '' ?>>
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
                        $courseSlug = !empty($course['slug']) ? (string)$course['slug'] : '';
                        $isPaid = !empty($course['is_paid']) || (!empty($course['price_cents']) && (int)$course['price_cents'] > 0);
                        
                        if ($isPaid && $courseSlug !== '') {
                            $courseLink = '/curso/' . urlencode($courseSlug) . '/checkout';
                            $buttonText = 'Comprar curso';
                        } elseif ($courseSlug !== '') {
                            $courseLink = '/curso/' . urlencode($courseSlug);
                            $buttonText = 'Ver curso';
                        } else {
                            $courseLink = '/painel-externo/curso?id=' . (int)$course['id'];
                            $buttonText = 'Ver curso';
                        }
                        ?>
                        <a href="<?= $courseLink ?>" class="btn" style="margin-left: auto;">
                            <?= $buttonText ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($highlightSlug !== ''): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const highlightedCourse = document.getElementById('highlighted-course');
    if (highlightedCourse) {
        setTimeout(function() {
            highlightedCourse.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
        }, 300);
    }
});
</script>
<?php endif; ?>
