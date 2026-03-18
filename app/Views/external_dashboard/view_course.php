<?php
/** @var array $course */
/** @var bool $hasAccess */
/** @var array|null $branding */
?>
<div class="header">
    <h1><?= htmlspecialchars($course['title'] ?? '', ENT_QUOTES, 'UTF-8') ?></h1>
    <?php if (!empty($course['short_description'])): ?>
        <p><?= htmlspecialchars($course['short_description'], ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
</div>

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

    <?php if ($hasAccess): ?>
        <div style="padding: 16px; background: rgba(107, 226, 141, 0.1); border: 1px solid #6be28d; border-radius: 10px; margin-bottom: 16px;">
            <div style="font-size: 14px; font-weight: 600; color: #6be28d; margin-bottom: 8px;">✅ Você tem acesso a este curso</div>
            <p style="font-size: 13px; color: var(--text-secondary); margin: 0;">Acesse o conteúdo completo do curso através do link abaixo.</p>
        </div>
        
        <?php if (!empty($course['external_token'])): ?>
            <a href="/curso-externo/membros?token=<?= urlencode($course['external_token']) ?>" class="btn" style="display: inline-block;">
                Acessar conteúdo do curso
            </a>
        <?php endif; ?>
    <?php else: ?>
        <div style="padding: 16px; background: rgba(255, 204, 128, 0.1); border: 1px solid #ffcc80; border-radius: 10px; margin-bottom: 16px;">
            <div style="font-size: 14px; font-weight: 600; color: #ffcc80; margin-bottom: 8px;">🔒 Curso não adquirido</div>
            <p style="font-size: 13px; color: var(--text-secondary); margin: 0;">Você ainda não tem acesso a este curso. Adquira agora para começar a estudar.</p>
        </div>

        <?php if (!empty($course['is_paid']) && !empty($course['price_cents'])): ?>
            <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                <div style="font-size: 24px; font-weight: 800; color: var(--accent);">
                    R$ <?= number_format($course['price_cents'] / 100, 2, ',', '.') ?>
                </div>
                <?php if (!empty($course['external_token'])): ?>
                    <a href="/curso-externo/checkout?token=<?= urlencode($course['external_token']) ?>" class="btn">
                        Comprar agora
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div style="font-size: 16px; font-weight: 600; color: #6be28d; margin-bottom: 12px;">Curso Gratuito</div>
            <?php if (!empty($course['external_token'])): ?>
                <a href="/curso-externo?token=<?= urlencode($course['external_token']) ?>" class="btn">
                    Inscrever-se gratuitamente
                </a>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

<div style="margin-top: 20px;">
    <a href="/painel-externo/cursos" style="color: var(--text-secondary); font-size: 14px; text-decoration: underline;">
        ← Voltar para cursos disponíveis
    </a>
</div>
