<?php
/** @var array $course */
/** @var array|null $branding */
/** @var string $token */
/** @var string|null $error */

$courseTitle = trim((string)($course['title'] ?? ''));

$isPartnerSite = !empty($isPartnerSite);
$slug = isset($slug) ? trim((string)$slug) : '';

$loginAction = '/login';
$checkoutHref = '/';
if ($slug !== '') {
    $loginAction = '/curso/' . urlencode($slug) . '/login';
    $checkoutHref = '/curso/' . urlencode($slug) . '/checkout';
}
?>

<h1 style="font-size:20px; font-weight:900; margin:0 0 8px 0;">Login</h1>
<div class="hint" style="margin-bottom:16px;">Entre com sua conta para continuar a compra de <b><?= htmlspecialchars($courseTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></b></div>

<?php if (!empty($error)): ?>
    <div style="padding: 12px; background: rgba(229, 57, 53, 0.1); border: 1px solid #e53935; border-radius: 10px; margin-bottom: 16px; color: #ff6f60; font-size: 14px;">
        <?= htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
    </div>
<?php endif; ?>

<form action="<?= $loginAction ?>" method="post" style="display: flex; flex-direction: column; gap: 14px; max-width: 400px;">
    <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
    
    <div>
        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--text-primary);">E-mail</label>
        <input type="email" name="email" required style="width: 100%; padding: 10px 12px; background: rgba(255,255,255,0.05); border: 1px solid var(--border); border-radius: 10px; color: var(--text-primary); font-size: 14px;">
    </div>
    
    <div>
        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--text-primary);">Senha</label>
        <input type="password" name="password" required style="width: 100%; padding: 10px 12px; background: rgba(255,255,255,0.05); border: 1px solid var(--border); border-radius: 10px; color: var(--text-primary); font-size: 14px;">
    </div>
    
    <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-top: 8px;">
        <button type="submit" class="btn">Entrar</button>
        <a href="<?= $checkoutHref ?>" class="btn-outline">Voltar para cadastro</a>
    </div>
</form>

<div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border);">
    <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 8px;">Ainda não tem uma conta?</p>
    <a href="<?= $checkoutHref ?>" style="color: var(--accent); font-size: 14px; font-weight: 600; text-decoration: none;">
        Criar conta e comprar curso →
    </a>
</div>
