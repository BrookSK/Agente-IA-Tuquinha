<?php
/** @var array $course */
/** @var array|null $branding */
/** @var string $token */
/** @var string|null $error */

$courseTitle = trim((string)($course['title'] ?? ''));
$companyName = isset($branding) && is_array($branding) ? trim((string)($branding['company_name'] ?? '')) : '';

$isPartnerSite = !empty($isPartnerSite);
$slug = isset($slug) ? trim((string)$slug) : '';

$loginAction = '/login';
$forgotHref = '/senha/esqueci';
$checkoutHref = '/';
$backHref = '/';

if ($slug !== '') {
    $loginAction = '/curso/' . urlencode($slug) . '/login';
    $forgotHref = '/curso/' . urlencode($slug) . '/senha/esqueci';
    $checkoutHref = '/curso/' . urlencode($slug) . '/checkout';
    $backHref = '/curso/' . urlencode($slug);
}
?>

<div class="container-narrow">
    <div class="card">
        <div style="text-align: center; margin-bottom: 2rem;">
            <h1 style="font-size: 2rem; font-weight: 900; margin-bottom: 0.5rem;">Bem-vindo de Volta</h1>
            <p style="color: var(--text-secondary); font-size: 1rem;">
                Entre com sua conta para continuar
            </p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form action="<?= $loginAction ?>" method="post">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
            
            <div class="form-group">
                <label class="form-label">E-mail</label>
                <input type="email" name="email" required class="form-input" placeholder="seu@email.com">
            </div>
            
            <div class="form-group">
                <label class="form-label">Senha</label>
                <input type="password" name="password" required class="form-input" placeholder="••••••••">
            </div>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="remember" style="width: 18px; height: 18px;">
                    <span style="font-size: 0.9rem; color: var(--text-secondary);">Lembrar-me</span>
                </label>
                <a href="<?= $forgotHref ?>" style="color: var(--accent); text-decoration: none; font-size: 0.9rem; font-weight: 600;">
                    Esqueci minha senha
                </a>
            </div>
            
            <button type="submit" class="btn" style="width: 100%; font-size: 1.05rem;">
                Entrar
            </button>
        </form>

        <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--border); text-align: center;">
            <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 1rem;">
                Ainda não tem uma conta?
            </p>
            <a href="<?= $checkoutHref ?>" class="btn" style="display: inline-block; padding: 0.75rem 1.5rem;">
                Criar Conta Agora
            </a>
        </div>
    </div>
    
    <div style="text-align: center; margin-top: 1.5rem;">
        <a href="<?= $backHref ?>" style="color: var(--text-secondary); text-decoration: none; font-size: 0.9rem;">
            ← Voltar para a página inicial
        </a>
    </div>
</div>
