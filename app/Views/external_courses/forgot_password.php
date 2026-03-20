<?php
/** @var array $course */
/** @var array|null $branding */
/** @var string $token */
/** @var string|null $error */
/** @var string|null $success */

$companyName = isset($branding) && is_array($branding) ? trim((string)($branding['company_name'] ?? '')) : '';

$isPartnerSite = !empty($isPartnerSite);
$slug = isset($slug) ? trim((string)$slug) : '';

$formAction = '/senha/esqueci';
$loginHref = '/login';
if ($slug !== '') {
    $formAction = '/curso/' . urlencode($slug) . '/senha/esqueci';
    $loginHref = '/curso/' . urlencode($slug) . '/login';
}
?>

<div class="container-narrow">
    <div class="card">
        <div style="text-align: center; margin-bottom: 2rem;">
            <h1 style="font-size: 2rem; font-weight: 900; margin-bottom: 0.5rem;">Esqueceu sua Senha?</h1>
            <p style="color: var(--text-secondary); font-size: 1rem;">
                Sem problemas! Digite seu e-mail e enviaremos um link para redefinir sua senha.
            </p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="success-message">
                <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form action="<?= $formAction ?>" method="post">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
            
            <div class="form-group">
                <label class="form-label">E-mail</label>
                <input type="email" name="email" required class="form-input" placeholder="seu@email.com">
                <div class="form-hint">
                    Digite o e-mail que você usou para criar sua conta
                </div>
            </div>
            
            <button type="submit" class="btn" style="width: 100%; font-size: 1.05rem;">
                Enviar Link de Recuperação
            </button>
        </form>

        <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--border); text-align: center;">
            <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 1rem;">
                Lembrou sua senha?
            </p>
            <a href="<?= $loginHref ?>" style="color: var(--accent); text-decoration: none; font-weight: 600;">
                Voltar para o Login
            </a>
        </div>
    </div>
</div>
