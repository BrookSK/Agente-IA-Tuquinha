<?php
/** @var array $course */
/** @var array|null $branding */
/** @var string $token */
/** @var string|null $error */

$courseTitle = trim((string)($course['title'] ?? ''));
$priceCents = isset($course['price_cents']) ? (int)$course['price_cents'] : 0;
$price = number_format(max($priceCents, 0) / 100, 2, ',', '.');

$isPartnerSite = !empty($isPartnerSite);
$slug = isset($slug) ? trim((string)$slug) : '';

$loginAction = '/login';
$checkoutAction = '/';
$backHref = '/';
if ($slug !== '') {
    $loginAction = '/curso/' . urlencode($slug) . '/login';
    $checkoutAction = '/curso/' . urlencode($slug) . '/checkout';
    $backHref = '/curso/' . urlencode($slug);
}
?>

<h1 style="font-size:20px; font-weight:900; margin:0 0 8px 0;">
    <?php if ($priceCents > 0): ?>
        Checkout: <?= htmlspecialchars($courseTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
    <?php else: ?>
        Cadastro Gratuito
    <?php endif; ?>
</h1>
<?php if ($priceCents > 0): ?>
    <div class="hint" style="margin-bottom:12px;">Valor: <b>R$ <?= $price ?></b> (pagamento único via Asaas).</div>
<?php else: ?>
    <div class="hint" style="margin-bottom:12px;">Crie sua conta gratuitamente para acessar conteúdos e comunidades.</div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="error"><?= htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
<?php endif; ?>

<div style="padding: 14px; background: rgba(255,204,128,0.1); border: 1px solid #ffcc80; border-radius: 12px; margin-bottom: 20px;">
    <div style="font-size: 14px; font-weight: 600; color: #ffcc80; margin-bottom: 8px;">👤 Já tem uma conta?</div>
    <p style="font-size: 13px; color: var(--text-secondary); margin: 0 0 12px 0;">Se você já possui uma conta, faça login para continuar a compra.</p>
    <button type="button" onclick="toggleLoginForm()" class="btn-outline" style="display: inline-block; padding: 8px 16px; border-radius: 999px; border: 1px solid var(--border); background: transparent; color: var(--text-primary); font-size: 13px; font-weight: 600; cursor: pointer;">
        Fazer login
    </button>
</div>

<div id="loginFormContainer" style="display: none; padding: 16px; background: rgba(255,255,255,0.03); border: 1px solid var(--border); border-radius: 12px; margin-bottom: 20px;">
    <h3 style="font-size: 16px; font-weight: 700; margin: 0 0 12px 0;">Login</h3>
    
    <form action="<?= $loginAction ?>" method="post" style="display: flex; flex-direction: column; gap: 12px;">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        
        <div>
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">E-mail</label>
            <input type="email" name="email" required style="width: 100%; padding: 10px 12px; background: rgba(255,255,255,0.05); border: 1px solid var(--border); border-radius: 10px; color: var(--text-primary); font-size: 14px;">
        </div>
        
        <div>
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Senha</label>
            <input type="password" name="password" required style="width: 100%; padding: 10px 12px; background: rgba(255,255,255,0.05); border: 1px solid var(--border); border-radius: 10px; color: var(--text-primary); font-size: 14px;">
        </div>
        
        <div style="display: flex; gap: 10px; align-items: center;">
            <button type="submit" class="btn">Entrar</button>
            <button type="button" onclick="toggleLoginForm()" class="btn-outline">Cancelar</button>
        </div>
    </form>
</div>

<script>
function toggleLoginForm() {
    const container = document.getElementById('loginFormContainer');
    if (container.style.display === 'none') {
        container.style.display = 'block';
        container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    } else {
        container.style.display = 'none';
    }
}
</script>

<style>
    .billing-grid { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:10px; }
    @media (max-width: 720px) { .billing-grid { grid-template-columns:1fr; } }
    .billing-option { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:12px 12px; border-radius:14px; border:1px solid var(--border-subtle); background:var(--surface-subtle); cursor:pointer; user-select:none; }
    .billing-option:hover { border-color: rgba(255,255,255,0.20); }
    .billing-option input { width:16px; height:16px; }
    .billing-option-title { font-weight:800; color:var(--text-primary); font-size:13px; line-height:1.2; white-space:nowrap; }
    .billing-option-hint { font-size:11px; color:var(--text-secondary); margin-top:3px; }
    .billing-option-left { display:flex; align-items:flex-start; gap:10px; min-width:0; }
</style>

<form action="<?= $checkoutAction ?>" method="post" class="grid">
    <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">

    <div style="grid-column: 1 / -1; font-size: 12px; text-transform: uppercase; letter-spacing: 0.12em; color: var(--text-secondary);">Sua conta</div>

    <div>
        <label>Nome completo*</label>
        <input name="name" required>
    </div>
    <div>
        <label>E-mail*</label>
        <input name="email" type="email" required>
    </div>
    <div>
        <label>Senha*</label>
        <input name="password" type="password" minlength="8" required>
        <div class="hint" style="margin-top:6px;">Mínimo 8 caracteres. Enviaremos esta senha por e-mail.</div>
    </div>

    <div style="grid-column: 1 / -1; margin-top:8px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.12em; color: var(--text-secondary);">Dados pessoais</div>

    <div>
        <label>CPF*</label>
        <input name="cpf" required>
    </div>
    <div>
        <label>Data de nascimento*</label>
        <input name="birthdate" type="date" required>
    </div>
    <div>
        <label>Telefone</label>
        <input name="phone">
    </div>

    <div style="grid-column: 1 / -1; margin-top:8px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.12em; color: var(--text-secondary);">Endereço</div>

    <div>
        <label>CEP*</label>
        <input name="postal_code" required>
    </div>
    <div>
        <label>Endereço*</label>
        <input name="address" required>
    </div>
    <div>
        <label>Número*</label>
        <input name="address_number" required>
    </div>
    <div>
        <label>Complemento</label>
        <input name="complement">
    </div>
    <div>
        <label>Bairro*</label>
        <input name="province" required>
    </div>
    <div>
        <label>Cidade*</label>
        <input name="city" required>
    </div>
    <div>
        <label>Estado (UF)*</label>
        <input name="state" maxlength="2" required style="text-transform:uppercase;">
    </div>

    <?php if ($priceCents > 0): ?>
        <div style="grid-column: 1 / -1; margin-top:8px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.12em; color: var(--text-secondary);">Forma de pagamento</div>

        <div style="grid-column: 1 / -1;" class="billing-grid">
            <label class="billing-option">
                <div class="billing-option-left">
                    <input type="radio" name="billing_type" value="PIX" checked>
                    <div>
                        <div class="billing-option-title">PIX</div>
                        <div class="billing-option-hint">Aprovação rápida</div>
                    </div>
                </div>
            </label>
            <label class="billing-option">
                <div class="billing-option-left">
                    <input type="radio" name="billing_type" value="BOLETO">
                    <div>
                        <div class="billing-option-title">Boleto</div>
                        <div class="billing-option-hint">Pode levar até 3 dias</div>
                    </div>
                </div>
            </label>
            <label class="billing-option">
                <div class="billing-option-left">
                    <input type="radio" name="billing_type" value="CREDIT_CARD">
                    <div>
                        <div class="billing-option-title">Cartão de crédito</div>
                        <div class="billing-option-hint">Pague no cartão</div>
                    </div>
                </div>
            </label>
        </div>
    <?php endif; ?>

    <div style="grid-column: 1 / -1; display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-top:8px;">
        <button type="submit" class="btn">
            <?php if ($priceCents > 0): ?>
                Gerar pagamento
            <?php else: ?>
                Criar conta gratuita
            <?php endif; ?>
        </button>
        <a class="btn-outline" href="<?= $backHref ?>">Voltar</a>
    </div>
</form>
