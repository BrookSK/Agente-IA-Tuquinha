<?php
/** @var array $course */
/** @var array|null $branding */
/** @var string $token */

$title = trim((string)($course['title'] ?? ''));
$desc = trim((string)($course['short_description'] ?? ''));
$long = trim((string)($course['description'] ?? ''));
$priceCents = isset($course['price_cents']) ? (int)$course['price_cents'] : 0;
$price = number_format(max($priceCents, 0) / 100, 2, ',', '.');
?>

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
        Valor: R$ <?= $price ?>
    </div>
    <a class="btn" href="/curso-externo/checkout?token=<?= urlencode($token) ?>">Comprar agora</a>
</div>

<div class="hint" style="margin-top:12px;">
    Após o pagamento, seu acesso ao curso será liberado automaticamente.
</div>
