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

$isPartnerSite = !empty($isPartnerSite);
$slug = isset($slug) ? trim((string)$slug) : '';

$checkoutHref = '/';
if ($slug !== '') {
    $checkoutHref = '/curso/' . urlencode($slug) . '/checkout';
}
?>

<?php if ($imagePath !== ''): ?>
<div style="width: 100%; max-width: 600px; margin: 0 auto 20px; border-radius: 14px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
    <img src="<?= htmlspecialchars($imagePath, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" 
         alt="<?= htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" 
         style="width: 100%; height: auto; display: block;">
</div>
<?php endif; ?>

<h1 style="font-size:22px; font-weight:900; margin:0 0 8px 0;"><?= htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h1>

<?php if ($desc !== ''): ?>
    <div class="hint" style="margin-bottom:10px;">
        <?= htmlspecialchars($desc, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if ($long !== ''): ?>
    <div class="hint" style="margin-bottom:12px; white-space:pre-line;">
        <?= htmlspecialchars($long, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div style="display:flex; flex-wrap:wrap; gap:10px; align-items:center; margin-top:10px;">
    <div style="font-size:14px; font-weight:900; color: var(--text-primary);">
      <?php if (!empty($course['is_paid']) && !empty($course['price_cents'])): ?>
        <a href="<?= $checkoutHref ?>" class="btn">
            Comprar por R$ <?= number_format($course['price_cents'] / 100, 2, ',', '.') ?>
        </a>
    <?php else: ?>
        <a href="<?= $checkoutHref ?>" class="btn">
            Cadastrar-se gratuitamente
        </a>
    <?php endif; ?>
    </div>
</div>

<div class="hint" style="margin-top:12px;">
    Após o pagamento, seu acesso ao curso será liberado automaticamente.
</div>
