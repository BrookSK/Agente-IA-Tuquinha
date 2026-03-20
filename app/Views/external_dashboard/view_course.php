<?php
/** @var array $course */
/** @var bool $hasAccess */
/** @var array $modules */
/** @var array|null $branding */
?>
<style>
    .lesson-item:hover {
        background: rgba(255,255,255,0.06) !important;
        border-color: var(--accent) !important;
    }
    
    @media (max-width: 768px) {
        .header h1 {
            font-size: 22px !important;
        }
        
        .header p {
            font-size: 13px !important;
        }
        
        .card {
            padding: 14px !important;
        }
        
        .lesson-item {
            padding: 10px !important;
            gap: 10px !important;
        }
        
        .lesson-item > div:first-child {
            font-size: 18px !important;
        }
        
        .lesson-item > div:nth-child(2) > div:first-child {
            font-size: 13px !important;
        }
        
        .lesson-item > div:nth-child(2) > div:last-child {
            font-size: 11px !important;
        }
    }
    
    @media (max-width: 640px) {
        .header h1 {
            font-size: 20px !important;
        }
        
        .header p {
            font-size: 12px !important;
        }
        
        .card {
            padding: 12px !important;
            margin-bottom: 14px !important;
        }
    }
</style>
<div class="header">
    <h1><?= htmlspecialchars($course['title'] ?? '', ENT_QUOTES, 'UTF-8') ?></h1>
    <?php if (!empty($course['short_description'])): ?>
        <p><?= htmlspecialchars($course['short_description'], ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
</div>

<?php if (!$hasAccess): ?>
    <?php if (!empty($course['image_path'])): ?>
    <div style="width: 100%; max-width: 700px; margin: 0 auto 24px; border-radius: 14px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
        <img src="<?= htmlspecialchars($course['image_path'], ENT_QUOTES, 'UTF-8') ?>" 
             alt="<?= htmlspecialchars($course['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
             style="width: 100%; height: auto; display: block;">
    </div>
    <?php endif; ?>

    <div class="card">
        <?php if (!empty($course['description'])): ?>
            <div style="font-size: 14px; line-height: 1.6; color: var(--text-secondary); margin-bottom: 20px; white-space: pre-line;">
                <?= htmlspecialchars($course['description'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <div style="padding: 16px; background: rgba(255, 204, 128, 0.1); border: 1px solid #ffcc80; border-radius: 10px; margin-bottom: 16px;">
            <div style="font-size: 14px; font-weight: 600; color: #ffcc80; margin-bottom: 8px;">🔒 Curso não adquirido</div>
            <p style="font-size: 13px; color: var(--text-secondary); margin: 0;">Você ainda não tem acesso a este curso. Adquira agora para começar a estudar.</p>
        </div>

        <?php if (!empty($course['is_paid']) && !empty($course['price_cents'])): ?>
            <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                <div style="font-size: 24px; font-weight: 800; color: var(--accent);">
                    R$ <?= number_format($course['price_cents'] / 100, 2, ',', '.') ?>
                </div>
                <?php if (!empty($course['slug'])): ?>
                    <a href="/curso/<?= urlencode((string)$course['slug']) ?>/checkout" class="btn">
                        Comprar agora
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div style="font-size: 16px; font-weight: 600; color: #6be28d; margin-bottom: 12px;">Curso Gratuito</div>
            <?php if (!empty($course['slug'])): ?>
                <a href="/curso/<?= urlencode((string)$course['slug']) ?>" class="btn">
                    Inscrever-se gratuitamente
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div style="padding: 14px; background: rgba(107, 226, 141, 0.1); border: 1px solid #6be28d; border-radius: 10px; margin-bottom: 20px;">
        <div style="font-size: 14px; font-weight: 600; color: #6be28d;">✅ Você tem acesso completo a este curso</div>
    </div>

    <?php if (empty($modules)): ?>
        <div class="card" style="text-align: center; padding: 40px;">
            <div style="font-size: 48px; margin-bottom: 12px;">�</div>
            <p style="font-size: 16px; color: var(--text-secondary);">Este curso ainda não possui módulos cadastrados.</p>
        </div>
    <?php else: ?>
        <?php foreach ($modules as $module): ?>
            <div class="card" style="margin-bottom: 16px;">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                    <div style="font-size: 24px;">📖</div>
                    <h3 style="font-size: 18px; font-weight: 700; margin: 0;">
                        <?= htmlspecialchars($module['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </h3>
                </div>
                
                <?php if (!empty($module['description'])): ?>
                    <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 16px;">
                        <?= htmlspecialchars($module['description'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                <?php endif; ?>

                <?php if (!empty($module['lessons'])): ?>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <?php foreach ($module['lessons'] as $lesson): ?>
                            <?php 
                            $isCompleted = !empty($lesson['is_completed']);
                            $lessonUrl = '/painel-externo/aula?id=' . (int)$lesson['id'] . '&course_id=' . (int)$course['id'];
                            ?>
                            <a href="<?= $lessonUrl ?>" style="display: flex; align-items: center; gap: 12px; padding: 12px; background: rgba(255,255,255,0.03); border: 1px solid var(--border); border-radius: 10px; text-decoration: none; transition: all 0.2s;" class="lesson-item">
                                <div style="font-size: 20px;">
                                    <?= $isCompleted ? '✅' : '▶️' ?>
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-size: 14px; font-weight: 600; color: var(--text-primary); margin-bottom: 2px;">
                                        <?= htmlspecialchars($lesson['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <?php if (!empty($lesson['duration_minutes'])): ?>
                                        <div style="font-size: 12px; color: var(--text-secondary);">
                                            ⏱️ <?= (int)$lesson['duration_minutes'] ?> min
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="font-size: 13px; color: var(--text-secondary); font-style: italic;">Nenhuma aula neste módulo ainda.</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
<?php endif; ?>

<div style="margin-top: 20px;">
    <a href="/painel-externo/cursos" style="color: var(--text-secondary); font-size: 14px; text-decoration: underline;">
        ← Voltar para cursos disponíveis
    </a>
</div>
