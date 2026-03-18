<?php
/** @var array $course */
/** @var array|null $branding */
/** @var string $token */

$title = trim((string)($course['title'] ?? ''));
$desc = trim((string)($course['short_description'] ?? ''));
$long = trim((string)($course['description'] ?? ''));
$priceCents = isset($course['price_cents']) ? (int)$course['price_cents'] : 0;
$price = number_format(max($priceCents, 0) / 100, 2, ',', '.');
$imagePath = trim((string)($course['image_path'] ?? ''));
$heroImageUrl = isset($branding) && is_array($branding) ? trim((string)($branding['hero_image_url'] ?? '')) : '';
?>

<div class="container">
    <?php if ($heroImageUrl !== ''): ?>
        <div class="hero-section">
            <img src="<?= htmlspecialchars($heroImageUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Hero" class="hero-image">
        </div>
    <?php endif; ?>
    
    <div class="hero-section">
        <h1 class="hero-title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
        
        <?php if ($desc !== ''): ?>
            <p class="hero-subtitle"><?= htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin-top: 2rem;">
            <?php if ($priceCents > 0): ?>
                <a href="/curso-externo/checkout?token=<?= urlencode($token) ?>" class="btn" style="font-size: 1.125rem; padding: 1rem 2rem;">
                    Comprar por R$ <?= $price ?>
                </a>
            <?php else: ?>
                <a href="/curso-externo/checkout?token=<?= urlencode($token) ?>" class="btn" style="font-size: 1.125rem; padding: 1rem 2rem;">
                    Cadastrar-se Gratuitamente
                </a>
            <?php endif; ?>
            
            <?php if (!empty($_SESSION['user_id'])): ?>
                <a href="/painel-externo" class="btn-outline" style="font-size: 1.125rem; padding: 1rem 2rem;">
                    Acessar Meu Painel
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($imagePath !== ''): ?>
        <div style="max-width: 900px; margin: 3rem auto;">
            <h2 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem; text-align: center;">Conteúdo do Curso</h2>
            <div style="display: flex; justify-content: center;">
                <div style="width: 100%; max-width: 400px; border-radius: 12px; overflow: hidden; box-shadow: 0 8px 24px rgba(0,0,0,0.3); transition: transform 0.3s ease, box-shadow 0.3s ease;" 
                     onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 12px 32px rgba(0,0,0,0.4)';" 
                     onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 8px 24px rgba(0,0,0,0.3)';">
                    <img src="<?= htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8') ?>" 
                         alt="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>" 
                         style="width: 100%; height: auto; display: block; aspect-ratio: 16/9; object-fit: cover;">
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($long !== ''): ?>
        <div style="max-width: 800px; margin: 3rem auto;">
            <div class="card">
                <h2 style="font-size: 1.75rem; font-weight: 800; margin-bottom: 1.5rem;">Sobre o Curso</h2>
                <div style="font-size: 1.05rem; line-height: 1.8; color: var(--text-secondary); white-space: pre-line;">
                    <?= htmlspecialchars($long, ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <div style="max-width: 800px; margin: 3rem auto;">
        <div class="card" style="text-align: center;">
            <h2 style="font-size: 1.75rem; font-weight: 800; margin-bottom: 1rem;">Pronto para Começar?</h2>
            <p style="font-size: 1.05rem; color: var(--text-secondary); margin-bottom: 2rem;">
                <?php if ($priceCents > 0): ?>
                    Adquira acesso completo ao curso agora mesmo.
                <?php else: ?>
                    Crie sua conta gratuitamente e comece a aprender hoje.
                <?php endif; ?>
            </p>
            
            <?php if ($priceCents > 0): ?>
                <a href="/curso-externo/checkout?token=<?= urlencode($token) ?>" class="btn" style="font-size: 1.125rem; padding: 1rem 2.5rem;">
                    Comprar Agora - R$ <?= $price ?>
                </a>
            <?php else: ?>
                <a href="/curso-externo/checkout?token=<?= urlencode($token) ?>" class="btn" style="font-size: 1.125rem; padding: 1rem 2.5rem;">
                    Começar Gratuitamente
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>
